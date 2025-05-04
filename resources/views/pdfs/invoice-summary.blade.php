<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 5px;
            color: #2c3e50;
        }
        h2 {
            font-size: 16px;
            margin: 0 0 20px;
            font-weight: normal;
            color: #7f8c8d;
        }
        h3 {
            font-size: 18px;
            margin: 30px 0 15px;
            color: #2c3e50;
        }
        .invoice-details {
            width: 100%;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
        }
        .invoice-details .hotel-info {
            width: 60%;
        }
        .invoice-details .invoice-info {
            width: 35%;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 5px 0;
            vertical-align: top;
        }
        .invoice-details .label {
            font-weight: bold;
            width: 120px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.items th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table.items td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 40%;
            margin-top: 20px;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals table td {
            padding: 8px;
        }
        .totals table .label {
            font-weight: bold;
        }
        .grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #ddd;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #7f8c8d;
            text-align: center;
            clear: both;
        }
        .section-divider {
            border-top: 1px solid #ddd;
            margin: 40px 0 20px;
            clear: both;
        }
        .stamp {
            text-transform: uppercase;
            font-size: 20px;
            color: #D23;
            border: 3px solid #D23;
            padding: 5px 10px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
            transform: rotate(-10deg);
            opacity: 0.5;
            position: absolute;
            top: 100px;
            right: 50px;
        }
        .combined-table th, .combined-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .combined-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #ddd;
            text-align: left;
        }
        .combined-table tr.invoice-row {
            background-color: #f9f9f9;
        }
        .combined-table tr.expense-row {
            background-color: #ffffff;
        }
        .combined-table .section-header {
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- You can add a company logo here -->
        <!-- <img src="{{ public_path('images/logo.png') }}" class="logo"> -->
        <h1>INVOICE SUMMARY</h1>
        <h2>{{ $hotel->name }}</h2>
    </div>

    <div class="invoice-details">
        <div class="hotel-info">
            <table>
                <tr>
                    <td class="label">Hotel:</td>
                    <td>
                        <strong>{{ $hotel->name }}</strong><br>
                        {{ $hotel->address }}<br>
                        @if($hotel->city || $hotel->state || $hotel->zip)
                            {{ $hotel->city }}, {{ $hotel->state }} {{ $hotel->zip }}<br>
                        @endif
                        @if($hotel->country)
                            {{ $hotel->country }}<br>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Contact:</td>
                    <td>{{ $hotel->contact_person }}</td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td>{{ $hotel->phone }}</td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td>{{ $hotel->email }}</td>
                </tr>
            </table>
        </div>
        <div class="invoice-info">
            <table>
                <tr>
                    <td class="label">Invoice Date:</td>
                    <td>{{ $generatedDate }}</td>
                </tr>
                <tr>
                    <td class="label">Payment Terms:</td>
                    <td>Due upon receipt</td>
                </tr>
            </table>
        </div>
    </div>

    <h3>Detail Invoice</h3>
    <table class="combined-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Tnvoice</th>
                <th>Trip Type</th>
                <th>Number Of Passengers</th>
                <th>Expense Type</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoicesData as $invoice)
                <tr class="invoice-row">
                    <td rowspan="2">
                        <strong>Invoice:</strong> {{ $invoice['invoice_number'] }}<br>
                        {{ date('d-m-Y', strtotime($invoice['trip_date'])) }}
                    </td>
                    <td rowspan="2">{{ $invoice['trip_type'] }}</td>
                    <td rowspan="2" class="text-center">{{ $invoice['passenger_count'] ?? '--' }}</td>
                    
                </tr>
                <tr>
                    <td colspan="2">
                        <table class="combined-table" style="width: 100%; border-collapse: collapse; margin: 0;">
                            @foreach($expensesData as $expense)
                                @if($expense['trip_date'] == $invoice['trip_date'] && $expense['trip_type'] == $invoice['trip_type'])
                                <tr>
                                    <td style="border: none; padding: 4px 8px;">{{ $expense['expense_type'] }}</td>
                                    <td style="border: none; padding: 4px 8px; text-align: right;">${{ number_format($expense['amount'], 2) }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr><td colspan="5" style="border-bottom: 2px solid #ddd; height: 10px;"></td></tr>
            @endforeach
        </tbody>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Total Amount:</td>
                <td class="text-right">${{ number_format($totalAmount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label">Amount Due:</td>
                <td class="text-right">${{ number_format($totalAmount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear:both;"></div>

    <div class="footer">
        <p>Thank you for your business! Please make payment by the due date.</p>
        <p>This invoice was generated automatically on {{ $generatedDate }}.</p>
        @if(isset($paymentInstructions))
            <p>{{ $paymentInstructions }}</p>
        @endif
    </div>
</body>
</html>