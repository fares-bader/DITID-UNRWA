<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($log) ? 'Load Note - ' . $log->id : 'Bulk' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 15mm; }
        @media print { 
            body { margin: 0; } 
            .no-print { display: none; } 
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px;
            color: #000; background: #fff; width: 100%; max-width: 210mm; margin: 0 auto; line-height: 1.4;
        }
        .page-container {
            page-break-after: always; 
            position: relative;
            height: 275mm;
        }
        .page-container:last-child {
            page-break-after: auto; 
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { vertical-align: middle; }
        
        .header-title { text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 4px; vertical-align: top;}
        .small-text { font-size: 10px; color: #555; }
        
        .info-box { 
            border: 1px solid #bbb; 
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .info-box td {
            padding: 5px 8px;
            border-bottom: 1px solid #ddd;
        }
        .info-box tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            width: 125px;
            font-size: 10px;
            font-weight: bold;
            color: #555;
            background-color: #f5f5f5;
            border-right: 1px solid #ddd;
        }
        .info-value {
            font-weight: bold;
            font-size: 11px;
        }

        .goods-table { margin-top: 10px; border: 1.5px solid #888; }
        .goods-table th, .goods-table td { border: 1px solid #888; padding: 5px; text-align: center; vertical-align: middle; font-size: 11px; }
        .goods-table th { font-weight: normal; background-color: #eee !important; color: #222; height: 25px;}
        
        .fixed-bottom-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }

        .arabic-text { direction: rtl; text-align: center; font-size: 11px; margin-bottom: 8px; }
        
        .signature-box { border: 1px solid #bbb; border-radius: 4px; margin-bottom: 8px; padding: 8px; }
        .sig-line { border-bottom: 1px solid #888; display: inline-block; width: 130px; margin: 0 5px; }
        
        .page-footer {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #555;
            margin-top: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body onload="setTimeout(function(){ window.print(); }, 1000)">

    @php
        $isConsolidated = $consolidatedLoadNote ?? false;
        $isTransfer = false;
        $previousUser = null;
        $checkinLog = null;
        $collectedFrom = '____________________';

        if (!$isConsolidated && isset($log) && $log->item_id) {
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

            // ====== UNRWA Logic: Determine the "Collected From" Location ======
            if ($isTransfer && $previousUser) {
                // If it's an immediate transfer, it came from the previous user's location
                $collectedFrom = $previousUser->location?->name ?? $previousUser->department?->name ?? 'Unknown Location';
            } elseif ($log->item_type === \App\Models\Asset::class) {
                // If it's an asset, it came from its Default Location (RTD Location - Warehouse)
                $collectedFrom = $log->item?->defaultLoc?->name ?? $issuer?->location?->name ?? '____________________';
            } else {
                // For consumables/accessories (their location doesn't change on checkout)
                $collectedFrom = $log->item?->location?->name ?? $issuer?->location?->name ?? '____________________';
            }
            // =================================================================
        }

        $loadNoteNo = $isConsolidated
            ? $consolidatedReference
            : "ISD/" . date('y') . "/" . ($log->id ?? 'XX');
        $loadNoteLink = $isConsolidated ? $consolidatedQrUrl : url()->current();
        $loadNoteQrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($loadNoteLink);

        $chunks = collect($allLogs)->chunk(5);
        $totalPages = $chunks->count();
    @endphp

    @foreach($chunks as $pageIndex => $chunk)
    <div class="page-container">

        <!-- Header -->
        <table style="margin-bottom: 15px;">
            <tr>
                <td style="width: 25%; vertical-align: top;">
                    <h2 style="margin:0; font-size: 18px;">U.N.R.W.A.</h2>
                    <div class="small-text">05.6.240.1</div>
                </td>
                <td style="width: 50%; text-align: center;" class="header-title">
                    <div style="margin-bottom: 5px;">
                        @if($isConsolidated)
                            CONSOLIDATED LOAD NOTE
                        @elseif($isTransfer)
                            TRANSFER NOTE
                        @else
                            LOAD NOTE
                        @endif
                    </div>
                    <img src="{{ $loadNoteQrUrl }}" style="width: 55px; height: 55px;" alt="Load Note QR" />
                </td>
                <td style="width: 25%; text-align: right; vertical-align: top;">
                    <div style="font-weight:bold; margin-bottom: 5px;">Date: <span style="font-weight:normal; border-bottom:1px solid #ccc; padding: 0 5px;">{{ date('d M Y') }}</span></div>
                    <div style="font-weight:bold;">No.: <span style="font-weight:normal; border-bottom:1px solid #ccc; padding: 0 5px;">{{ $loadNoteNo }}</span></div>
                </td>
            </tr>
        </table>

        <table style="margin-bottom: 2px;">
            <tr>
                <td style="width: 49%; vertical-align: top;">
                    <table class="info-box">
                        <tr>
                            <td class="info-label">FROM <br><span class="small-text">(UNRWA Office)</span></td>
                            <td class="info-value">
                                {{ $issuer?->department?->name ?? '____________________' }}
                                @if($isTransfer && $previousUser)
                                    <div style="font-size: 10px; font-weight: normal; color: #666; margin-top:2px;">(Transferred from: {{ $previousUser->first_name }} {{ $previousUser->last_name }})</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">COLLECTED FROM<br><span class="small-text">(Supplier/Location)</span></td>
                            <td class="info-value">{{ $collectedFrom }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 2%;"></td>
                <td style="width: 49%; vertical-align: top;">
                    <table class="info-box">
                        <tr>
                            <td class="info-label">TO <br><span class="small-text">(Carrier/Dept)</span></td>
                            <td class="info-value">
                                {{ $log->target?->department?->name ?? '____________________' }}
                                @if(isset($log->target?->sub_department) && !empty($log->target->sub_department))
                                    / {{ $log->target->sub_department }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">AT <br><span class="small-text">(City/Place)</span></td>
                            <td class="info-value">{{ $log->item?->location?->city ?? $log->item?->location?->address ?? $log->item?->location?->name ?? '____________________' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="info-box" style="width: 100%;">
            <tr>
                <td class="info-label" style="width: 125px;">TO BE DELIVERED TO<br><span class="small-text">Consignee Details</span></td>
                <td class="info-value" style="font-size: 13px;">
                    {{ $log->target?->display_name ?? $log->target?->name ?? '_____________________' }}
                    
                    @if(isset($log->target?->department))
                        <span style="font-size: 11px; font-weight:normal; margin-left: 8px;">
                            / Dept: {{ $log->target->department->name }} 
                            @if(isset($log->target?->sub_department) && !empty($log->target->sub_department))
                                - {{ $log->target->sub_department }}
                            @endif
                        </span>
                    @endif
                    
                    <!-- ====== UNRWA: Actual Destination Location ====== -->
                    @if(isset($log->location) || isset($log->item?->location))
                        <span style="font-size: 12px; font-weight:bold; margin-left: 8px;">
                            / Loc: {{ $log->location?->name ?? $log->item?->location?->name }}
                        </span>
                    @elseif(isset($log->target?->location))
                        <span style="font-size: 11px; font-weight:normal; margin-left: 8px;">
                            / User Loc: {{ $log->target->location->name }}
                        </span>
                    @endif
                    <!-- ============================================================= -->
                </td>
            </tr>
        </table>

        <table class="info-box" style="width: 100%;">
            <tr>
                @if($isTransfer && $checkinLog && $checkinLog->user)
                <td class="info-label" style="width: 95px;">Requested By</td>
                <td class="info-value" style="width: 35%;">{{ $checkinLog->user->first_name ?? '' }} {{ $checkinLog->user->last_name ?? '' }}</td>
                <td style="border-right: 1px solid #ddd; width: 1px; padding:0;"></td>
                @endif
                <td class="info-label" style="width: 130px;">Approved & Issued By</td>
                <td class="info-value" style="width: 35%;">
                    @if($isConsolidated)
                        {{ trans('general.load_note_multiple_issuers') }}
                    @else
                        {{ $issuer?->first_name ?? '' }} {{ $issuer?->last_name ?? '' }}
                    @endif
                </td>
                <td class="info-label" style="width: 75px; text-align: center;">Signature</td>
                <td style="text-align: center; width: 150px;">
                    <span class="sig-line" style="width: 110px; margin: 0;"></span>
                </td>
            </tr>
        </table>

        <div style="font-size: 11px; margin-top: 10px; margin-bottom: 4px;">Please receive for despatch the goods described below :-</div>

        <!-- Goods Table -->
        <table class="goods-table" style="margin-top: 0;">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 8%;">QR</th>
                    <th style="width: 42%;">GOODS DESCRIPTION</th>
                    <th style="width: 8%;">UNIT</th>
                    <th style="width: 9%;">QTY</th>
                    <th style="width: 28%;">CHECKOUT NOTES</th>
                </tr>
            </thead>
            <tbody>
                @php $rowCount = 0; @endphp
                @foreach($chunk as $index => $singleLog)
                    @php
                        $item = $singleLog->item;
                        $newLocator = ''; $oldLocator = ''; 
                        
                        if ($singleLog->item_type === \App\Models\Asset::class && $item && $item->model && $item->model->fieldset) {
                            foreach($item->model->fieldset->fields as $field) {
                                $fieldName = strtolower(trim($field->name));
                                if (str_contains($fieldName, 'new locator')) { $newLocator = $item->{$field->db_column}; }
                                if (str_contains($fieldName, 'old locator')) { $oldLocator = $item->{$field->db_column}; }
                            }
                        }
                        
                        $finalLocator = !empty($newLocator) ? $newLocator : (!empty($oldLocator) ? $oldLocator : '');
                        $poNumber = $item->order_number ?? '';

                        $itemName = $item->name ?? 'Unknown Item';
                        $unit = 'EA';
                        $routePrefix = 'hardware';

                        if ($singleLog->item_type === \App\Models\Asset::class) {
                            $itemName = $item->model->name ?? $item->name ?? 'Asset';
                        } elseif ($singleLog->item_type === \App\Models\Consumable::class) {
                            $itemName = $item->name ?? 'Consumable';
                            $routePrefix = 'consumables';
                        } elseif ($singleLog->item_type === \App\Models\Accessory::class) {
                            $itemName = $item->name ?? 'Accessory';
                            $routePrefix = 'accessories';
                        } elseif ($singleLog->item_type === \App\Models\License::class) {
                            $itemName = $item->name ?? 'License';
                            $unit = 'Lic';
                            $routePrefix = 'licenses';
                        } elseif ($singleLog->item_type === \App\Models\Component::class) {
                            $itemName = $item->name ?? 'Component';
                            $routePrefix = 'components';
                        }

                        $itemLink = config('app.url') . "/" . $routePrefix . "/" . ($item->id ?? '');
                        $itemQrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=" . urlencode($itemLink);
                        $rowCount++;
                    @endphp
                    <tr style="height: 65px;">
                        <td>{{ ($pageIndex * 5) + $loop->iteration }}</td>
                        <td><img src="{{ $itemQrUrl }}" style="width: 45px; height: 45px;" alt="QR" /></td>
                        <td style="text-align: left; padding: 5px;">
                            <span style="font-weight: bold; font-size: 12px;">{{ $itemName }}</span>
                            <br>
                            <span style="font-size: 10px; color:#444;">
                                @if($isConsolidated)
                                    Issued {{ \Carbon\Carbon::parse($singleLog->created_at)->format('d M Y') }}
                                    by {{ $singleLog->adminuser?->display_name ?? '—' }}<br>
                                @endif
                                @if($singleLog->item_type === \App\Models\Asset::class)
                                    Asset Tag: {{ $item->asset_tag ?? '-' }}
                                @elseif($singleLog->item_type === \App\Models\Consumable::class)
                                    Item No: {{ $item->item_no ?? '-' }}
                                @elseif($singleLog->item_type === \App\Models\Accessory::class)
                                    Model No: {{ $item->model_number ?? '-' }}
                                @elseif($singleLog->item_type === \App\Models\License::class)
                                    Serial/Key: {{ $item->serial ?? '-' }}
                                @elseif($singleLog->item_type === \App\Models\Component::class)
                                    Serial: {{ $item->serial ?? '-' }}
                                @endif

                                @if(!empty($finalLocator))
                                    &nbsp;|&nbsp; <strong>Loc:</strong> {{ $finalLocator }}
                                @endif
                            </span>
                            
                            @if(!empty($poNumber))
                                <div style="font-size: 9.5px; color: #666; font-style: italic; margin-top: 2px;">
                                    PO No: {{ $poNumber }}
                                </div>
                            @endif
                        </td>
                        <td style="font-weight: bold;">{{ $unit }}</td>
                        <td style="font-weight: bold; font-size: 12px;">1</td>
                        <td style="font-size: 11px; text-align: right; padding-right: 5px;">{{ $singleLog->note ?? '-' }}</td>
                    </tr>
                @endforeach

                @for($i = $rowCount; $i < 5; $i++)
                    <tr style="height: 65px;">
                        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    </tr>
                @endfor

                <tr style="background-color: #eee;">
                    <td colspan="3" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL&nbsp;</td>
                    <td colspan="3" style="font-weight: bold; font-size: 14px; text-align: left; padding-left: 10px;">{{ count($allLogs) }} items</td>
                </tr>
            </tbody>
        </table>

        <div class="fixed-bottom-footer">
            
            <div class="arabic-text">
                ان الشاحنة ادناه محملة الى لاجئي فلسطين في لبنان - سوريا - المملكة الاردنية الهاشمية - لذلك نرجو السلطات المختصة تسهيل معاملاتها لتصل باقرب وقت ممكن
            </div>

            <table class="signature-box" style="width: 100%;">
                <tr>
                    <td style="width: 32%; border-right: 1px solid #ddd; padding-right: 8px;">
                        <div style="font-weight: bold; margin-bottom: 5px; color: #555; font-size: 10px;">CARRIER DETAILS</div>
                        <div style="font-size: 10px; margin-bottom: 3px;"><strong>Wagon/Track:</strong> <span style="font-weight:bold; color: #333;">{{ $log->vehicle_number ?? '__________' }}</span></div>
                        <div style="font-size: 10px;"><strong>Driver:</strong> <span style="font-weight:bold; color: #333;">{{ $log->driver_name ?? '__________' }}</span></div>
                    </td>
                    <td style="width: 34%; border-right: 1px solid #ddd; padding: 0 10px; text-align: center; vertical-align: bottom;">
                        <div class="small-text" style="margin-bottom: 25px;">Despatched by<br>ممثل الوكالة</div>
                        <span class="sig-line" style="width: 85%;"></span>
                    </td>
                    <td style="width: 34%; padding-left: 10px; text-align: center; vertical-align: bottom;">
                        <div class="small-text" style="margin-bottom: 25px;">Carrier's Signature<br>امضاء السائق</div>
                        <span class="sig-line" style="width: 85%;"></span>
                    </td>
                </tr>
            </table>

            <table class="signature-box" style="width: 100%; background-color: #fbfbfb;">
                <tr>
                    <td style="width: 60%; font-size: 10px; padding-right: 10px; vertical-align: top;">
                        <strong style="letter-spacing: 1px; font-size: 11px;">RECEIPT:</strong> I have received goods in good condition. 
                        <div style="font-family: Arial; direction:rtl; margin-top:2px; color:#333;">استلمت البضاعة المذكورة أعلاه بالتمام.</div>
                        
                        <div style="margin-top: 8px; border-top: 1px dashed #bbb; padding-top: 5px;">
                            <strong>Remarks / ملاحظات الاستلام:</strong>
                            <div style="border-bottom: 1px dotted #888; height: 18px; margin-top: 2px;"></div>
                        </div>
                    </td>
                    <td style="width: 40%; text-align: center; vertical-align: bottom; padding-bottom: 3px;">
                        <div class="small-text" style="margin-bottom: 25px;">Received by (Consignee Signature)</div>
                        <span class="sig-line" style="width: 90%;"></span>
                    </td>
                </tr>
            </table>
            
            <div class="page-footer">
                Page {{ $pageIndex + 1 }} of {{ $totalPages }}
            </div>

        </div>

    </div>
    @endforeach

</body>
</html>
