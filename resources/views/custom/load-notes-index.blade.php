@extends('layouts/default')

@section('title', 'Document Center (Load Notes & Receipts)')

@section('content')
<x-container>
    <x-box>
        <div class="table-responsive">
            <table class="table table-striped snipe-table"
                   data-toggle="table"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-sortable="true">
                <thead>
                    <tr>
                        <th data-sortable="true">Ref No.</th>
                        <th data-sortable="true">Date</th>
                        <th data-sortable="true">Type</th>
                        <th data-sortable="true">Target</th>
                        <th>Items</th>
                        <th data-sortable="true">Issuer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedLogs as $key => $logs)
                        @php
                            $first = $logs->first();
                            $isCheckout = $first->action_type === 'checkout';
                            $typeLabel = $isCheckout ? 'Load Note' : 'Return Receipt';
                            $typeClass = $isCheckout ? 'bg-maroon' : 'bg-purple';
                            
                            if ($isCheckout && $first->item_type === \App\Models\Asset::class) {
                                $checkinLog = \App\Models\Actionlog::where('item_id', $first->item_id)
                                    ->where('action_type', 'checkin from')
                                    ->where('created_at', '>=', \Carbon\Carbon::parse($first->created_at)->subMinutes(1))
                                    ->where('created_at', '<=', \Carbon\Carbon::parse($first->created_at)->addSeconds(5))
                                    ->first();
                                if ($checkinLog) {
                                    $typeLabel = 'Transfer Note';
                                    $typeClass = 'bg-orange';
                                }
                            }
                        @endphp
                        <tr>
                            <td style="vertical-align: middle; font-weight:bold;">
                                {{ $isCheckout ? 'ISD/' . date('y', strtotime($first->created_at)) . '/' . $first->id : 'RTN-' . $first->id }}
                            </td>
                            <td style="vertical-align: middle;">{{ $first->created_at }}</td>
                            <td style="vertical-align: middle;"><span class="badge {{ $typeClass }}">{{ $typeLabel }}</span></td>
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
                            <td style="vertical-align: middle;">
                                @if($isCheckout)
                                    <a href="{{ url('/hardware/load-note/' . $first->id) }}" class="btn btn-sm btn-info" target="_blank" data-tooltip="true" title="Print Load Note">
                                        <i class="fas fa-print"></i>
                                    </a>
                                @else
                                    <a href="{{ url('/hardware/checkin-receipt/' . $first->id) }}" class="btn btn-sm btn-primary" target="_blank" data-tooltip="true" title="Print Check-in Receipt">
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
@stop