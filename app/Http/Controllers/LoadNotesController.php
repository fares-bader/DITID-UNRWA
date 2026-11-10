<?php

namespace App\Http\Controllers;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use App\Services\SharePointService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoadNotesController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'document_type' => ['nullable', 'in:load_note,return_receipt'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $actionTypes = match ($filters['document_type'] ?? null) {
            'load_note' => ['checkout'],
            'return_receipt' => ['checkin from'],
            default => ['checkout', 'checkin from'],
        };

        $logsQuery = Actionlog::with(['item', 'target', 'user', 'adminuser', 'location'])
            ->whereIn('action_type', $actionTypes)
            ->where('target_type', User::class);

        if (! empty($filters['target_user_id'])) {
            $logsQuery->where('target_id', $filters['target_user_id']);
        }

        if (! empty($filters['from'])) {
            $logsQuery->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $logsQuery->whereDate('created_at', '<=', $filters['to']);
        }

        $logs = $logsQuery->orderBy('created_at', 'desc')->limit(2000)->get();
        $batchIdsByLogId = DB::table('unrwa_asset_request_assets')
            ->whereIn('checkout_action_log_id', $logs->pluck('id'))
            ->whereNotNull('batch_id')
            ->pluck('batch_id', 'checkout_action_log_id');

        $groupedLogs = $logs->groupBy(function ($log) use ($batchIdsByLogId) {
            $batchId = $batchIdsByLogId->get($log->id);

            if ($batchId && $log->action_type === 'checkout') {
                return 'batch|'.$batchId;
            }

            return \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s').'|'.$log->target_id.'|'.$log->action_type;
        });

        $users = User::query()
            ->whereKey($logs->pluck('target_id')->unique()->values())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'username']);

        $reportStats = [
            'notes' => $groupedLogs->count(),
            'delivery_items' => $logs->where('action_type', 'checkout')->count(),
            'recipients' => $logs->pluck('target_id')->unique()->count(),
        ];

        return view('custom.load-notes-index', compact('groupedLogs', 'users', 'filters', 'reportStats'));
    }

    public function printCombinedLoadNote(Request $request): View
    {
        $validated = $request->validate([
            'selection_mode' => ['required', 'in:selected,all'],
            'target_user_id' => ['required', 'integer'],
            'log_ids' => ['required_if:selection_mode,selected', 'array', 'max:2000'],
            'log_ids.*' => ['integer', 'distinct'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $targetUser = User::query()->findOrFail($validated['target_user_id']);
        $logsQuery = Actionlog::query()
            ->where('target_type', User::class)
            ->where('target_id', $targetUser->id)
            ->where('action_type', 'checkout');

        if (! empty($validated['from'])) {
            $logsQuery->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $logsQuery->whereDate('created_at', '<=', $validated['to']);
        }

        if ($validated['selection_mode'] === 'all') {
            $allLogsRaw = $logsQuery->orderBy('created_at')->orderBy('id')->get();
        } else {
            $selectedIds = collect($validated['log_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $selectedLogs = (clone $logsQuery)->whereIn('id', $selectedIds)->get();

            if ($selectedLogs->count() !== $selectedIds->count()) {
                throw ValidationException::withMessages([
                    'log_ids' => trans('general.load_note_same_recipient_required'),
                ]);
            }

            $expandedLogs = collect();
            foreach ($selectedLogs as $selectedLog) {
                $batchId = DB::table('unrwa_asset_request_assets')
                    ->where('checkout_action_log_id', $selectedLog->id)
                    ->value('batch_id');

                $noteLogsQuery = clone $logsQuery;
                if ($batchId) {
                    $batchLogIds = DB::table('unrwa_asset_request_assets')
                        ->where('batch_id', $batchId)
                        ->pluck('checkout_action_log_id')
                        ->filter();
                    $noteLogsQuery->whereIn('id', $batchLogIds);
                } else {
                    $noteLogsQuery->where('created_at', $selectedLog->created_at);
                }

                $expandedLogs = $expandedLogs->merge($noteLogsQuery->get());
            }

            $allLogsRaw = $expandedLogs->unique('id')->sortBy('created_at')->values();
        }

        if ($allLogsRaw->isEmpty()) {
            throw ValidationException::withMessages([
                'target_user_id' => trans('general.load_note_no_deliveries'),
            ]);
        }

        $allLogs = Actionlog::with(['item', 'target', 'location', 'adminuser'])
            ->whereIn('id', $allLogsRaw->pluck('id'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function ($singleLog) {
                if ($singleLog->item_type === Asset::class && $singleLog->item) {
                    $singleLog->item->load(['model.fieldset.fields', 'company', 'defaultLoc', 'location']);
                }

                return $singleLog;
            });
        $log = $allLogs->first();
        $issuer = null;
        $consolidatedLoadNote = true;
        $consolidatedReference = 'ISD/'.now()->format('y').'/AGG/'.$targetUser->id.'-'.now()->format('YmdHis');
        $consolidatedQrUrl = route('document.center', [
            'target_user_id' => $targetUser->id,
            'document_type' => 'load_note',
        ]);

        return view('custom.load-note', compact(
            'log',
            'allLogs',
            'issuer',
            'consolidatedLoadNote',
            'consolidatedReference',
            'consolidatedQrUrl',
        ));
    }

    // =========================================================
    // UNRWA: SharePoint Upload / Download Logic
    // =========================================================
    
 public function uploadSignedNote(Request $request)
    {
        $request->validate([
            'log_id'      => 'required|exists:action_logs,id',
            'signed_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
        ]);

        try {
            $log = \App\Models\Actionlog::findOrFail($request->log_id);
            $file = $request->file('signed_file');
            $settings = \App\Models\Setting::getSettings(); // جلب الإعدادات
            
            // 1. توليد اسم الملف وحفظه محلياً ليُعرض في النظام
            $prefix = $log->action_type === 'checkout' ? 'ISD' : 'RTN';
            $fileName = $prefix . '_' . $log->id . '_Signed_' . time() . '.' . $file->getClientOriginalExtension();
            
            $file->storeAs('private_uploads/signed_notes', $fileName);
            
            $log->signed_note_path = $fileName;
            $log->save();

            // 2. التحقق من طريقة الأرشفة المفضلة
            if ($settings->sharepoint_method === 'email' && !empty($settings->sharepoint_archive_email)) {
                
                $archiveEmail = $settings->sharepoint_archive_email;
                
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($archiveEmail, $file, $fileName, $prefix, $log) {
                    $message->to($archiveEmail)
                            ->subject("Signed Note - {$prefix}-{$log->id}")
                            ->html("<p>Automated backup of signed document for record ID: {$log->id}</p>")
                            ->attach($file->getRealPath(), [
                                'as' => $fileName,
                                'mime' => $file->getMimeType(),
                            ]);
                });

                return redirect()->back()->with('success', 'Document uploaded and emailed to SharePoint archive successfully!');
            }
            
            // لاحقاً إذا فعلت الـ API ستضع الكود الخاص به هنا ضمن (elseif sharepoint_method == 'api')

            return redirect()->back()->with('success', 'Document uploaded locally successfully (Email archiving disabled).');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function downloadSignedNote($id, SharePointService $spService)
    {
        try {
            $log = Actionlog::findOrFail($id);

            if (!$log->signed_note_path) {
                return redirect()->back()->with('error', 'No signed document found for this record.');
            }

            // Fetch from SharePoint
            $fileData = $spService->downloadFile($log->signed_note_path);
            
            $prefix = $log->action_type === 'checkout' ? 'ISD' : 'RTN';
            $downloadName = $prefix . '_Signed_' . $log->id . '.pdf'; // Usually PDF

            return response($fileData['content'])
                ->header('Content-Type', $fileData['type'])
                ->header('Content-Disposition', 'inline; filename="' . $downloadName . '"'); // inline يعرضه بالمتصفح بدل التحميل الاجباري

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Download failed: ' . $e->getMessage());
        }
    }
}
