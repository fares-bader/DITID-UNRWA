@extends('layouts/default')

@section('title', 'Request IT Equipment - UNRWA')

@section('content')
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="box box-primary" style="border-top-color: #0055A4;">
            <div class="box-header with-border text-center">
                <h3 class="box-title" style="color: #0055A4; font-weight: bold;">
                    <i class="fas fa-desktop"></i> UNRWA IT Equipment Request Portal
                </h3>
            </div>
            
            <div class="box-body">
                @if(session('success'))
                    <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Please check the form below for errors.</div>
                @endif

                <form action="{{ route('unrwa.requests.store') }}" method="POST" id="requestForm">
                    @csrf
                    
                    <div id="dynamic-items-container">
                        <!-- بداية سطر الطلب -->
                        <div class="item-row box box-solid" style="padding: 15px; border: 1px solid #ddd; background-color: #fdfdfd; margin-bottom: 15px;">
                            <button type="button" class="btn btn-danger btn-sm remove-item-btn pull-right" style="display:none;"><i class="fas fa-times"></i> Remove</button>
                            <h4 style="margin-top: 0; color: #555;"><i class="fas fa-box"></i> Item Request</h4>
                            <hr style="margin-top: 5px; margin-bottom: 15px;">

                            <div class="row">
                                <!-- Category -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Equipment Category <span class="text-danger">*</span></label>
                                        <!-- تم مسح id واستخدام class للتعرف عليه -->
                                        <select class="form-control select2 category-select" name="category_id[]" required>
                                            <option value="">-- Select Category --</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">
                                                    {{ $category->parent ? $category->parent->name . ' -> ' : '' }}{{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Model (Admin Only) -->
                                @if($isManagerOrAdmin)
                                <div class="col-md-5">
                                    <div class="form-group" style="padding: 5px; border-left: 3px solid #f39c12; background-color: #fff;">
                                        <label style="color: #d35400;">Specific Model (Optional)</label>
                                        <select class="form-control select2 model-select" name="model_id[]">
                                            <option value="" data-qty="-" data-category-id="">-- Any Model --</option>
                                            @foreach($models as $model)
                                                <option value="{{ $model->id }}" data-qty="{{ $model->available_count }}" data-category-id="{{ $model->category_id }}">
                                                    {{ $model->name }} ({{ $model->model_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="availability_badge" style="display:none; margin-top: 5px;">
                                            <span class="label qty_label" style="font-size: 11px;">
                                                <i class="fas fa-box-open"></i> In Stock: <span class="qty_val"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Quantity -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control qty-input" name="quantity[]" value="1" min="1" max="50" required>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="col-md-12">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>Justification / Notes</label>
                                        <input type="text" class="form-control" name="notes[]" placeholder="Briefly explain why this equipment is needed...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- نهاية سطر الطلب -->
                    </div>

                    <div class="box-footer">
                        <button type="button" id="add-item-btn" class="btn btn-success"><i class="fas fa-plus"></i> Add Another Item</button>
                        <button type="submit" class="btn btn-primary pull-right" style="background-color: #0055A4;"><i class="fas fa-paper-plane"></i> Submit All Requests</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="model-options-storage" style="display:none;">
    <select id="master-model-list">
        <option value="" data-qty="-" data-category-id="">-- Any Model --</option>
        @if($isManagerOrAdmin)
            @foreach($models as $model)
                <option value="{{ $model->id }}" data-qty="{{ $model->available_count }}" data-category-id="{{ $model->category_id }}">
                    {{ $model->name }} ({{ $model->model_number }})
                </option>
            @endforeach
        @endif
    </select>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        var isManagerOrAdmin = {{ $isManagerOrAdmin ? 'true' : 'false' }};
        
        function filterModelsForRow($row) {
            if (!isManagerOrAdmin) return;
            var $categorySelect = $row.find('.category-select');
            var $modelSelect = $row.find('.model-select');
            var selectedCategoryId = $categorySelect.val();
            var currentSelectedModel = $modelSelect.val();

            $modelSelect.empty();
            $modelSelect.append($('#master-model-list').find('option[value=""]').clone());

            if (selectedCategoryId) {
                var $filteredOptions = $('#master-model-list').find('option').filter(function() {
                    return $(this).data('category-id') == selectedCategoryId;
                });
                $modelSelect.append($filteredOptions.clone());
            }

            if ($modelSelect.find('option[value="' + currentSelectedModel + '"]').length > 0) {
                $modelSelect.val(currentSelectedModel);
            } else {
                $modelSelect.val('');
            }
            $modelSelect.trigger('change.select2');
            showAvailabilityForRow($row);
        }

        function showAvailabilityForRow($row) {
            if (!isManagerOrAdmin) return;
            var $modelSelect = $row.find('.model-select');
            var qty = $modelSelect.find(':selected').data('qty');
            var $badge = $row.find('.availability_badge');
            var $label = $row.find('.qty_label');

            if (qty !== undefined && qty !== '-') {
                $row.find('.qty_val').text(qty);
                $badge.fadeIn();
                if (qty == 0) {
                    $label.removeClass('label-success').addClass('label-danger');
                } else {
                    $label.removeClass('label-danger').addClass('label-success');
                }
            } else {
                $badge.fadeOut();
            }
        }

        $(document).on('change', '.category-select', function() {
            filterModelsForRow($(this).closest('.item-row'));
        });

        $(document).on('change', '.model-select', function() {
            showAvailabilityForRow($(this).closest('.item-row'));
        });

        // زر إضافة سطر جديد (مصحح 100%)
        $('#add-item-btn').click(function() {
            var $firstRow = $('.item-row').first();
            
            // استنساخ السطر
            var $clone = $firstRow.clone();
            
            // تنظيف النسخة من بقايا مكتبة Select2 القديمة
            $clone.find('.select2-container').remove();
            $clone.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
            $clone.find('option').removeAttr('data-select2-id');
            
            // إعادة ضبط القيم للسطر الجديد
            $clone.find('select').val('');
            $clone.find('input[type="number"]').val('1');
            $clone.find('input[type="text"]').val('');
            $clone.find('.availability_badge').hide();
            $clone.find('.remove-item-btn').show(); 
            
            // إلحاق السطر الجديد بالشاشة
            $('#dynamic-items-container').append($clone);
            
            // تفعيل مكتبة Select2 للسطر الجديد فقط
            $clone.find('.select2').select2();
            
            filterModelsForRow($clone);
        });

        // حذف سطر
        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('.item-row').remove();
        });

        // تشغيل الفلترة للسطر الأول عند التحميل
        filterModelsForRow($('.item-row').first());
    });
</script>
@endsection