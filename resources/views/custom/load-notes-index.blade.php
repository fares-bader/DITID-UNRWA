@extends('layouts/default')

@section('title', 'Document Center')

@section('content')
<x-container>
    <x-box>
        <!-- Toolbar for Bootstrap Table -->
        <div id="toolbar" class="toolbar">
            <select id="filterType" class="form-control select2" style="min-width: 200px;" data-placeholder="Filter by Type">
                <option value="">All Types</option>
                <option value="Load Note">Load Note</option>
                <option value="Return Receipt">Return Receipt</option>
                <option value="Transfer Note">Transfer Note</option>
            </select>
        </div>

        <div class="table-responsive">
            <table id="documentCenterTable" 
                   class="table table-striped snipe-table"
                   data-toggle="table"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-sortable="true"
                   data-toolbar="#toolbar">
                <thead>
                    <tr>
                        <th data-sortable="true" data-field="ref_no">Ref No.</th>
                        <th data-sortable="true" data-field="date">Date</th>
                        <!-- استخدام Formatter لتلوين النص دون تخريب الفرز -->
                        <th data-sortable="true" data-field="doc_type" data-formatter="typeBadgeFormatter">Type</th>
                        <th data-sortable="true" data-field="target">Target</th>
                        <th data-field="items">Items</th>
                        <th data-sortable="true" data-field="issuer">Issuer</th>
                        <th data-field="action" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedLogs as $key => $logs)
                        @php
                            $first = $logs->first();
                            $isCheckout = $first->action_type === 'checkout';
                            $typeLabel = $isCheckout ? 'Load Note' : 'Return Receipt';
                            
                            if ($isCheckout && $first->item_type === \App\Models\Asset::class) {
                                $checkinLog = \App\Models\Actionlog::where('item_id', $first->item_id)
                                    ->where('action_type', 'checkin from')
                                    ->where('created_at', '>=', \Carbon\Carbon::parse($first->created_at)->subMinutes(1))
                                    ->where('created_at', '<=', \Carbon\Carbon::parse($first->created_at)->addSeconds(5))
                                    ->first();
                                if ($checkinLog) {
                                    $typeLabel = 'Transfer Note';
                                }
                            }
                        @endphp
                        <tr>
                            <td style="vertical-align: middle; font-weight:bold; font-size: 13px;">
                                {{ $isCheckout ? 'ISD/' . date('y', strtotime($first->created_at)) . '/' . $first->id : 'RTN-' . $first->id }}
                            </td>
                            <td style="vertical-align: middle;">{{ $first->created_at }}</td>
                            
                            <!-- نضع النص الصافي فقط هنا لكي ينجح الفرز 100% -->
                            <td style="vertical-align: middle;">{{ $typeLabel }}</td>
                            
                            <td style="vertical-align: middle;">
                                @if($first->target)
                                    <i class="fas fa-user text-muted"></i> {!! $first->target->present()->nameUrl() !!}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <ul style="padding-left: 15px; margin-bottom: 0; line-height: 1.8;">
                                @foreach($logs as $log)
                                    @if($log->item)
                                        <li>
                                            {!! $log->item->present()->nameUrl() !!}
                                            @if($log->item->asset_tag)
                                                <span class="text-muted" style="font-size:11px;">({{ $log->item->asset_tag }})</span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                                </ul>
                            </td>
                            <td style="vertical-align: middle;">
                                @if($first->user)
                                    {!! $first->user->present()->nameUrl() !!}
                                @endif
                            </td>
                            <td style="vertical-align: middle; text-align:center;">
                                @if($isCheckout)
                                    <a href="{{ url('/hardware/load-note/' . $first->id) }}" class="btn btn-sm btn-info" target="_blank" data-tooltip="true" title="Print Load Note">
                                        <i class="fas fa-print"></i>
                                    </a>
                                @else
                                    <a href="{{ url('/hardware/checkin-receipt/' . $first->id) }}" class="btn btn-sm btn-primary" target="_blank" data-tooltip="true" title="Print Return Receipt">
                                        <i class="fas fa-print"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-box>
</x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table', ['simple_view' => true])

    <script nonce="{{ csrf_token() }}">
        // دالة التلوين الذكية (تُحوّل النص إلى شارة ملونة بعد أن يقرأه النظام)
        function typeBadgeFormatter(value, row, index) {
            var badgeClass = 'bg-gray';
            var cleanValue = $.trim(value);
            
            if (cleanValue === 'Load Note') {
                badgeClass = 'bg-maroon';
            } else if (cleanValue === 'Return Receipt') {
                badgeClass = 'bg-purple';
            } else if (cleanValue === 'Transfer Note') {
                badgeClass = 'bg-orange';
            }
            
            return '<span class="badge ' + badgeClass + '">' + cleanValue + '</span>';
        }

        $(function() {
            var $table = $('#documentCenterTable');
            
            // تشغيل الفرز عند تغيير خيار القائمة المنسدلة
            $('#filterType').on('change', function () {
                var filterValue = $(this).val();
                if (filterValue === "") {
                    $table.bootstrapTable('filterBy', {}); // إظهار الكل
                } else {
                    $table.bootstrapTable('filterBy', {
                        doc_type: filterValue // مطابقة النص الصافي
                    });
                }
            });
        });
    </script>
@stop