@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/hardware/general.bulk_checkout') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

    <style>
        .input-group { padding-left: 0px !important; }
        .unrwa-fieldset { margin-bottom: 35px; }
        .unrwa-legend { font-size: 16px; font-weight: 600; padding-bottom: 10px; margin-bottom: 20px; }
        .unrwa-legend i { margin-right: 8px; }
        .unrwa-highlight { border-left: 4px solid #f39c12; padding: 15px 15px 1px 15px; margin-bottom: 15px; background: rgba(243, 156, 18, 0.04); border-radius: 0 4px 4px 0; }
        .unrwa-automation { border-left: 4px solid #3c8dbc; padding: 15px 15px 1px 15px; margin-bottom: 15px; background: rgba(60, 141, 188, 0.04); border-radius: 0 4px 4px 0; }
    </style>

    <x-container columns="2">
        <x-page-column class="col-md-7">

            <x-form id="checkout_form" route="{{ url()->current() }}" data-disable-empty-on-submit data-autofocus-select2-search>
                
                <!-- Hidden Fields for Automation & Logic -->
                <input type="hidden" name="checkout_to_type" value="user">

                <x-box header="{{ trans('admin/hardware/form.tag') }} (Bulk Mode)">

                    <!-- ============================================== -->
                    <!-- SECTION 1: ASSETS INFO -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset">
                        <legend class="unrwa-legend"><i class="fas fa-boxes text-primary"></i> Selected Assets</legend>

                        @include ('partials.forms.edit.asset-select', [
                            'translated_name' => trans('general.assets'),
                            'fieldname' => 'selected_assets[]',
                            'multiple' => true,
                            'required' => true,
                            'asset_status_type' => 'RTD',
                            'select_id' => 'assigned_assets_select',
                            'asset_selector_div_id' => 'assets_to_checkout_div',
                            'asset_ids' => old('selected_assets'),
                        ])

                        <x-form.row :label="trans('admin/hardware/form.status')" name="status_id">
                            <x-slot:input>
                                <x-input.select name="status_id" :options="$statusLabel_list" :selected="old('status_id', $status_id ?? null)" style="width: 100%;" aria-label="status_id" />
                            </x-slot:input>
                        </x-form.row>

                        <x-form.checkbox-row name="requestable" :label="trans('admin/hardware/general.requestable')" data-user-preference-key="snipeit.checkout.requestable_default.{{ auth()->id() ?? 'guest' }}" data-had-old-input="{{ ((bool) old('requestable', false)) || session()->has('_old_input.requestable') ? '1' : '0' }}" />
                    </fieldset>


                    <!-- ============================================== -->
                    <!-- SECTION 2: ASSIGNMENT DETAILS -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset">
                        <legend class="unrwa-legend"><i class="fas fa-users text-primary"></i> Assignment Details</legend>

                        <x-input.user-select :label="trans('general.user')" name="assigned_user" :selected="old('assigned_user')" />
                        
                        <div class="unrwa-highlight">
                            @include ('partials.forms.edit.location-select', [
                                'translated_name' => 'Destination (Exists In)', 
                                'fieldname' => 'destination_location', 
                                'required' => true,
                                'style' => ''
                            ])
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <p class="help-block text-warning" style="margin-top: -5px;"><i class="fas fa-exclamation-circle"></i> Mandatory: Physical destination where these assets will reside.</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>


                    <!-- ============================================== -->
                    <!-- SECTION 3: LOGISTICS & DISPATCH -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset">
                        <legend class="unrwa-legend"><i class="fas fa-truck-loading text-primary"></i> Logistics & Dispatch</legend>

                        <x-form.row :label="trans('admin/hardware/form.checkout_date')" name="checkout_at" type="datetimepicker" input_div_class="col-md-5" />

                        <x-form.row :label="trans('admin/hardware/form.expected_checkin')" name="expected_checkin" type="datetimepicker" :default_now="false" input_div_class="col-md-5" />

                        <x-form.row label="SAP PO Number" name="contract_po_number">
                            <x-slot:input>
                                <div class="input-group col-md-8">
                                    <span class="input-group-addon"><i class="fas fa-file-invoice"></i></span>
                                    <x-input.text class="form-control" name="contract_po_number" id="contract_po_number" :value="old('contract_po_number')" />
                                </div>
                            </x-slot:input>
                        </x-form.row>

                        <x-form.row label="Driver's Name" name="driver_name">
                            <x-slot:input>
                                <div class="input-group col-md-8">
                                    <span class="input-group-addon"><i class="fas fa-id-card"></i></span>
                                    <x-input.text class="form-control" name="driver_name" id="driver_name" :value="old('driver_name')" />
                                </div>
                            </x-slot:input>
                        </x-form.row>

                        <x-form.row label="Wagon / Track No." name="vehicle_number">
                            <x-slot:input>
                                <div class="input-group col-md-8">
                                    <span class="input-group-addon"><i class="fas fa-car"></i></span>
                                    <x-input.text class="form-control" name="vehicle_number" id="vehicle_number" :value="old('vehicle_number')" />
                                </div>
                            </x-slot:input>
                        </x-form.row>
                    </fieldset>


                    <!-- ============================================== -->
                    <!-- SECTION 4: AUTOMATION & NOTES -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset" style="margin-bottom: 0;">
                        <legend class="unrwa-legend"><i class="fas fa-mail-bulk text-primary"></i> Automation & Notes</legend>

                        <div class="unrwa-automation">
                            <div class="form-group">
                                <label class="col-md-3 control-label">Email Bulk Note</label>
                                <div class="col-md-9">
                                    <label style="cursor: pointer; font-weight: 600; margin-top: 5px;">
                                        <input type="checkbox" value="1" name="send_pdf_email" id="send_pdf_email" {{ old('send_pdf_email') ? 'checked' : '' }} class="minimal">
                                        Send Bulk PDF Load Note to the user automatically
                                    </label>
                                </div>
                            </div>

                            <div class="form-group {{ $errors->has('cc_email') ? 'has-error' : '' }}">
                                <label for="cc_email" class="col-md-3 control-label">CC Email</label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fas fa-envelope"></i></span>
                                        <input class="form-control" type="email" name="cc_email" id="cc_email" value="{{ old('cc_email') }}" placeholder="security@unrwa.org">
                                    </div>
                                    <p class="help-block" style="font-size: 11.5px; margin-top: 5px;">Optional. Works only if the checkbox above is checked.</p>
                                </div>
                            </div>
                        </div>

                        <x-form.row :label="trans('general.notes')" name="note">
                            <x-slot:input>
                                <textarea class="form-control col-md-9" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                            </x-slot:input>
                        </x-form.row>
                    </fieldset>

                    <x-slot:customfooter>
                        <x-redirect_submit_options
                            index_route="hardware.index"
                            :button_label="trans('general.checkout')"
                            :options="[
                                'bulk_checkout' => trans('admin/hardware/form.redirect_to_bulk_checkout'),
                                'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.assets')]),
                            ]"
                        />
                    </x-slot:customfooter>

                </x-box>

            </x-form>

        </x-page-column>

        <x-page-column class="col-md-5">
            <x-side-panel.removed-assets
                :items="$removed_assets"
                :message="trans('general.assigned_assets_removed')"
            />
            <livewire:checkout-target-panel type="assets" />
        </x-page-column>

    </x-container>
@stop