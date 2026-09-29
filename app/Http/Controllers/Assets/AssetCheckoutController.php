<?php

namespace App\Http\Controllers\Assets;

use App\Actions\Acceptances\CreateCheckoutAcceptanceAction;
use App\Exceptions\CheckoutNotAllowed;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetCheckoutRequest;
use App\Http\Traits\CheckInOutTrait;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AssetCheckoutController extends Controller
{
    use CheckInOutTrait;

    /**
     * Returns a view that presents a form to check an asset out to a
     * user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @param  int  $assetId
     *
     * @since [v1.0]
     *
     * @return View
     */
    public function create(Asset $asset): View|RedirectResponse
    {

        $this->authorize('checkout', $asset);

        if (! $asset->model) {
            return redirect()->route('hardware.show', $asset)
                ->with('error', trans('admin/hardware/general.model_invalid_fix'));
        }

        // Invoke the validation to see if the audit will complete successfully
        $asset->setRules($asset->getRules() + $asset->customFieldValidationRules());

        if ($asset->isInvalid()) {
            // Also flash the specific validation messages via
            // multi_error_messages so they surface in the top alert
            // on the edit page. See the matching block in
            // AssetCheckinController::create() for the reasoning.
            return redirect()->route('hardware.edit', $asset)
                ->withErrors($asset->getErrors())
                ->with('multi_error_messages', $asset->getErrors()->all());
        }

        if ($asset->availableForCheckout()) {
            return view('hardware/checkout', compact('asset'))
                ->with('statusLabel_list', Helper::deployableStatusLabelList())
                ->with('table_name', 'Assets')
                ->with('item', $asset);
        }

        return redirect()->route('hardware.index')
            ->with('error', trans('admin/hardware/message.checkout.not_available'));
    }

    /**
     * Validate and process the form data to check out an asset to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     *
     * @since [v1.0]
     */
    public function store(AssetCheckoutRequest $request, $assetId): RedirectResponse
    {

        try {
            // Check if the asset exists
            if (! $asset = Asset::find($assetId)) {
                return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.does_not_exist'));
            } elseif (! $asset->availableForCheckout()) {
                return redirect()->route('hardware.index')->with('error', trans('admin/hardware/message.checkout.not_available'));
            }
            $this->authorize('checkout', $asset);

            if (! $asset->model) {
                return redirect()->route('hardware.show', $asset)->with('error', trans('admin/hardware/general.model_invalid_fix'));
            }

            $admin = auth()->user();

            $target = $this->determineCheckoutTarget();

            $asset = $this->updateAssetLocation($asset, $target);

            $checkout_at = date('Y-m-d H:i:s');
            if (($request->filled('checkout_at')) && ($request->input('checkout_at') != date('Y-m-d'))) {
                $checkout_at = $request->input('checkout_at');
            }

            $expected_checkin = '';
            if ($request->filled('expected_checkin')) {
                $expected_checkin = $request->input('expected_checkin');
            }

            if ($request->filled('status_id')) {
                $asset->status_id = $request->input('status_id');
            }

            // Two-way toggle: checked = requestable, unchecked (or absent) =
            // not. The form pre-populates the checkbox with the asset's current
            // state so users can flip either direction (e.g. mark "no longer
            // requestable" during checkout because the item is now assigned).
            $asset->requestable = $request->boolean('requestable');

            if (! empty($asset->licenseseats->all())) {
                if (request('checkout_to_type') == 'user') {
                    foreach ($asset->licenseseats as $seat) {
                        $seat->assigned_to = $target->id;
                        $seat->save();
                    }
                }
            }

            // Add any custom fields that should be included in the checkout
            $asset->customFieldsForCheckinCheckout('display_checkout');

            if (! $asset->canCheckoutTo($target)) {
                $targetType = match (class_basename($target)) {
                    'User' => trans('general.user'),
                    'Location' => trans('general.location'),
                    default => trans('general.asset'),
                };

                return redirect()->route('hardware.checkout.create', $asset)->with('error', trans('general.error_checkout_company_mismatch', [
                    'item' => trans('general.asset').' "'.$asset->display_name.'"',
                    'item_company' => $asset->company?->name ?? trans('general.unassigned'),
                    'target' => $targetType.' "'.($target->name ?? $target->username ?? $target->id).'"',
                ]));
            }

            session()->put([
                'redirect_option' => $request->input('redirect_option'),
                'checkout_to_type' => $request->input('checkout_to_type'),
                'sign_in_place' => $request->boolean('sign_in_place'),
            ]);

            // Concurrency guard. availableForCheckout() above ran on an
            // unlocked read, so two simultaneous form submits can both
            // observe the asset as available and both proceed through
            // checkOut(), producing duplicate checkout-history rows and
            // double-incrementing checkout_counter on a single-assignment
            // asset. Re-fetch the row under lockForUpdate INSIDE a
            // transaction and re-check availability against the locked
            // snapshot; the second request blocks until the first commits
            // and then sees the asset as no longer available. Mirrors the
            // pattern in Api\AssetsController::checkout and
            // ConsumablesController::store (GHSA-x4g2-87xc-m5jm).
            $checkedOut = DB::transaction(function () use ($asset, $target, $admin, $checkout_at, $expected_checkin, $request): bool {
                $locked = Asset::whereKey($asset->id)->lockForUpdate()->first();
                if (! $locked || ! $locked->availableForCheckout()) {
                    return false;
                }

                return (bool) $asset->checkOut($target, $admin, $checkout_at, $expected_checkin, $request->input('note'), $request->input('name'), null, $request->boolean('sign_in_place'));
            });

            if ($checkedOut) {
                $log = \App\Models\Actionlog::where('item_id', $asset->id)
                    ->where('item_type', \App\Models\Asset::class)
                    ->where('action_type', 'checkout')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($log) {
                    $log->driver_name = $request->input('driver_name');
                    $log->vehicle_number = $request->input('vehicle_number');
                    $log->contract_po_number = $request->input('contract_po_number');
                    $log->save();
                }

                // When sign_in_place is requested and the target is a user, redirect to the
                // acceptance/signature page so the user can sign in person. The signature is
                // attributed to the target user, not the admin.

                if ($request->boolean('sign_in_place') && $target instanceof User) {
                    $acceptance = CheckoutAcceptance::where('checkoutable_type', Asset::class)
                        ->where('checkoutable_id', $asset->id)
                        ->where('assigned_to_id', $target->id)
                        ->pending()
                        ->latest()
                        ->first();

                    // If requireAcceptance() is false the listener won't have created one; create it now.
                    if (! $acceptance) {
                        $acceptance = CreateCheckoutAcceptanceAction::run($asset, $target);
                    }

                    session([
                        'sign_in_place_acceptance_id' => $acceptance->id,
                        'sign_in_place_item_id' => $asset->id,
                        'sign_in_place_resource_type' => 'Assets',
                    ]);

return redirect()->route('account.accept.item', $acceptance->id)
                        ->with('success', trans('admin/hardware/message.checkout.success'))
                        ->with('load_note_url', route('hardware.loadnote', $log->id));
                }

                return Helper::getRedirectOption($request, $asset->id, 'Assets')
                    ->with('success', trans('admin/hardware/message.checkout.success'))
                    ->with('load_note_url', route('hardware.loadnote', $log->id));
            }

            // Redirect back to the checkout form with the specific
            // validation messages surfaced via multi_error_messages
            // (replaces the previous stringified MessageBag concat).
            return redirect()->route('hardware.checkout.create', $asset)
                ->with('error', trans('admin/hardware/message.checkout.error'))
                ->with('multi_error_messages', $asset->getErrors()->all());
        } catch (ModelNotFoundException $e) {
            return redirect()->back()
                ->with('error', trans('admin/hardware/message.checkout.error'))
                ->withErrors($asset->getErrors())
                ->with('multi_error_messages', $asset->getErrors()->all());
        } catch (CheckoutNotAllowed $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
public function printLoadNote($logId)
    {
        // تم إزالة 'item.model' و 'item.company' و 'location' لأنها قد لا تتوفر في كل أنواع العناصر (مثل Consumables)
        // تم ترك 'target.department' لأن الهدف (الموظف) لديه قسم دائماً.
        $log = \App\Models\Actionlog::with(['item', 'target.department'])->findOrFail($logId);

        // الخدعة: جلب كل حركات الإخراج التي تمت لنفس الشخص في نفس اللحظة (Bulk)
        // قمنا أيضاً بإزالة 'item.model' من هنا لنفس السبب.
        $allLogs = \App\Models\Actionlog::with(['item'])
            ->where('target_id', $log->target_id)
            ->where('target_type', $log->target_type)
            ->where('action_type', 'checkout')
            ->where('created_at', $log->created_at) // نفس وقت التسليم بالضبط
            ->get();

        $issuer = \App\Models\User::with('department')->find($log->created_by);

        return view('custom.load-note', compact('log', 'allLogs', 'issuer'));
    }
public function printLatestLoadNote($assetId)
    {
        // سحب السجل الأساسي الذي ضغطنا لطباعته (بدون إجبار تحميل الموديل هنا لتفادي الخطأ)
        $log = \App\Models\Actionlog::with(['item', 'target.department', 'location'])
            ->where('item_id', $assetId)
            ->where('item_type', \App\Models\Asset::class)
            ->where('action_type', 'checkout')
            ->orderBy('id', 'desc')
            ->firstOrFail();

        // سحب كل السجلات التي تم تسليمها لنفس الموظف في نفس اللحظة (مذكرة التجميع)
        $allLogsRaw = \App\Models\Actionlog::with(['item'])
            ->where('target_id', $log->target_id)
            ->where('target_type', $log->target_type)
            ->where('action_type', 'checkout')
            ->where('created_at', $log->created_at)
            ->get();

        // التحميل الذكي (Eager Loading): نحمل الـ Model فقط إذا كانت المادة Asset لتفادي خطأ الإكسسوارات
        $allLogs = $allLogsRaw->map(function ($singleLog) {
            if ($singleLog->item_type === \App\Models\Asset::class && $singleLog->item) {
                // تحميل الموديل والشركة فقط للأجهزة
                $singleLog->item->load(['model', 'company']);
            }
            return $singleLog;
        });

        // إذا كان الجهاز الأساسي نفسه Asset، نحمل موديله للترويسة (إن لزم)
        if ($log->item_type === \App\Models\Asset::class && $log->item) {
            $log->item->load(['model', 'company']);
        }

        $issuer = \App\Models\User::with('department')->find($log->created_by);

        return view('custom.load-note', compact('log', 'allLogs', 'issuer'));
    }

    public function printCheckinReceipt($logId)
    {
        $log = \App\Models\Actionlog::with(['item.model', 'item.location', 'target.department'])->findOrFail($logId);
        
        $allLogs = \App\Models\Actionlog::with(['item.model', 'item.location'])
            ->where('target_id', $log->target_id)
            ->where('target_type', $log->target_type)
            ->where('action_type', 'checkin from')
            ->where('created_at', $log->created_at)
            ->get();
            
        $receiver = \App\Models\User::find($log->created_by);
        
        return view('custom.checkin-receipt', compact('log', 'allLogs', 'receiver'));
    }
}
