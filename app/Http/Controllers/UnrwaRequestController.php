<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\AssetModel;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\UnrwaAssetRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnrwaRequestController extends Controller
{
public function index()
    {
        $user = Auth::user();

        $requestStatus = request()->query('status');
        $requestsQuery = UnrwaAssetRequest::with(['user', 'category', 'assetModel'])
            ->withCount('fulfilledAssets')
            ->orderByRaw("FIELD(status, 'Pending', 'Fulfilled', 'Canceled') ASC")
            ->orderByRaw("FIELD(request_tier, 'Manager/Admin', 'User') ASC")
            ->orderBy('created_at', 'desc');

        if (in_array($requestStatus, ['Pending', 'Fulfilled', 'Canceled'], true)) {
            $requestsQuery->where('status', $requestStatus);
        }

        $requests = $requestsQuery->get();

        $categories = Category::where('category_type', 'asset')->orderBy('name', 'asc')->get();
        $models = AssetModel::orderBy('name', 'asc')->get();

        $batchRequestLinks = DB::table('unrwa_asset_request_assets')
            ->whereIn('request_id', $requests->pluck('id'))
            ->whereNotNull('batch_id')
            ->select('request_id', 'batch_id')
            ->distinct()
            ->get()
            ->groupBy('request_id');

        $groupColors = ['#006d77', '#6b4c9a', '#a24b00', '#236b45', '#a83246', '#365f91'];
        $batchGroups = DB::table('unrwa_asset_request_assets')
            ->whereIn('batch_id', $batchRequestLinks->flatten(1)->pluck('batch_id')->unique()->values())
            ->whereNotNull('batch_id')
            ->select(
                'batch_id',
                DB::raw('COUNT(DISTINCT request_id) as request_count'),
                DB::raw('COUNT(DISTINCT asset_id) as asset_count'),
                DB::raw('MIN(created_at) as created_at')
            )
            ->groupBy('batch_id')
            ->orderByDesc(DB::raw('MIN(created_at)'))
            ->get()
            ->map(function ($batch) use ($groupColors) {
                $batch->display_id = strtoupper(substr(str_replace('-', '', $batch->batch_id), 0, 8));
                $batch->color = $groupColors[abs(crc32($batch->batch_id)) % count($groupColors)];

                return $batch;
            })
            ->keyBy('batch_id');

        foreach ($requests as $req) {
            $req->fulfilled_quantity = $req->status === 'Fulfilled'
                ? $req->quantity
                : min($req->quantity, $req->fulfilled_assets_count);
            $req->remaining_quantity = max(0, $req->quantity - $req->fulfilled_quantity);

            if ($req->status == 'Pending') {
                $req->available_count = \App\Models\Asset::whereNull('assigned_to')
                    ->whereHas('status', function($q) {
                        $q->where('deployable', 1)->where('pending', 0)->where('archived', 0);
                    })
                    ->whereHas('model', function($q) use ($req) {
                        $q->where('category_id', $req->category_id);
                        if ($req->model_id) { $q->where('id', $req->model_id); }
                    })->count();
            } else {
                $req->available_count = '-';
            }

            $req->batch_groups = collect($batchRequestLinks->get($req->id, []))
                ->map(fn ($link) => $batchGroups->get($link->batch_id))
                ->filter()
                ->unique('batch_id')
                ->values();
            $req->batch_search_term = $req->batch_groups
                ->map(fn ($batch) => 'batch '.$batch->display_id)
                ->implode(' ');

            if ($req->status == 'Fulfilled' && $req->fulfilled_at && $req->fulfilled_asset_id) {
                $log = \App\Models\Actionlog::where('item_id', $req->fulfilled_asset_id)
                    ->where('item_type', \App\Models\Asset::class)
                    ->where('action_type', 'checkout')
                    ->orderBy('id', 'desc')
                    ->first();
                    
                $req->log_id = $log ? $log->id : null;
            }
        }

        return view('hardware.unrwa-requested', compact('requests', 'categories', 'models'));
    }

    public function show(int $id): View
    {
        $assetRequest = UnrwaAssetRequest::with([
            'user.department',
            'user.location',
            'category',
            'assetModel',
            'parentRequest',
            'followUpRequests.category',
            'followUpRequests.assetModel',
            'fulfilledAssets.model',
            'fulfilledAssets.status',
            'fulfilledAssets.location',
            'fulfilledAsset.model',
            'fulfilledAsset.status',
            'fulfilledAsset.location',
        ])->withCount('fulfilledAssets')->findOrFail($id);

        $assets = $assetRequest->fulfilledAssets;
        if ($assetRequest->fulfilledAsset && ! $assets->contains('id', $assetRequest->fulfilledAsset->id)) {
            $assets->push($assetRequest->fulfilledAsset);
        }

        $checkoutLogs = Actionlog::with(['adminuser', 'location'])
            ->where('item_type', Asset::class)
            ->where('action_type', 'checkout')
            ->whereIn('item_id', $assets->pluck('id'))
            ->orderByDesc('created_at')
            ->get();
        $logsById = $checkoutLogs->keyBy('id');
        $latestLogsByAsset = $checkoutLogs->groupBy('item_id')->map->first();
        $batchColors = ['#006d77', '#6b4c9a', '#a24b00', '#236b45', '#a83246', '#365f91'];

        foreach ($assets as $asset) {
            $checkoutLogId = $asset->pivot?->checkout_action_log_id;
            $asset->setRelation('checkoutLog', $checkoutLogId
                ? $logsById->get($checkoutLogId)
                : $latestLogsByAsset->get($asset->id));

            $batchId = $asset->pivot?->batch_id;
            $asset->batchDisplayId = $batchId ? strtoupper(substr(str_replace('-', '', $batchId), 0, 8)) : null;
            $asset->batchColor = $batchId ? $batchColors[abs(crc32($batchId)) % count($batchColors)] : null;
        }

        $fulfilledQuantity = $assetRequest->status === 'Fulfilled'
            ? $assetRequest->quantity
            : min($assetRequest->quantity, $assets->count());
        $remainingQuantity = max(0, $assetRequest->quantity - $fulfilledQuantity);

        return view('hardware.unrwa-request-detail', compact(
            'assetRequest',
            'assets',
            'fulfilledQuantity',
            'remainingQuantity'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $categories = Category::where('category_type', 'asset')->orderBy('name', 'asc')->get();
        $isManagerOrAdmin = $user->isSuperUser() || $user->hasAccess('admin');

        $models = collect();
        if ($isManagerOrAdmin) {
            $models = AssetModel::orderBy('name', 'asc')->get()->map(function($model) {
                $model->available_count = \App\Models\Asset::where('model_id', $model->id)
                    ->whereNull('assigned_to')
                    ->whereHas('status', function($q) {
                        $q->where('deployable', 1)->where('pending', 0)->where('archived', 0);
                    })->count();
                return $model;
            });
        }

        $requestHistory = UnrwaAssetRequest::with(['category', 'assetModel', 'parentRequest'])
            ->withCount('fulfilledAssets')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'historyPage');

        return view('hardware.unrwa-request', compact('categories', 'models', 'isManagerOrAdmin', 'requestHistory'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isManagerOrAdmin = $user->isSuperUser() || $user->hasAccess('admin');

        $request->validate([
            'category_id'   => 'required|array',
            'category_id.*' => 'required|exists:categories,id',
            'model_id'      => 'nullable|array',
            'quantity'      => 'required|array',
            'quantity.*'    => 'required|integer|min:1|max:50',
            'notes'         => 'nullable|array',
        ]);

        $requestTier = $isManagerOrAdmin ? 'Manager/Admin' : 'User';

        foreach ($request->category_id as $index => $cat_id) {
            $assetRequest = new UnrwaAssetRequest();
            $assetRequest->user_id = $user->id;
            $assetRequest->category_id = $cat_id;
            $assetRequest->quantity = $request->quantity[$index];
            $assetRequest->request_tier = $requestTier;
            $assetRequest->notes = $request->notes[$index] ?? null;
            $assetRequest->status = 'Pending';

            if ($isManagerOrAdmin && !empty($request->model_id[$index])) {
                $assetRequest->model_id = $request->model_id[$index];
            }

            $assetRequest->save();
        }

        return redirect()->back()->with('success', 'Your requests have been submitted successfully!');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'quantity'    => 'required|integer|min:1',
        ]);

        $assetRequest = UnrwaAssetRequest::findOrFail($id);
        $assetRequest->category_id = $request->category_id;
        $assetRequest->model_id = $request->model_id ?: null;
        $assetRequest->quantity = $request->quantity;
        if ($request->filled('notes')) {
            $assetRequest->notes = $request->notes;
        }
        $assetRequest->save();

        return redirect()->back()->with('success', 'Request updated successfully.');
    }

    public function cancel($id)
    {
        $user = Auth::user();

        $assetRequest = UnrwaAssetRequest::findOrFail($id);
        $assetRequest->status = 'Canceled';
        $assetRequest->save();

        return redirect()->back()->with('success', 'Request has been canceled/rejected.');
    }

    public function fulfill($id)
    {
        $user = Auth::user();

        $request = UnrwaAssetRequest::with(['user', 'category', 'assetModel'])
            ->withCount('fulfilledAssets')
            ->findOrFail($id);

        if ($request->status !== 'Pending') {
            return redirect()->route('unrwa.requests.manage')->with('error', 'This request is no longer pending.');
        }

        $request->remaining_quantity = max(0, $request->quantity - $request->fulfilled_assets_count);

        if ($request->remaining_quantity === 0) {
            return redirect()->route('unrwa.requests.manage')->with('success', 'This request has already been fulfilled.');
        }

        $query = \App\Models\Asset::with(['model', 'location', 'status'])
            ->whereNull('assigned_to')
            ->whereHas('status', function($q) {
                $q->where('deployable', 1)->where('pending', 0)->where('archived', 0);
            })
            ->whereHas('model', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
                if ($request->model_id) { $q->where('id', $request->model_id); }
            });

        $availableAssets = $query->get();

        return view('hardware.unrwa-fulfill', compact('request', 'availableAssets'));
    }
    public function bulkFulfill(Request $request)
    {
        $user = Auth::user();

        $requestIds = array_values(array_unique($request->input('request_ids', [])));
        if (!$requestIds || count($requestIds) == 0) {
            return redirect()->back()->with('error', 'Please select at least one request.');
        }

        $requests = UnrwaAssetRequest::with(['user', 'category', 'assetModel'])
            ->withCount('fulfilledAssets')
            ->whereIn('id', $requestIds)
            ->where('status', 'Pending')
            ->get();

        if ($requests->isEmpty() || $requests->count() !== count($requestIds)) {
            return redirect()->back()->with('error', 'One or more selected requests are no longer pending. Refresh the request list and try again.');
        }

        $userIds = $requests->pluck('user_id')->unique();
        if ($userIds->count() > 1) {
            return redirect()->back()->with('error', 'You can only combine requests belonging to the SAME user.');
        }

        $targetUser = $requests->first()->user;

        foreach ($requests as $req) {
            $req->remaining_quantity = max(0, $req->quantity - $req->fulfilled_assets_count);
            $req->availableAssets = \App\Models\Asset::with(['model', 'location', 'status'])
                ->whereNull('assigned_to')
                ->whereHas('status', function($q) {
                    $q->where('deployable', 1)->where('pending', 0)->where('archived', 0);
                })
                ->whereHas('model', function($q) use ($req) {
                    $q->where('category_id', $req->category_id);
                    if ($req->model_id) { $q->where('id', $req->model_id); }
                })->get();
        }

        return view('hardware.unrwa-bulk-fulfill', compact('requests', 'targetUser'));
    }
}
