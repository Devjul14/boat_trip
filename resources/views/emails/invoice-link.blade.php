<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .content {
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #777;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0 0 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 3px;
            font-weight: bold;
        }
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Summary</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $contactPerson }},</p>
            
            <p>Please find attached the invoice summary for {{ $hotel->name }} for the period of {{ $month }}/{{ $year }}.</p>
            
            <p>This summary includes a total of {{ $invoiceCount }} invoice(s) with a total amount of <span class="amount">${{ number_format($totalAmount, 2) }}</span>.</p>
            <p>You can view the invoice using the link below:</p>
            <p><a href="{{ $viewLink }}" class="btn" target="_blank">View Invoice</a></p>
            <p>Payment is due upon receipt. If you have any questions regarding this invoice or need further clarification, please don't hesitate to contact our accounting department.</p>
            
            <p>Thank you for your business!</p>
            
            <p>Best regards,<br>
            The Accounting Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply directly to this email.</p>
            <p>If you have any questions, please contact our accounting department.</p>
        </div>
    </div>
</body>
</html>