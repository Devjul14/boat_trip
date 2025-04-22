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
            padding: 0;
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
        .invoice-details {
            width: 100%;
            margin-bottom: 30px;
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
        }
        .page-break {
            page-break-after: always;
        }
        /* Additional classes for specific design elements */
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
                    @if($hotel->phone)
                        Phone: {{ $hotel->phone }}<br>
                    @endif
                    @if($hotel->email)
                        Email: {{ $hotel->email }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Contact Person:</td>
                <td>{{ $hotel->contact_person }}</td>
            </tr>
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

    <h3>Invoice Items</h3>
    <table class="items">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Trip Date</th>
                <th>Trip Type</th>
                <th>Passengers</th>
                <th>Month/Year</th>
                <th class="text-right">Amount</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoicesData as $invoice)
            <tr>
                <td>{{ $invoice['invoice_number'] }}</td>
                <td>{{ $invoice['trip_date'] }}</td>
                <td>{{ $invoice['trip_type'] }}</td>
                <td class="text-center">{{ $invoice['passenger_count'] }}</td>
                <td>{{ $invoice['month_year'] }}</td>
                <td class="text-right">${{ number_format($invoice['amount'], 2) }}</td>
                <td>{{ $invoice['due_date'] }}</td>
            </tr>
            @endforeach
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