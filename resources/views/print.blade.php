<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">
    <title>Prescription #{{ $prescription->id }} - {{ $prescription->appointment->patient->name }}</title>
    
    <style>
        /* General Page Styling (On Screen) */
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            line-height: 1.5;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .prescription-card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .clinic-info h2 {
            margin: 0;
            color: #2b6cb0;
            font-size: 24px;
        }

        .clinic-info p {
            margin: 2px 0;
            font-size: 13px;
            color: #666;
        }

        .patient-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .rx-section {
            margin-bottom: 30px;
        }

        .rx-symbol {
            font-size: 32px;
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 10px;
            font-family: Georgia, serif;
        }

        .content-box {
            font-size: 15px;
            white-space: pre-line;
            padding-left: 10px;
        }

        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 220px;
            text-align: center;
            padding-top: 5px;
            font-size: 14px;
        }

        .action-buttons {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            background-color: #2b6cb0;
            color: #fff;
            border: none;
            padding: 10px 25px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background-color: #2c5282;
        }

        /* =================================================== */
        /* CRITICAL: CSS PRINT RULES (Gipatakdo alang sa Papel) */
        /* =================================================== */
        @media print {
            /* 1. Itago ang dili kinahanglan nga elements sa papel */
            .no-print, .action-buttons {
                display: none !important;
            }

            /* 2. I-reset ang background ug margins para sa standard paper */
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .prescription-card {
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }

            /* 3. Siguroha nga sakto ang pagkagawas sa mga border colors */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* 4. I-set ang page margins sa printer */
            @page {
                size: A4;
                margin: 1.5cm;
            }
        }
    </style>
</head>
<body>

    <!-- Print Button (Matago kini inig print) -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Print Prescription</button>
    </div>

    <!-- Printable Content -->
    <div class="prescription-card">
        <!-- Header / Clinic Details -->
        <div class="header">
            <div class="clinic-info">
                <h2>HEALTHCARE CLINIC</h2>
                <p>123 Medical Center Way, Suite 400</p>
                <p>Contact: (02) 8888-1234 | info@healthcareclinic.com</p>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 12px; color: #666;">Rx No:</p>
                <strong style="font-size: 16px;">#{{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}</strong>
            </div>
        </div>

        <!-- Patient Details -->
        <div class="patient-info">
            <div>
                <strong>Patient Name:</strong> {{ $prescription->appointment->patient->name }}<br>
                <strong>Email:</strong> {{ $prescription->appointment->patient->email }}
            </div>
            <div>
                <strong>Date:</strong> {{ $prescription->created_at->format('F d, Y') }}<br>
                <strong>Doctor:</strong> {{ $prescription->appointment->doctor->display_name ?? 'N/A' }}
            </div>
        </div>

        <!-- Diagnosis Section -->
        <div class="rx-section">
            <h4 style="margin-bottom: 5px; color: #4a5568;">Diagnosis / Condition:</h4>
            <div class="content-box">
                {{ $prescription->diagnosis }}
            </div>
        </div>

        <!-- Rx / Medicines Section -->
        <div class="rx-section">
            <div class="rx-symbol">℞</div>
            <div class="content-box">
                {{ $prescription->medicines }}
            </div>
        </div>

        <!-- Special Instructions -->
        @if($prescription->instructions)
            <div class="rx-section">
                <h4 style="margin-bottom: 5px; color: #4a5568;">Instructions / Advice:</h4>
                <div class="content-box">
                    {{ $prescription->instructions }}
                </div>
            </div>
        @endif

        <!-- Doctor Signature Footer -->
        <div class="footer">
            <div style="font-size: 11px; color: #718096;">
                <p>Valid only with physician's signature.<br>Generated via Clinic Management System.</p>
            </div>
            <div class="signature-line">
                <strong>{{ $prescription->appointment->doctor->display_name ?? 'N/A' }}</strong><br>
                <span style="font-size: 12px; color: #666;">License No: {{ $prescription->appointment->doctor->license_number ?? 'XXXXX' }}</span>
            </div>
        </div>
    </div>

</body>
</html>
