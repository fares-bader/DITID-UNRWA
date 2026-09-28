<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($log) ? 'Load Note - ' . $log->id : 'Bulk' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 15mm; }
        @media print { body { margin: 0; } .no-print { display: none; } }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px;
            color: #000; background: #fff; width: 100%; max-width: 210mm; margin: 0 auto; line-height: 1.4;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { vertical-align: top; }
        .value-line { border-bottom: 1px solid #000; font-weight: bold; padding: 0 5px; min-height: 18px; word-wrap: break-word; }
        .inline-line { border-bottom: 1px solid #000; display: inline-block; font-weight: bold; padding: 0 5px; text-align: center; }
        .header-title { text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 4px; }
        .small-text { font-size: 10px; }
        .goods-table { margin-top: 15px; border: 1px solid #000; }
        .goods-table th, .goods-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .goods-table th { font-weight: normal; background-color: #f9f9f9 !important; -webkit-print-color-adjust: exact; }
        .arabic-text { direction: rtl; text-align: center; font-size: 11px; margin: 15px 0; }
    </style>
</head>
<body onload="window.print()">

    @php
        $totalGrossWeight = 0;
        $totalNetWeight = 0;
        $finalPoNumber = $log->contract_po_number;

        $isTransfer = false;
        $previousUser = null;

        if (isset($log) && $log->item_id) {
            $checkinLog = \App\Models\Actionlog::with('target')
                ->where('item_id', $log->item_id)
                ->where('item_type', \App\Models\Asset::class)
                ->where('action_type', 'checkin from')
                ->where('created_at', '>=', \Carbon\Carbon::parse($log->created_at)->subMinutes(1))
                ->where('created_at', '<=', \Carbon\Carbon::parse($log->created_at)->addSeconds(5))
                ->latest('id')
                ->first();
                
            if ($checkinLog && $checkinLog->target_type === \App\Models\User::class) {
                $isTransfer = true;
                $previousUser = $checkinLog->target; 
            }
        }
    @endphp

    <!-- Header -->
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 25%;">
                <h2 style="margin:0; font-size: 18px;">U.N.R.W.A.</h2>
                <div class="small-text">05.6.240.1</div>
            </td>
            <td style="width: 50%;" class="header-title">
                @if($isTransfer)
                    TRANSFER NOTE
                @else
                    LOAD NOTE
                @endif
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                Date <span class="inline-line" style="min-width: 80px;">{{ date('D d/m/Y') }}</span><br><br>
                No. <span class="inline-line" style="min-width: 80px;">ISD/{{ date('y') }}/{{ $log->id ?? 'XX' }}</span>
            </td>
        </tr>
    </table>

    <!-- Addresses -->
    <table style="margin-bottom: 10px; width: 100%;">
        <tr>
            <td style="width: 55%;">
                <table style="width: 95%;">
                    <tr>
                        <td style="width: 35px; padding-top: 2px;">TO</td>
                        <td class="value-line">{{ $log->target?->department?->name ?? '____________________' }}</td>
                    </tr>
                    <tr><td></td><td class="small-text" style="text-align: center;">(Carrier's Name)</td></tr>
                </table>
            </td>
            <td style="width: 45%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 45px; padding-top: 2px;">FROM</td>
                        <td class="value-line">
                            {{ $issuer?->department?->name ?? '____________________' }}
                            @if($isTransfer && $previousUser)
                                <br><span style="font-size: 11px; font-weight: normal; color: #444;">(Transferred from: {{ $previousUser->first_name }} {{ $previousUser->last_name }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td></td><td class="small-text" style="text-align: center;">(UNRWA Office)</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-bottom: 10px; width: 100%;">
        <tr>
            <td style="width: 55%;">
                <table style="width: 95%;">
                    <tr>
                        <td style="width: 155px; padding-top: 2px;">TO BE COLLECTED FROM</td>
                        <td class="value-line">{{ $log->item?->location?->name ?? '____________________' }}</td>
                    </tr>
                    <tr><td></td><td class="small-text" style="text-align: center;">(Supplier's Name)</td></tr>
                </table>
            </td>
            <td style="width: 45%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 25px; padding-top: 2px;">AT</td>
                        <td class="value-line">{{ $log->item?->location?->city ?? $log->item?->location?->address ?? $log->item?->location?->name ?? '____________________' }}</td>
                    </tr>
                    <tr><td></td><td class="small-text" style="text-align: center;">(Place from which to collect goods)</td></tr>
                </table>
            </td>
        </tr>
    </table>

@php
        // استخراج المكاتب الفرعية (Sub-Units) من الحقول المخصصة للمواد المسلمة
        $subUnits = [];
        foreach($allLogs as $singleLog) {
            if ($singleLog->item_type === \App\Models\Asset::class && $singleLog->item && $singleLog->item->model && $singleLog->item->model->fieldset) {
                foreach($singleLog->item->model->fieldset->fields as $field) {
                    // نبحث عن الحقل الذي يحتوي على كلمة Sub-Department أو Unit
                    if (stripos($field->name, 'Sub-Department') !== false || stripos($field->name, 'Unit') !== false || stripos($field->name, 'Office') !== false) {
                        $val = $singleLog->item->{$field->db_column};
                        if (!empty($val)) {
                            $subUnits[] = $val;
                        }
                    }
                }
            }
        }
        $subUnits = array_unique(array_filter($subUnits));
    @endphp

    <table style="margin-bottom: 15px; width: 100%;">
        <tr>
            <td style="width: 100%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 140px; padding-top: 2px;">TO BE DELIVERED TO</td>
                        <td class="value-line" style="width: 60%; line-height: 1.6; padding-bottom: 5px;">
                            <!-- الاسم الأساسي للمستلم (تم تصحيح طريقة جلب الاسم هنا) -->
                            <span style="font-size: 13px;">{{ $log->target?->display_name ?? $log->target?->name ?? '_____________________' }}</span>
                            
                            <!-- القسم الرئيسي -->
                            @if(isset($log->target?->department))
                                <span style="font-size: 11px; color: #333;"><strong>/ Dept:</strong> {{ $log->target->department->name }}</span>
                            @endif

                            <!-- الموقع الجغرافي الهرمي -->
                            @if(isset($log->target?->location))
                                <span style="font-size: 11px; color: #333;">
                                    <strong>/ Loc:</strong> {{ $log->target->location->name }}
                                    @if($log->target->location->parent)
                                        ({{ $log->target->location->parent->name }})
                                    @endif
                                </span>
                            @endif

                            <!-- المكاتب والوحدات الفرعية -->
                            @if(!empty($subUnits))
                                <span style="font-size: 11px; color: #333;"><strong>/ </strong> {{ implode(' , ', $subUnits) }}</span>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                    <tr><td></td><td class="small-text" style="text-align: center;">Consignee Details</td><td></td></tr>
                </table>
            </td>
        </tr>
    </table>
    <!-- Order Info & Signatures -->
<table style="margin-top: 15px;">
        <tr>
            <td style="width: 45%;">
                <table style="border: 1px solid #000; height: 45px;">
                    <tr>
                        <td style="width: 50%; padding: 4px; border-right: 1px solid #000;">
                            <div class="small-text">Contract Purchase Order No.</div>
                            <div style="font-weight: bold; text-align: center; margin-top: 5px;">{{ $finalPoNumber ?? '------' }}</div>
                        </td>
                        <td style="width: 50%; padding: 4px;"><div class="small-text">Despatch Order No.</div></td>
                    </tr>
                </table>
            </td>
            <td style="width: 55%; padding-left: 20px; vertical-align: bottom; padding-bottom: 5px;">
                <table style="width: 100%;">
                    @if($isTransfer && $checkinLog && $checkinLog->user)
                    <tr>
                        <td style="width: 100px; padding-bottom: 5px;" class="small-text">Requested By</td>
                        <td class="value-line" style="text-align: center; font-size: 11px;">
                            {{ $checkinLog->user->first_name ?? '' }} {{ $checkinLog->user->last_name ?? '' }}
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="width: 100px;" class="small-text">Approved & Issued By</td>
                        <td class="value-line" style="text-align: center; font-size: 11px;">
                            {{ $issuer?->first_name ?? '' }} {{ $issuer?->last_name ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 50%;"><div style="font-size: 11px;">Please receive for despatch the goods described below :-</div></td>
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
@foreach($allLogs as $index => $singleLog)
                @php
                    $item = $singleLog->item;
                    $grossWeight = 0; $netWeight = 0; $sapPo = '';
                    $newLocator = ''; $oldLocator = ''; // متغيرات الـ Locators
                    
                    // 1. استخراج الأوزان ورقم الطلبية والـ Locators من الحقول المخصصة
                    if ($singleLog->item_type === \App\Models\Asset::class && $item && $item->model && $item->model->fieldset) {
                        foreach($item->model->fieldset->fields as $field) {
                            $fieldName = strtolower(trim($field->name));
                            if (str_contains($fieldName, 'gross weight')) { $grossWeight = (float) $item->{$field->db_column}; }
                            if (str_contains($fieldName, 'net weight')) { $netWeight = (float) $item->{$field->db_column}; }
                            if (str_contains($fieldName, 'sap') || str_contains($fieldName, 'po')) { $sapPo = $item->{$field->db_column}; }
                            
                            // التقاط قيم الـ Locators
                            if (str_contains($fieldName, 'new locator')) { $newLocator = $item->{$field->db_column}; }
                            if (str_contains($fieldName, 'old locator')) { $oldLocator = $item->{$field->db_column}; }
                        }
                    }
                    $totalGrossWeight += $grossWeight;
                    $totalNetWeight += $netWeight;
                    
                    // تحديث رقم الطلبية إذا كان فارغاً
                    if(empty($finalPoNumber)) { $finalPoNumber = $sapPo ?: ($item->order_number ?? ''); }

                    // تحديد اللوكيتر النهائي (الجديد أولاً، وإلا القديم، وإلا فارغ)
                    $finalLocator = !empty($newLocator) ? $newLocator : (!empty($oldLocator) ? $oldLocator : '');

                    // 2. منطق ذكي لتحديد اسم المادة وتفاصيلها بناءً على نوعها
                    $itemName = $item->name ?? 'Unknown Item';
                    $itemDetails = '';
                    $unit = 'EA';

                    if ($singleLog->item_type === \App\Models\Asset::class) {
                        // إذا كان جهازاً
                        $itemName = $item->model->name ?? $item->name ?? 'Asset';
                        
                        // عرض رقم الوكالة LC، وإضافة اللوكيتر إذا وُجد (مع إزالة السيريال نمبر)
                        $itemDetails = "Asset Tag: " . ($item->asset_tag ?? '-');
                        if (!empty($finalLocator)) {
                            $itemDetails .= " &nbsp;&nbsp;|&nbsp;&nbsp; <strong style='color:#333;'>Locator: " . $finalLocator . "</strong>";
                        }
                    } elseif ($singleLog->item_type === \App\Models\Consumable::class) {
                        // إذا كان مستهلكاً
                        $itemName = $item->name ?? 'Consumable';
                        $itemDetails = "Item No: " . ($item->item_no ?? '-');
                    } elseif ($singleLog->item_type === \App\Models\Accessory::class) {
                        // إذا كان إكسسواراً
                        $itemName = $item->name ?? 'Accessory';
                        $itemDetails = "Model No: " . ($item->model_number ?? '-');
                    } elseif ($singleLog->item_type === \App\Models\License::class) {
                        // إذا كان ترخيصاً
                        $itemName = $item->name ?? 'License';
                        $itemDetails = "Serial/Key: " . ($item->serial ?? '-');
                        $unit = 'Lic';
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: left; padding: 6px;">
                        <span style="font-weight: bold;">{{ $itemName }}</span>
                        @if($itemDetails != '')
                            <br><span style="font-size: 11px;">{!! $itemDetails !!}</span>
                        @endif
                    </td>
                    <td style="font-weight: bold;">{{ $unit }}</td>
                    <td style="font-weight: bold;">1</td>
                    <td>{{ $grossWeight ?: '-' }}</td>
                    <td>{{ $netWeight ?: '-' }}</td>
                </tr>
            @endforeach

            <!-- فراغ لدفع الفوتر للأسفل -->
            <tr>
                <td style="height: 120px; border-bottom: none; border-top: none;"></td>
                <td style="border-bottom: none; border-top: none;"></td>
                <td style="border-bottom: none; border-top: none;"></td>
                <td style="border-bottom: none; border-top: none;"></td>
                <td style="border-bottom: none; border-top: none;"></td>
                <td style="border-bottom: none; border-top: none;"></td>
            </tr>

            <!-- سطر المجاميع -->
            <tr style="background-color: #f9f9f9; -webkit-print-color-adjust: exact;">
                <td colspan="3" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL / المجموع الإجمالي &nbsp;</td>
                <td style="font-weight: bold; font-size: 14px;">{{ count($allLogs) }}</td>
                <td style="font-weight: bold; font-size: 14px;">{{ $totalGrossWeight ?: '-' }}</td>
                <td style="font-weight: bold; font-size: 14px;">{{ $totalNetWeight ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="arabic-text">
        ان الشاحنة ادناه محملة الى لاجئي فلسطين في لبنان - سوريا - المملكة الاردنية الهاشمية - لذلك نرجو السلطات المختصة تسهيل معاملاتها لتصل باقرب وقت ممكن
    </div>

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

    <table style="border-top: 1px solid #000; margin-top: 20px; padding-top: 10px;">
        <tr>
            <td style="width: 75%; font-size: 11px;">
                <span style="letter-spacing: 2px; font-weight: bold;">RECEIPT</span> &nbsp;&nbsp;&nbsp; 
                I have received the goods described above in good condition subject to following remarks ( if any )
            </td>
            <td style="width: 25%; text-align: right; vertical-align: bottom; padding-top: 30px;">
                <div class="small-text">Received by (Signature)</div>
                <span class="inline-line" style="width: 150px; margin-top: 10px;"></span>
            </td>
        </tr>
    </table>

</body>
</html>