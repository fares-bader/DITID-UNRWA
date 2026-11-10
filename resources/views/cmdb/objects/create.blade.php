@extends('layouts/default')
@section('title', 'Create CMDB Configuration Item')

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-plus-circle"></i> Document New Configuration Item</h3>
            </div>
            
            <form action="{{ route('cmdb.objects.store') }}" method="POST">
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
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="object-name">Item Name (Identifier) <span class="text-danger">*</span></label>
                            <input id="object-name" type="text" class="form-control" name="name" placeholder="e.g. HQ Main Fiber Line" value="{{ old('name') }}" required>
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label for="type_id">Configuration Type <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="type_id" id="type_id" required>
                                <option value="">-- Select Type --</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>
                    <div id="dynamic-fields-wrapper" role="region" aria-label="Configuration fields" aria-live="polite" style="background: #fdfdfd; padding: 15px; border: 1px dashed #ccc; border-radius: 5px;">
                        <p class="text-muted text-center" id="dynamic-placeholder"><i class="fas fa-magic"></i> Select a Configuration Type to load fields...</p>
                    </div>
                    <hr>

                    <div class="form-group">
                        <label for="object-notes">General Notes</label>
                        <textarea id="object-notes" class="form-control" name="notes" rows="3" placeholder="Any additional documentation...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                
                <div class="box-footer text-right">
                    <a href="{{ route('cmdb.objects.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        var cmdbTypes = @json($types);
        var previousValues = @json(old('dynamic_fields', []));

        $('#type_id').on('change', function() {
            var selectedId = $(this).val();
            var $wrapper = $('#dynamic-fields-wrapper');
            var $placeholder = $('<p class="text-muted text-center">').text('Select a Configuration Type to load fields...');
            
            $wrapper.empty();

            if(!selectedId) {
                $wrapper.append($placeholder);
                return;
            }

            var selectedType = cmdbTypes.find(t => t.id == selectedId);
            
            if(selectedType && selectedType.schema_definition && selectedType.schema_definition.length > 0) {
                var $row = $('<div class="row">');

                selectedType.schema_definition.forEach(function(field, index) {
                    var id = 'dynamic-field-' + index;
                    var inputName = 'dynamic_fields[' + field.name + ']';
                    var $group = $('<div class="col-md-6 form-group">');
                    var $label = $('<label class="text-primary">').attr('for', id).text(field.name);
                    var $input;

                    if (field.type === 'textarea') {
                        $input = $('<textarea>', { class: 'form-control', name: inputName, rows: 2, id: id });
                    } else {
                        var inputType = ['text', 'number', 'date', 'url'].includes(field.type) ? field.type : 'text';
                        $input = $('<input>', { type: inputType, class: 'form-control', name: inputName, id: id });
                    }

                    $input.val(previousValues[field.name] || '');
                    $group.append($label, $input);
                    $row.append($group);
                });

                $wrapper.append($row);
            } else {
                $wrapper.append($('<p class="text-warning text-center">').text('This type has no dynamic fields defined.'));
            }
        });

        if ($('#type_id').val()) {
            $('#type_id').trigger('change');
        }
    });
</script>
@endsection
