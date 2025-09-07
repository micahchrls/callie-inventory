<?php

namespace App\Exports;

use App\Models\Product\ProductVariant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class InventoryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    public function query()
    {
        return ProductVariant::with([
            'product.productCategory',
            'product.productSubCategory'
        ]);
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Variation Name',
            'Category',
            'Sub Category',
            'Size',
            'Color',
            'Material',
            'Variant Initial',
            'Current Stock',
            'Reorder Level',
            'Status',
            'Active',
            'Last Restocked',
            'Created',
            'Updated',
            'Notes',
        ];
    }

    public function map($variant): array
    {
        // Build variation name from attributes if no explicit name
        $variationName = $variant->variation_name;
        if (!$variationName) {
            $attributes = array_filter([
                $variant->size,
                $variant->color,
                $variant->material,
            ]);
            $variationName = !empty($attributes) ? implode(' | ', $attributes) : 'Standard';
        }

        // Calculate dynamic status
        $status = 'In Stock';
        if ($variant->status === 'discontinued') {
            $status = 'Discontinued';
        } elseif ($variant->quantity_in_stock <= 0) {
            $status = 'Out of Stock';
        } elseif ($variant->quantity_in_stock <= $variant->reorder_level) {
            $status = 'Low Stock';
        }

        return [
            $variant->sku,
            $variant->product->name ?? 'N/A',
            $variationName,
            $variant->product->productCategory->name ?? '-',
            $variant->product->productSubCategory->name ?? '-',
            $variant->size ?? '-',
            $variant->color ?? '-',
            $variant->material ?? '-',
            $variant->variant_initial ?? '-',
            $variant->quantity_in_stock,
            $variant->reorder_level,
            $status,
            $variant->is_active ? 'Yes' : 'No',
            $variant->last_restocked_at ? $variant->last_restocked_at->format('M d, Y') : 'Never',
            $variant->created_at ? $variant->created_at->format('M d, Y') : '-',
            $variant->updated_at ? $variant->updated_at->format('M d, Y H:i') : '-',
            $variant->notes ?? '-',
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
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Blue theme for inventory
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // All data styling
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Stock quantity conditional formatting
        for ($row = 2; $row <= $highestRow; $row++) {
            $stockValue = $sheet->getCell('J' . $row)->getCalculatedValue(); // Current Stock column
            $reorderValue = $sheet->getCell('K' . $row)->getCalculatedValue(); // Reorder Level column

            if (is_numeric($stockValue)) {
                if ($stockValue <= 0) {
                    // Out of stock - Red
                    $sheet->getStyle('J' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FEE2E2'],
                        ],
                        'font' => ['color' => ['rgb' => 'DC2626'], 'bold' => true],
                    ]);
                } elseif (is_numeric($reorderValue) && $stockValue <= $reorderValue) {
                    // Low stock - Orange
                    $sheet->getStyle('J' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FED7AA'],
                        ],
                        'font' => ['color' => ['rgb' => 'EA580C'], 'bold' => true],
                    ]);
                } else {
                    // In stock - Green
                    $sheet->getStyle('J' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D1FAE5'],
                        ],
                        'font' => ['color' => ['rgb' => '059669'], 'bold' => true],
                    ]);
                }
            }
        }

        return [];
    }

    public function title(): string
    {
        return 'Inventory Export';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Freeze header row
                $sheet->freezePane('A2');

                // Auto filter
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $sheet->setAutoFilter('A1:' . $highestColumn . $highestRow);

                // Set column widths for better readability
                $sheet->getColumnDimension('A')->setWidth(15); // SKU
                $sheet->getColumnDimension('B')->setWidth(30); // Product Name
                $sheet->getColumnDimension('C')->setWidth(25); // Variation Name
                $sheet->getColumnDimension('D')->setWidth(20); // Category
                $sheet->getColumnDimension('E')->setWidth(20); // Sub Category
                $sheet->getColumnDimension('F')->setWidth(12); // Size
                $sheet->getColumnDimension('G')->setWidth(12); // Color
                $sheet->getColumnDimension('H')->setWidth(15); // Material
                $sheet->getColumnDimension('I')->setWidth(12); // Variant Initial
                $sheet->getColumnDimension('J')->setWidth(12); // Current Stock
                $sheet->getColumnDimension('K')->setWidth(12); // Reorder Level
                $sheet->getColumnDimension('L')->setWidth(15); // Status
                $sheet->getColumnDimension('M')->setWidth(8);  // Active
                $sheet->getColumnDimension('N')->setWidth(15); // Last Restocked
                $sheet->getColumnDimension('O')->setWidth(12); // Created
                $sheet->getColumnDimension('P')->setWidth(15); // Updated
                $sheet->getColumnDimension('Q')->setWidth(25); // Notes

                // Center align numeric columns
                $sheet->getStyle('J:M')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
