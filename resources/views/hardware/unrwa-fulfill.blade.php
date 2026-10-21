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
                        <p><strong>Requested Quantity:</strong> <span class="badge" style="font-size: 18px; background-color: #d9534f;" id="maxQty">{{ $request->quantity }}</span></p>
                        <p><strong>Notes:</strong> {{ $request->notes ?: 'None' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Select Assets to Fulfill</h3>
                <div class="box-tools pull-right">
                    Selected: <strong id="selectedCount" class="text-success" style="font-size: 16px;">0</strong> / {{ $request->quantity }}
                </div>
            </div>
            
            <div class="box-body">
                @if($availableAssets->count() > 0)
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
                                        <input type="checkbox" name="selected_assets[]" value="{{ $asset->id }}" data-status="{{ $asset->status_id }}" class="asset-checkbox" style="transform: scale(1.5);">
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
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No available items found for this category/model in the inventory.
                    </div>
                @endif
            </div>

            <div class="box-footer">
                <button type="button" class="btn bg-maroon pull-right" id="openModalBtn" disabled>
                    <i class="fas fa-truck"></i> Proceed to Dispatch (<span id="btnCount">0</span> Items)
                </button>
            </div>
        </div>
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
              <label>Driver's Name</label>
              <input type="text" class="form-control" id="driver_name" placeholder="Optional">
          </div>
          <div class="form-group">
              <label>Wagon / Track No.</label>
              <input type="text" class="form-control" id="vehicle_number" placeholder="Optional">
          </div>
          <div class="form-group">
              <label>SAP PO Number</label>
              <input type="text" class="form-control" id="contract_po_number" placeholder="Optional">
          </div>
          <div class="form-group">
              <label>Notes</label>
              <textarea class="form-control" id="note" rows="2" placeholder="Fulfilled request #{{ $request->id }}"></textarea>
          </div>
          <hr>
          <div class="form-group">
              <label class="form-control" style="cursor: pointer; background-color: #f9f9f9; border-left: 3px solid #0055A4;">
                  <input type="checkbox" id="send_pdf_email" checked>
                  <strong>Send Bulk PDF Load Note via Email</strong>
              </label>
          </div>
          <div class="form-group">
              <label>CC Email (Manager/Security)</label>
              <div class="input-group">
                  <span class="input-group-addon"><i class="fas fa-envelope"></i></span>
                  <input class="form-control" type="email" id="cc_email" placeholder="security@unrwa.org">
              </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmFulfillBtn"><i class="fas fa-check"></i> Confirm & Fulfill</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        var maxQty = parseInt($('#maxQty').text());
        var selectedAssets = [];

        $('.asset-checkbox').change(function() {
            var checkedCheckboxes = $('.asset-checkbox:checked');
            var checkedCount = checkedCheckboxes.length;
            
            if (checkedCount > maxQty) {
                $(this).prop('checked', false);
                checkedCount--;
                alert('You cannot select more than the requested quantity (' + maxQty + ').');
            }
            
            $('#selectedCount').text(checkedCount);
            $('#btnCount').text(checkedCount);

            $('#openModalBtn').prop('disabled', checkedCount === 0);
            
            selectedAssets = [];
            checkedCheckboxes.each(function() {
                selectedAssets.push({
                    id: $(this).val(),
                    status_id: $(this).data('status')
                });
            });
        });

        $('#openModalBtn').click(function() {
            $('#dispatchModal').modal('show');
        });

        $('#confirmFulfillBtn').click(function() {
            var $btn = $(this);
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

            var driver = $('#driver_name').val();
            var vehicle = $('#vehicle_number').val();
            var po = $('#contract_po_number').val();
            var note = $('#note').val();
            var sendEmail = $('#send_pdf_email').is(':checked') ? 1 : 0;
            var ccEmail = $('#cc_email').val();
            var userId = {{ $request->user_id }};
            var requestId = {{ $request->id }};
            var baseUrl = "{{ url('/') }}";
            var token = "{{ csrf_token() }}";

            var requests = [];

            $.each(selectedAssets, function(index, asset) {
                var url = baseUrl + "/hardware/" + asset.id + "/checkout";
                
                var payload = {
                    _token: token,
                    assigned_user: userId,
                    checkout_to_type: 'user',
                    status_id: asset.status_id,
                    driver_name: driver,
                    vehicle_number: vehicle,
                    contract_po_number: po,
                    note: note,
                    send_pdf_email: (index === 0) ? sendEmail : 0,
                    cc_email: ccEmail,
                    unrwa_request_id: requestId 
                };

                requests.push($.ajax({
                    url: url,
                    type: 'POST',
                    data: payload
                }));
            });

            Promise.all(requests).then(function() {
                alert('Success! Items dispatched and Load Note generated.');
                window.location.href = "{{ route('unrwa.requests.manage') }}"; 
            }).catch(function() {
                alert('Warning: Some items encountered errors during checkout. Returning to management screen.');
                window.location.href = "{{ route('unrwa.requests.manage') }}";
            });
        });
    });
</script>
@endsection