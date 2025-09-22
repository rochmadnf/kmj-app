import puppeteer from "puppeteer";

// ==============================
// Helper Functions
// ==============================
const getArgs = () =>
    Object.fromEntries(
        process.argv.slice(2).map((arg) => {
            const [key, value] = arg.replace(/^--/, "").split("=");
            return [key, value];
        })
    );

async function setToDB(result) {
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
    return fetch("http://127.0.0.1:8000/api/check-up", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: result,
    });
}

// ==============================
// Crypto Loader
// ==============================
async function injectCryptoLoader(page, moduleUrl = "/_nuxt/CB4h-W_t.js") {
    await page.evaluateOnNewDocument((url) => {
        (async function loadCtkCrypto(moduleUrl) {
            async function importModule(url) {
                try {
                    return await import(url);
                } catch {
                    const txt = await fetch(url, {
                        credentials: "same-origin",
                    }).then((r) => {
                        if (!r.ok) throw new Error("Fetch failed: " + r.status);
                        return r.text();
                    });
                    const blobUrl = URL.createObjectURL(
                        new Blob([txt], { type: "application/javascript" })
                    );
                    const mod = await import(blobUrl);
                    URL.revokeObjectURL(blobUrl);
                    return mod;
                }
            }

            function resolveFactory(mod) {
                const candidates = ["u", "Zo", "getCryptoFactory"];
                for (const k of candidates) {
                    if (typeof mod[k] === "function") return mod[k];
                }
                if (mod.default && typeof mod.default === "object") {
                    for (const k of candidates) {
                        if (typeof mod.default[k] === "function")
                            return mod.default[k];
                    }
                }
                for (const key of Object.keys(mod)) {
                    if (typeof mod[key] === "function") {
                        try {
                            const maybe = mod[key]();
                            if (
                                (maybe && typeof maybe.then === "function") ||
                                (maybe &&
                                    typeof maybe === "object" &&
                                    ("encrypt" in maybe || "decrypt" in maybe))
                            ) {
                                return mod[key];
                            }
                        } catch {
                            /* ignore */
                        }
                    }
                }
                return null;
            }

            try {
                importModule(moduleUrl).then((mod) => {
                    const factoryFn = resolveFactory(mod);
                    if (!factoryFn)
                        return console.error("Crypto factory not found");

                    window.ctkCrypto = factoryFn();
                    console.log("✅ ctkCrypto loaded!");
                });
            } catch (err) {
                console.error("Failed to load ctkCrypto:", err);
            }
        })(url);
    }, moduleUrl);
}

// ==============================
// Decrypt Function
// ==============================
async function decryptRaporForKey(page, key) {
    return new Promise(async (resolve, reject) => {
        let done = false;

        const onResponse = async (response) => {
            if (!response.url().includes("/api/rapor/detail-rapor-ckg-sekolah"))
                return;

            try {
                const json = await response.json();
                const encrypted = json.data;
                // console.log(`[${key}] Encrypted sample:`, encrypted.slice(0, 80) + "...");

                const decrypted = await page.evaluate(async (enc) => {
                    if (
                        !window.ctkCrypto ||
                        typeof window.ctkCrypto.decrypt !== "function"
                    ) {
                        throw new Error("ctkCrypto.decrypt belum siap");
                    }
                    return window.ctkCrypto.decrypt(enc);
                }, encrypted);

                let parsed;
                try {
                    parsed = JSON.parse(decrypted);
                } catch {
                    parsed = decrypted;
                    // console.log(`[${key}] Hasil bukan JSON, disimpan sebagai string.`);
                }

                try {
                    const dbResponse = await setToDB(
                        JSON.stringify(parsed, null, 2)
                    );

                    if (dbResponse.status === 201) {
                        console.log("✅ Result berhasil disimpan");
                    } else if (dbResponse.status === 200) {
                        console.log("🔁 Data sudah tersedia");
                    }
                } catch (err) {
                    console.error(
                        "❌ Terjadi error saat simpan result:",
                        err.message
                    );
                    if (err.stack) console.error(err.stack);
                }

                if (!done) {
                    done = true;
                    page.off("response", onResponse);
                    resolve(parsed);
                }
            } catch (err) {
                if (!done) {
                    done = true;
                    page.off("response", onResponse);
                    reject(err);
                }
            }
        };

        page.on("response", onResponse);

        await page.goto(
            `https://pkg.kemkes.go.id/rapor?key=${key}&source=ckg-sekolah`,
            { waitUntil: "networkidle2", timeout: 0 }
        );
    });
}

// ==============================
// Main Process
// ==============================
async function main() {
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();

    await injectCryptoLoader(page);

    try {
        const screenings = await fetch(
            "http://127.0.0.1:8000/api/screenings"
        ).then((res) => res.json());

        if (screenings.data.length > 0) {
            const keys = screenings.data.map((s) => s.token_report);
            for (let i = 0; i < keys.length; i++) {
                console.log(`➡️ Data ke-${i + 1}`);
                await decryptRaporForKey(page, keys[i]);
                console.log("\n\n");
            }
        } else {
            console.log("Tidak ada data.");
        }
    } catch (err) {
        console.error("Error:", err);
    } finally {
        await browser.close();
    }
}

main();
