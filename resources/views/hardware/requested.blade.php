@extends('layouts/default')

@section('title0')
  {{ trans('admin/hardware/general.requested') }}
  {{ trans('general.assets') }}
@stop

{{-- Page title --}}
@section('title')
    @yield('title0')  @parent
@stop

{{-- Page content --}}
@section('content')

<div class="row"><!-- .row -->
    <div class="col-md-12"><!-- .col-md-12 -->
        <div class="box box-default"><!-- .box -->
            <div class="box-body"><!-- .bow-body -->
                <div class="row"><!-- .row -->
                    <div class="col-md-12"><!-- col-md-12 -->

                        <!-- أزرار التحكم الجماعية (Toolbar) -->
                        <div id="toolbar">
                            <button id="bulkCheckoutRequestedBtn" class="btn btn-success btn-sm">
                                <i class="fas fa-check-double"></i> Checkout Selected to User (Load Note)
                            </button>
                        </div>

                        <table
                            data-toolbar="#toolbar"
                            class="table table-striped snipe-table"
                            id="requestedAssets"
                            data-id-table="requestedAssets"
                            data-cookie-id-table="requestedAssets"
                            data-export-options='{
                            "fileName": "export-assetrequests-{{ date('Y-m-d') }}",
                            "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                            <thead>
                                <tr role="row">
                                    <!-- حقول مخفية للـ Checkbox والبيانات البرمجية -->
                                    <th data-checkbox="true" data-field="checkbox"></th>
                                    <th data-field="item_id" data-visible="false">Item ID</th>
                                    <th data-field="item_type" data-visible="false">Item Type</th>
                                    <th data-field="user_id" data-visible="false">User ID</th>
                                    <th data-field="user_name" data-visible="false">User Name</th>
                                    <th data-field="status_id" data-visible="false">Status ID</th>

                                    <!-- الحقول الأساسية -->
                                    <th scope="col" class="col-md-1">{{ trans('general.image') }}</th>
                                    <th scope="col" class="col-md-2">{{ trans('general.name') }}</th>
                                    <th scope="col" class="col-md-2" data-sortable="true">{{ trans('admin/hardware/table.location') }}</th>
                                    <th scope="col" class="col-md-2" data-sortable="true">{{ trans('admin/hardware/form.expected_checkin') }}</th>
                                    <th scope="col" class="col-md-3" data-sortable="true">{{ trans('admin/hardware/table.requesting_user') }}</th>
                                    <th scope="col" class="col-md-2">{{ trans('admin/hardware/table.requested_date') }}</th>
                                    <th scope="col" class="col-md-1">{{ trans('button.actions') }}</th>
                                    <th scope="col" class="col-md-1">{{ trans('general.checkout') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requestedItems as $request)

                                    @if ($request->requestable)
                                    <tr>
                                        <!-- تعبئة البيانات البرمجية في الجدول -->
                                        <td></td>
                                        <td>{{ $request->requestable->id }}</td>
                                        <td>{{ $request->itemType() }}</td>
                                        <td>{{ $request->requestingUser() ? $request->requestingUser()->id : '' }}</td>
                                        <td>{{ $request->requestingUser() ? $request->requestingUser()->display_name : '' }}</td>
                                        <td>{{ $request->itemType() == 'asset' ? $request->requestable->status_id : '' }}</td>

                                        <td>
                                        @if (($request->itemType() == "asset") && ($request->requestable))
                                            <a href="{{ $request->requestable->getImageUrl() }}" data-toggle="lightbox" data-type="image"><img src="{{ $request->requestable->getImageUrl() }}" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive" alt="{{ $request->requestable->name }}"></a>
                                        @elseif (($request->itemType() == "asset_model") && ($request->requestable))
                                            <a href="{{ Storage::disk('public')->url(app('models_upload_path') . $request->requestable->image) }}" data-toggle="lightbox" data-type="image"><img src="{{ Storage::disk('public')->url(app('models_upload_path') . $request->requestable->image) }}" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive" alt="{{ $request->requestable->name }}"></a>
                                        @elseif (($request->itemType() == "accessory") && ($request->requestable) && ($request->requestable->getImageUrl()))
                                            <a href="{{ $request->requestable->getImageUrl() }}" data-toggle="lightbox" data-type="image"><img src="{{ $request->requestable->getImageUrl() }}" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive" alt="{{ $request->requestable->name }}"></a>
                                        @endif
                                        </td>
                                        <td>

                                            @if ($request->itemType() == "asset")
                                                <a href="{{ config('app.url') }}/hardware/{{ $request->requestable->id }}">
                                                    {{ $request->name() }}
                                                </a>
                                            @elseif ($request->itemType() == "asset_model")
                                                <a href="{{ config('app.url') }}/models/{{ $request->requestable->id }}">
                                                    {{ $request->name() }}
                                                </a>
                                            @elseif ($request->itemType() == "accessory")
                                                <a href="{{ config('app.url') }}/accessories/{{ $request->requestable->id }}">
                                                    {{ $request->name() }}
                                                </a>
                                            @endif

                                        </td>
                                        <td>
                                            {{ $request->location() ? $request->location()->name : '' }}
                                        </td>

                                        <td>
                                            @if ($request->itemType() == "asset")
                                            {{ App\Helpers\Helper::getFormattedDateObject($request->requestable->expected_checkin, 'datetime', false) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($request->requestingUser() && !$request->requestingUser()->trashed())
                                                <a href="{{ config('app.url') }}/users/{{ $request->requestingUser()->id }}">
                                                    {{ $request->requestingUser()->display_name }}
                                                </a>
                                            @else
                                                {{ trans('admin/reports/general.deleted_user') }}
                                            @endif
                                        </td>
                                        <td>
                                            {{ App\Helpers\Helper::getFormattedDateObject($request->created_at, 'datetime', false) }}
                                        </td>
                                        <td>
                                            <form
                                            method="POST"
                                            action="{{ route('account/request-item', [
                                            $request->itemType(),
                                            $request->requestable->id,
                                            true,
                                            $request->requestingUser()?->id
                                            ]) }}"
                                            accept-charset="UTF-8"
                                            >
                                            @csrf
                                                <button class="btn btn-warning btn-sm" data-tooltip="true" title="{{ trans('general.cancel_request') }}">{{ trans('button.cancel') }}</button>
                                            </form>
                                        </td>
                                        <td>
                                            @if ($request->itemType() == "asset")
                                                @if ($request->requestable->assigned_to=='')
                                                    <a href="{{ config('app.url') }}/hardware/{{ $request->requestable->id }}/checkout" class="btn btn-sm bg-maroon" data-tooltip="true" title="{{ trans('general.checkout_user_tooltip') }}">{{ trans('general.checkout') }}</a>
                                                @else
                                                    <a href="{{ config('app.url') }}/hardware/{{ $request->requestable->id }}/checkin" class="btn btn-sm bg-purple" data-tooltip="true" title="{{ trans('general.checkin_tooltip') }}">{{ trans('general.checkin') }}</a>
                                                @endif
                                            @elseif ($request->itemType() == "accessory")
                                                <a href="{{ config('app.url') }}/accessories/{{ $request->requestable->id }}/checkout" class="btn btn-sm bg-maroon" data-tooltip="true" title="{{ trans('general.checkout_user_tooltip') }}">{{ trans('general.checkout') }}</a>
                                            @endif
                                        </td>

                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>

                    </div> <!-- /.col-md-12 -->
                </div> <!-- /.row -->
            </div><!-- /.box-body -->
        </div><!-- /.box -->
    </div> <!-- .col-md-12 -->
