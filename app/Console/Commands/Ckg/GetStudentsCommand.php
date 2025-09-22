<?php

namespace App\Console\Commands\Ckg;

use App\Models\Patient;
use App\Models\Screening;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GetStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ckg:get-students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a list of students from CKG Sekolah Sehat Indonesiaku';

    /**
     * Execute the console command.
     */
    protected $satuSehatHelper = null;

    public function __construct()
    {
        parent::__construct();
        $this->satuSehatHelper = new \App\Helpers\SatuSehatHelper();
    }

    public function handle()
    {
        $facilities =  collect([
            [
                "kode" =>  "13303",
                "nama" =>  "SD",
                "nama_singkat" =>  "SD"
            ],
            [
                "kode" => "13304",
                "nama" => "SMP",
                "nama_singkat" => "SMP"
            ],
            [
                "kode" => "13305",
                "nama" => "SMA",
                "nama_singkat" => "SMA"
            ],

            [
                "kode" =>  "13307",
                "nama" =>  "Pondok Pesantren",
                "nama_singkat" =>  "Pesantren"
            ],
        ]);

        $facility = $this->choice(
            'Pilih Sub Sarana Binaan: ',
            $facilities->pluck('nama')->toArray(),
            0
        );

        $this->info("Sub Sarana Binaan: " . $facility);

        $facility = $facilities->where('nama', $facility)->first();

        $schoolBySubType = $this->getSchoolBySubType((int)$facility['kode']);

        $schoolBySubType = collect($schoolBySubType['data']);


        $selectedSchool = $this->choice(
            'Pilih Sekolah: ',
            $schoolBySubType->map(fn($item) => "{$item['school_name']} - {$item['ihs_no']}")->toArray(),
            0
        );

        $selectedSchool = $schoolBySubType->where('ihs_no', explode(" - ", $selectedSchool)[1])->first();

        $this->info("Sekolah: " . $selectedSchool['school_name']);

        $schoolClasses = collect($this->getSchoolLevels($selectedSchool["category_short_name"])['data']);

        $selectedClass = $this->choice(
            'Pilih Kelas: ',
            $schoolClasses->map(fn($item) => "{$item['code']} - {$item['name']}")->toArray(),
            0
        );

        $selectedClass = $schoolClasses->where('code', explode(" - ", $selectedClass)[0])->first();

        $this->info("Kelas: " . $selectedClass['name']);

        // $students = $this->getStudentsBySchoolId($selectedSchool['ihs_no'], $selectedClass['code'], 1)['data'][0];
        $totalStudent = $this->getStudentsBySchoolId($selectedSchool['ihs_no'], $selectedClass['code'], 1)['pagination']['total_data'];

        $students = $this->getStudentsBySchoolId($selectedSchool['ihs_no'], $selectedClass['code'], $totalStudent);
        if (isset($students)) {
            $this->info("🧮 Total Siswa Selesai: " . $totalStudent);
            $c = 1;

            foreach ($students['data'] as $student) {
                $this->info("➡️ {$c}. {$student['patient']['full_name']} - NIK: {$student['patient']['nik']}");

                $patient = $this->setPatient($student);

                if (!empty($patient)) {
                    $this->setScreening([...$patient, ...['register_date' => $student['register_date'], 'schoolCode' => $selectedSchool['ihs_no'], 'classCode' => $selectedClass['code']]]);
                } else {
                    $this->info("🔁 Skip. Data tidak lengkap.");
                }
                $c++;
                $this->newLine(1);
            }
        }

        $this->newLine(2);
    }

    public function setPatient(array $patient): array
    {
        if ($patient['self_check_status'] === "Lengkap") {

            $id = null;

            $data = Patient::where('nik', $patient['patient']['nik']);
            if ($data->exists()) {
                $this->warn("🔁 Skip. Tersedia");
                $id = $data->first()?->id;
            } else {
                $data = Patient::create([
                    'nik' => $patient['patient']['nik'],
                    'full_name' => $patient['patient']['full_name'],
                    'born_date' => $patient['patient']['born_date'],
                    'gender' => $patient['patient']['gender'] === 'PEREMPUAN' ? false : true,
                ]);
                $id = $data->id;
            }

            return [
                'id' => $id,
                'reg_id' => $patient['reg_id'],
            ];
        }

        return [];
    }

    public function setScreening(array $data)
    {
        if (Screening::where('register_id', $data['reg_id'])->exists()) {
            $this->warn("🔁 Skip. Screening tersedia.");
            return;
        } else {



            $headers = ['Referer' => 'https://sehatindonesiaku.kemkes.go.id/ckg-pelayanan-sekolah'];

            // 1. Encrypt dulu datanya agar dapat token encrypt
            $encryptRes = $this->satuSehatHelper->fetchPostUrl(
                "https://sehatindonesiaku.kemkes.go.id/encrypt",
                [
                    "data" => json_encode([
                        'regId ' => $data['reg_id'],
                        'schoolCode' => $data['schoolCode'],
                        'classCode' => $data['classCode'],
                    ])
                ],
                $headers
            )->json();

            // 2. Set header referer ke detail pemeriksaan dengan token encrypt
            $headers['Referer'] .= '/detail-pemeriksaan?q=' . $encryptRes['token_encrypt'];

            // 3. Dectrypt token encrypt untuk mendapatkan token decrypt
            $dectryptRes = $this->satuSehatHelper->fetchPostUrl(
                "https://sehatindonesiaku.kemkes.go.id/decrypt",
                [
                    "data" => $encryptRes['token_encrypt']
                ],
                $headers
            )->json();

            // 4. Fetch data screening dengan token decrypt
            $fetchScreening = $this->satuSehatHelper->fetchPostUrl(
                "https://sehatindonesiaku.kemkes.go.id/api/pkg/anak-sekolah/get-screening",
                collect(json_decode($dectryptRes['token_decrypt'], true))->mapWithKeys(function ($value, $key) {
                    return [Str::snake($key) => $value];
                })->toArray(),
                $headers
            )->json();

            if ((int) $fetchScreening['statusCode'] === 200) {
                $s_d = $fetchScreening['data'];
                Screening::create([
                    'register_id' => $data['reg_id'],
                    'register_date' => $data['register_date'],
                    'patient_id' => $data['id'],
                    'ticket_number' => $s_d['ticket_number'],
                    'token_report' => $s_d['patient_detail']['token_report'],
                    'klaster_code' => $s_d['patient_klaster_code'],
                    'klaster_name' => $s_d['patient_klaster_name'],
                    'school_name' => $s_d['school']['name'],
                    'school_code' => $s_d['school']['code'],
                    'school_category' => $s_d['school']['category'],
                    'class_name' => $s_d['school']['class_name'],
                    'class_code' => $s_d['school']['class_code'],
                ]);

                $this->info('👌 Screening berhasil disimpan.');
            }
        }
    }

    public function getSchoolLevels(string $level)
    {
        return $this->satuSehatHelper->fetchPostUrl(
            "https://sehatindonesiaku.kemkes.go.id/api/pkg/anak-sekolah/list-jenjang-sekolah",
            [
                "category_code" => $level
            ],
            [
                'Referer' => 'https://sehatindonesiaku.kemkes.go.id/ckg-pelayanan-sekolah'
            ]
        )->json();
    }

    public function getSchoolBySubType(int $subTypeCode, int $limit = 35)
    {
        return $this->satuSehatHelper->fetchPostUrl(
            "https://sehatindonesiaku.kemkes.go.id/api/pkg/sarana/get-sarana-sekolah",
            [
                "data" => [
                    "category_code" => $subTypeCode,
                    "faskes_code" => 1000077625,
                    "search" => "",
                    "limit" => $limit,
                    "offset" => 0

                ]
            ],
            [
                'Referer' => 'https://sehatindonesiaku.kemkes.go.id/ckg-sarana'
            ]
        )->json();
    }

    public function getStudentsBySchoolId(string $schoolId, string $classCode, int $limit = 1)
    {
        return $this->satuSehatHelper->fetchPostUrl(
            "https://sehatindonesiaku.kemkes.go.id/api/pkg/anak-sekolah/list-patient",
            [
                "data" => [
                    "page" =>  1,
                    "limit" =>  $limit,
                    "school_code" =>  $schoolId,
                    "class_code" =>  $classCode,
                    "status" =>  "layanan-final",
                    "patient_name" =>  "",
                    "patient_nik" =>  ""
                ]
            ],
            [
                'Referer' => 'https://sehatindonesiaku.kemkes.go.id/ckg-pelayanan-sekolah'
            ]
        )->json();
    }
}
