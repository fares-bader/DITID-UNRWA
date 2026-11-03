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
                    
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Item Name (Identifier) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. HQ Main Fiber Line" required>
                        </div>
                        
                        <div class="col-md-6 form-group">
                            <label>Configuration Type <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="type_id" id="type_id" required>
                                <option value="">-- Select Type --</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>
                    <div id="dynamic-fields-wrapper" style="background: #fdfdfd; padding: 15px; border: 1px dashed #ccc; border-radius: 5px;">
                        <p class="text-muted text-center" id="dynamic-placeholder"><i class="fas fa-magic"></i> Select a Configuration Type to load fields...</p>
                    </div>
                    <hr>

                    <div class="form-group">
                        <label>General Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Any additional documentation..."></textarea>
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

        $('#type_id').on('change', function() {
            var selectedId = $(this).val();
            var $wrapper = $('#dynamic-fields-wrapper');
            
            $wrapper.empty();

            if(!selectedId) {
                $wrapper.html('<p class="text-muted text-center"><i class="fas fa-magic"></i> Select a Configuration Type to load fields...</p>');
                return;
            }

            var selectedType = cmdbTypes.find(t => t.id == selectedId);
            
            if(selectedType && selectedType.schema_definition && selectedType.schema_definition.length > 0) {
                var html = '<div class="row">';
                
                selectedType.schema_definition.forEach(function(field) {
                    html += '<div class="col-md-6 form-group">';
                    html += '<label class="text-primary">' + field.name + '</label>';
                    
                    var inputName = 'dynamic_fields[' + field.name + ']';
                    
                    if(field.type === 'textarea') {
                        html += '<textarea class="form-control" name="' + inputName + '" rows="2"></textarea>';
                    } else if(field.type === 'date') {
                        html += '<input type="date" class="form-control" name="' + inputName + '">';
                    } else {
                        // text, number, url
                        html += '<input type="' + field.type + '" class="form-control" name="' + inputName + '">';
                    }
                    html += '</div>';
                });
                
                html += '</div>';
                $wrapper.append(html);
            } else {
                $wrapper.html('<p class="text-warning text-center"><i class="fas fa-exclamation-triangle"></i> This type has no dynamic fields defined.</p>');
            }
        });
    });
</script>
@endsection