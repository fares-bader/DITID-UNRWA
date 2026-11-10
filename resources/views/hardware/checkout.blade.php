@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/hardware/general.checkout') }}
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

            <x-form id="checkout_form" route="{{ url()->current() }}">
                
                <!-- Hidden Fields for Automation & Logic -->
                <input type="hidden" name="unrwa_request_id" value="{{ request('unrwa_req', old('unrwa_request_id')) }}">
                <input type="hidden" name="checkout_to_type" value="user">

                <x-box header="{{ trans('admin/hardware/form.tag') }} {{ $asset->asset_tag }}">

                    <!-- ============================================== -->
                    <!-- SECTION 1: ASSET INFO -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset">
                        <legend class="unrwa-legend"><i class="fas fa-box-open text-primary"></i> Asset Information</legend>

                        @if ($asset->company)
                            <x-form.static :label="trans('general.company')">{!! $asset->company->present()->formattedNameLink !!}</x-form.static>
                        @endif

                        @if ($asset->model->category)
                            <x-form.static :label="trans('general.category')">{!! $asset->model->category->present()->formattedNameLink !!}</x-form.static>
                        @endif

                        <x-form.static :label="trans('admin/hardware/form.model')">
                            @if (($asset->model) && ($asset->model->name))
                                {{ $asset->model->name }}
                            @else
                                <span class="text-danger text-bold"><x-icon type="warning" /> {{ trans('admin/hardware/general.model_invalid') }}</span>
                                {{ trans('admin/hardware/general.model_invalid_fix') }}
                                <a href="{{ route('hardware.edit', $asset->id) }}"><strong>{{ trans('admin/hardware/general.edit') }}</strong></a>
                            @endif
                        </x-form.static>

                        <x-form.row :label="trans('admin/hardware/form.name')" :$item name="name" />

                        <x-form.row :label="trans('admin/hardware/form.status')" name="status_id">
                            <x-slot:input>
                                <x-input.select name="status_id" :options="$statusLabel_list" :selected="$asset->status_id" required style="width: 100%;" aria-label="status_id" />
                            </x-slot:input>
                        </x-form.row>

                        <x-form.checkbox-row name="requestable" :label="trans('admin/hardware/general.requestable')" :item="$asset" data-user-preference-key="snipeit.checkout.requestable_default.{{ auth()->id() ?? 'guest' }}" data-had-old-input="{{ ((bool) old('requestable', false)) || session()->has('_old_input.requestable') ? '1' : '0' }}" />

                        @include('models/custom_fields_form', ['model' => $asset->model, 'show_custom_fields_type' => 'checkout'])
                    </fieldset>


                    <!-- ============================================== -->
                    <!-- SECTION 2: ASSIGNMENT DETAILS -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset">
                        <legend class="unrwa-legend"><i class="fas fa-user-check text-primary"></i> Assignment Details</legend>

                        <x-input.user-select :label="trans('general.user')" name="assigned_user" :selected="old('assigned_user', request('req_user'))" :companyId="$asset->company_id" />
                        
                        <div class="unrwa-highlight">
                            @include ('partials.forms.edit.location-select', [
                                'translated_name' => 'Destination (Exists In)', 
                                'fieldname' => 'destination_location', 
                                'company_id' => $asset->company_id, 
                                'required' => true,
                                'style' => ''
                            ])
                            <div class="form-group">
                                <div class="col-md-9 col-md-offset-3">
                                    <p class="help-block text-warning" style="margin-top: -5px;"><i class="fas fa-exclamation-circle"></i> Mandatory: Physical destination where this asset will reside.</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>


                    <!-- ============================================== -->
                    <!-- SECTION 3: LOGISTICS & DISPATCH -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset">
                        <legend class="unrwa-legend"><i class="fas fa-truck text-primary"></i> Logistics & Dispatch</legend>

                        <x-form.row :label="trans('admin/hardware/form.checkout_date')" name="checkout_at" type="datetimepicker" :item="$item" :default="date('Y-m-d H:i:s')" input_div_class="col-md-5" />

                        <x-form.row :label="trans('admin/hardware/form.expected_checkin')" name="expected_checkin" type="datetimepicker" :item="$item" :default_now="false" input_div_class="col-md-5" />

                        <x-form.row label="Contract / PO Number" name="contract_po_number">
                            <x-slot:input>
                                <div class="input-group col-md-8">
                                    <span class="input-group-addon"><i class="fas fa-file-invoice"></i></span>
                                    <input class="form-control" type="text" name="contract_po_number" id="contract_po_number" value="{{ old('contract_po_number') }}">
                                </div>
                            </x-slot:input>
                        </x-form.row>

                        <x-form.row label="Driver's Name" name="driver_name">
                            <x-slot:input>
                                <div class="input-group col-md-8">
                                    <span class="input-group-addon"><i class="fas fa-id-card"></i></span>
                                    <input class="form-control" type="text" name="driver_name" id="driver_name" value="{{ old('driver_name') }}">
                                </div>
                            </x-slot:input>
                        </x-form.row>

                        <x-form.row label="Wagon / Track No." name="vehicle_number">
                            <x-slot:input>
                                <div class="input-group col-md-8">
                                    <span class="input-group-addon"><i class="fas fa-car"></i></span>
                                    <input class="form-control" type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number') }}">
                                </div>
                            </x-slot:input>
                        </x-form.row>
                    </fieldset>


                    <!-- ============================================== -->
                    <!-- SECTION 4: AUTOMATION & NOTES -->
                    <!-- ============================================== -->
                    <fieldset class="unrwa-fieldset" style="margin-bottom: 0;">
                        <legend class="unrwa-legend"><i class="fas fa-magic text-primary"></i> Automation & Notes</legend>

                        <div class="unrwa-automation">
                            <div class="form-group">
                                <label class="col-md-3 control-label">Email Load Note</label>
                                <div class="col-md-9">
                                    <label style="cursor: pointer; font-weight: 600; margin-top: 5px;">
                                        <input type="checkbox" value="1" name="send_pdf_email" id="send_pdf_email" {{ old('send_pdf_email') ? 'checked' : '' }} class="minimal">
                                        Send PDF Load Note to the user automatically
                                    </label>
                                </div>
                            </div>

                            <div class="form-group {{ $errors->has('cc_email') ? 'has-error' : '' }}">
                                <label for="cc_email" class="col-md-3 control-label">CC Email</label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fas fa-envelope"></i></span>
                                        <input class="form-control" type="email" name="cc_email" id="cc_email" value="{{ old('cc_email') }}" placeholder="security@unrwa.org" @if($errors->has('cc_email')) aria-invalid="true" aria-describedby="cc_email-error cc_email-help" @else aria-describedby="cc_email-help" @endif>
                                    </div>
                                    <x-form.error name="cc_email" />
                                    <p id="cc_email-help" class="help-block" style="font-size: 11.5px; margin-top: 5px;">Optional (e.g. Manager/Security). Works only if checkbox above is checked.</p>
                                </div>
                            </div>
                        </div>

                        <x-form.row :label="trans('general.notes')" name="note">
                            <x-slot:input>
                                <textarea class="col-md-9 form-control" id="note" name="note" rows="3" @required($snipeSettings->require_checkinout_notes)>{{ old('note', $asset->note) }}</textarea>
                            </x-slot:input>
                        </x-form.row>

                        @if ($asset->requireAcceptance() || (string) $snipeSettings->require_accept_signature === '1' || $asset->getEula() || ($snipeSettings->webhook_endpoint != ''))
                            <div class="form-group notification-callout" style="margin-top: 20px;">
                                <div class="col-md-9 col-md-offset-3">
                                    <x-callout type="info" role="status">
                                        @if ($asset->requireAcceptance()) <x-icon type="email" class="fa-fw"/> {{ trans('admin/categories/general.required_acceptance') }} <br> @endif
                                        @if ((string) $snipeSettings->require_accept_signature === '1') <x-icon type="signature" class="fa-fw"/> {{ trans('admin/categories/general.required_signature') }} <br> @endif
                                        @if ($asset->getEula()) <x-icon type="email" class="fa-fw"/> {{ trans('admin/categories/general.required_eula') }} <br> @endif
                                        @if (($asset->model?->category) && ($asset->model->category->checkin_email)) <x-icon type="email" class="fa-fw"/> {{ trans('admin/categories/general.checkin_email_notification') }} <br> @endif
                                        @if ($snipeSettings->webhook_endpoint != '') <i class="fab fa-slack fa-fw" aria-hidden="true"></i> {{ trans('general.webhook_msg_note') }} @endif
                                    </x-callout>
                                </div>

                                @if ($asset->requireAcceptance() || (string) $snipeSettings->require_accept_signature === '1')
                                    <div id="sign_in_place_div" class="col-md-9 col-md-offset-3">
                                        <div style="background: rgba(40, 167, 69, 0.05); border-left: 4px solid #28a745; padding: 15px; border-radius: 0 4px 4px 0;">
                                            <label style="cursor: pointer; margin-bottom: 0;">
                                                <input type="checkbox" value="1" name="sign_in_place" @checked(old('sign_in_place', session('sign_in_place', false))) aria-label="sign_in_place" class="minimal">
                                                <strong style="margin-left: 5px;">{{ trans('general.sign_in_place') }}</strong>
                                            </label>
                                            <p class="help-block" style="margin-top: 5px; margin-bottom: 0; font-size: 11.5px; margin-left: 22px;">{{ trans('general.sign_in_place_help') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </fieldset>

                    <x-slot:customfooter>
                        <x-redirect_submit_options
                            index_route="hardware.index"
                            :button_label="trans('general.checkout')"
                            :disabled_select="!$asset->model"
                            :options="[
                                'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.assets')]),
                                'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.asset')]),
                                'target' => trans('admin/hardware/form.redirect_to_checked_out_to'),
                            ]"
                        />
                    </x-slot:customfooter>

                </x-box>
            </x-form>

        </x-page-column>

        <x-page-column class="col-md-5">
            <livewire:checkout-target-panel type="assets" />
        </x-page-column>

    </x-container>
@stop
