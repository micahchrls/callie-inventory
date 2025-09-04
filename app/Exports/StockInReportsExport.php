<?php

namespace App\Exports;

use App\Models\StockIn;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockInReportsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $timePeriod;
    protected $startDate;
    protected $endDate;
    protected $periodLabel;

    public function __construct(string $timePeriod, Carbon $startDate, Carbon $endDate, string $periodLabel)
    {
        $this->timePeriod = $timePeriod;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->periodLabel = $periodLabel;
    }

    public function collection()
    {
        return StockIn::query()
            ->with(['product', 'productVariant', 'user'])
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date/Time',
            'Product Name',
            'Variant SKU',
            'Variants',
            'Quantity Added',
            'Reason',
            'User',
            'Notes',
        ];
    }

    public function map($stockIn): array
    {
        // Build variant name from individual fields
        $variantParts = array_filter([
            $stockIn->productVariant->size,
            $stockIn->productVariant->color,
            $stockIn->productVariant->material,
            $stockIn->productVariant->variant_initial,
        ]);

        $variantName = !empty($variantParts) ? implode('|', $variantParts) : 'Standard';

        return [
            Carbon::parse($stockIn->created_at)->format('Y-m-d H:i:s'),
            $stockIn->product->name ?? 'N/A',
            $stockIn->productVariant->sku ?? 'N/A',
            $variantName,
            '+' . number_format($stockIn->total_quantity),
            ucfirst(str_replace('_', ' ', $stockIn->reason ?? 'N/A')),
            $stockIn->user->name ?? 'System',
            $stockIn->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Header styling
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'], // Green for stock in
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Add borders to all cells
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        // Style quantity column (E) with green color
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getStyle('E' . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '10B981'],
                ],
            ]);
        }

        // Style cost columns (G, H) with currency formatting
        if ($highestRow > 1) {
            $sheet->getStyle('G2:H' . $highestRow)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => '059669'],
                ],
            ]);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Auto-filter
        $sheet->setAutoFilter('A1:' . $highestColumn . $highestRow);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Date/Time
            'B' => 30,  // Product Name
            'C' => 15,  // Variant SKU
            'D' => 20,  // Variant Name
            'E' => 15,  // Quantity Added
            'F' => 15,  // Reason
            'K' => 15,  // User
            'L' => 30,  // Notes
        ];
    }

    public function title(): string
    {
        return 'Stock In Report - ' . $this->periodLabel;
    }
}
