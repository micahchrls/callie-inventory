<?php

namespace App\Exports;

use App\Models\StockOut;
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

class StockOutReportsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return StockOut::query()
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
            'Quantity Removed',
            'Platform',
            'Reason',
            'User',
            'Notes',
        ];
    }

    public function map($stockOut): array
    {
        // Build variant name from individual fields
        $variantParts = array_filter([
            $stockOut->productVariant->size,
            $stockOut->productVariant->color,
            $stockOut->productVariant->material,
            $stockOut->productVariant->variant_initial,
        ]);

        $variantName = ! empty($variantParts) ? implode('|', $variantParts) : 'Standard';

        return [
            Carbon::parse($stockOut->created_at)->format('Y-m-d H:i:s'),
            $stockOut->product->name ?? 'N/A',
            $stockOut->productVariant->sku ?? 'N/A',
            $variantName,
            '-'.number_format($stockOut->total_quantity),
            ucfirst(str_replace('_', ' ', $stockOut->platform ?? 'N/A')),
            ucfirst(str_replace('_', ' ', $stockOut->reason ?? 'N/A')),
            $stockOut->user->name ?? 'System',
            $stockOut->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Header styling
        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EF4444'], // Red for stock out
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Add borders to all cells
        $sheet->getStyle('A1:'.$highestColumn.$highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        // Style quantity column (E) with red color
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getStyle('E'.$row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'EF4444'],
                ],
            ]);
        }

        // Style platform column (F) with specific colors
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getStyle('F'.$row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'DC2626'],
                ],
            ]);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Auto-filter
        $sheet->setAutoFilter('A1:'.$highestColumn.$highestRow);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Date/Time
            'B' => 30,  // Product Name
            'C' => 15,  // Variant SKU
            'D' => 20,  // Variant Name
            'E' => 15,  // Quantity Removed
            'F' => 15,  // Platform
            'G' => 15,  // Reason
            'H' => 15,  // User
            'I' => 30,  // Notes
        ];
    }

    public function title(): string
    {
        return 'Stock Out Report - '.$this->periodLabel;
    }
}
