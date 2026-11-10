<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\Setting;
use App\Models\UnrwaAssetRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

/**
 * This controller handles all actions related to the Admin Dashboard
 * for the Snipe-IT Asset Management application.
 *
 * @author A. Gianotto <snipe@snipe.net>
 *
 * @version v1.0
 */
class DashboardController extends Controller
{
    /**
     * Check authorization and display admin dashboard, otherwise display
     * the user's checked-out assets.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v1.0]
     */
    public function index(): View|RedirectResponse
    {
        // Show the page
        if (auth()->user()->hasAccess('admin')) {
            $asset_stats = null;

            $counts['asset'] = Asset::count();
            $counts['accessory'] = Accessory::count();
            $counts['license'] = License::assetcount();
            $counts['consumable'] = Consumable::count();
            $counts['component'] = Component::count();
            $counts['user'] = Company::scopeCompanyables(auth()->user())->count();
            $counts['grand_total'] = $counts['asset'] + $counts['accessory'] + $counts['license'] + $counts['consumable'];

            $canViewAssets = Gate::allows('index', Asset::class);
            $canManageRequests = Gate::allows('unrwa_requests.manage');
            $canViewDocumentCenter = Gate::allows('document_center.view');
            $canCheckinAssets = Gate::allows('checkin', Asset::class);

            $counts['ready_to_deploy'] = $canViewAssets ? Asset::RTD()->count() : null;
            $counts['due_for_checkin'] = $canCheckinAssets
                ? Asset::DueOrOverdueForCheckin(Setting::getSettings())->count()
                : null;

            $counts['pending_requests'] = $canManageRequests
                ? UnrwaAssetRequest::where('status', 'Pending')->count()
                : null;
            $counts['recent_deliveries'] = null;

            if ($canViewDocumentCenter) {
                $counts['recent_deliveries'] = Actionlog::query()
                    ->where('action_type', 'checkout')
                    ->where('target_type', User::class)
                    ->where('created_at', '>=', now()->subDays(30)->startOfDay())
                    ->count();
            }

            if ((! file_exists(storage_path().'/oauth-private.key')) || (! file_exists(storage_path().'/oauth-public.key'))) {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('passport:install', ['--no-interaction' => true]);
            }

            return view('dashboard')->with('asset_stats', $asset_stats)->with('counts', $counts)->with(compact(
                'canCheckinAssets',
                'canManageRequests',
                'canViewDocumentCenter',
                'canViewAssets',
            ));
        } else {
            Session::reflash();

            // Redirect to the profile page
            return redirect()->intended('account/view-assets');
        }
    }
}
