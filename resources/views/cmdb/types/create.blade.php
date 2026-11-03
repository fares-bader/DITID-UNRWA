@extends('layouts/default')
@section('title', 'Create CMDB Object Type')

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-hammer"></i> Build New CMDB Type</h3>
            </div>
            
            <form action="{{ route('cmdb.types.store') }}" method="POST">
                @csrf
                <div class="box-body">
                    
                    <!-- Basic Info -->
                    <div class="row">
                        <div class="col-md-6 form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                            <label>Type Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. WAN Link, Server, Router" required value="{{ old('name') }}">
                            {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Icon Class (FontAwesome Icon)</label>
                            <input type="text" class="form-control" name="icon" placeholder="fas fa-server" value="{{ old('icon', 'fas fa-server') }}">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Brief description of this type...">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <hr>
                    <h4 class="text-primary"><i class="fas fa-list"></i> Define Custom Fields</h4>
                    <p class="text-muted">What data do you want to store for this object type? (e.g. IP Address, Subnet Mask, VLAN)</p>

                    <!-- Dynamic Fields Container -->
                    <div id="dynamic-fields-container">
                        <div class="row field-row" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px;">
                            <div class="col-md-6 form-group" style="margin-bottom: 0;">
                                <label>Field Name</label>
                                <input type="text" class="form-control" name="field_name[]" placeholder="e.g. IP Address" required>
                            </div>
                            <div class="col-md-5 form-group" style="margin-bottom: 0;">
                                <label>Field Type</label>
                                <select class="form-control" name="field_type[]" required>
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="url">URL</option>
                                </select>
                            </div>
                            <div class="col-md-1 text-center" style="margin-top: 25px;">
                                <button type="button" class="btn btn-danger btn-sm remove-field-btn" style="display:none;" title="Remove Field"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
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
        // Add new field row
        $('#add-field-btn').click(function() {
            var $firstRow = $('.field-row').first();
            var $clone = $firstRow.clone();
            
            $clone.find('input[type="text"]').val('');
            $clone.find('select').val('text');
            $clone.find('.remove-field-btn').show();
            
            $('#dynamic-fields-container').append($clone);
        });

        // Remove field row
        $(document).on('click', '.remove-field-btn', function() {
            $(this).closest('.field-row').remove();
        });
    });
</script>
@endsection