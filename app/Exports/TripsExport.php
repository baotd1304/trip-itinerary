<?php

namespace App\Exports;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TripsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithEvents,
    ShouldAutoSize
{
    use Exportable;

    private int $rowCount = 0;

    public function __construct(
        private readonly string $month,   // 'Y-m'
        private readonly int $carId,
        private readonly string $status = 'confirmed',
    ) {}

    public function query(): Builder
    {
        return Trip::query()
            ->with('tripExpense')
            ->where('status', $this->status)
            ->ofCar($this->carId)
            ->ofMonth($this->month)
            ->orderBy('day')
            ->orderBy('departure_time');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Itinerary',
            'Km beginning',
            'Km End',
            'Distance',
            'Time Start',
            'Time End',
            'Overtime',
            'Overnight',
            'Toll fee/Air port fee',
            'Holiday',
        ];
    }

    /** @param  Trip  $trip */
    public function map($trip): array
    {
        $this->rowCount++;

        $exp = $trip->tripExpense;

        $overtime  = (float) (($exp?->overtime ?? 0) );
        $overnight = $exp?->is_overnight ? (float)  1 : 0;
        $holiday   = $exp?->is_holiday ? (float) 1 : 0;
        $tollAir   = (float) (($exp?->toll_fee ?? 0) + ($exp?->airport_fee ?? 0));

        return [
            optional($trip->day)->format('d/m/Y'),
            trim(($trip->origin ?? '').' - '.($trip->destination ?? ''), ' -'),
            (int) $trip->odo_start,
            (int) $trip->odo_end,
            (int) $trip->distance,
            $trip->departure_time ? Carbon::parse($trip->departure_time)->format('H:i') : null,
            $trip->arrival_time ? Carbon::parse($trip->arrival_time)->format('H:i') : null,
            $overtime,
            $overnight,
            $tollAir,
            $holiday,
        ];
    }

    public function title(): string
    {
        return Carbon::createFromFormat('Y-m', $this->month)->format('m-Y');
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 11],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastRow  = $this->rowCount + 1;   // +1 dòng heading
                $totalRow = $lastRow + 1;

                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->freezePane('A2');

                if ($this->rowCount === 0) {
                    $sheet->setCellValue('A2', 'Không có dữ liệu phù hợp.');
                    $sheet->mergeCells('A2:K2');

                    return;
                }

                // Định dạng số
                $sheet->getStyle("C2:E{$lastRow}")->getNumberFormat()->setFormatCode('#.##0');
                $sheet->getStyle("H2:K{$lastRow}")->getNumberFormat()->setFormatCode('#.##0');
                $sheet->getStyle("F2:G{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A2:A{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Dòng tổng cộng
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
                $sheet->setCellValue("E{$totalRow}", "=SUM(E2:E{$lastRow})");
                foreach (['H', 'I', 'J', 'K'] as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}2:{$col}{$lastRow})");
                }
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F1F5F9'],
                    ],
                ]);
                $sheet->getStyle("E{$totalRow}")->getNumberFormat()->setFormatCode('#.##0');
                $sheet->getStyle("H{$totalRow}:K{$totalRow}")
                    ->getNumberFormat()->setFormatCode('#.##0');

                // Viền toàn bảng
                $sheet->getStyle("A1:K{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                $sheet->setAutoFilter("A1:K{$lastRow}");
            },
        ];
    }
}