</div> <!-- .row -->

<!-- النافذة المنبثقة الذكية الخاصة بالأونروا (UNRWA Load Note Modal) -->
<div class="modal fade" id="loadNoteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-maroon" style="color: white;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="fas fa-truck"></i> Generate UNRWA Load Note</h4>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
            You are checking out <strong id="modal_item_count" style="font-size:18px;"></strong> items to: <br>
            <strong id="modal_target_user" style="font-size:20px;"></strong>
        </div>
        <hr>
        <div class="form-group">
            <label>Driver's Name (اسم السائق)</label>
            <input type="text" class="form-control" id="bulk_driver_name" placeholder="Enter driver name">
        </div>
        <div class="form-group">
            <label>Wagon / Track No. (رقم السيارة)</label>
            <input type="text" class="form-control" id="bulk_vehicle_number" placeholder="Enter vehicle number">
        </div>
        <div class="form-group">
            <label>SAP PO Number (رقم الطلبية)</label>
            <input type="text" class="form-control" id="bulk_po_number" placeholder="Enter PO number">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmBulkCheckoutBtn"><i class="fas fa-check"></i> Confirm & Checkout</button>
      </div>
    </div>
  </div>
</div>

@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table', [
        'exportFile' => 'requested-export',
        'search' => true,
        'clientSearch' => true,
    ])

