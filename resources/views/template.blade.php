<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2c7be5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c7be5;
            margin: 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .total {
            margin-top: 30px;
            padding: 15px;
            background: #f0f7ff;
            border-radius: 5px;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #2c7be5;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>

    {{-- Clinic Header --}}
    <div class="header">
        <h1>🦷 Cabinet Dentaire</h1>
        <p>Dental Clinic Management System</p>
    </div>

    {{-- Receipt Info --}}
    <div class="info-row">
        <span class="label">Receipt Number:</span>
        <span>{{ $receipt_number }}</span>
    </div>

    <div class="info-row">
        <span class="label">Issue Date:</span>
        <span>{{ $issue_date }}</span>
    </div>

    <div class="info-row">
        <span class="label">Payment Date:</span>
        <span>{{ $payment->payment_date }}</span>
    </div>

    {{-- Patient Info --}}
    <div class="info-row">
        <span class="label">Patient Name:</span>
        <span>{{ $patient->user->name }}</span>
    </div>

    <div class="info-row">
        <span class="label">Patient Email:</span>
        <span>{{ $patient->user->email }}</span>
    </div>

    {{-- Payment Info --}}
    <div class="info-row">
        <span class="label">Payment Method:</span>
        <span>{{ ucfirst($payment->payment_method) }}</span>
    </div>

    {{-- Total --}}
    <div class="total">
        Total Amount: {{ $payment->amount }} DZD
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for your trust — Cabinet Dentaire</p>
        <p>This receipt was generated automatically</p>
    </div>

</body>
</html>