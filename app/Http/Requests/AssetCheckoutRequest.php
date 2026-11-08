<?php

namespace App\Http\Requests;

use App\Models\Setting;

class AssetCheckoutRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
public function rules()
    {
        $settings = Setting::getSettings();

        $rules = [
            'assigned_user' => 'numeric|nullable|required_without_all:assigned_asset,assigned_location|exists_undeleted:users,id',
            
            // ====== UNRWA Customization: Require Destination Location when checking out to a User ======
            'destination_location' => 'nullable|numeric|exists_undeleted:locations,id|required_with:assigned_user',
            // ===========================================================================================
            
            'assigned_asset' => 'numeric|nullable|required_without_all:assigned_user,assigned_location|exists_undeleted:assets,id',
            'assigned_location' => 'numeric|nullable|required_without_all:assigned_user,assigned_asset|exists_undeleted:locations,id',
            'status_id' => 'nullable|exists:status_labels,id,deployable,1',
            'checkout_to_type' => 'required|in:asset,location,user',
            'checkout_at' => [
                'nullable',
                'date',
            ],
            'expected_checkin' => [
                'nullable',
                'date',
            ],
            'requestable' => 'nullable|boolean',
        ];

        if ($settings->require_checkinout_notes) {
            $rules['note'] = 'required|string';
        }

        return $rules;
    }
}
