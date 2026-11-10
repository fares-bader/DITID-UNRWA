@extends('layouts/default')
@section('title', 'Fulfill Request #' . $request->id)

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-solid box-primary">
            <div class="box-header with-border"><h3 class="box-title">Request Details</h3></div>
            <div class="box-body" style="font-size: 15px;">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Requested By:</strong> <span class="text-primary">{{ $request->user->display_name }}</span></p>
                        <p><strong>Category:</strong> <span class="label label-info">{{ $request->category->name }}</span></p>
                        @if($request->assetModel) <p><strong>Specific Model:</strong> {{ $request->assetModel->name }}</p> @endif
                    </div>
                    <div class="col-md-6 text-right">
                        <p><strong>Requested Quantity:</strong> <span class="badge" style="font-size: 18px; background-color: #d9534f;">{{ $request->quantity }}</span></p>
                        @if($request->fulfilled_assets_count > 0)<p><strong>Already fulfilled:</strong> {{ $request->fulfilled_assets_count }} · <strong>Remaining:</strong> <span id="maxQty">{{ $request->remaining_quantity }}</span></p>@else<span id="maxQty" class="sr-only">{{ $request->remaining_quantity }}</span>@endif
                        <p><strong>Notes:</strong> {{ $request->notes ?: 'None' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Select Assets to Fulfill</h3>
                <div class="box-tools pull-right">
                    Selected: <strong id="selectedCount" class="text-success" style="font-size: 16px;" aria-live="polite">0</strong> / {{ $request->remaining_quantity }} remaining
                </div>
            </div>
            
            <div class="box-body">
                <form action="{{ route('unrwa.requests.fulfill-assets') }}" method="POST" id="fulfillForm">
                    @csrf
                    <input type="hidden" name="checkout_to_type" value="user">
                    <input type="hidden" name="assigned_user" value="{{ $request->user_id }}">
                    <input type="hidden" name="redirect_option" value="unrwa_requests">
                    <input type="hidden" name="unrwa_request_ids[]" value="{{ $request->id }}">
                    <div id="selected-assets-container"></div>
                @if($availableAssets->count() > 0)
                    <div class="table-responsive" role="region" aria-label="Available assets" tabindex="0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 30px;"></th>
                                <th>Asset Tag</th>
                                <th>Model</th>
                                <th>Serial</th>
                                <th>Status</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availableAssets as $asset)
                                <tr>
                                    <td>
                                        <input type="checkbox" id="asset-{{ $asset->id }}" name="request_assets[{{ $request->id }}][]" value="{{ $asset->id }}" data-status="{{ $asset->status_id }}" data-asset-tag="{{ $asset->asset_tag }}" class="asset-checkbox" style="transform: scale(1.5);">
                                        <label class="sr-only" for="asset-{{ $asset->id }}">Select asset {{ $asset->asset_tag }}</label>
                                    </td>
                                    <td style="font-weight:bold;">{{ $asset->asset_tag }}</td>
                                    <td>{{ $asset->model->name }}</td>
                                    <td>{{ $asset->serial ?: '-' }}</td>
                                    <td><span class="label" style="background-color: {{ $asset->status->color }};">{{ $asset->status->name }}</span></td>
                                    <td>{{ $asset->location ? $asset->location->name : 'Default' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No available items found for this category/model in the inventory.
                    </div>
                @endif
            </div>

            <div class="box-footer">
                <p id="selection-limit-message" class="text-danger" role="status" aria-live="polite"></p>
                <button type="button" class="btn bg-maroon pull-right" id="openModalBtn" disabled>
                    <i class="fas fa-truck"></i> Proceed to Dispatch (<span id="btnCount">0</span> Items)
                </button>
            </div>
        </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dispatchModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-maroon" style="color: white;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="fas fa-file-invoice"></i> Dispatch Details (Load Note)</h4>
      </div>
      <div class="modal-body">
          <div class="form-group">
              <label for="destination_location">Destination location <span class="text-danger">*</span></label>
              <select class="form-control js-data-ajax" data-endpoint="locations" data-company-id="{{ $request->user->company_id }}" data-placeholder="Select a destination" name="destination_location" id="destination_location" form="fulfillForm" required>
                  @if($request->user->location)
                      <option value="{{ $request->user->location_id }}" selected>{{ $request->user->location->name }}</option>
                  @endif
              </select>
              <p class="help-block">Choose the physical location where the assets will be placed.</p>
          </div>
          <div class="form-group">
              <label for="driver_name">Driver's Name</label>
              <input type="text" class="form-control" id="driver_name" name="driver_name" form="fulfillForm" placeholder="Optional">
          </div>
          <div class="form-group">
              <label for="vehicle_number">Wagon / Track No.</label>
              <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" form="fulfillForm" placeholder="Optional">
          </div>
          <div class="form-group">
              <label for="contract_po_number">SAP PO Number</label>
              <input type="text" class="form-control" id="contract_po_number" name="contract_po_number" form="fulfillForm" placeholder="Optional">
          </div>
          <div class="form-group">
              <label for="note">Notes</label>
              <textarea class="form-control" id="note" name="note" form="fulfillForm" rows="2" placeholder="Fulfilled request #{{ $request->id }}"></textarea>
          </div>
          <hr>
          <div class="form-group" id="remainder-decision" style="display:none;" aria-live="polite">
              <div class="alert alert-info">
                  <strong>{{ trans('general.unrwa_remainder_title') }}</strong>
                  <span>{{ trans('general.unrwa_remainder_help') }}</span>
              </div>
              <label for="remainder-action">{{ trans('general.unrwa_remainder_question', ['count' => '__COUNT__']) }}</label>
              <select class="form-control" id="remainder-action" name="remainder_action[{{ $request->id }}]" form="fulfillForm">
                  <option value="">{{ trans('general.unrwa_remainder_choose') }}</option>
                  <option value="separate">{{ trans('general.unrwa_remainder_separate') }}</option>
                  <option value="cancel">{{ trans('general.unrwa_remainder_cancel') }}</option>
              </select>
          </div>
          <hr>
          <div class="form-group">
              <label class="form-control" style="cursor: pointer; background-color: #f9f9f9; border-left: 3px solid #0055A4;">
                  <input type="checkbox" id="send_pdf_email" name="send_pdf_email" value="1" form="fulfillForm" checked>
                  <strong>Send Bulk PDF Load Note via Email</strong>
              </label>
          </div>
          <div class="form-group">
              <label for="cc_email">CC Email (Manager/Security)</label>
              <div class="input-group">
                  <span class="input-group-addon"><i class="fas fa-envelope"></i></span>
                  <input class="form-control" type="email" id="cc_email" name="cc_email" form="fulfillForm" placeholder="security@unrwa.org">
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" form="fulfillForm" class="btn btn-success" id="confirmFulfillBtn"><i class="fas fa-check"></i> Confirm & Fulfill</button>
      </div>
    </div>
  </div>
</div>

<div id="fulfillment-result" class="alert" role="status" aria-live="polite" aria-atomic="true" style="display:none;"></div>

@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        var maxQty = parseInt($('#maxQty').text());

        function updateRemainderDecision(checkedCount) {
            var remainingAfterDispatch = Math.max(0, maxQty - checkedCount);
            var $decision = $('#remainder-decision');
            var $select = $('#remainder-action');

            if (checkedCount > 0 && remainingAfterDispatch > 0) {
                $decision.show();
                $decision.find('label').text(@json(trans('general.unrwa_remainder_question', ['count' => '__COUNT__'])).replace('__COUNT__', remainingAfterDispatch));
                $select.prop('required', true);
            } else {
                $decision.hide();
                $select.prop('required', false).val('');
            }
        }

        $('.asset-checkbox').change(function() {
            var checkedCheckboxes = $('.asset-checkbox:checked');
            var checkedCount = checkedCheckboxes.length;
            $('#selection-limit-message').text('');
            
            if (checkedCount > maxQty) {
                $(this).prop('checked', false);
                checkedCount--;
                $('#selection-limit-message').text('You cannot select more than the requested quantity (' + maxQty + ').');
            }

            checkedCheckboxes = $('.asset-checkbox:checked');
            checkedCount = checkedCheckboxes.length;
            
            $('#selectedCount').text(checkedCount);
            $('#btnCount').text(checkedCount);
            updateRemainderDecision(checkedCount);

            $('#openModalBtn').prop('disabled', checkedCount === 0);
            
            selectedAssets = [];
        });

        $('#openModalBtn').click(function() {
            $('#dispatchModal').modal('show');
        });

        $('#fulfillForm').on('submit', function(event) {
            var selected = $('.asset-checkbox:checked');
            if (!selected.length) {
                event.preventDefault();
                $('#selection-limit-message').text('Select at least one asset to continue.');
                return;
            }
            if (selected.length < maxQty && !$('#remainder-action').val()) {
                event.preventDefault();
                $('#selection-limit-message').text(@json(trans('general.unrwa_remainder_validation')));
                $('#dispatchModal').modal('show');
                $('#remainder-action').focus();
                return;
            }
            if (!$('#destination_location').val()) {
                event.preventDefault();
                $('#dispatchModal').modal('show');
                $('#destination_location').focus();
                return;
            }
            $('#selected-assets-container').empty();
            selected.each(function() {
                $('<input>', { type: 'hidden', name: 'selected_assets[]', value: this.value }).appendTo('#selected-assets-container');
            });
            $('#confirmFulfillBtn').html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Processing...').prop('disabled', true).attr('aria-busy', 'true');
        });
    });
</script>
@endsection
