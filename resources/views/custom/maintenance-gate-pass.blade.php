<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Gate Pass - {{ $maintenance->id ?? 'MNT' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 15mm; }
        @media print { body { margin: 0; } .no-print { display: none; } }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.4; max-width: 210mm; margin: 0 auto; color: #000; background: #fff; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { vertical-align: top; }
        .value-line { border-bottom: 1px solid #000; font-weight: bold; padding: 0 5px; }
        .inline-line { border-bottom: 1px solid #000; display: inline-block; font-weight: bold; padding: 0 5px; text-align: center; }
        .header-title { text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 2px; }
        .details-table { margin-top: 15px; border: 1px solid #000; }
        .details-table th, .details-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .details-table th { background-color: #f9f9f9 !important; -webkit-print-color-adjust: exact; width: 25%; font-weight: normal; }
        .details-table td { font-weight: bold; }
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
                MAINTENANCE GATE PASS
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                Date: <span class="inline-line" style="min-width: 80px;">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span><br><br>
                Ref No: <span class="inline-line" style="min-width: 80px;">MNT-{{ $maintenance->id ?? 'XX' }}</span>
            </td>
        </tr>
    </table>

    <!-- Supplier Info -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 140px; padding-top: 5px;">TO:</td>
            <td class="value-line" style="width: 40%; vertical-align: middle; font-size: 14px;">
                {{ $maintenance->supplier?->name ?? 'Internal IT Workshop' }}
            </td>
            <td style="width: 40px;"></td>
            <td style="width: 120px; padding-top: 5px;">SUPPORT TICKET:</td>
            <td class="value-line" style="vertical-align: middle;">{{ $maintenance->id ?? 'N/A' }}</td>
        </tr>
    </table>

    <div style="font-size: 11px; margin-bottom: 5px; font-weight: bold;">Asset Details:</div>
    <table class="details-table" style="margin-top: 0;">
        <tr>
            <th>Asset Name</th>
            <td>{{ $maintenance->asset?->model?->name ?? $maintenance->asset?->name ?? 'Unknown Asset' }}</td>
            <th>Asset Tag</th>
            <td>{{ $maintenance->asset?->asset_tag ?? '-' }}</td>
        </tr>
        <tr>
            <th>Serial Number</th>
            <td>{{ $maintenance->asset?->serial ?? '-' }}</td>
            <th>Hardware Category</th>
            <td>{{ $maintenance->asset?->model?->category?->name ?? '-' }}</td>
        </tr>
    </table>

    <div style="font-size: 11px; margin-top: 20px; margin-bottom: 5px; font-weight: bold;">Maintenance Details</div>
    <table class="details-table" style="margin-top: 0;">
        <tr>
            <th>Maintenance Type</th>
            <td colspan="3">{{ $maintenance->asset_maintenance_type ?? 'Repair' }}</td>
        </tr>
        <tr>
            <th>Fault / Title</th>
            <td colspan="3">{{ $maintenance->title ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Expected Completion</th>
            <td>{{ $maintenance->completion_date ? \Carbon\Carbon::parse($maintenance->completion_date)->format('d/m/Y') : 'N/A' }}</td>
            <th>Warranty Status</th>
            <td>{{ $maintenance->is_warranty ? 'Under Warranty' : 'Out of Warranty' }}</td>
        </tr>
    </table>

    <!-- Declaration -->
    <div style="margin-top: 30px; font-size: 12px; line-height: 1.6; text-align: justify; padding: 15px; border: 1px dashed #666; background-color: #fafafa; -webkit-print-color-adjust: exact;">
        <strong>Terms & Declaration</strong><br>
        The supplier/technician acknowledges the receipt of the aforementioned item(s) for the sole purpose of maintenance/repair. UNRWA reserves the right to request the return of the item at any time. The supplier is fully responsible for the physical safety of the device while in their custody.<br>
        <div style="direction: rtl; font-family: Tahoma, Arial, sans-serif; margin-top: 10px;">
            يقر المورد/الفني باستلام الجهاز المذكور أعلاه لغرض الصيانة والإصلاح فقط. تحتفظ الأونروا بحق استرجاع الجهاز في أي وقت. يتحمل المورد المسؤولية الكاملة عن السلامة المادية للجهاز طوال فترة تواجده في عهدته.
        </div>
    </div>

    <!-- Signatures -->
    <table style="margin-top: 50px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div style="margin-bottom: 40px; font-weight: bold;">Authorized By:</div>
                <span class="inline-line" style="width: 200px;"></span><br>
                <span style="font-size: 11px;">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            </td>
            <td style="width: 50%; text-align: center;">
                <div style="margin-bottom: 40px; font-weight: bold;">Received By:</div>
                <span class="inline-line" style="width: 200px;"></span><br>
                <span style="font-size: 11px;">Signature & Stamp</span>
            </td>
        </tr>
    </table>
</body>
</html>