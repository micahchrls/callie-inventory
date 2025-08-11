<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Transactions Report</title>
    <style>
        @page {
            margin: 20px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .info-row {
            margin-bottom: 5px;
            font-size: 11px;
            color: #666;
        }
        
        .summary-stats {
            background-color: #f3f4f6;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .summary-stats span {
            margin-right: 20px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background-color: #e5e7eb;
            color: #1f2937;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #d1d5db;
        }
        
        td {
            padding: 8px;
            border: 1px solid #d1d5db;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 2px solid #e5e7eb;
            font-size: 14px;
            font-weight: bold;
        }
        
        .product-name {
            font-weight: 500;
        }
        
        .sku {
            font-family: monospace;
            font-size: 11px;
        }
        
        .qty {
            font-weight: bold;
            color: #dc2626;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #e5e7eb !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Stock Transactions Report</div>
        <div class="info-row">Date: {{ $date }}</div>
        <div class="info-row">User: {{ $userName }}</div>
        <div class="info-row">Print time: {{ $printTime }}</div>
        @if($platform)
        <div class="info-row">Platform: {{ $platform }}</div>
        @endif
    </div>
    
    <div class="summary-stats">
        <span>Order quantity: {{ $orderQuantity }}</span>
        <span>Product quantity: {{ $productQuantity }}</span>
        <span>Item quantity: {{ $itemQuantity }}</span>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 50px;">No</th>
                <th>Product Name</th>
                <th style="width: 130px;">SKU</th>
                <th style="width: 130px;">Seller SKU</th>
                <th class="text-center" style="width: 80px;">Qty</th>
                <th style="width: 100px;">Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockMovements as $index => $movement)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="product-name">
                    {{ $movement->productVariant->product->name }}
                    @if($movement->productVariant->variation_name && $movement->productVariant->variation_name !== 'Standard')
                        <small>[{{ $movement->productVariant->variation_name }}]</small>
                    @endif
                    @if($movement->productVariant->platform)
                        <small>({{ $movement->productVariant->platform->name }})</small>
                    @endif
                </td>
                <td class="sku">{{ $movement->productVariant->sku ?? '-' }}</td>
                <td class="sku">{{ $movement->productVariant->sku ?? '-' }}</td>
                <td class="text-center qty">{{ abs($movement->quantity_change) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</td>
            </tr>
            @endforeach
            
            @if($stockMovements->count() > 0)
            <tr class="total-row">
                <td colspan="4" class="text-right">Total:</td>
                <td class="text-center qty">{{ $itemQuantity }}</td>
                <td></td>
            </tr>
            @endif
        </tbody>
    </table>
    
    @if($platform)
    <div class="footer">
        {{ $platform }} Shop
    </div>
    @endif
</body>
</html>
