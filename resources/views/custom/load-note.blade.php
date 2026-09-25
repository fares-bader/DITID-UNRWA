<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Load Note - {{ $log->id ?? 'Preview' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 15mm;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            box-sizing: border-box;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td { vertical-align: top; }
        .value-line {
            border-bottom: 1px solid #000;
            font-weight: bold;
            padding: 0 5px;
            min-height: 18px;
            word-wrap: break-word;
        }
        .inline-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            font-weight: bold;
            padding: 0 5px;
            min-width: 80px;
            text-align: center;
        }
        .header-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 4px;
        }
        .small-text { font-size: 10px; }
        .goods-table {
            margin-top: 15px;
            border: 1px solid #000;
        }
        .goods-table th, .goods-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        .goods-table th {
            font-weight: normal;
            background-color: #f9f9f9 !important;
            -webkit-print-color-adjust: exact;
        }
        .goods-body-row td {
            height: 380px;
            vertical-align: top;
        }
        .arabic-text {
            direction: rtl;
            text-align: center;
            font-size: 11px;
            margin: 15px 0;
        }
        .footer-boxes td { padding: 5px; }
    </style>
</head>
<body onload="window.print()">

    @php
        // جلب البيانات المخصصة (الأوزان ورقم ساب) من قاعدة البيانات قبل رسم الصفحة
        $grossWeight = '';
        $netWeight = '';
        $sapPo = '';
        
        $asset = $log->item;
        if ($asset && $asset->model && $asset->model->fieldset) {
            foreach($asset->model->fieldset->fields as $field) {
                $fieldName = strtolower(trim($field->name));
                
                if (str_contains($fieldName, 'gross weight')) {
                    $grossWeight = $asset->{$field->db_column};
                }
                if (str_contains($fieldName, 'net weight')) {
                    $netWeight = $asset->{$field->db_column};
                }
                if (str_contains($fieldName, 'sap') || str_contains($fieldName, 'po')) {
                    $sapPo = $asset->{$field->db_column};
                }
            }
        }

        // تحديد رقم الـ PO النهائي (الأولوية للحقل المخصص، ثم لبيانات شاشة التسليم، ثم للرقم الافتراضي للجهاز)
        $finalPoNumber = $sapPo ?: ($log->contract_po_number ?: $asset->order_number);
    @endphp

    <!-- Header -->
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 25%;">
                <h2 style="margin:0; font-size: 18px;">U.N.R.W.A.</h2>
                <div class="small-text">05.6.240.1</div>
            </td>
            <td style="width: 50%;" class="header-title">
                LOAD NOTE
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                Date <span class="inline-line">{{ date('D d/m/Y') }}</span><br><br>
                No. <span class="inline-line">ISD/{{ date('y') }}/{{ $log->id ?? 'XXXX' }}</span>
            </td>
        </tr>
    </table>

    <!-- Addresses Structure -->
    <table>
        <tr>
            <td style="width: 60%;">
                <table style="width: 90%;">
                    <tr>
                        <td style="width: 35px; padding-top: 2px;">TO</td>
                        <td class="value-line">{{ $log->target?->department?->name ?? '____________________' }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="small-text" style="text-align: center;">(Carrier's Name)</td>
                    </tr>
                    
                    <tr>
                        <td style="padding-top: 8px;">FROM</td>
                        <td class="value-line" style="margin-top: 8px;">{{ $log->item?->company?->name ?? '____________________' }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="small-text" style="text-align: center;">(UNRWA Office)</td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%;">
                <div style="height: 40px;"></div>
                <table>
                    <tr>
                        <td style="width: 25px; padding-top: 2px;">AT</td>
                        <td class="value-line">{{ $log->location?->name ?? $log->item?->location?->name ?? '____________________' }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="small-text" style="text-align: center;">(Place from which to collect goods)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 5px;">
        <tr>
            <td style="width: 155px; padding-top: 2px;">TO BE COLLECTED FROM</td>
            <td class="value-line" style="width: 40%;">{{ $issuer?->department?->name ?? '____________________' }}</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td class="small-text" style="text-align: center;">(Supplier's Name)</td>
            <td></td>
        </tr>
    </table>

    <table style="margin-top: 5px;">
        <tr>
            <td style="width: 135px; padding-top: 2px;">TO BE DELIVERED TO</td>
            <td class="value-line" style="width: 50%;">{{ $log->target?->name ?? '_____________________' }}</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td class="small-text" style="text-align: center;">Consignee</td>
            <td></td>
        </tr>
    </table>

    <!-- Order Info & Signatures -->
    <table style="margin-top: 15px;">
        <tr>
            <td style="width: 45%;">
                <table style="border: 1px solid #000; height: 45px;">
                    <tr>
                        <td style="width: 50%; padding: 4px; border-right: 1px solid #000;">
                            <div class="small-text">Contract Purchase Order No.<br>Or Donation No. Ref.</div>
                            <!-- عرض الـ SAP/PO النهائي هنا -->
                            <div style="font-weight: bold; text-align: center; margin-top: 5px;">{{ $finalPoNumber ?? '' }}</div>
                        </td>
                        <td style="width: 50%; padding: 4px;">
                            <div class="small-text">Despatch Order No.</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 55%; padding-left: 20px; vertical-align: bottom; padding-bottom: 5px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 60px;">Issued By:</td>
                        <td class="value-line" style="text-align: center;">
                            {{ $issuer?->first_name ?? '' }} {{ $issuer?->last_name ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 50%;">
                <div style="font-size: 11px;">Please receive for despatch the goods described below :-</div>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: bottom;">
                Despatched by (Signature) <span class="inline-line" style="width: 120px;"></span> ممثل الوكالة
            </td>
        </tr>
    </table>

    <!-- Goods Table -->
    <table class="goods-table">
        <thead>
            <tr>
                <th style="width: 6%;">Item</th>
                <th style="width: 48%;">GOODS</th>
                <th style="width: 8%;">UNIT</th>
                <th style="width: 10%;">No. of<br>Units</th>
                <th style="width: 14%;">Gross<br>Weight</th>
                <th style="width: 14%;">Net<br>Weight</th>
            </tr>
        </thead>
        <tbody>
            <tr class="goods-body-row">
                <td>1</td>
                <td style="text-align: left; line-height: 1.6; padding: 10px;">
                    <span style="font-weight: bold; font-size: 14px;">{{ $asset->model->name ?? 'Laptop DELL Latitude' }}</span><br>
                    LC: {{ $asset->asset_tag ?? '' }}<br>
                    ST: {{ $asset->serial ?? '' }}
                </td>
                <td style="font-weight: bold;">EA</td>
                <td style="font-weight: bold;">1</td>
                <!-- عرض الوزن الإجمالي -->
                <td style="font-weight: bold; font-size: 14px;">{{ $grossWeight }}</td>
                <!-- عرض الوزن الصافي -->
                <td style="font-weight: bold; font-size: 14px;">{{ $netWeight }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Arabic Disclaimer -->
    <div class="arabic-text">
        ان الشاحنة ادناه محملة الى لاجئي فلسطين في لبنان - سوريا - المملكة الاردنية الهاشمية - لذلك نرجو السلطات المختصة تسهيل معاملاتها لتصل باقرب وقت ممكن
    </div>

    <!-- Drivers & Signatures Box -->
    <table class="footer-boxes" style="border-top: 1px solid #000; margin-top: 10px; padding-top: 10px;">
        <tr>
            <td style="width: 25%;">
                <div class="small-text">Wagon / Track No.</div>
                <div class="small-text" style="direction: rtl; margin-bottom: 5px;">رقم السيارة او الشاحنة</div>
                <div class="value-line" style="text-align: center;">{{ $log->vehicle_number ?? '' }}</div>
            </td>
            <td style="width: 30%; padding-left: 15px;">
                <div class="small-text">Driver's name</div>
                <div class="small-text" style="direction: rtl; margin-bottom: 5px;">اسم السائق</div>
                <div class="value-line" style="text-align: center;">{{ $log->driver_name ?? '' }}</div>
            </td>
            <td style="width: 20%; text-align: center;">
                <div class="small-text">Checked & Received</div>
                <div class="small-text" style="direction: rtl;">استلمت البضاعة<br>اعلاه بالتمام</div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: bottom;">
                <div class="small-text">Carrier's Signature</div>
                <div class="small-text" style="direction: rtl; margin-bottom: 10px; margin-right: 25px;">امضاء السائق</div>
                <span class="inline-line" style="width: 130px;"></span>
            </td>
        </tr>
    </table>

    <!-- Final Receipt Line -->
    <table style="border-top: 1px solid #000; margin-top: 25px; padding-top: 10px;">
        <tr>
            <td style="width: 75%; font-size: 11px;">
                <span style="letter-spacing: 2px; font-weight: bold;">RECEIPT</span> &nbsp;&nbsp;&nbsp; 
                I have received the goods described above in good condition subject to following remarks ( if any )
            </td>
            <td style="width: 25%; text-align: right; vertical-align: bottom; padding-top: 40px;">
                <div class="small-text">Received by (Signature)</div>
                <span class="inline-line" style="width: 150px; margin-top: 10px;"></span>
            </td>
        </tr>
    </table>

</body>
</html>