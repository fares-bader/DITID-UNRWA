@extends('layouts/default')

@section('title', 'Manage UNRWA Requests')

@section('content')
<div class="row">
    <div class="col-md-12">
        
        @if(session('unrwa_fulfillment_results'))
            @php
                $fulfillmentResults = session('unrwa_fulfillment_results');
                $fulfilledAssets = $fulfillmentResults['successes'] ?? [];
                $failedAssets = $fulfillmentResults['failures'] ?? [];
            @endphp
            <div class="alert {{ count($failedAssets) ? (count($fulfilledAssets) ? 'alert-warning' : 'alert-danger') : 'alert-success' }}" role="status" aria-live="polite" aria-atomic="true">
                <h4><i class="fas {{ count($failedAssets) ? 'fa-triangle-exclamation' : 'fa-check-circle' }}" aria-hidden="true"></i> {{ trans('general.bulk_checkout_summary') }}</h4>
                <p>{{ trans('general.bulk_checkout_completed', ['count' => count($fulfilledAssets)]) }} · {{ trans('general.bulk_checkout_failed', ['count' => count($failedAssets)]) }}</p>

                @if(count($fulfilledAssets))
                    <details>
                        <summary>{{ trans('general.bulk_checkout_completed_items') }}</summary>
                        <ul>@foreach($fulfilledAssets as $assetTag)<li><bdi dir="ltr">{{ $assetTag }}</bdi></li>@endforeach</ul>
                    </details>
                @endif

                @if(count($failedAssets))
                    <p>{{ trans('general.bulk_checkout_next_step') }}</p>
                    <ul>
                        @foreach($failedAssets as $failedAsset)
                            <li>
                                <strong><bdi dir="ltr">{{ $failedAsset['asset_tag'] ?: trans('general.asset_tag_unavailable') }}</bdi></strong>
                                @forelse($failedAsset['messages'] as $message)
                                    <div>{{ $message }}</div>
                                @empty
                                    <div>{{ trans('general.bulk_checkout_failed_unknown') }}</div>
                                @endforelse
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if(!empty($fulfillmentResults['remainders']))
                    <h5>{{ trans('general.unrwa_remainder_result_title') }}</h5>
                    <ul>
                        @foreach($fulfillmentResults['remainders'] as $remainder)
                            <li>
                                {{ trans('general.unrwa_remainder_result', ['count' => $remainder['quantity'], 'request' => $remainder['request_id']]) }}
                                @if($remainder['action'] === 'separate')
                                    {{ trans('general.unrwa_remainder_created', ['id' => $remainder['follow_up_request_id']]) }}
                                @else
                                    {{ trans('general.unrwa_remainder_canceled') }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if(session('unrwa_load_note_url'))
                    <a class="btn btn-primary btn-sm" href="{{ session('unrwa_load_note_url') }}" target="_blank" rel="noopener">
                        <i class="fas fa-print" aria-hidden="true"></i> {{ trans('general.unrwa_print_load_note') }}
                    </a>
                @endif
            </div>
        @else
            @if(session('success'))
                <div class="alert alert-success" role="status"><i class="fas fa-check" aria-hidden="true"></i> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning" role="status">{{ session('warning') }}</div>
            @endif
        @endif

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-list-alt"></i> UNRWA Equipment Requests</h3>
            </div>
            <div class="box-body">
                <div class="row request-filters">
                    <div class="col-sm-6 form-group">
                        <label for="request-search">Search requests</label>
                        <input type="search" id="request-search" class="form-control" placeholder="Person, category, model, notes or request number">
                    </div>
                    <div class="col-sm-3 form-group">
                        <label for="request-status-filter">Status</label>
                        <select id="request-status-filter" class="form-control">
                            <option value="">All statuses</option><option value="pending">Pending</option><option value="fulfilled">Fulfilled</option><option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <div class="col-sm-3 form-group">
                        <label for="request-group-filter">{{ trans('general.unrwa_group_filter') }}</label>
                        <select id="request-group-filter" class="form-control">
                            <option value="">{{ trans('general.unrwa_group_filter_all') }}</option>
                            <option value="grouped">{{ trans('general.unrwa_group_filter_linked') }}</option>
                            <option value="individual">{{ trans('general.unrwa_group_filter_unlinked') }}</option>
                        </select>
                    </div>
                </div>
                
                <form action="{{ route('unrwa.requests.bulk-fulfill') }}" method="POST" id="bulkFulfillForm">
                    @csrf
                    
                    <div style="margin-bottom: 15px;">
                        <button type="button" id="btnBulkFulfill" class="btn btn-success">
                            <i class="fas fa-check-double"></i> Fulfill Selected Requests (Unified Load Note)
                        </button>
                    </div>
                    <p id="bulk-selection-message" class="text-danger" role="status" aria-live="polite"></p>

                    <div class="table-responsive desktop-request-table">
                        <table class="table table-striped table-hover snipe-table" id="unrwaRequestsTable">
<thead>
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th>ID</th>
                                    <th>Priority</th>
                                    <th>Requested By</th>
                                    <th>Category</th>
                                    <th>Specific Model</th>
                                    <th>Req. Qty</th>
                                    <th>In Stock</th>
                                    <th>Status</th>
                                    <th>{{ trans('general.unrwa_batch_column') }}</th>
                                    <th>Date Requested</th>
                                    <th>Notes</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr class="request-record {{ $req->batch_groups->isNotEmpty() ? 'has-delivery-group' : '' }}" data-status="{{ strtolower($req->status) }}" data-grouped="{{ $req->batch_groups->isNotEmpty() ? 'yes' : 'no' }}" data-search="{{ strtolower($req->id.' '.($req->user?->display_name ?? '').' '.($req->category?->name ?? '').' '.($req->assetModel?->name ?? 'Any Model').' '.($req->notes ?? '').' '.$req->batch_search_term) }}" @if($req->batch_groups->isNotEmpty()) style="--request-group-color: {{ $req->batch_groups->first()->color }};" @endif>
                                        <td>
                                            @if($req->status == 'Pending' && $req->available_count > 0 && $req->remaining_quantity > 0)
                                                <input type="checkbox" id="request-selection-{{ $req->id }}" name="request_ids[]" value="{{ $req->id }}" class="req-checkbox" data-user="{{ $req->user_id }}" style="transform: scale(1.3);">
                                                <label class="sr-only" for="request-selection-{{ $req->id }}">Select request {{ $req->id }} by {{ $req->user?->display_name ?? 'Unknown' }}</label>
                                            @endif
                                        </td>
                                        
                                        <td><a class="request-id-link" href="{{ route('unrwa.requests.show', $req->id) }}" aria-label="{{ trans('general.unrwa_request_view_details', ['id' => $req->id]) }}"><bdi dir="ltr">{{ $req->id }}</bdi></a>
                                            @if($req->parent_request_id)
                                                <small class="request-lineage"><span>{{ trans('general.unrwa_request_follow_up_badge') }}</span><bdi dir="ltr">{{ trans('general.unrwa_request_follow_up_chain', ['child' => $req->id, 'parent' => $req->parent_request_id]) }}</bdi></small>
                                            @endif
                                        </td>
                                        
                                        <td>
                                            @if($req->request_tier == 'Manager/Admin')
                                                <span class="request-priority-icon priority-manager" role="img" tabindex="0" data-toggle="tooltip" title="{{ trans('general.unrwa_priority_manager') }}" aria-label="{{ trans('general.unrwa_priority_manager') }}"><i class="fas fa-star" aria-hidden="true"></i></span>
                                            @else
                                                <span class="request-priority-icon priority-user" role="img" tabindex="0" data-toggle="tooltip" title="{{ trans('general.unrwa_priority_user') }}" aria-label="{{ trans('general.unrwa_priority_user') }}"><i class="fas fa-user" aria-hidden="true"></i></span>
                                            @endif
                                        </td>
                                        
                                        <td>{{ $req->user ? $req->user->display_name : 'Unknown' }}</td>
                                        
                                        <td style="font-weight: bold;">{{ $req->category ? $req->category->name : 'N/A' }}</td>
                                        
                                        <td>
                                            @if($req->assetModel) <span class="text-info">{{ $req->assetModel->name }}</span>
                                            @else <span class="text-muted">Any Model</span> @endif
                                        </td>
                                        
                                        <td style="font-weight: bold; font-size: 14px;">{{ $req->quantity }}</td>
                                        
                                        <td style="font-weight: bold; font-size: 14px;">
                                            @if($req->status == 'Pending')
                                                @if($req->available_count >= $req->quantity)
                                                    <span class="text-success"><i class="fas fa-check-circle"></i> {{ $req->available_count }}</span>
                                                @else
                                                    <span class="text-danger"><i class="fas fa-times-circle"></i> {{ $req->available_count }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        
                                        <td>
                                            @if($req->status == 'Pending') 
                                                @if($req->fulfilled_quantity > 0)<span class="label label-info">In progress {{ $req->fulfilled_quantity }}/{{ $req->quantity }}</span>@else<span class="label label-warning">Pending</span>@endif
                                            @elseif($req->status == 'Fulfilled') 
                                                <span class="label label-success">Fulfilled</span>
                                            @else 
                                                <span class="label label-default">{{ $req->status }}</span> 
                                            @endif
                                        </td>

                                        <td class="batch-groups-cell">
                                            @forelse($req->batch_groups as $batchGroup)
                                                <div class="request-batch-group">
                                                    <span class="request-batch-pill" role="group" style="--batch-color: {{ $batchGroup->color }};" data-toggle="tooltip" title="{{ trans('general.unrwa_batch_tooltip', ['requests' => $batchGroup->request_count, 'assets' => $batchGroup->asset_count]) }}" aria-label="{{ trans('general.unrwa_batch_tooltip', ['requests' => $batchGroup->request_count, 'assets' => $batchGroup->asset_count]) }}">
                                                        <i class="fas fa-link" aria-hidden="true"></i>
                                                        <bdi dir="ltr">{{ trans('general.unrwa_batch_number', ['id' => $batchGroup->display_id]) }}</bdi>
                                                    </span>
                                                    <small>{{ trans('general.unrwa_batch_summary', ['requests' => $batchGroup->request_count, 'assets' => $batchGroup->asset_count]) }}</small>
                                                </div>
                                            @empty
                                                <span class="text-muted">&mdash;</span>
                                            @endforelse
                                        </td>
                                        
                                        <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                        
                                        <td>
                                            <button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" title="{{ $req->notes ?: 'No notes provided' }}" aria-label="Request notes: {{ $req->notes ?: 'No notes provided' }}">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        </td>
                                        
                                        <td class="text-right">
                                            @if($req->status == 'Pending')
                                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $req->id }}" title="Edit Request" aria-label="Edit request {{ $req->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-danger cancel-btn" data-url="{{ route('unrwa.requests.cancel', $req->id) }}" title="Reject Request" aria-label="Reject request {{ $req->id }}">
                                                    <i class="fas fa-times"></i>
                                                </button>

                                                @if($req->available_count > 0 && $req->remaining_quantity > 0)
                                                    <a href="{{ route('unrwa.requests.fulfill', $req->id) }}" class="btn btn-sm bg-maroon" data-toggle="tooltip" title="{{ trans('general.unrwa_fulfill_request', ['id' => $req->id]) }}" aria-label="{{ trans('general.unrwa_fulfill_request', ['id' => $req->id]) }}">
                                                        <i class="fas fa-check" aria-hidden="true"></i>
                                                    </a>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-default" disabled title="Not enough stock"><i class="fas fa-ban text-danger"></i></button>
                                                @endif

                                            @elseif($req->status == 'Fulfilled' && $req->log_id)
                                                <!-- ====== UNRWA Customization: Print Load Note Button ====== -->
                                                <a href="{{ route('hardware.loadnote', $req->log_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-default" data-toggle="tooltip" title="{{ trans('general.unrwa_print_load_note') }}" aria-label="{{ trans('general.unrwa_print_request_load_note', ['id' => $req->id]) }}">
                                                    <i class="fas fa-print" aria-hidden="true"></i>
                                                </a>
                                                <!-- ========================================================= -->
                                            @else
                                                <button type="button" class="btn btn-sm btn-default" disabled>Processed</button>
                                            @endif
                                        </td>
                                    </tr>

                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-muted">There are no equipment requests to display.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mobile-request-cards" aria-label="Equipment requests">
                        @if($requests->isEmpty())
                            <div class="alert alert-info">There are no equipment requests to display.</div>
                        @endif
                        @foreach($requests as $req)
                            <article class="request-card {{ $req->batch_groups->isNotEmpty() ? 'has-delivery-group' : '' }}" data-status="{{ strtolower($req->status) }}" data-grouped="{{ $req->batch_groups->isNotEmpty() ? 'yes' : 'no' }}" data-search="{{ strtolower($req->id.' '.($req->user?->display_name ?? '').' '.($req->category?->name ?? '').' '.($req->assetModel?->name ?? 'Any Model').' '.($req->notes ?? '').' '.$req->batch_search_term) }}" @if($req->batch_groups->isNotEmpty()) style="--request-group-color: {{ $req->batch_groups->first()->color }};" @endif>
                                <div class="request-card-head">
                                    <strong><a class="request-id-link" href="{{ route('unrwa.requests.show', $req->id) }}" aria-label="{{ trans('general.unrwa_request_view_details', ['id' => $req->id]) }}">Request #{{ $req->id }}</a> @if($req->parent_request_id)<small class="request-lineage"><span>{{ trans('general.unrwa_request_follow_up_badge') }}</span><bdi dir="ltr">{{ trans('general.unrwa_request_follow_up_chain', ['child' => $req->id, 'parent' => $req->parent_request_id]) }}</bdi></small>@endif</strong>
                                    <span class="request-card-priority-status">
                                        @if($req->request_tier == 'Manager/Admin')
                                            <span class="request-priority-icon priority-manager" role="img" tabindex="0" data-toggle="tooltip" title="{{ trans('general.unrwa_priority_manager') }}" aria-label="{{ trans('general.unrwa_priority_manager') }}"><i class="fas fa-star" aria-hidden="true"></i></span>
                                        @else
                                            <span class="request-priority-icon priority-user" role="img" tabindex="0" data-toggle="tooltip" title="{{ trans('general.unrwa_priority_user') }}" aria-label="{{ trans('general.unrwa_priority_user') }}"><i class="fas fa-user" aria-hidden="true"></i></span>
                                        @endif
                                        <span class="request-status-text">@if($req->status === 'Pending'){{ $req->fulfilled_quantity ? 'In progress' : 'Pending' }}@else{{ $req->status }}@endif</span>
                                    </span>
                                </div>
                                <p><strong>Requested by:</strong> {{ $req->user?->display_name ?? 'Unknown' }}</p>
                                <p><strong>Category:</strong> {{ $req->category?->name ?? 'N/A' }} · {{ $req->assetModel?->name ?? 'Any model' }}</p>
                                <p><strong>Quantity:</strong> {{ $req->fulfilled_quantity }}/{{ $req->quantity }} completed · {{ $req->available_count }} available</p>
                                @foreach($req->batch_groups as $batchGroup)
                                    <div class="request-batch-group mobile-batch-group">
                                        <span class="request-batch-pill" role="group" style="--batch-color: {{ $batchGroup->color }};" data-toggle="tooltip" title="{{ trans('general.unrwa_batch_tooltip', ['requests' => $batchGroup->request_count, 'assets' => $batchGroup->asset_count]) }}" aria-label="{{ trans('general.unrwa_batch_tooltip', ['requests' => $batchGroup->request_count, 'assets' => $batchGroup->asset_count]) }}">
                                            <i class="fas fa-link" aria-hidden="true"></i> <bdi dir="ltr">{{ trans('general.unrwa_batch_number', ['id' => $batchGroup->display_id]) }}</bdi>
                                        </span>
                                        <small>{{ trans('general.unrwa_batch_summary', ['requests' => $batchGroup->request_count, 'assets' => $batchGroup->asset_count]) }}</small>
                                    </div>
                                @endforeach
                                @if($req->notes)<p class="request-card-notes">{{ $req->notes }}</p>@endif
                                <div class="request-card-actions">
                                    @if($req->status === 'Pending')
                                        @if($req->available_count > 0 && $req->remaining_quantity > 0)
                                            <button type="button" class="btn btn-default btn-sm mobile-request-select" data-request-id="{{ $req->id }}" data-user="{{ $req->user_id }}" aria-pressed="false">Select</button>
                                            <a href="{{ route('unrwa.requests.fulfill', $req->id) }}" class="btn btn-success btn-sm" title="{{ trans('general.unrwa_fulfill_request', ['id' => $req->id]) }}" aria-label="{{ trans('general.unrwa_fulfill_request', ['id' => $req->id]) }}"><i class="fas fa-check" aria-hidden="true"></i></a>
                                        @endif
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#editModal{{ $req->id }}">Edit</button>
                                        <button type="button" class="btn btn-danger btn-sm cancel-btn" data-url="{{ route('unrwa.requests.cancel', $req->id) }}">Reject</button>
                                    @elseif($req->status === 'Fulfilled' && $req->log_id)
                                        <a href="{{ route('hardware.loadnote', $req->log_id) }}" target="_blank" rel="noopener" class="btn btn-default btn-sm" data-toggle="tooltip" title="{{ trans('general.unrwa_print_load_note') }}" aria-label="{{ trans('general.unrwa_print_request_load_note', ['id' => $req->id]) }}"><i class="fas fa-print" aria-hidden="true"></i></a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <p id="request-no-results" class="alert alert-info" role="status" style="display:none">No requests match these filters.</p>
                </form> 

                @foreach($requests as $req)
                    @if($req->status == 'Pending')
                        <div class="modal fade" id="editModal{{ $req->id }}" tabindex="-1" role="dialog" aria-labelledby="editModalTitle{{ $req->id }}">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="editModalTitle{{ $req->id }}">Edit Request #{{ $req->id }}</h4>
                                    </div>
                                    <div class="modal-body text-left">
                                        <div class="form-group">
                                            <label for="edit_cat_{{ $req->id }}">Category</label>
                                            <select class="form-control edit-category" id="edit_cat_{{ $req->id }}" required>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ $req->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_model_{{ $req->id }}">Model (Optional)</label>
                                            <select class="form-control edit-model" id="edit_model_{{ $req->id }}">
                                                <option value="">-- Any Model --</option>
                                                @foreach($models as $model)
                                                    <option value="{{ $model->id }}" {{ $req->model_id == $model->id ? 'selected' : '' }}>{{ $model->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_qty_{{ $req->id }}">Quantity</label>
                                            <input type="number" class="form-control edit-qty" id="edit_qty_{{ $req->id }}" value="{{ $req->quantity }}" min="1" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_notes_{{ $req->id }}">Notes</label>
                                            <textarea class="form-control edit-notes" id="edit_notes_{{ $req->id }}" rows="3">{{ $req->notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary save-edit-btn" data-id="{{ $req->id }}" data-url="{{ route('unrwa.requests.update', $req->id) }}">Save Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
                
                
                <form id="actionForm" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" name="category_id" id="action_cat">
                    <input type="hidden" name="model_id" id="action_model">
                    <input type="hidden" name="quantity" id="action_qty">
                    <input type="hidden" name="notes" id="action_notes">
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
        function filterRequests() {
            var query = ($('#request-search').val() || '').toLowerCase().trim();
            var status = $('#request-status-filter').val();
            var grouped = $('#request-group-filter').val();
            $('.request-record, .request-card').each(function() {
                var isGrouped = $(this).data('grouped') === 'yes';
                var groupMatches = !grouped || (grouped === 'grouped' && isGrouped) || (grouped === 'individual' && !isGrouped);
                var matches = (!query || String($(this).data('search')).indexOf(query) !== -1) && (!status || $(this).data('status') === status) && groupMatches;
                $(this).toggle(matches);
            });
            $('#request-no-results').toggle($('.request-record:visible, .request-card:visible').length === 0);
        }
        $('#request-search, #request-status-filter, #request-group-filter').on('input change', filterRequests);
        $('.mobile-request-select').on('click', function() {
            var $button = $(this);
            var $checkbox = $('#request-selection-' + $button.data('request-id'));
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            $button.attr('aria-pressed', $checkbox.prop('checked') ? 'true' : 'false').text($checkbox.prop('checked') ? 'Selected' : 'Select');
        });
        $(document).on('change', '.req-checkbox', function() {
            $('#bulk-selection-message').text('');
        });

        $(document).on('click', '#btnBulkFulfill', function(e) {
            e.preventDefault();
            var selected = $('.req-checkbox:checked');
            
            if(selected.length === 0) {
                $('#bulk-selection-message').text('Please select at least one pending request.');
                return;
            }
            
            var userId = null;
            var isValid = true;
            
            selected.each(function() {
                var currentUserId = $(this).data('user');
                if(userId === null) {
                    userId = currentUserId;
                } else if(userId !== currentUserId) {
                    $('#bulk-selection-message').text('Select requests for one person at a time to create a unified load note.');
                    isValid = false;
                    return false;
                }
            });

            if(isValid) {
                $(this).html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Processing...').prop('disabled', true).attr('aria-busy', 'true');
                $('#bulkFulfillForm').submit();
            }
        });

        $(document).on('click', '.cancel-btn', function() {
            if(confirm(@json(trans('general.unrwa_request_cancel_confirm')))) {
                var url = $(this).data('url');
                var $form = $('#actionForm');
                $form.attr('action', url);
                $form.submit();
            }
        });

        $(document).on('click', '.save-edit-btn', function() {
            var reqId = $(this).data('id');
            var url = $(this).data('url');
            
            $('#action_cat').val($('#edit_cat_' + reqId).val());
            $('#action_model').val($('#edit_model_' + reqId).val());
            $('#action_qty').val($('#edit_qty_' + reqId).val());
            $('#action_notes').val($('#edit_notes_' + reqId).val());

            var $form = $('#actionForm');
            $form.attr('action', url);
            $form.submit();
        });
    });
</script>
@endsection
