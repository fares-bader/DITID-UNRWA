<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Return Receipt - {{ $log->id ?? 'Checkin' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 15mm; }
        @media print { body { margin: 0; } .no-print { display: none; } }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.4; max-width: 210mm; margin: 0 auto; color: #000; background: #fff; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { vertical-align: top; }
        .value-line { border-bottom: 1px solid #000; font-weight: bold; padding: 0 5px; }
        .inline-line { border-bottom: 1px solid #000; display: inline-block; font-weight: bold; padding: 0 5px; text-align: center; }
        .header-title { text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 2px; }
        .goods-table { margin-top: 20px; border: 1px solid #000; }
        .goods-table th, .goods-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .goods-table th { background-color: #f9f9f9 !important; -webkit-print-color-adjust: exact; }
    </style>
</head>
<body onload="window.print()">
    <!-- Header -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 25%;">
                <h2 style="margin:0; font-size: 18px;">U.N.R.W.A.</h2>
                <div style="font-size: 10px;">IT Department</div>
            </td>
            <td style="width: 50%;" class="header-title">
                RETURN RECEIPT<br>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                Date: <span class="inline-line" style="min-width: 80px;">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y') }}</span><br><br>
                Ref No: <span class="inline-line" style="min-width: 80px;">RTN-{{ $log->id ?? 'XX' }}</span>
            </td>
        </tr>
    </table>

    <!-- Info -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 120px; padding-top: 5px;">RETURNED BY:</td>
            <td class="value-line" style="width: 40%; vertical-align: middle;">
                {{ $log->target?->name ?? '________________' }} 
                @if($log->target?->employee_num) ({{ $log->target->employee_num }}) @endif
            </td>
            <td style="width: 40px;"></td>
            <td style="width: 120px; padding-top: 5px;">DEPARTMENT:</td>
            <td class="value-line" style="vertical-align: middle;">{{ $log->target?->department?->name ?? '________________' }}</td>
        </tr>
    </table>

    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 120px; padding-top: 5px;">RECEIVED BY:</td>
            <td class="value-line" style="width: 40%; vertical-align: middle;">
                {{ $receiver?->first_name ?? '' }} {{ $receiver?->last_name ?? '________________' }}
            </td>
            <td style="width: 40px;"></td>
            <td style="width: 120px; padding-top: 5px;">LOCATION:</td>
            <td class="value-line" style="vertical-align: middle;">{{ $log->item?->location?->name ?? '________________' }}</td>
        </tr>
    </table>

    <!-- Table -->
    <table class="goods-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 25%;">Asset Tag</th>
                <th style="width: 25%;">Serial No.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allLogs as $index => $singleLog)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align: left;">{{ $singleLog->item?->model?->name ?? 'Asset' }}</td>
                <td>{{ $singleLog->item?->asset_tag ?? '-' }}</td>
                <td>{{ $singleLog->item?->serial ?? '-' }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #f9f9f9; -webkit-print-color-adjust: exact;">
                <td colspan="2" style="text-align: right; font-weight: bold;">Total Items:</td>
                <td colspan="2" style="font-weight: bold; font-size: 14px;">{{ count($allLogs) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Declaration -->
    <div style="margin-top: 30px; font-size: 12px; line-height: 1.6; text-align: justify; padding: 15px; border: 1px dashed #666; background-color: #fafafa; -webkit-print-color-adjust: exact;">
        <strong>Clearance Declaration:</strong><br>
        This is to confirm that the Information Technology Department (DITID) has successfully received the above-listed item(s) from the mentioned employee. The employee is hereby cleared from any technical or financial responsibility regarding these specific items, pending final technical inspection if necessary.<br>
        <div style="direction: rtl; font-family: Tahoma, Arial, sans-serif; margin-top: 10px;">
            نؤكد بموجب هذا أن قسم تكنولوجيا المعلومات قد استلم المادة/المواد المذكورة أعلاه من الموظف المعني. وبناءً عليه، تُبرأ ذمة الموظف من أي مسؤولية فنية أو مالية تجاه هذه المواد تحديداً، بانتظار الفحص الفني النهائي إن لزم الأمر.
        </div>
    </div>

    <!-- Signatures -->
    <table style="margin-top: 50px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div style="margin-bottom: 40px; font-weight: bold;">Returned By:</div>
                <span class="inline-line" style="width: 200px;"></span><br>
                <span style="font-size: 11px;">Signature</span>
            </td>
            <td style="width: 50%; text-align: center;">
                <div style="margin-bottom: 40px; font-weight: bold;">Received By:</div>
                <span class="inline-line" style="width: 200px;"></span><br>
                <span style="font-size: 11px;">Signature</span>
            </td>
        </tr>
    </table>
</body>
</html>