@extends('layouts/default')

@section('title', 'Manage UNRWA Requests')

@section('content')
<div class="row">
    <div class="col-md-12">
        
        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
        @endif

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-list-alt"></i> UNRWA Equipment Requests</h3>
            </div>
            <div class="box-body">
                
                <!-- نموذج الصرف المجمع يغلف الجدول بالكامل -->
                <form action="{{ route('unrwa.requests.bulk-fulfill') }}" method="POST" id="bulkFulfillForm">
                    @csrf
                    
                    <!-- زر الصرف المجمع -->
                    <div style="margin-bottom: 15px;">
                        <button type="button" id="btnBulkFulfill" class="btn btn-success">
                            <i class="fas fa-check-double"></i> Fulfill Selected Requests (Unified Load Note)
                        </button>
                    </div>

                    <div class="table-responsive">
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
                                    <th>Status / Batch</th> <!-- تعديل اسم العمود -->
                                    <th>Date Requested</th>
                                    <th>Notes</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $req)
                                    <tr>
                                        <td>
                                            @if($req->status == 'Pending' && $req->available_count >= $req->quantity)
                                                <input type="checkbox" name="request_ids[]" value="{{ $req->id }}" class="req-checkbox" data-user="{{ $req->user_id }}" style="transform: scale(1.3);">
                                            @endif
                                        </td>
                                        
                                        <td>{{ $req->id }}</td>
                                        
                                        <td>
                                            @if($req->request_tier == 'Manager/Admin')
                                                <span class="label label-danger"><i class="fas fa-star"></i> Manager/Admin</span>
                                            @else
                                                <span class="label label-primary">User</span>
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
                                                <span class="label label-warning">Pending</span>
                                            @elseif($req->status == 'Fulfilled') 
                                                <span class="label label-success">Fulfilled</span>
                                                <!-- ====== UNRWA Customization: Batch Display ====== -->
                                                @if(isset($req->batch_info))
                                                    <div style="margin-top: 4px;">
                                                        <span class="badge {{ $req->batch_info['color'] }}" data-toggle="tooltip" title="Items in the same batch share one Load Note">
                                                            <i class="fas fa-layer-group"></i> Batch #{{ $req->batch_info['id'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <!-- ================================================ -->
                                            @else 
                                                <span class="label label-default">{{ $req->status }}</span> 
                                            @endif
                                        </td>
                                        
                                        <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                        
                                        <td>
                                            <button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" title="{{ $req->notes ?: 'No notes provided' }}">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        </td>
                                        
                                        <td class="text-right">
                                            @if($req->status == 'Pending')
                                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $req->id }}" title="Edit Request">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-danger cancel-btn" data-url="{{ route('unrwa.requests.cancel', $req->id) }}" title="Reject Request">
                                                    <i class="fas fa-times"></i>
                                                </button>

                                                @if($req->available_count >= $req->quantity)
                                                    <a href="{{ route('unrwa.requests.fulfill', $req->id) }}" class="btn btn-sm bg-maroon" title="Process Single Request">
                                                        <i class="fas fa-check"></i> Fulfill
                                                    </a>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-default" disabled title="Not enough stock"><i class="fas fa-ban text-danger"></i></button>
                                                @endif

                                            @elseif($req->status == 'Fulfilled' && $req->log_id)
                                                <!-- ====== UNRWA Customization: Print Load Note Button ====== -->
                                                <a href="{{ route('hardware.loadnote', $req->log_id) }}" target="_blank" class="btn btn-sm btn-default" style="border-color: #0055A4; color: #0055A4;" title="Print Load Note">
                                                    <i class="fas fa-print"></i> Print Note
                                                </a>
                                                <!-- ========================================================= -->
                                            @else
                                                <button type="button" class="btn btn-sm btn-default" disabled>Processed</button>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- (محتوى الـ Edit Modal المخفي يبقى كما هو بدون تغيير) -->
                                    @if($req->status == 'Pending')
                                    <div class="modal fade" id="editModal{{ $req->id }}" tabindex="-1" role="dialog">
                                        <!-- ... كود الـ Edit Modal كما هو ... -->
                                      <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                          <div class="modal-header bg-primary">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title">Edit Request #{{ $req->id }}</h4>
                                          </div>
                                          <div class="modal-body text-left">
                                              <div class="form-group">
                                                  <label>Category</label>
                                                  <select class="form-control edit-category" id="edit_cat_{{ $req->id }}" required>
                                                      @foreach($categories as $cat)
                                                          <option value="{{ $cat->id }}" {{ $req->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                      @endforeach
                                                  </select>
                                              </div>
                                              <div class="form-group">
                                                  <label>Model (Optional)</label>
                                                  <select class="form-control edit-model" id="edit_model_{{ $req->id }}">
                                                      <option value="">-- Any Model --</option>
                                                      @foreach($models as $model)
                                                          <option value="{{ $model->id }}" {{ $req->model_id == $model->id ? 'selected' : '' }}>{{ $model->name }}</option>
                                                      @endforeach
                                                  </select>
                                              </div>
                                              <div class="form-group">
                                                  <label>Quantity</label>
                                                  <input type="number" class="form-control edit-qty" id="edit_qty_{{ $req->id }}" value="{{ $req->quantity }}" min="1">
                                              </div>
                                              <div class="form-group">
                                                  <label>Notes</label>
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
                            </tbody>
                        </table>
                    </div>
                </form> <!-- نهاية نموذج الصرف المجمع -->
                
                <!-- نموذج مخفي لعمليات الرفض والتعديل لتفادي تداخل الـ Forms -->
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
        // تفعيل التلميحات (Tooltips)
        $('[data-toggle="tooltip"]').tooltip();

        // 1. التحكم بالصرف المجمع (Bulk Fulfill)
        $(document).on('click', '#btnBulkFulfill', function(e) {
            e.preventDefault();
            var selected = $('.req-checkbox:checked');
            
            if(selected.length === 0) {
                alert('Please select at least one pending request. يرجى اختيار طلب واحد على الأقل.');
                return;
            }
            
            var userId = null;
            var isValid = true;
            
            // التحقق من أن كل الطلبات المحددة تعود لنفس الموظف
            selected.each(function() {
                var currentUserId = $(this).data('user');
                if(userId === null) {
                    userId = currentUserId;
                } else if(userId !== currentUserId) {
                    alert('Error: All selected requests must belong to the SAME user to generate a unified Load Note. يجب اختيار طلبات لنفس الموظف فقط لدمجها في مذكرة واحدة.');
                    isValid = false;
                    return false;
                }
            });

            if(isValid) {
                // تغيير شكل الزر ليدل على التحميل
                $(this).html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
                $('#bulkFulfillForm').submit();
            }
        });

        // 2. التحكم برفض الطلب (Cancel Request)
        $(document).on('click', '.cancel-btn', function() {
            if(confirm('Are you sure you want to reject/cancel this request? هل أنت متأكد من رفض/إلغاء هذا الطلب؟')) {
                var url = $(this).data('url');
                var $form = $('#actionForm');
                $form.attr('action', url);
                $form.submit();
            }
        });

        // 3. التحكم بتعديل الطلب (Edit Request)
        $(document).on('click', '.save-edit-btn', function() {
            var reqId = $(this).data('id');
            var url = $(this).data('url');
            
            // سحب البيانات من النافذة المنبثقة
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