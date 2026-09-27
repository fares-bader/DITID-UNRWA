<?php

namespace App\Http\Controllers;

use App\Models\Actionlog;
use App\Models\User;
use Illuminate\Http\Request;

class LoadNotesController extends Controller
{
    public function index()
    {
        $groupedLogs = Actionlog::with(['item', 'target', 'user'])
            ->whereIn('action_type', ['checkout', 'checkin from'])
            ->where('target_type', User::class)
            ->orderBy('created_at', 'desc')
            ->limit(2000)
            ->get()
            ->groupBy(function($log) {
                return \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') . '|' . $log->target_id . '|' . $log->action_type;
            });

        return view('custom.load-notes-index', compact('groupedLogs'));
    }
}