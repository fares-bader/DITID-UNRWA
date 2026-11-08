<?php

namespace App\Http\Controllers;

use App\Models\Actionlog;
use App\Models\User;
use App\Services\SharePointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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