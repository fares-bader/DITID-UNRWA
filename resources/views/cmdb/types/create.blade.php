@extends('layouts/default')
@section('title', isset($type) ? 'Edit CMDB Object Type' : 'Create CMDB Object Type')

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-hammer"></i> {{ isset($type) ? 'Edit Schema: ' . $type->name : 'Build New CMDB Type' }}</h3>
            </div>
            
            <form action="{{ isset($type) ? route('cmdb.types.update', $type->id) : route('cmdb.types.store') }}" method="POST">
                @csrf
                <div class="box-body">
                    @if($errors->any())
                        <div class="alert alert-danger" role="alert" aria-live="assertive">
                            <p>Please correct the following errors:</p>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <!-- Basic Info -->
                    <div class="row">
                        <div class="col-md-6 form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label>Type Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. WAN Link, Server, Router" required value="{{ old('name', $type->name ?? '') }}">
                            {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Icon Class (FontAwesome Icon)</label>
                            <input type="text" class="form-control" name="icon" placeholder="fas fa-server" value="{{ old('icon', $type->icon ?? 'fas fa-server') }}">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Brief description of this type...">{{ old('description', $type->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <hr>
                    <h4 class="text-primary"><i class="fas fa-list"></i> Define Custom Fields</h4>
                    <p class="text-muted">What data do you want to store for this object type? (e.g. IP Address, Subnet Mask, VLAN)</p>

                    <!-- Dynamic Fields Container -->
                    <div id="dynamic-fields-container">
                        @php
                            $fields = old('field_name', isset($type) ? ($type->schema_definition ?? []) : [ ['name' => '', 'type' => 'text'] ]);
                            // If schema is completely empty but form loads, ensure at least one blank row exists
                            if (count($fields) == 0) {
                                $fields = [ ['name' => '', 'type' => 'text'] ];
                            }
                        @endphp
                        
                        @foreach($fields as $index => $field)
                            @php
                                $fName = is_array($field) ? $field['name'] : $field;
                                $fType = is_array($field) ? ($field['type'] ?? 'text') : (old('field_type')[$index] ?? 'text');
                            @endphp
                            <div class="row field-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px;">
                                <div class="col-md-6 form-group" style="margin-bottom: 0;">
                            <label for="field-name-{{ $index }}">Field Name</label>
                            <input id="field-name-{{ $index }}" type="text" class="form-control" name="field_name[]" placeholder="e.g. IP Address" value="{{ $fName }}" required>
                                </div>
                                <div class="col-md-5 form-group" style="margin-bottom: 0;">
                            <label for="field-type-{{ $index }}">Field Type</label>
                            <select id="field-type-{{ $index }}" class="form-control" name="field_type[]" required>
                                        <option value="text" {{ $fType == 'text' ? 'selected' : '' }}>Text</option>
                                        <option value="textarea" {{ $fType == 'textarea' ? 'selected' : '' }}>Textarea</option>
                                        <option value="number" {{ $fType == 'number' ? 'selected' : '' }}>Number</option>
                                        <option value="date" {{ $fType == 'date' ? 'selected' : '' }}>Date</option>
                                        <option value="url" {{ $fType == 'url' ? 'selected' : '' }}>URL</option>
                                    </select>
                                </div>
                                <div class="col-md-1 text-center" style="margin-top: 25px;">
                                    <button type="button" class="btn btn-danger btn-sm remove-field-btn" style="{{ $loop->first && count($fields) == 1 ? 'display:none;' : '' }}" title="Remove Field" aria-label="Remove field {{ $index + 1 }}"><i class="fas fa-times" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-field-btn" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Add Another Field</button>

                </div>
                
                <div class="box-footer text-right">
                    <a href="{{ route('cmdb.types.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Type Schema</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        // Toggle removal button visibility logic
        function updateRemoveButtons() {
            var rowCount = $('.field-row').length;
            if (rowCount > 1) {
                $('.remove-field-btn').show();
            } else {
                $('.remove-field-btn').hide();
            }
        }

        // Add new field row
        $('#add-field-btn').click(function() {
            var $firstRow = $('.field-row').first();
            var $clone = $firstRow.clone();
            var newIndex = $('.field-row').length;

            $clone.find('input[name="field_name[]"]').attr('id', 'field-name-' + newIndex).prev('label').attr('for', 'field-name-' + newIndex);
            $clone.find('select[name="field_type[]"]').attr('id', 'field-type-' + newIndex).prev('label').attr('for', 'field-type-' + newIndex);
            $clone.find('.remove-field-btn').attr('aria-label', 'Remove field ' + (newIndex + 1));
            
            $clone.find('input[type="text"]').val('');
            $clone.find('select').val('text');
            
            $('#dynamic-fields-container').append($clone);
            updateRemoveButtons();
        });

        // Remove field row
        $(document).on('click', '.remove-field-btn', function() {
            if ($('.field-row').length > 1) {
                $(this).closest('.field-row').remove();
                updateRemoveButtons();
            }
        });
        
        updateRemoveButtons();
    });
</script>
@endsection
