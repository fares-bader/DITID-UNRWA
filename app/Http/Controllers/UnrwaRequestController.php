<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\AssetModel;
use App\Models\UnrwaAssetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnrwaRequestController extends Controller
{
public function index()
    {
        $user = Auth::user();
        if (!$user->isSuperUser() && !$user->hasAccess('admin')) { abort(403, 'Unauthorized access.'); }

        $requests = UnrwaAssetRequest::with(['user', 'category', 'assetModel'])
            ->orderByRaw("FIELD(status, 'Pending', 'Fulfilled', 'Canceled') ASC")
            ->orderByRaw("FIELD(request_tier, 'Manager/Admin', 'User') ASC")
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = Category::where('category_type', 'asset')->orderBy('name', 'asc')->get();
        $models = AssetModel::orderBy('name', 'asc')->get();

        $groupColors = ['bg-purple', 'bg-orange', 'bg-teal', 'bg-maroon', 'bg-navy', 'bg-olive'];
        $timeGroups = [];
        $groupIndex = 0;

        foreach ($requests as $req) {
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

            if ($req->status == 'Fulfilled' && $req->fulfilled_at && $req->fulfilled_asset_id) {
                $timeKey = \Carbon\Carbon::parse($req->fulfilled_at)->format('Y-m-d H:i');
                
                if (!isset($timeGroups[$timeKey])) {
                    $timeGroups[$timeKey] = [
                        'id' => $groupIndex + 1,
                        'color' => $groupColors[$groupIndex % count($groupColors)]
                    ];
                    $groupIndex++;
                }
                $req->batch_info = $timeGroups[$timeKey];

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

        return view('hardware.unrwa-request', compact('categories', 'models', 'isManagerOrAdmin'));
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
        if (!$user->isSuperUser() && !$user->hasAccess('admin')) { abort(403); }

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
        if (!$user->isSuperUser() && !$user->hasAccess('admin')) { abort(403); }

        $assetRequest = UnrwaAssetRequest::findOrFail($id);
        $assetRequest->status = 'Canceled';
        $assetRequest->save();

        return redirect()->back()->with('success', 'Request has been canceled/rejected.');
    }

    public function fulfill($id)
    {
        $user = Auth::user();
        if (!$user->isSuperUser() && !$user->hasAccess('admin')) { abort(403); }

        $request = UnrwaAssetRequest::with(['user', 'category', 'assetModel'])->findOrFail($id);

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
        if (!$user->isSuperUser() && !$user->hasAccess('admin')) { abort(403); }

        $requestIds = $request->input('request_ids');
        if (!$requestIds || count($requestIds) == 0) {
            return redirect()->back()->with('error', 'Please select at least one request.');
        }

        $requests = UnrwaAssetRequest::with(['user', 'category', 'assetModel'])->whereIn('id', $requestIds)->get();

        $userIds = $requests->pluck('user_id')->unique();
        if ($userIds->count() > 1) {
            return redirect()->back()->with('error', 'You can only combine requests belonging to the SAME user.');
        }

        $targetUser = $requests->first()->user;

        foreach ($requests as $req) {
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