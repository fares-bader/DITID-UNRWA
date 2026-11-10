@extends('layouts/default')

@section('title', trans('general.unrwa_request_detail_title', ['id' => $assetRequest->id]))

@section('content')
@php
    $requestStatus = $assetRequest->status;
    $statusKey = match (strtolower($requestStatus)) {
        'fulfilled' => 'general.unrwa_request_status_fulfilled',
        'canceled' => 'general.unrwa_request_status_canceled',
        default => $fulfilledQuantity > 0 ? 'general.unrwa_request_status_in_progress' : 'general.unrwa_request_status_pending',
    };
@endphp

<div class="row unrwa-request-detail">
    <div class="col-md-12">
        <div class="request-detail-header">
            <div>
                <p class="request-detail-eyebrow">{{ trans('general.unrwa_request_details') }}</p>
                <h1>{{ trans('general.unrwa_request_detail_title', ['id' => $assetRequest->id]) }}</h1>
                <p class="request-detail-subtitle">{{ $assetRequest->category?->name ?? trans('general.unrwa_request_no_value') }}
                    @if($assetRequest->assetModel) · {{ $assetRequest->assetModel->name }} @endif
                </p>
                @if($assetRequest->parentRequest)
                    <div class="request-detail-lineage">
                        <span><i class="fas fa-code-branch" aria-hidden="true"></i> {{ trans('general.unrwa_request_follow_up_badge') }}</span>
                        <a href="{{ route('unrwa.requests.show', $assetRequest->parentRequest->id) }}" aria-label="{{ trans('general.unrwa_request_parent_link', ['id' => $assetRequest->parentRequest->id]) }}">
                            <bdi dir="ltr">{{ trans('general.unrwa_request_follow_up_chain', ['child' => $assetRequest->id, 'parent' => $assetRequest->parentRequest->id]) }}</bdi>
                        </a>
                        <small>{{ trans('general.unrwa_request_continues_previous') }}</small>
                    </div>
                @endif
            </div>
            <div class="request-detail-actions">
                <a href="{{ route('unrwa.requests.manage') }}" class="btn btn-default">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> {{ trans('general.unrwa_request_back_to_manage') }}
                </a>
                @if($assetRequest->status === 'Pending' && $remainingQuantity > 0)
                    <a href="{{ route('unrwa.requests.fulfill', $assetRequest->id) }}" class="btn btn-success">
                        <i class="fas fa-check" aria-hidden="true"></i> {{ trans('general.unrwa_fulfill_request', ['id' => $assetRequest->id]) }}
                    </a>
                @endif
            </div>
        </div>

        <div class="request-detail-stats" aria-label="{{ trans('general.unrwa_request_details') }}">
            <div class="request-detail-stat">
                <span><i class="fas fa-circle-info" aria-hidden="true"></i> {{ trans('general.unrwa_request_history_status') }}</span>
                <strong><i class="fas {{ $assetRequest->status === 'Fulfilled' ? 'fa-circle-check' : ($assetRequest->status === 'Canceled' ? 'fa-ban' : 'fa-clock') }}" aria-hidden="true"></i> {{ trans($statusKey) }}</strong>
            </div>
            <div class="request-detail-stat">
                <span><i class="fas fa-boxes-stacked" aria-hidden="true"></i> {{ trans('general.unrwa_request_requested_quantity') }}</span>
                <strong><bdi dir="ltr">{{ $assetRequest->quantity }}</bdi></strong>
            </div>
            <div class="request-detail-stat">
                <span><i class="fas fa-box-open" aria-hidden="true"></i> {{ trans('general.unrwa_request_fulfilled_quantity') }}</span>
                <strong><bdi dir="ltr">{{ $fulfilledQuantity }}</bdi></strong>
            </div>
            <div class="request-detail-stat">
                <span><i class="fas fa-hourglass-half" aria-hidden="true"></i> {{ trans('general.unrwa_request_remaining_quantity') }}</span>
                <strong><bdi dir="ltr">{{ $remainingQuantity }}</bdi></strong>
            </div>
        </div>

        <section class="box box-default" aria-labelledby="request-information-title">
            <div class="box-header with-border">
                <h2 class="box-title" id="request-information-title"><i class="fas fa-clipboard-list" aria-hidden="true"></i> {{ trans('general.unrwa_request_details') }}</h2>
            </div>
            <div class="box-body">
                <div class="request-detail-info-columns">
                    <article class="request-info-card requester-info-card">
                        <h3><span class="request-info-icon"><i class="fas fa-user" aria-hidden="true"></i></span>{{ trans('general.unrwa_request_requester_card') }}</h3>
                        <dl class="request-detail-list">
                            <div><dt>{{ trans('general.unrwa_request_requested_by') }}</dt><dd>{{ $assetRequest->user?->display_name ?? trans('general.unrwa_request_no_value') }}</dd></div>
                            @if($assetRequest->user?->email)
                                <div><dt>{{ trans('general.email') }}</dt><dd><a href="mailto:{{ $assetRequest->user->email }}">{{ $assetRequest->user->email }}</a></dd></div>
                            @endif
                            @if($assetRequest->user?->department)
                                <div><dt>{{ trans('general.unrwa_request_department') }}</dt><dd>{{ $assetRequest->user->department->name }}</dd></div>
                            @endif
                            @if($assetRequest->user?->location)
                                <div><dt>{{ trans('general.location') }}</dt><dd>{{ $assetRequest->user->location->name }}</dd></div>
                            @endif
                        </dl>
                    </article>
                    <article class="request-info-card equipment-info-card">
                        <h3><span class="request-info-icon"><i class="fas fa-laptop" aria-hidden="true"></i></span>{{ trans('general.unrwa_request_information_card') }}</h3>
                        <dl class="request-detail-list">
                            <div><dt>{{ trans('general.unrwa_request_category') }}</dt><dd>{{ $assetRequest->category?->name ?? trans('general.unrwa_request_no_value') }}</dd></div>
                            <div><dt>{{ trans('general.unrwa_request_requested_model') }}</dt><dd>{{ $assetRequest->assetModel?->name ?? trans('general.unrwa_request_any_model') }}</dd></div>
                            <div><dt>{{ trans('general.unrwa_request_priority') }}</dt><dd>{{ $assetRequest->request_tier === 'Manager/Admin' ? trans('general.unrwa_priority_manager') : trans('general.unrwa_priority_user') }}</dd></div>
                            <div><dt>{{ trans('general.unrwa_request_submitted_on') }}</dt><dd><bdi dir="ltr">{{ $assetRequest->created_at?->format('Y-m-d H:i') ?? trans('general.unrwa_request_no_value') }}</bdi></dd></div>
                        </dl>
                    </article>
                </div>
                <div class="request-detail-notes">
                    <h3><i class="fas fa-comment-dots" aria-hidden="true"></i> {{ trans('general.unrwa_request_reason') }}</h3>
                    <p>{{ $assetRequest->notes ?: trans('general.unrwa_request_no_notes') }}</p>
                </div>
            </div>
        </section>

        <section class="box box-default" aria-labelledby="request-items-title">
            <div class="box-header with-border">
                <div class="request-items-heading">
                    <div>
                        <h2 class="box-title" id="request-items-title"><i class="fas fa-boxes-stacked" aria-hidden="true"></i> {{ trans('general.unrwa_request_items_title') }}</h2>
                        <p>{{ trans('general.unrwa_request_items_subtitle', ['count' => $assets->count()]) }}</p>
                    </div>
                    <span class="label label-default">{{ trans('general.unrwa_request_items_count', ['count' => $assets->count()]) }}</span>
                </div>
            </div>
            <div class="box-body">
                @if($assets->isEmpty())
                    <div class="request-detail-empty" role="status">
                        <i class="fas fa-box-open fa-2x" aria-hidden="true"></i>
                        <p>{{ trans('general.unrwa_request_no_items') }}</p>
                    </div>
                @else
                    <p class="request-detail-table-hint"><i class="fas fa-arrows-left-right" aria-hidden="true"></i> {{ trans('general.unrwa_request_table_scroll_hint') }}</p>
                    <div class="table-responsive request-detail-table-wrap" role="region" aria-label="{{ trans('general.unrwa_request_items_title') }}" tabindex="0">
                        <table class="table table-striped table-hover request-detail-items-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('general.unrwa_request_asset_tag') }}</th>
                                    <th>{{ trans('general.unrwa_request_requested_model') }}</th>
                                    <th>{{ trans('general.unrwa_request_serial') }}</th>
                                    <th>{{ trans('general.unrwa_request_current_status') }}</th>
                                    <th>{{ trans('general.unrwa_request_delivery_location') }}</th>
                                    <th>{{ trans('general.unrwa_request_delivered_on') }}</th>
                                    <th>{{ trans('general.unrwa_request_processed_by') }}</th>
                                    <th>{{ trans('general.unrwa_batch_column') }}</th>
                                    <th>{{ trans('general.unrwa_request_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assets as $asset)
                                    <tr>
                                        <td><a href="{{ route('hardware.show', $asset) }}"><bdi dir="ltr">{{ $asset->asset_tag }}</bdi></a></td>
                                        <td>{{ $asset->model?->name ?? trans('general.unrwa_request_no_value') }}</td>
                                        <td><bdi dir="ltr">{{ $asset->serial ?: trans('general.unrwa_request_no_value') }}</bdi></td>
                                        <td>{{ $asset->status?->name ?? trans('general.unrwa_request_no_value') }}</td>
                                        <td>{{ $asset->checkoutLog?->location?->name ?? $asset->location?->name ?? trans('general.unrwa_request_no_value') }}</td>
                                        <td><bdi dir="ltr">{{ $asset->checkoutLog?->created_at?->format('Y-m-d H:i') ?? trans('general.unrwa_request_no_value') }}</bdi></td>
                                        <td>{{ $asset->checkoutLog?->adminuser?->display_name ?? trans('general.unrwa_request_no_value') }}</td>
                                        <td>
                                            @if($asset->pivot?->batch_id)
                                                    <span class="request-batch-pill" style="--batch-color: {{ $asset->batchColor }};">
                                                    <i class="fas fa-link" aria-hidden="true"></i>
                                                    <bdi dir="ltr">{{ trans('general.unrwa_batch_number', ['id' => $asset->batchDisplayId]) }}</bdi>
                                                </span>
                                            @else
                                                {{ trans('general.unrwa_request_no_value') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($asset->checkoutLog)
                                                <a href="{{ route('hardware.loadnote', $asset->checkoutLog->id) }}" target="_blank" rel="noopener" class="btn btn-default btn-xs" data-toggle="tooltip" title="{{ trans('general.unrwa_print_load_note') }}" aria-label="{{ trans('general.unrwa_print_request_load_note', ['id' => $assetRequest->id]) }}">
                                                    <i class="fas fa-print" aria-hidden="true"></i>
                                                </a>
                                            @else
                                                {{ trans('general.unrwa_request_no_value') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        @if($assetRequest->followUpRequests->isNotEmpty())
            <section class="box box-default" aria-labelledby="request-followups-title">
                <div class="box-header with-border">
                    <h2 class="box-title" id="request-followups-title">{{ trans('general.unrwa_request_follow_ups') }}</h2>
                </div>
                <div class="list-group">
                    @foreach($assetRequest->followUpRequests as $followUp)
                        <a href="{{ route('unrwa.requests.show', $followUp->id) }}" class="list-group-item request-follow-up-link">
                            <span><strong>{{ trans('general.unrwa_request_follow_up_badge') }} <bdi dir="ltr">{{ trans('general.unrwa_request_follow_up_chain', ['child' => $followUp->id, 'parent' => $assetRequest->id]) }}</bdi></strong>
                                <small>{{ $followUp->category?->name ?? trans('general.unrwa_request_no_value') }} <span aria-hidden="true">·</span> {{ $followUp->quantity }} {{ trans('general.unrwa_request_quantity') }}</small>
                            </span>
                            <span class="label label-default">{{ trans('general.unrwa_request_status_'.strtolower($followUp->status)) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(function() {
        $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover focus' });
    });
</script>
@endsection
