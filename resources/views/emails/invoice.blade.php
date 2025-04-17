<!-- resources/views/emails/invoice.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #0066cc;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
        }
        .invoice-details {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #0066cc;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Invoice Notification</h2>
    </div>
    
    <div class="content">
        <p>Dear {{ $contactPerson }},</p>
        
        <p>We hope this email finds you well. Please be informed that a new invoice has been generated for {{ $hotel->name }}.</p>
        
        <div class="invoice-details">
            <h3>Invoice Details:</h3>
            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Hotel:</strong> {{ $hotel->name }}</p>
            <p><strong>Hotel Address:</strong> {{ $hotel->address }}</p>
            @if($trip)
                <p><strong>Trip Date:</strong> {{ $trip->date }}</p>
                <p><strong>Bill Number:</strong> {{ $trip->bill_number }}</p>
                <p><strong>Trip Type:</strong> {{ $trip->tripType->name ?? 'N/A' }}</p>
            @endif
            <p><strong>Month/Year:</strong> {{ $invoice->month }}/{{ $invoice->year }}</p>
            <p><strong>Total Amount:</strong> ${{ number_format($invoice->total_amount, 2) }}</p>
            <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        </div>
        
        <p>The invoice is currently in draft status. We will be sending the official invoice shortly.</p>
        
        <p>If you have any questions or need further information, please feel free to contact us.</p>
        
        <p>Thank you for your continued partnership.</p>
        
        <p>Best regards,<br>
        The Management Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>