@extends('layouts/default')
@section('title', 'Bulk Fulfill Requests for ' . $targetUser->display_name)

@section('content')
<div class="row">
    <div class="col-md-12">
        <form action="{{ route('unrwa.requests.fulfill-assets') }}" method="POST" id="fulfillForm">
            @csrf
            <input type="hidden" name="checkout_to_type" value="user">
            <input type="hidden" name="assigned_user" value="{{ $targetUser->id }}">
            <input type="hidden" name="redirect_option" value="unrwa_requests">
            <div id="selected-assets-container"></div>
            
            @foreach($requests as $req)
                <input type="hidden" name="unrwa_request_ids[]" value="{{ $req->id }}">
            @endforeach

            <div class="box box-solid box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fas fa-truck-loading"></i> Fulfilling Requests for: {{ $targetUser->display_name }}</h3>
                </div>
            </div>

            @foreach($requests as $req)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title text-info">
                        <strong>{{ $req->category->name }}</strong> 
                        @if($req->assetModel) ({{ $req->assetModel->name }}) @endif
                    </h3>
                    <div class="box-tools pull-right">
                        Remaining: <span class="badge bg-red" id="maxQty_{{ $req->id }}">{{ $req->remaining_quantity }}</span> |
                        Selected: <strong id="selCount_{{ $req->id }}" class="text-success" aria-live="polite">0</strong>
                    </div>
                </div>
                
                <div class="box-body">
                    <div class="table-responsive" role="region" aria-label="Available assets for {{ $req->category->name }}" tabindex="0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;"></th>
                                <th>Asset Tag</th>
                                <th>Model</th>
                                <th>Serial</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($req->availableAssets as $asset)
                                <tr>
                                    <td>
                                        <input type="checkbox" id="bulk-asset-{{ $asset->id }}" name="request_assets[{{ $req->id }}][]" value="{{ $asset->id }}" class="asset-checkbox req-group-{{ $req->id }}" data-req="{{ $req->id }}" style="transform: scale(1.4);">
                                        <label class="sr-only" for="bulk-asset-{{ $asset->id }}">Select asset {{ $asset->asset_tag }}</label>
                                    </td>
                                    <td style="font-weight:bold;">{{ $asset->asset_tag }}</td>
                                    <td>{{ $asset->model->name }}</td>
                                    <td>{{ $asset->serial ?: '-' }}</td>
                                    <td>{{ $asset->location ? $asset->location->name : 'Default' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No available assets match this request.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <p id="selection-limit-{{ $req->id }}" class="text-danger" role="status" aria-live="polite"></p>
                    <div class="alert alert-info remainder-decision" id="remainder-decision-{{ $req->id }}" style="display:none;" aria-live="polite">
                        <strong>{{ trans('general.unrwa_remainder_title') }}</strong>
                        <p>{{ trans('general.unrwa_remainder_question', ['count' => '__COUNT__']) }}</p>
                        <label for="remainder-action-{{ $req->id }}">{{ trans('general.unrwa_remainder_help') }}</label>
                        <select class="form-control remainder-action" id="remainder-action-{{ $req->id }}" name="remainder_action[{{ $req->id }}]">
                            <option value="">{{ trans('general.unrwa_remainder_choose') }}</option>
                            <option value="separate">{{ trans('general.unrwa_remainder_separate') }}</option>
                            <option value="cancel">{{ trans('general.unrwa_remainder_cancel') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="box box-solid">
                <div class="box-body text-right">
                    <button type="button" class="btn bg-maroon btn-lg" id="openModalBtn" disabled>
                        <i class="fas fa-file-invoice"></i> Proceed to Dispatch Details
                    </button>
                </div>
            </div>

            <div class="modal fade" id="dispatchModal" tabindex="-1" role="dialog">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header bg-maroon" style="color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-file-invoice"></i> Dispatch Details (Unified Load Note)</h4>
                  </div>
                  <div class="modal-body">
                      <div class="form-group">
                          <label class="form-control" for="bulk_send_pdf_email" style="cursor: pointer; background-color: #f9f9f9; border-left: 3px solid #0055A4;">
                              <input type="checkbox" id="bulk_send_pdf_email" name="send_pdf_email" value="1" checked>
                              <strong>Send unified load note by email</strong>
                          </label>
                      </div>
                      <div class="form-group">
                          <label for="destination_location">Destination location <span class="text-danger">*</span></label>
                      <select class="form-control js-data-ajax" data-endpoint="locations" data-company-id="{{ $targetUser->company_id }}" data-placeholder="Select a destination" name="destination_location" id="destination_location" required>
                              @if($targetUser->location)
                                  <option value="{{ $targetUser->location_id }}" selected>{{ $targetUser->location->name }}</option>
                              @endif
                          </select>
                          <p class="help-block">Choose the physical location where the assets will be placed.</p>
                      </div>
                      <div class="form-group">
                          <label for="bulk_driver_name">Driver's Name</label>
                          <input type="text" class="form-control" id="bulk_driver_name" name="driver_name" placeholder="Optional">
                      </div>
                      <div class="form-group">
                          <label for="bulk_vehicle_number">Wagon / Track No.</label>
                          <input type="text" class="form-control" id="bulk_vehicle_number" name="vehicle_number" placeholder="Optional">
                      </div>
                      <div class="form-group">
                          <label for="bulk_contract_po_number">SAP PO Number</label>
                          <input type="text" class="form-control" id="bulk_contract_po_number" name="contract_po_number" placeholder="Optional">
                      </div>
                      <div class="form-group">
                          <label for="bulk_note">Unified Notes</label>
                          <textarea class="form-control" id="bulk_note" name="note" rows="2" placeholder="Fulfilled multiple requests..."></textarea>
                      </div>
                      <hr>
                      <div class="form-group">
                          <label for="bulk_cc_email">CC Email (Manager/Security)</label>
                          <div class="input-group">
                              <span class="input-group-addon"><i class="fas fa-envelope"></i></span>
                              <input class="form-control" type="email" id="bulk_cc_email" name="cc_email" placeholder="security@unrwa.org">
                          </div>
                      </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="confirmBulkFulfillBtn" class="btn btn-success"><i class="fas fa-check-double" aria-hidden="true"></i> Confirm & Fulfill</button>
                  </div>
                </div>
              </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        var remainderQuestion = @json(trans('general.unrwa_remainder_question', ['count' => '__COUNT__']));

        $('.asset-checkbox').change(function() {
            var reqId = $(this).data('req');
            var max = parseInt($('#maxQty_' + reqId).text());
            var checkedCount = $('.req-group-' + reqId + ':checked').length;
            $('#selection-limit-' + reqId).text('');
            
            if (checkedCount > max) {
                $(this).prop('checked', false);
                $('#selection-limit-' + reqId).text('You cannot select more than the requested quantity for this item category.');
                checkedCount = $('.req-group-' + reqId + ':checked').length;
            }
            
            $('#selCount_' + reqId).text(checkedCount);
            var remainingAfterDispatch = Math.max(0, max - checkedCount);
            var $decision = $('#remainder-decision-' + reqId);
            var $action = $('#remainder-action-' + reqId);
            if (checkedCount > 0 && remainingAfterDispatch > 0) {
                $decision.show().find('p').text(remainderQuestion.replace('__COUNT__', remainingAfterDispatch));
                $action.prop('required', true);
            } else {
                $decision.hide();
                $action.prop('required', false).val('');
            }

            var totalChecked = $('.asset-checkbox:checked').length;
            $('#openModalBtn').prop('disabled', totalChecked === 0);
        });

        $('#openModalBtn').click(function() {
            $('#dispatchModal').modal('show');
        });

        $('#fulfillForm').on('submit', function(event) {
            var selected = $('.asset-checkbox:checked');
            if (!selected.length || !$('#destination_location').val()) {
                event.preventDefault();
                if (!selected.length) {
                    $('[role="status"]').first().text('Select at least one asset to continue.');
                } else {
                    $('#dispatchModal').modal('show');
                    $('#destination_location').focus();
                }
                return;
            }
            var missingRemainderChoice = false;
            @foreach($requests as $req)
                var max{{ $req->id }} = parseInt($('#maxQty_{{ $req->id }}').text());
                var selected{{ $req->id }} = $('.req-group-{{ $req->id }}:checked').length;
                if (selected{{ $req->id }} > 0 && selected{{ $req->id }} < max{{ $req->id }} && !$('#remainder-action-{{ $req->id }}').val()) {
                    missingRemainderChoice = true;
                    $('#remainder-action-{{ $req->id }}').focus();
                }
            @endforeach
            if (missingRemainderChoice) {
                event.preventDefault();
                return;
            }
            $('#selected-assets-container').empty();
            selected.each(function() {
                $('<input>', { type: 'hidden', name: 'selected_assets[]', value: this.value }).appendTo('#selected-assets-container');
            });
            $('#confirmBulkFulfillBtn')
                .html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Processing...')
                .prop('disabled', true)
                .attr('aria-busy', 'true');
        });
    });
</script>
@endsection
