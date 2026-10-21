@extends('layouts/default')
@section('title', 'Bulk Fulfill Requests for ' . $targetUser->display_name)

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- استخدمنا url المباشر لتفادي خطأ Route Not Found -->
        <form action="{{ url('hardware/bulkcheckout') }}" method="POST" id="fulfillForm">
            @csrf
            <input type="hidden" name="checkout_to_type" value="user">
            <input type="hidden" name="assigned_user" value="{{ $targetUser->id }}">
            <input type="hidden" name="send_pdf_email" value="1"> <!-- إرسال الايميل اجباري -->
            
            <!-- تمرير معرفات الطلبات لإغلاقها لاحقاً -->
            @foreach($requests as $req)
                <input type="hidden" name="unrwa_request_ids[]" value="{{ $req->id }}">
            @endforeach

            <div class="box box-solid box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fas fa-truck-loading"></i> Fulfilling Requests for: {{ $targetUser->display_name }}</h3>
                </div>
            </div>

            <!-- عرض قسم لكل طلب -->
            @foreach($requests as $req)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title text-info">
                        <strong>{{ $req->category->name }}</strong> 
                        @if($req->assetModel) ({{ $req->assetModel->name }}) @endif
                    </h3>
                    <div class="box-tools pull-right">
                        Req. Qty: <span class="badge bg-red" id="maxQty_{{ $req->id }}">{{ $req->quantity }}</span> | 
                        Selected: <strong id="selCount_{{ $req->id }}" class="text-success">0</strong>
                    </div>
                </div>
                
                <div class="box-body">
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
                            @foreach($req->availableAssets as $asset)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_assets[]" value="{{ $asset->id }}" class="asset-checkbox req-group-{{ $req->id }}" data-req="{{ $req->id }}" style="transform: scale(1.4);">
                                    </td>
                                    <td style="font-weight:bold;">{{ $asset->asset_tag }}</td>
                                    <td>{{ $asset->model->name }}</td>
                                    <td>{{ $asset->serial ?: '-' }}</td>
                                    <td>{{ $asset->location ? $asset->location->name : 'Default' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            <!-- زر فتح النافذة الختامية -->
            <div class="box box-solid">
                <div class="box-body text-right">
                    <button type="button" class="btn bg-maroon btn-lg" id="openModalBtn" disabled>
                        <i class="fas fa-file-invoice"></i> Proceed to Dispatch Details
                    </button>
                </div>
            </div>

            <!-- Modal المذكرة الختامية -->
            <div class="modal fade" id="dispatchModal" tabindex="-1" role="dialog">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header bg-maroon" style="color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-file-invoice"></i> Dispatch Details (Unified Load Note)</h4>
                  </div>
                  <div class="modal-body">
                      <div class="form-group">
                          <label>Driver's Name (اسم السائق)</label>
                          <input type="text" class="form-control" name="driver_name" placeholder="Optional">
                      </div>
                      <div class="form-group">
                          <label>Wagon / Track No. (رقم السيارة)</label>
                          <input type="text" class="form-control" name="vehicle_number" placeholder="Optional">
                      </div>
                      <div class="form-group">
                          <label>SAP PO Number (رقم الطلبية المجمع - إن وجد)</label>
                          <input type="text" class="form-control" name="contract_po_number" placeholder="Optional">
                      </div>
                      <div class="form-group">
                          <label>Unified Notes (ملاحظات الصرف)</label>
                          <textarea class="form-control" name="note" rows="2" placeholder="Fulfilled multiple requests..."></textarea>
                      </div>
                      <hr>
                      <div class="form-group">
                          <label>CC Email (Manager/Security)</label>
                          <div class="input-group">
                              <span class="input-group-addon"><i class="fas fa-envelope"></i></span>
                              <input class="form-control" type="email" name="cc_email" placeholder="security@unrwa.org">
                          </div>
                      </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <!-- التنفيذ المباشر للفورم -->
                    <button type="submit" class="btn btn-success" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processing...';"><i class="fas fa-check-double"></i> Confirm & Fulfill</button>
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
        $('.asset-checkbox').change(function() {
            var reqId = $(this).data('req');
            var max = parseInt($('#maxQty_' + reqId).text());
            var checkedCount = $('.req-group-' + reqId + ':checked').length;
            
            // منع تجاوز الكمية لكل صنف على حدة
            if (checkedCount > max) {
                $(this).prop('checked', false);
                checkedCount--;
                alert('You cannot select more than the requested quantity for this item category.');
            }
            
            $('#selCount_' + reqId).text(checkedCount);

            // تفعيل الزر إذا تم اختيار جهاز واحد على الأقل من كامل الشاشة
            var totalChecked = $('.asset-checkbox:checked').length;
            $('#openModalBtn').prop('disabled', totalChecked === 0);
        });

        $('#openModalBtn').click(function() {
            $('#dispatchModal').modal('show');
        });
    });
</script>
@endsection