@extends('layouts/default')

@section('title', 'SharePoint Integration Settings')

@section('content')

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fab fa-microsoft text-info"></i> Microsoft SharePoint Integration</h3>
            </div>
            
            <form action="{{ route('settings.sharepoint.save') }}" method="POST">
                @csrf
                <div class="box-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Choose how Snipe-IT should send Signed Load Notes to SharePoint.
                    </div>

                    <!-- Integration Method Selector -->
                    <div class="form-group">
                        <label>Integration Method <span class="text-danger">*</span></label>
                        <select class="form-control select2" name="sharepoint_method" id="sharepoint_method">
                            <option value="email" {{ old('sharepoint_method', $setting->sharepoint_method) == 'email' ? 'selected' : '' }}>Power Automate (Email-to-SharePoint)</option>
                            <option value="api" {{ old('sharepoint_method', $setting->sharepoint_method) == 'api' ? 'selected' : '' }}>Direct API (App Registration)</option>
                        </select>
                    </div>

                    <hr>

                    <!-- Email Method Fields -->
                    <div id="method_email_fields" style="display: none;">
                        <h4 class="text-primary">Power Automate Configuration</h4>
                        <p class="text-muted">Snipe-IT will securely email the signed documents to this address. Configure a Power Automate flow on this mailbox to save attachments to SharePoint.</p>
                        
                        <div class="form-group {{ $errors->has('sharepoint_archive_email') ? 'has-error' : '' }}">
                            <label>Target Archive Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="sharepoint_archive_email" value="{{ old('sharepoint_archive_email', $setting->sharepoint_archive_email) }}" placeholder="e.g. archive@unrwa.org">
                        </div>
                    </div>

                    <!-- API Method Fields -->
                    <div id="method_api_fields" style="display: none;">
                        <h4 class="text-primary">Graph API Configuration (App Registration)</h4>
                        <p class="text-muted">Use Microsoft Entra ID App credentials for direct upload.</p>

                        <div class="form-group {{ $errors->has('sharepoint_tenant_id') ? 'has-error' : '' }}">
                            <label>Directory (tenant) ID</label>
                            <input type="text" class="form-control" name="sharepoint_tenant_id" value="{{ old('sharepoint_tenant_id', $setting->sharepoint_tenant_id) }}" placeholder="e.g. 8x5x9x...">
                        </div>

                        <div class="form-group {{ $errors->has('sharepoint_client_id') ? 'has-error' : '' }}">
                            <label>Application (client) ID</label>
                            <input type="text" class="form-control" name="sharepoint_client_id" value="{{ old('sharepoint_client_id', $setting->sharepoint_client_id) }}" placeholder="e.g. 1a2b3c...">
                        </div>

                        <div class="form-group {{ $errors->has('sharepoint_client_secret') ? 'has-error' : '' }}">
                            <label>Client Secret Value</label>
                            <input type="password" class="form-control" name="sharepoint_client_secret" placeholder="{{ $setting->sharepoint_client_secret ? 'Enter a new secret to update, or leave blank to keep current' : 'Client Secret' }}">
                        </div>

                        <div class="form-group {{ $errors->has('sharepoint_site_id') ? 'has-error' : '' }}">
                            <label>SharePoint Site ID</label>
                            <input type="text" class="form-control" name="sharepoint_site_id" value="{{ old('sharepoint_site_id', $setting->sharepoint_site_id) }}" placeholder="e.g. contoso.sharepoint.com,xxx,yyy">
                        </div>

                        <div class="form-group {{ $errors->has('sharepoint_drive_id') ? 'has-error' : '' }}">
                            <label>Document Library (Drive) ID</label>
                            <input type="text" class="form-control" name="sharepoint_drive_id" value="{{ old('sharepoint_drive_id', $setting->sharepoint_drive_id) }}" placeholder="e.g. b!xxx...">
                        </div>
                    </div>

                </div>
                
                <div class="box-footer text-right">
                    <a href="{{ route('settings.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        function toggleFields() {
            var method = $('#sharepoint_method').val();
            if (method === 'email') {
                $('#method_api_fields').slideUp();
                $('#method_email_fields').slideDown();
            } else {
                $('#method_email_fields').slideUp();
                $('#method_api_fields').slideDown();
            }
        }

        // Run on load
        toggleFields();

        // Run on change
        $('#sharepoint_method').change(function() {
            toggleFields();
        });
    });
</script>
@endsection