@extends('layouts/default')

@section('title', 'Document Center')

@section('content')
<x-container>
    <x-box>
        <div class="document-summary" aria-label="{{ trans('general.load_note_delivery_report') }}">
            <div class="document-summary-item">
                <i class="fas fa-file-invoice text-blue" aria-hidden="true"></i>
                <span>{{ trans('general.load_note_report_notes') }}</span>
                <strong>{{ number_format($reportStats['notes']) }}</strong>
            </div>
            <div class="document-summary-item">
                <i class="fas fa-boxes-stacked text-teal" aria-hidden="true"></i>
                <span>{{ trans('general.load_note_report_items') }}</span>
                <strong>{{ number_format($reportStats['delivery_items']) }}</strong>
            </div>
            <div class="document-summary-item">
                <i class="fas fa-users text-purple" aria-hidden="true"></i>
                <span>{{ trans('general.load_note_report_recipients') }}</span>
                <strong>{{ number_format($reportStats['recipients']) }}</strong>
            </div>
        </div>

        <form method="GET" action="{{ route('document.center') }}" class="document-filter-bar">
            <div class="form-group">
                <label for="filterType">{{ trans('general.load_note_document_type') }}</label>
                <select id="filterType" name="document_type" class="form-control">
                    <option value="">{{ trans('general.load_note_all_documents') }}</option>
                    <option value="load_note" @selected(($filters['document_type'] ?? '') === 'load_note')>{{ trans('general.load_note_type_load_note') }}</option>
                    <option value="return_receipt" @selected(($filters['document_type'] ?? '') === 'return_receipt')>{{ trans('general.load_note_return_receipts') }}</option>
                </select>
            </div>
            <div class="form-group document-filter-recipient">
                <label for="filterRecipient">{{ trans('general.load_note_recipient') }}</label>
                <select id="filterRecipient" name="target_user_id" class="form-control">
                    <option value="">{{ trans('general.load_note_all_recipients') }}</option>
                    @foreach($users as $recipient)
                        <option value="{{ $recipient->id }}" @selected((string) ($filters['target_user_id'] ?? '') === (string) $recipient->id)>
                            {{ $recipient->display_name ?: $recipient->username }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group document-filter-date">
                <label for="filterFrom">{{ trans('general.load_note_from') }}</label>
                <input id="filterFrom" type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="form-group document-filter-date">
                <label for="filterTo">{{ trans('general.load_note_to') }}</label>
                <input id="filterTo" type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="document-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter" aria-hidden="true"></i> {{ trans('general.load_note_filter') }}</button>
                @if(collect($filters)->filter()->isNotEmpty())
                    <a href="{{ route('document.center') }}" class="document-clear-link">{{ trans('general.load_note_clear') }}</a>
                @endif
            </div>
        </form>

        <form id="combinedLoadNoteForm" method="POST" action="{{ route('document.center.load-note.combined') }}">
            @csrf
            <input type="hidden" name="target_user_id" value="{{ $filters['target_user_id'] ?? '' }}">
            <input type="hidden" name="from" value="{{ $filters['from'] ?? '' }}">
            <input type="hidden" name="to" value="{{ $filters['to'] ?? '' }}">
            <div class="document-action-bar">
                <div class="document-selection-status" id="documentSelectionStatus" aria-live="polite">
                    @if(empty($filters['target_user_id']))
                        {{ trans('general.load_note_recipient_filter_help') }}
                    @else
                        {{ trans('general.load_note_selected_count', ['count' => 0]) }}
                    @endif
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" @disabled(empty($filters['target_user_id']))>
                        <i class="fas fa-file-invoice" aria-hidden="true"></i> {{ trans('general.load_note_combined_actions') }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <button type="submit" form="combinedLoadNoteForm" name="selection_mode" value="selected" class="document-dropdown-action">
                                <i class="fas fa-check-square" aria-hidden="true"></i> {{ trans('general.load_note_print_selected') }}
                            </button>
                        </li>
                        <li>
                            <button type="submit" form="combinedLoadNoteForm" name="selection_mode" value="all" class="document-dropdown-action">
                                <i class="fas fa-layer-group" aria-hidden="true"></i> {{ trans('general.load_note_generate_all') }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            @error('log_ids')<div class="alert alert-danger">{{ $message }}</div>@enderror
            @error('target_user_id')<div class="alert alert-warning">{{ $message }}</div>@enderror
        </form>

        @if($groupedLogs->isEmpty())
            <div class="callout callout-info" role="status">
                <h4><i class="fas fa-folder-open" aria-hidden="true"></i> {{ trans('general.load_note_empty_title') }}</h4>
                <p>{{ trans('general.load_note_empty_help') }}</p>
                @if(collect($filters)->filter()->isNotEmpty())
                    <a href="{{ route('document.center') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-filter-circle-xmark" aria-hidden="true"></i> {{ trans('general.load_note_clear') }}
                    </a>
                @endif
            </div>
        @else
        <div class="table-responsive">
            <table id="documentCenterTable" 
                   class="table table-striped snipe-table"
                   data-toggle="table"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-sortable="true"
            >
                <thead>
                    <tr>
                        <th data-field="select" data-sortable="false" class="text-center" style="width:42px;">{{ trans('general.load_note_select') }}</th>
                        <th data-sortable="true" data-field="ref_no">{{ trans('general.load_note_reference') }}</th>
                        <th data-sortable="true" data-field="date">{{ trans('general.load_note_date') }}</th>
                        <th data-sortable="true" data-field="doc_type" data-formatter="typeBadgeFormatter">{{ trans('general.load_note_type') }}</th>
                        <th data-sortable="true" data-field="target">{{ trans('general.load_note_recipient') }}</th>
                        <th data-field="item_count" data-sortable="true" class="text-center">{{ trans('general.load_note_quantity') }}</th>
                        <th data-field="items">{{ trans('general.load_note_materials_identifiers') }}</th>
                        <th data-sortable="true" data-field="issuer">{{ trans('general.load_note_issuer') }}</th>
                        <th data-field="action" class="text-center" style="min-width: 120px;">{{ trans('general.load_note_action') }}</th>
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

                            $reference = $isCheckout ? 'ISD/' . date('y', strtotime($first->created_at)) . '/' . $first->id : 'RTN-' . $first->id;
                        @endphp
                        <tr>
                            <td class="text-center" style="vertical-align: middle;">
                                @if($isCheckout && $first->target_id)
                                    <input class="load-note-select" type="checkbox" form="combinedLoadNoteForm" name="log_ids[]" value="{{ $first->id }}" data-target-user="{{ $first->target_id }}" aria-label="Select {{ $reference }}" @disabled(empty($filters['target_user_id']))>
                                @endif
                            </td>
                            <td style="vertical-align: middle; font-weight:bold; font-size: 13px;">
                                <bdi dir="ltr">{{ $reference }}</bdi>
                            </td>
                            <td style="vertical-align: middle;">{{ $first->created_at }}</td>
                            
                            <td style="vertical-align: middle;">{{ $typeLabel }}</td>
                            
                            <td style="vertical-align: middle;">
                                @if($first->target)
                                    <i class="fas fa-user text-muted"></i> {!! $first->target->present()->nameUrl() !!}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center" style="vertical-align: middle;">
                                <span class="label label-default"><bdi dir="ltr">{{ $logs->count() }}</bdi></span>
                            </td>
                            <td>
                                <ul style="padding-left: 15px; margin-bottom: 0; line-height: 1.6;">
                                @foreach($logs as $log)
                                    @if($log->item)
                                        <li>
                                            {!! $log->item->present()->nameUrl() !!}
                                            <span class="text-muted" style="font-size:11px;">
                                                {{ class_basename($log->item_type) }}
                                                @if($log->item->asset_tag) · <bdi dir="ltr">{{ $log->item->asset_tag }}</bdi> @endif
                                                @if($log->item->serial) · <bdi dir="ltr">{{ $log->item->serial }}</bdi> @endif
                                                @if($log->item->model?->name) · {{ $log->item->model->name }} @endif
                                            </span>
                                        </li>
                                    @endif
                                @endforeach
                                </ul>
                            </td>
                            <td style="vertical-align: middle;">
                                @if($first->adminuser)
                                    {!! $first->adminuser->present()->nameUrl() !!}
                                @endif
                            </td>
                            <td style="vertical-align: middle; text-align:center;">
                                <div class="btn-group">
                                    <!-- Print Button -->
                                    @if($isCheckout)
                                        <a href="{{ url('/hardware/load-note/' . $first->id) }}" class="btn btn-sm btn-info" target="_blank" rel="noopener" data-tooltip="true" title="Print Original Note" aria-label="Print Original Note">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @else
                                        <a href="{{ url('/hardware/checkin-receipt/' . $first->id) }}" class="btn btn-sm btn-primary" target="_blank" rel="noopener" data-tooltip="true" title="Print Original Receipt" aria-label="Print Original Receipt">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @endif

                                    <!-- SharePoint Signed Note Buttons -->
                                    @if($first->signed_note_path)
                                        <a href="{{ route('document.center.download-signed', $first->id) }}" class="btn btn-sm btn-success" data-tooltip="true" title="View Signed Note (SharePoint)" aria-label="View Signed Note (SharePoint)" target="_blank" rel="noopener">
                                            <i class="fab fa-microsoft"></i>
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-default" data-toggle="modal" data-target="#uploadSignedModal" data-logid="{{ $first->id }}" title="Upload Signed Note" aria-label="Upload Signed Note">
                                            <i class="fas fa-cloud-upload-alt text-muted"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </x-box>
</x-container>

<!-- ====== UNRWA: Upload Signed Note Modal ====== -->
<div class="modal fade" id="uploadSignedModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('document.center.upload-signed') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="log_id" id="modal_log_id" value="">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-signature text-primary"></i> Upload Signed Document</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This file will be uploaded directly to your Microsoft SharePoint library.
                    </div>
                    <div class="form-group">
                        <label>Select File (PDF, JPG, PNG)</label>
                        <input type="file" name="signed_file" accept=".pdf,.jpg,.jpeg,.png" required class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-cloud-upload-alt"></i> Upload to SharePoint</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ============================================== -->

@stop

@push('css')
<style>
.document-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    margin: 0 0 16px;
    padding: 10px 14px;
    background: #f7f9fc;
    border: 1px solid #e6ebf1;
    border-radius: 4px;
}

.document-summary-item {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 190px;
    padding: 3px 18px;
    color: #5e6874;
    border-right: 1px solid #e1e6ec;
}

.document-summary-item:first-child { padding-left: 4px; }
.document-summary-item:last-child { border-right: 0; }
.document-summary-item strong { margin-left: auto; color: #263445; font-size: 16px; }

.document-filter-bar {
    display: grid;
    grid-template-columns: minmax(170px, 1.1fr) minmax(210px, 1.35fr) minmax(140px, .8fr) minmax(140px, .8fr) auto;
    gap: 12px;
    align-items: end;
    margin-bottom: 14px;
    padding: 14px;
    background: #fbfcfd;
    border: 1px solid #e6ebf1;
    border-radius: 4px;
}

.document-filter-bar .form-group { min-width: 0; margin: 0; }
.document-filter-bar label { margin-bottom: 5px; color: #596575; font-size: 12px; }
.document-filter-actions { display: flex; align-items: center; gap: 10px; padding-bottom: 0; white-space: nowrap; }
.document-clear-link { font-size: 12px; }

.document-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
    padding: 9px 12px;
    background: #f5f8fb;
    border-left: 3px solid #3c8dbc;
    border-radius: 3px;
}

.document-selection-status { color: #596575; font-size: 12px; }
.document-dropdown-action { display: block; width: 100%; padding: 8px 14px; border: 0; background: transparent; color: #333; text-align: start; }
.document-dropdown-action:hover, .document-dropdown-action:focus { background: #f4f6f8; color: #222; text-decoration: none; }
.document-dropdown-action .fas { width: 20px; color: #68788a; }

@media (max-width: 991px) {
    .document-filter-bar { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .document-filter-recipient { grid-column: span 2; }
}

@media (max-width: 600px) {
    .document-summary { padding: 8px 10px; }
    .document-summary-item { flex: 1 1 100%; min-width: 0; padding: 7px 2px; border-right: 0; border-bottom: 1px solid #e1e6ec; }
    .document-summary-item:last-child { border-bottom: 0; }
    .document-filter-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; padding: 11px; }
    .document-filter-recipient { grid-column: span 2; }
    .document-filter-actions { grid-column: span 2; }
    .document-action-bar { align-items: flex-start; flex-direction: column; }
    .document-action-bar .btn-group { width: 100%; }
    .document-action-bar .dropdown-toggle { width: 100%; text-align: left; }
}
</style>
@endpush

@section('moar_scripts')
    @include ('partials.bootstrap-table', ['simple_view' => true])

    <script nonce="{{ csrf_token() }}">
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
            var $combinedForm = $('#combinedLoadNoteForm');
            var $selectionStatus = $('#documentSelectionStatus');
            var selectedLogIds = new Set();
            var selectedCountTemplate = @json(trans('general.load_note_selected_count', ['count' => '__COUNT__']));

            var updateSelectionStatus = function () {
                if ($combinedForm.find('[name="target_user_id"]').val()) {
                    $selectionStatus.text(selectedCountTemplate.replace('__COUNT__', selectedLogIds.size));
                }
            };

            var restoreSelectedRows = function () {
                $table.find('.load-note-select').each(function () {
                    this.checked = selectedLogIds.has(String(this.value));
                });
            };

            $table.on('change', '.load-note-select', function () {
                if (this.checked) {
                    selectedLogIds.add(String(this.value));
                } else {
                    selectedLogIds.delete(String(this.value));
                }

                updateSelectionStatus();
            });

            $table.on('post-body.bs.table page-change.bs.table', restoreSelectedRows);

            $combinedForm.on('submit', function (event) {
                var selectionMode = $(document.activeElement).val() || 'selected';
                var recipientId = $combinedForm.find('[name="target_user_id"]').val();

                if (!recipientId) {
                    event.preventDefault();
                    window.alert(@json(trans('general.load_note_select_recipient')));
                    return;
                }

                $table.find('.load-note-select').prop('checked', false);
                $combinedForm.find('input[data-selection-cache="true"]').remove();

                if (selectionMode === 'selected') {
                    if (selectedLogIds.size === 0) {
                        event.preventDefault();
                        window.alert(@json(trans('general.load_note_select_records')));
                        return;
                    }

                    selectedLogIds.forEach(function (logId) {
                        $('<input>', {
                            type: 'hidden',
                            name: 'log_ids[]',
                            value: logId,
                            'data-selection-cache': 'true'
                        }).appendTo($combinedForm);
                    });
                }
            });

            // Pass the Log ID to the Modal
            $('#uploadSignedModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var logId = button.data('logid');
                var modal = $(this);
                modal.find('#modal_log_id').val(logId);
            });
        });
    </script>
@stop
