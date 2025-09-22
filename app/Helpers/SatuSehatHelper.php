<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SatuSehatHelper
{
    public function getHeaders(): array
    {
        return [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
            "Origin" => "https://sehatindonesiaku.kemkes.go.id",
            "Sec-Ch-Ua" => '"Chromium";v="140", "Not=A?Brand";v="24", "Microsoft Edge";v="140"',
            "Sec-Ch-Ua-Mobile" => '?0',
            "Sec-Ch-Ua" => 'Windows',
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-origin",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0",
        ];
    }

    public function fetchPostUrl(string $url, array|\JsonSerializable|\Illuminate\Contracts\Support\Arrayable $body, array $headers = [])
    {
        return Http::withHeaders([...$this->getHeaders(), ...$headers])->post($url, $body);
    }
}
