<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StockoutReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $data;
    protected $date;
    protected $platform;
    protected $orderQuantity;
    protected $productQuantity;
    protected $itemQuantity;

    public function __construct(Collection $data, string $date, ?string $platform = null)
    {
        $this->data = $data;
        $this->date = $date;
        $this->platform = $platform;
        
        // Calculate totals
        $this->orderQuantity = $data->count();
        $this->productQuantity = $data->unique('productVariant.id')->count();
        $this->itemQuantity = $data->sum(function ($item) {
            return abs($item->quantity_change);
        });
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Product Name',
            'SKU',
            'Seller SKU',
            'Qty',
        ];
    }

    public function map($stockMovement): array
    {
        static $index = 0;
        $index++;

        $productVariant = $stockMovement->productVariant;
        $product = $productVariant->product;
        
        // Get variation details
        $variationDetails = [];
        if ($productVariant->variation_name && $productVariant->variation_name !== 'Standard') {
            $variationDetails[] = $productVariant->variation_name;
        }
        
        // Build product name with variations
        $productName = $product->name;
        if (!empty($variationDetails)) {
            $productName .= ' [' . implode(', ', $variationDetails) . ']';
        }
        
        // Get platform info
        if ($productVariant->platform) {
            $productName .= ' (' . $productVariant->platform->name . ')';
        }

        return [
            $index,
            $productName,
            $productVariant->sku ?? '',
            $productVariant->sku ?? '', // Using same SKU for seller SKU as field doesn't exist
            abs($stockMovement->quantity_change),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->data->count() + 1; // +1 for header row
        
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // All cells border
            'A1:E' . $lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Center align specific columns
            'A2:A' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'E2:E' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  // No
            'B' => 45, // Product Name (increased width)
            'C' => 20, // SKU
            'D' => 20, // Seller SKU
            'E' => 12, // Qty
        ];
    }

    public function title(): string
    {
        return 'Picking List';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Add title and summary information
                $sheet->insertNewRowBefore(1, 5);
                
                // Add title
                $sheet->setCellValue('A1', 'Picking List');
                $sheet->mergeCells('A1:E1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Add user info
                $userName = auth()->user()->name ?? 'System';
                $sheet->setCellValue('A2', 'User: ' . $userName);
                $sheet->mergeCells('A2:E2');
                
                // Add print time
                $printTime = Carbon::parse($this->date)->format('m-d_H-i-s');
                $sheet->setCellValue('A3', 'Print time: ' . $printTime);
                $sheet->mergeCells('A3:E3');
                
                // Add summary stats
                $summaryText = "Order quantity: {$this->orderQuantity}    Product quantity: {$this->productQuantity}    Item quantity: {$this->itemQuantity}";
                $sheet->setCellValue('A4', $summaryText);
                $sheet->mergeCells('A4:E4');
                
                // Add empty row
                $sheet->setCellValue('A5', '');
                
                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(20);
                
                // Apply styles to the info rows
                $sheet->getStyle('A2:E4')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Add footer with platform info if available
                if ($this->platform) {
                    $lastRow = $sheet->getHighestRow();
                    $footerRow = $lastRow + 2;
                    $sheet->setCellValue('A' . $footerRow, $this->platform . ' Shop');
                    $sheet->mergeCells('A' . $footerRow . ':E' . $footerRow);
                    $sheet->getStyle('A' . $footerRow)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }
            },
        ];
    }
}
