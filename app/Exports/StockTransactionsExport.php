<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class StockTransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $stockMovements;
    protected $date;
    protected $platform;
    protected $transactionType;

    public function __construct($stockMovements, $date, $platform = null, $transactionType = null)
    {
        $this->stockMovements = $stockMovements;
        $this->date = $date;
        $this->platform = $platform;
        $this->transactionType = $transactionType;
    }

    public function collection()
    {
        return $this->stockMovements;
    }

    public function headings(): array
    {
        return [
            'Date/Time',
            'SKU',
            'Product Name',
            'Variant',
            'Platform',
            'Category',
            'Transaction Type',
            'Direction',
            'Quantity',
            'Stock Before',
            'Stock After',
            'Unit Cost',
            'Total Cost',
            'Reason',
            'Notes',
            'User',
        ];
    }

    public function map($movement): array
    {
        $direction = $movement->quantity_change > 0 ? 'IN' : 'OUT';
        $quantity = abs($movement->quantity_change);
        
        return [
            Carbon::parse($movement->created_at)->format('Y-m-d H:i:s'),
            $movement->productVariant->sku ?? 'N/A',
            $movement->productVariant->product->name ?? 'N/A',
            $movement->productVariant->variation_name ?? 'Standard',
            $movement->productVariant->platform->name ?? 'N/A',
            $movement->productVariant->product->productCategory->name ?? 'N/A',
            ucfirst(str_replace('_', ' ', $movement->movement_type)),
            $direction,
            ($direction === 'IN' ? '+' : '-') . $quantity,
            $movement->quantity_before,
            $movement->quantity_after,
            $movement->unit_cost ? '$' . number_format($movement->unit_cost, 2) : 'N/A',
            $movement->total_cost ? '$' . number_format($movement->total_cost, 2) : 'N/A',
            ucfirst($movement->reason ?? 'N/A'),
            $movement->notes ?? '',
            $movement->user->name ?? 'System',
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
                'startColor' => ['rgb' => '4A5568'],
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

        // Color code the direction column (H)
        for ($row = 2; $row <= $highestRow; $row++) {
            $direction = $sheet->getCell('H' . $row)->getValue();
            
            if ($direction === 'IN') {
                $sheet->getStyle('H' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '10B981'],
                    ],
                ]);
                $sheet->getStyle('I' . $row)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '10B981'],
                    ],
                ]);
            } elseif ($direction === 'OUT') {
                $sheet->getStyle('H' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'EF4444'],
                    ],
                ]);
                $sheet->getStyle('I' . $row)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => 'EF4444'],
                    ],
                ]);
            }
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
            'B' => 15,  // SKU
            'C' => 30,  // Product Name
            'D' => 20,  // Variant
            'E' => 15,  // Platform
            'F' => 20,  // Category
            'G' => 15,  // Transaction Type
            'H' => 10,  // Direction
            'I' => 12,  // Quantity
            'J' => 12,  // Stock Before
            'K' => 12,  // Stock After
            'L' => 12,  // Unit Cost
            'M' => 12,  // Total Cost
            'N' => 15,  // Reason
            'O' => 30,  // Notes
            'P' => 15,  // User
        ];
    }

    public function title(): string
    {
        $title = 'Stock Transactions';
        
        if ($this->transactionType === 'stock_in') {
            $title = 'Stock In';
        } elseif ($this->transactionType === 'stock_out') {
            $title = 'Stock Out';
        }
        
        return $title . ' - ' . Carbon::parse($this->date)->format('M d, Y');
    }
}
