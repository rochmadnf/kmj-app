<?php

namespace App\Exports\Ckg;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Models;

class RaporExport implements FromQuery, ShouldAutoSize, WithProperties, WithHeadings, WithMapping, WithColumnFormatting, WithColumnWidths
{

    protected int $rowNumber = 0;
    protected $dynamicColumns = null;
    protected string $schoolCategory = 'SD';

    use Exportable;
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        return  \App\Models\Screening::with(['patient', 'checkUpResult'])
            ->where('school_category', $this->schoolCategory)
            ->whereHas('checkUpResult', function ($q) {
                $q->whereRaw("results NOT LIKE '%\"hasil_pemeriksaan\": null%'");
            });
    }

    public function __construct(string $schoolCategory = 'SD')
    {
        $this->schoolCategory = $schoolCategory;

        $this->dynamicColumns = Models\Ckg\ListCheckUp::where('school_category', $this->schoolCategory)->get()->map(fn($item) => "{$item->label} - {$item->code}");
    }

    public function headings(): array
    {
        $s = [
            '#',
            'Nama Siswa',
            'NIK',
            'Tanggal Lahir',
            'Umur',
            'Kelas',
            'Kelas Klaster',
            'Asal Sekolah',
            'Jenjang Sekolah',
        ];

        return [...$s, ...$this->dynamicColumns->toArray()];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $extend = $this->dynamicColumns->toArray();
        $res = [];

        foreach ($row->checkUpResult->results as $result) {
            foreach ($result['list_parameter'] as $list) {
                foreach ($extend as $ex) {
                    if (str_contains($ex, $list['code'])) {
                        $res[] = [
                            'match' => "{$list['label']} - {$list['code']}",
                            'result' => trim($list['hasil']['nilai'] . " " . $list['hasil']['klasifikasi'])
                        ];
                    }
                }
            }
        }

        $res2 = [];

        foreach ($this->dynamicColumns as $dc) {
            $isMatch = false;
            foreach ($res as $re) {
                if ($dc === $re['match']) {
                    $isMatch = true;
                    $res2[] = $re['result'];
                }
            }

            if (!$isMatch) {
                $res2[] = '~~~~~';
            }
        }

        return [
            $this->rowNumber,
            $row->patient->full_name,
            " " . (string) $row->patient->nik,
            $row->patient->born_date->translatedFormat('d M Y'),
            $row->patient->born_date->age,
            $row->class_name,
            $row->klaster_name,
            $row->school_name,
            $row->school_category,
            ...$res2
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 50,
            'C' => 18,
            'D' => 15,
            'E' => 5.5,
            // 'F' => 8,
            // 'G' => 15,
            // 'H' => 30,
            // 'I' => 13,
        ];
    }


    public function properties(): array
    {
        return [
            'creator' => 'Rochmad Nurul Fahmi',
            'lastModifiedBy' => 'Rochmad Nurul Fahmi',
            'title' => 'Rapor CKG Sekolah',
            'description' => 'Daftar Hasil Pemeriksaan Siswa/Siswi Sekolah Binaan',
            'subject' => 'Rapor CKG Sekolah',
            'keywords' => 'rapor,ckg,school,sekolah,kamonji',
            'category' => 'Report',
            'manager' => 'Rochmad Nurul Fahmi',
            'company' => 'UPTD Puskesmas Kamonji',
        ];
    }
}
