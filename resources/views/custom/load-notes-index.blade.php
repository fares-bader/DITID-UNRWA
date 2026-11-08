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
                        <th data-sortable="true" data-field="doc_type" data-formatter="typeBadgeFormatter">Type</th>
                        <th data-sortable="true" data-field="target">Target</th>
                        <th data-field="items">Items</th>
                        <th data-sortable="true" data-field="issuer">Issuer</th>
                        <th data-field="action" class="text-center" style="min-width: 120px;">Action</th>
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
                                <div class="btn-group">
                                    <!-- Print Button -->
                                    @if($isCheckout)
                                        <a href="{{ url('/hardware/load-note/' . $first->id) }}" class="btn btn-sm btn-info" target="_blank" data-tooltip="true" title="Print Original Note">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @else
                                        <a href="{{ url('/hardware/checkin-receipt/' . $first->id) }}" class="btn btn-sm btn-primary" target="_blank" data-tooltip="true" title="Print Original Receipt">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @endif

                                    <!-- SharePoint Signed Note Buttons -->
                                    @if($first->signed_note_path)
                                        <a href="{{ route('document.center.download-signed', $first->id) }}" class="btn btn-sm btn-success" data-tooltip="true" title="View Signed Note (SharePoint)" target="_blank">
                                            <i class="fab fa-microsoft"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-default" data-toggle="modal" data-target="#uploadSignedModal" data-logid="{{ $first->id }}" title="Upload Signed Note">
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
            
            $('#filterType').on('change', function () {
                var filterValue = $(this).val();
                if (filterValue === "") {
                    $table.bootstrapTable('filterBy', {}); 
                } else {
                    $table.bootstrapTable('filterBy', {
                        doc_type: filterValue 
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