<script nonce="{{ csrf_token() }}">
$(function() {
    var selectedItemsForCheckout = [];

    // عند الضغط على زر الصرف الجماعي
    $('#bulkCheckoutRequestedBtn').click(function(e) {
        e.preventDefault();
        
        var selections = $('#requestedAssets').bootstrapTable('getSelections');

        if (selections.length === 0) {
            alert('Please select at least one requested item first.');
            return;
        }

        var firstUserId = selections[0].user_id;
        var firstUserName = selections[0].user_name;
        var isValid = true;

        // الفحص الذكي: هل كل المواد لنفس الموظف؟
        $.each(selections, function(index, row) {
            if (row.user_id !== firstUserId) {
                alert('Stop: You can only generate a Load Note for items requested by the SAME user. Please uncheck items belonging to other users.');
                isValid = false;
                return false;
            }
            if (row.item_type === 'asset_model') {
                alert('Notice: Asset Models cannot be bulk-checked out directly because you must assign a specific physical barcode/asset tag. Please process models individually.');
                isValid = false;
                return false;
            }
        });

        if (!isValid) return;

        // تجهيز وعرض النافذة المنبثقة للـ Load Note
        selectedItemsForCheckout = selections;
        $('#modal_item_count').text(selections.length);
        $('#modal_target_user').text(firstUserName);
        $('#loadNoteModal').modal('show');
    });

    // عند تأكيد الصرف من داخل النافذة المنبثقة
    $('#confirmBulkCheckoutBtn').click(function() {
        var $btn = $(this);
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        var driver = $('#bulk_driver_name').val();
        var vehicle = $('#bulk_vehicle_number').val();
        var po = $('#bulk_po_number').val();
        
        var requests = [];
        var baseUrl = "{{ url('/') }}";
        var token = "{{ csrf_token() }}";

        // إرسال الطلبات للنظام
        $.each(selectedItemsForCheckout, function(index, row) {
            var url = "";
            var payload = {
                _token: token,
                note: 'Fulfilled via Bulk Requests Checkout'
            };

            if (row.item_type === 'asset') {
                url = baseUrl + "/hardware/" + row.item_id + "/checkout";
                payload.assigned_user = row.user_id;
                payload.checkout_to_type = 'user';
                payload.status_id = row.status_id;
                payload.driver_name = driver;
                payload.vehicle_number = vehicle;
                payload.contract_po_number = po;
            } else if (row.item_type === 'accessory') {
                url = baseUrl + "/accessories/" + row.item_id + "/checkout";
                payload.assigned_to = row.user_id; 
            }

            requests.push($.ajax({
                url: url,
                type: 'POST',
                data: payload
            }));
        });

        // الانتظار حتى تنتهي كل عمليات الصرف
        Promise.all(requests).then(function() {
            alert('Success! All selected items have been checked out and the Load Note is ready in the Document Center.');
            window.location.reload();
        }).catch(function() {
            alert('Completed with notices. Some items may have encountered rules restrictions. Reloading...');
            window.location.reload();
        });
    });
});
</script>
@stop