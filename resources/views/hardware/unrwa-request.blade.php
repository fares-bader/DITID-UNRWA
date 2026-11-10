@extends('layouts/default')

@section('title', 'Request IT Equipment - UNRWA')

@push('css')
<style>
    .unrwa-request-page { max-width: 1120px; margin: 0 auto; }
    .unrwa-request-page .request-intro { padding: 28px 30px; color: #fff; background: linear-gradient(120deg, #064b88, #087e8b); border-radius: 4px 4px 0 0; }
    .unrwa-request-page .request-intro h1 { margin: 0 0 8px; font-size: 24px; font-weight: 600; }
    .unrwa-request-page .request-intro p { max-width: 720px; margin: 0; opacity: .92; }
    .unrwa-request-page .request-form-body { padding: 24px 30px; }
    .unrwa-request-page .item-row { padding: 20px; margin: 0 0 16px; background: #fff; border: 1px solid #dce4ec; border-radius: 5px; box-shadow: 0 2px 5px rgba(25, 45, 65, .04); }
    .unrwa-request-page .item-row-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .unrwa-request-page .item-row-heading h4 { margin: 0; color: #25415b; font-weight: 600; }
    .unrwa-request-page .item-number { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-inline-start: 5px; color: #07538a; background: #e8f2f9; border-radius: 50%; font-size: 12px; }
    .unrwa-request-page .unrwa-model-field { padding: 10px; background: #fffaf0; border-inline-start: 3px solid #e9a12a; border-radius: 3px; }
    .unrwa-request-page .request-form-footer { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; padding: 18px 0 0; border-top: 1px solid #e7edf2; }
    .unrwa-request-page .history-status { white-space: nowrap; }
    .unrwa-request-page .follow-up-note { display: block; margin-top: 4px; font-size: 12px; color: #647789; }
    @media (max-width: 767px) {
        .unrwa-request-page .request-intro, .unrwa-request-page .request-form-body { padding: 20px 16px; }
        .unrwa-request-page .request-intro h1 { font-size: 21px; }
        .unrwa-request-page .request-form-footer { display: block; }
        .unrwa-request-page .request-form-footer .btn { display: block; width: 100%; margin: 8px 0 0; }
    }
</style>
@endpush

@section('content')
<div class="row unrwa-request-page">
    <div class="col-xs-12">
        <div class="box box-primary">
            <header class="request-intro">
                <h1><i class="fas fa-laptop" aria-hidden="true"></i> {{ trans('general.unrwa_request_page_title') }}</h1>
                <p>{{ trans('general.unrwa_request_page_intro') }}</p>
            </header>
            
            <div class="request-form-body">
                @if(session('success'))
                    <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger" role="alert" aria-live="assertive">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Please check the form below for errors.
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('unrwa.requests.store') }}" method="POST" id="requestForm">
                    @csrf

                    @php
                        $requestItemCount = max(count(old('category_id', [])), count(old('quantity', [])), 1);
                    @endphp
                    
                    <div id="dynamic-items-container">
                        @for($index = 0; $index < $requestItemCount; $index++)
                        <section class="item-row">
                            <div class="item-row-heading">
                                <h4><i class="fas fa-box" aria-hidden="true"></i> {{ trans('general.unrwa_request_item') }} <span class="item-number">{{ $index + 1 }}</span></h4>
                                <button type="button" class="btn btn-default btn-sm remove-item-btn" style="display:none;" aria-label="{{ trans('general.unrwa_request_remove_item') }}"><i class="fas fa-times" aria-hidden="true"></i> {{ trans('general.unrwa_request_remove_item') }}</button>
                            </div>

                            <div class="row">
                                <!-- Category -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="category-{{ $index }}">{{ trans('general.unrwa_request_category') }} <span class="text-danger">*</span></label>
                                        <select id="category-{{ $index }}" class="form-control select2 category-select" name="category_id[]" required>
                                            <option value="">{{ trans('general.unrwa_request_select_category') }}</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id.'.$index) == $category->id ? 'selected' : '' }}>
                                                    {{ $category->parent ? $category->parent->name . ' -> ' : '' }}{{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Model (Admin Only) -->
                                @if($isManagerOrAdmin)
                                <div class="col-md-5">
                                    <div class="form-group unrwa-model-field">
                                        <label for="model-{{ $index }}">{{ trans('general.unrwa_request_model_optional') }}</label>
                                        <select id="model-{{ $index }}" class="form-control select2 model-select" name="model_id[]">
                                            <option value="" data-qty="-" data-category-id="">{{ trans('general.unrwa_request_any_model') }}</option>
                                            @foreach($models as $model)
                                                <option value="{{ $model->id }}" data-qty="{{ $model->available_count }}" data-category-id="{{ $model->category_id }}" {{ old('model_id.'.$index) == $model->id ? 'selected' : '' }}>
                                                    {{ $model->name }} ({{ $model->model_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="availability_badge" style="display:none; margin-top: 5px;">
                                            <span class="label qty_label" style="font-size: 11px;">
                                                <i class="fas fa-box-open" aria-hidden="true"></i> {{ trans('general.unrwa_request_in_stock') }}: <span class="qty_val"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Quantity -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="quantity-{{ $index }}">{{ trans('general.unrwa_request_quantity') }} <span class="text-danger">*</span></label>
                                        <input id="quantity-{{ $index }}" type="number" class="form-control qty-input" name="quantity[]" value="{{ old('quantity.'.$index, 1) }}" min="1" max="50" required>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="col-md-12">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="notes-{{ $index }}">{{ trans('general.unrwa_request_notes') }}</label>
                                        <input id="notes-{{ $index }}" type="text" class="form-control" name="notes[]" value="{{ old('notes.'.$index) }}" placeholder="{{ trans('general.unrwa_request_notes_hint') }}">
                                    </div>
                                </div>
                            </div>
                        </section>
                        @endfor
                    </div>

                    <div class="request-form-footer">
                        <button type="button" id="add-item-btn" class="btn btn-default"><i class="fas fa-plus" aria-hidden="true"></i> {{ trans('general.unrwa_request_add_item') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> {{ trans('general.unrwa_request_submit') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <section class="box box-default" aria-labelledby="request-history-title">
            <div class="box-header with-border">
                <h2 class="box-title" id="request-history-title"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i> {{ trans('general.unrwa_request_history') }}</h2>
                <span class="label label-default pull-right">{{ trans('general.unrwa_request_recent_count', ['count' => $requestHistory->total()]) }}</span>
            </div>
            <div class="box-body">
                @if($requestHistory->isEmpty())
                    <div class="text-center text-muted" style="padding: 28px 12px;">
                        <i class="fas fa-inbox fa-2x" aria-hidden="true"></i>
                        <h4>{{ trans('general.unrwa_request_history_empty_title') }}</h4>
                        <p>{{ trans('general.unrwa_request_history_empty_help') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead><tr>
                                <th>{{ trans('general.unrwa_request_number') }}</th>
                                <th>{{ trans('general.unrwa_request_category') }}</th>
                                <th>{{ trans('general.unrwa_request_quantity') }}</th>
                                <th>{{ trans('general.unrwa_request_history_status') }}</th>
                                <th>{{ trans('general.unrwa_request_submitted_at') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach($requestHistory as $historyRequest)
                                    @php
                                        $historyFulfilled = $historyRequest->status === 'Fulfilled'
                                            ? $historyRequest->quantity
                                            : min($historyRequest->quantity, $historyRequest->fulfilled_assets_count);
                                    @endphp
                                    <tr>
                                        <td><bdi dir="ltr">#{{ $historyRequest->id }}</bdi>
                                            @if($historyRequest->parentRequest)
                                                <span class="follow-up-note request-lineage"><span>{{ trans('general.unrwa_request_follow_up_badge') }}</span><bdi dir="ltr">{{ trans('general.unrwa_request_follow_up_chain', ['child' => $historyRequest->id, 'parent' => $historyRequest->parent_request_id]) }}</bdi></span>
                                            @endif
                                        </td>
                                        <td>{{ $historyRequest->category?->name ?? '—' }}@if($historyRequest->assetModel)<span class="follow-up-note">{{ $historyRequest->assetModel->name }}</span>@endif</td>
                                        <td><bdi dir="ltr">{{ $historyFulfilled }}/{{ $historyRequest->quantity }}</bdi></td>
                                        <td>
                                            @if($historyRequest->status === 'Fulfilled')
                                                <span class="label label-success history-status">{{ trans('general.unrwa_request_status_fulfilled') }}</span>
                                            @elseif($historyRequest->status === 'Canceled')
                                                <span class="label label-default history-status">{{ trans('general.unrwa_request_status_canceled') }}</span>
                                            @elseif($historyFulfilled > 0)
                                                <span class="label label-info history-status">{{ trans('general.unrwa_request_status_in_progress') }}</span>
                                            @else
                                                <span class="label label-warning history-status">{{ trans('general.unrwa_request_status_pending') }}</span>
                                            @endif
                                        </td>
                                        <td><bdi dir="ltr">{{ $historyRequest->created_at->format('Y-m-d H:i') }}</bdi></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">{{ $requestHistory->appends(request()->except('historyPage'))->links() }}</div>
                @endif
            </div>
        </section>
    </div>
</div>

<div id="model-options-storage" style="display:none;">
    <select id="master-model-list">
        <option value="" data-qty="-" data-category-id="">{{ trans('general.unrwa_request_any_model') }}</option>
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

        function syncItemNumbers() {
            $('.item-row').each(function(index) {
                $(this).find('.item-number').text(index + 1);
            });
        }

        $(document).on('change', '.category-select', function() {
            filterModelsForRow($(this).closest('.item-row'));
        });

        $(document).on('change', '.model-select', function() {
            showAvailabilityForRow($(this).closest('.item-row'));
        });

        $('#add-item-btn').click(function() {
            var $firstRow = $('.item-row').first();
            
            var $clone = $firstRow.clone();
            var newIndex = $('.item-row').length;
            
            $clone.find('.select2-container').remove();
            $clone.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
            $clone.find('option').removeAttr('data-select2-id');

            $clone.find('.category-select').attr('id', 'category-' + newIndex);
            $clone.find('label[for^="category-"]').attr('for', 'category-' + newIndex);
            $clone.find('.model-select').attr('id', 'model-' + newIndex);
            $clone.find('label[for^="model-"]').attr('for', 'model-' + newIndex);
            $clone.find('.qty-input').attr('id', 'quantity-' + newIndex);
            $clone.find('label[for^="quantity-"]').attr('for', 'quantity-' + newIndex);
            $clone.find('input[name="notes[]"]').attr('id', 'notes-' + newIndex);
            $clone.find('label[for^="notes-"]').attr('for', 'notes-' + newIndex);
            
            $clone.find('select').val('');
            $clone.find('input[type="number"]').val('1');
            $clone.find('input[type="text"]').val('');
            $clone.find('.availability_badge').hide();
            $clone.find('.remove-item-btn').show(); 
            
            $('#dynamic-items-container').append($clone);
            syncItemNumbers();
            
            $clone.find('.select2').select2();
            
            filterModelsForRow($clone);
        });

        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('.item-row').remove();
            syncItemNumbers();
        });

        filterModelsForRow($('.item-row').first());
    });
</script>
@endsection
