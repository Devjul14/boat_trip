<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Summary - {{ $hotel->name }}</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 14px;
        margin: 40px;
        color: #333;
    }

    .invoice-container {
        max-width: 800px;
        margin: auto;
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
    }

    .header h1 {
        margin: 0;
        font-size: 28px;
    }

    .subtitle {
        font-size: 18px;
        color: #666;
    }

    .hotel-info {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 30px;
        gap: 20px;
    }

    .info-column {
        flex: 1;
        min-width: 250px;
    }

    .info-row {
        margin-bottom: 8px;
    }

    .info-label {
        font-weight: bold;
        display: inline-block;
        width: 130px;
    }

    .info-value {
        display: inline-block;
    }

    .section-title {
        font-size: 20px;
        margin-bottom: 10px;
        border-bottom: 2px solid #eee;
        padding-bottom: 5px;
    }

    .hotel-info {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.info-column {
    flex: 1;
    min-width: 300px;
}

.info-row {
    margin-bottom: 8px;
}

.info-label {
    font-weight: bold;
    display: inline-block;
    width: 130px;
    vertical-align: top;
}

.info-value {
    display: inline-block;
    vertical-align: top;
}


    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }

    .invoice-table th, .invoice-table td {
        padding: 10px;
        vertical-align: top;
    }

    .invoice-table tbody tr {
        border-bottom: 1px solid #ccc;
    }

    .invoice-table thead tr {
        background-color: #f9f9f9;
        border-bottom: 2px solid #ccc;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .amount {
        font-weight: bold;
        color: #333;
    }

    .total-section {
        text-align: right;
        margin-top: 20px;
    }

    .total-row {
        margin-bottom: 10px;
    }

    .total-label {
        font-weight: bold;
        margin-right: 10px;
    }

    .final-total {
        font-size: 16px;
        border-top: 2px solid #ccc;
        padding-top: 10px;
    }

    .footer {
        margin-top: 40px;
        font-size: 13px;
        text-align: center;
        color: #666;
    }
</style>

</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <h3>INVOICE SUMMARY</h3>
        </div>

        <!-- Hotel Information -->
        <div class="hotel-info">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Hotel:</span>
                    <span class="info-value">
                        {{ $hotel->name }}<br>{{ $hotel->location ?? '' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    <span class="info-value">{{ $hotel->contact_person ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $hotel->phone ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $hotel->email ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice Date:</span>
                    <span class="info-value">{{ $generatedDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Terms:</span>
                    <span class="info-value">Due upon receipt</span>
                </div>
                @if($invoicesData[0]['due_date'])
                <div class="info-row">
                    <span class="info-label">Due Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoicesData[0]['due_date'])->format('d-m-Y') }}</span>
                </div>
                @endif
            </div>
        </div>


        <!-- Detail Invoice -->
        <h2 class="section-title">Detail Invoice</h2>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Trip Type</th>
                    <th class="text-center">Number Of Passengers</th>
                    <th>Expense Type</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @if(count($expensesData) > 0)
                    @php
                        $rowspan = count($expensesData);
                        $firstInvoice = $invoicesData[0];
                    @endphp
                    @foreach($expensesData as $index => $expense)
                        <tr>
                            @if($index === 0)
                                <td rowspan="{{ $rowspan }}">
                                    {{ $firstInvoice['invoice_number'] }}<br>
                                    <small style="color: #666;">
                                        {{ \Carbon\Carbon::parse($expense['trip_date'])->format('d-m-Y') }}
                                    </small>
                                </td>
                                <td rowspan="{{ $rowspan }}">{{ $expense['trip_type'] }}</td>
                                <td rowspan="{{ $rowspan }}" class="text-center">{{ $expense['passenger_count'] }}</td>
                            @endif
                            <td>{{ $expense['expense_type'] }}</td>
                            <td class="text-right amount">${{ number_format($expense['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <!-- Fallback jika tidak ada expense data, tampilkan data invoice utama -->
                    <tr>
                        <td>
                            {{ $invoicesData[0]['invoice_number'] }}<br>
                            <small style="color: #666;">
                                {{ \Carbon\Carbon::parse($invoicesData[0]['trip_date'])->format('d-m-Y') }}
                            </small>
                        </td>
                        <td>{{ $invoicesData[0]['trip_type'] }}</td>
                        <td class="text-center">{{ $invoicesData[0]['passenger_count'] }}</td>
                        <td>Trip Charge</td>
                        <td class="text-right amount">${{ number_format($invoicesData[0]['amount'], 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Total Amount:</span>
                <span class="total-value">${{ number_format($totalAmount, 2) }}</span>
            </div>
            <div class="total-row final-total">
                <span class="total-label">Amount Due:</span>
                <span class="total-value">${{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business! Please make payment by the due date.</p>
            <p>This invoice was generated automatically on {{ $generatedDate }}.</p>
        </div>
    </div>
</body>
</html>