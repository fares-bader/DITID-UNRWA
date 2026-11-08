<?php

namespace App\Http\Controllers;

use App\Models\CmdbObjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CmdbController extends Controller
{

    public function indexTypes()
    {
        $user = Auth::user();

        $types = CmdbObjectType::withCount('objects')->orderBy('created_at', 'desc')->get();

        return view('cmdb.types.index', compact('types'));
    }

    public function createType()
    {
        $user = Auth::user();

        return view('cmdb.types.create');
    }

    public function storeType(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'        => 'required|string|max:255|unique:cmdb_object_types',
            'icon'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'field_name'  => 'required|array',
            'field_type'  => 'required|array', 
        ]);

        $schema = [];
        foreach ($request->field_name as $index => $fieldName) {
            if (!empty($fieldName)) {
                $schema[] = [
                    'name' => $fieldName,
                    'type' => $request->field_type[$index] ?? 'text'
                ];
            }
        }

        CmdbObjectType::create([
            'name'              => $request->name,
            'icon'              => $request->icon ?: 'fas fa-server',
            'description'       => $request->description,
            'schema_definition' => $schema
        ]);

        return redirect()->route('cmdb.types.index')->with('success', 'CMDB Object Type created successfully!');
    }
public function editType($id)
    {
        $user = Auth::user();
        $type = CmdbObjectType::findOrFail($id);
        
        return view('cmdb.types.create', compact('type'));
    }

    public function updateType(Request $request, $id)
    {
        $user = Auth::user();
        $type = CmdbObjectType::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255|unique:cmdb_object_types,name,'.$type->id,
            'icon'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'field_name'  => 'nullable|array',
            'field_type'  => 'nullable|array', 
        ]);

        $schema = [];
        if ($request->has('field_name')) {
            foreach ($request->field_name as $index => $fieldName) {
                if (!empty($fieldName)) {
                    $schema[] = [
                        'name' => $fieldName,
                        'type' => $request->field_type[$index] ?? 'text'
                    ];
                }
            }
        }

        $type->update([
            'name'              => $request->name,
            'icon'              => $request->icon ?: 'fas fa-server',
            'description'       => $request->description,
            'schema_definition' => $schema
        ]);

        return redirect()->route('cmdb.types.index')->with('success', 'CMDB Object Type updated successfully!');
    }

    public function destroyType($id)
    {
        $user = Auth::user();
        $type = CmdbObjectType::withCount('objects')->findOrFail($id);

        if ($type->objects_count > 0) {
            return redirect()->back()->with('error', 'Cannot delete this type because it is being used by '.$type->objects_count.' object(s). Reassign or delete them first.');
        }

        $type->delete();

        return redirect()->route('cmdb.types.index')->with('success', 'CMDB Object Type deleted successfully.');
    }
    public function indexObjects()
    {
        $user = Auth::user();

        // تم إضافة 'actionLogs.user' لجلب السجل التاريخي لكل عنصر بذكاء
        $objects = \App\Models\CmdbObject::with(['type', 'creator', 'assets', 'relatedObjects', 'incomingRelations', 'files', 'actionLogs.user'])
                                         ->orderBy('created_at', 'desc')->get();
        $assetsList = \App\Models\Asset::select('id', 'asset_tag', 'name')->orderBy('asset_tag', 'asc')->get();
        
        $allObjectsList = \App\Models\CmdbObject::select('id', 'name')->orderBy('name', 'asc')->get();

        return view('cmdb.objects.index', compact('objects', 'assetsList', 'allObjectsList'));
    }

    public function createObject()
    {
        $user = Auth::user();

        $types = \App\Models\CmdbObjectType::all();

        return view('cmdb.objects.create', compact('types'));
    }

    public function storeObject(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'type_id' => 'required|exists:cmdb_object_types,id',
            'dynamic_fields' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        $object = \App\Models\CmdbObject::create([
            'name' => $request->name,
            'type_id' => $request->type_id,
            'attributes' => $request->dynamic_fields ?? [],
            'notes' => $request->notes,
            'created_by' => $user->id
        ]);

        // توثيق إنشاء العنصر
        $this->logCmdbAction($object, 'create', 'Created new Configuration Item');

        return redirect()->route('cmdb.objects.index')->with('success', 'Configuration Item created successfully!');
    }

    public function linkAsset(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'object_id'   => 'required|exists:cmdb_objects,id',
            'asset_ids'   => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $object = \App\Models\CmdbObject::findOrFail($request->object_id);
        
        $object->assets()->syncWithoutDetaching($request->asset_ids);

        // توثيق ربط الأجهزة
        $this->logCmdbAction($object, 'update', 'Linked '.count($request->asset_ids).' physical asset(s)');

        return redirect()->back()->with('success', 'Assets successfully linked to this configuration!');
    }

    public function unlinkAsset(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'object_id' => 'required|exists:cmdb_objects,id',
            'asset_id'  => 'required|exists:assets,id',
        ]);

        $object = \App\Models\CmdbObject::findOrFail($request->object_id);
        
        $object->assets()->detach($request->asset_id);

        // توثيق فك ارتباط الجهاز
        $this->logCmdbAction($object, 'update', 'Unlinked physical asset (ID: '.$request->asset_id.')');

        return redirect()->back()->with('success', 'Asset unlinked successfully.');
    }

    public function linkObject(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'parent_id' => 'required|exists:cmdb_objects,id',
            'child_ids' => 'required|array',
            'child_ids.*' => 'exists:cmdb_objects,id',
            'relation_type' => 'nullable|string|max:255'
        ]);

        $parent = \App\Models\CmdbObject::findOrFail($request->parent_id);
        
        $syncData = [];
        foreach ($request->child_ids as $childId) {
            if ($parent->id != $childId) {
                $syncData[$childId] = ['relation_type' => $request->relation_type ?: 'Connected To'];
            }
        }

        $parent->relatedObjects()->syncWithoutDetaching($syncData);

        // توثيق إضافة ارتباطات جديدة
        $this->logCmdbAction($parent, 'update', 'Added new configuration dependencies');

        return redirect()->back()->with('success', 'Configurations successfully linked!');
    }

    public function unlinkObject(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'parent_id' => 'required|exists:cmdb_objects,id',
            'child_id'  => 'required|exists:cmdb_objects,id',
        ]);

        $parent = \App\Models\CmdbObject::findOrFail($request->parent_id);
        $parent->relatedObjects()->detach($request->child_id);

        // توثيق فك ارتباط الإعدادات
        $this->logCmdbAction($parent, 'update', 'Removed configuration dependency (ID: '.$request->child_id.')');

        return redirect()->back()->with('success', 'Configuration link removed.');
    }

    public function uploadFile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'object_id' => 'required|exists:cmdb_objects,id',
            'file'      => 'required|file|max:10240',
            'notes'     => 'nullable|string'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        
        $filename = 'cmdb_' . time() . '_' . uniqid() . '.' . $extension;
        $file->storeAs('private_uploads/cmdb', $filename);

        \App\Models\CmdbFile::create([
            'cmdb_object_id' => $request->object_id,
            'filename'       => $filename,
            'original_name'  => $originalName,
            'extension'      => $extension,
            'notes'          => $request->notes,
            'created_by'     => $user->id
        ]);

        // توثيق رفع الملف
        $object = \App\Models\CmdbObject::find($request->object_id);
        $this->logCmdbAction($object, 'uploaded', 'Uploaded file: '.$originalName);

        return redirect()->back()->with('success', 'File uploaded successfully!');
    }

    public function downloadFile($id)
    {
        $user = Auth::user();

        $cmdbFile = \App\Models\CmdbFile::findOrFail($id);
        $path = storage_path('app/private_uploads/cmdb/' . $cmdbFile->filename);

        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'File not found on server.');
        }

        // توثيق تنزيل الملف (اختياري ولكنه ممتاز أمنياً)
        $this->logCmdbAction($cmdbFile->object, 'update', 'Downloaded file: '.$cmdbFile->original_name);

        return response()->download($path, $cmdbFile->original_name);
    }

    public function deleteFile(Request $request)
    {
        $user = Auth::user();

        $cmdbFile = \App\Models\CmdbFile::findOrFail($request->file_id);
        $path = storage_path('app/private_uploads/cmdb/' . $cmdbFile->filename);

        if (file_exists($path)) {
            unlink($path);
        }

        // نحفظ بيانات العنصر قبل حذف الملف لكي نتمكن من توثيق العملية
        $object = $cmdbFile->object;
        $fileName = $cmdbFile->original_name;
        
        $cmdbFile->delete();

        // توثيق حذف الملف
        $this->logCmdbAction($object, 'deleted', 'Deleted file: '.$fileName);

        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    // =========================================================================
    // الدالة السرية الخاصة بتسجيل حركات المراقبة (Audit Log Engine)
    // =========================================================================
    private function logCmdbAction($cmdbObj, $actionType, $note = null)
    {
        if(!$cmdbObj) return;
        
        $log = new \App\Models\Actionlog;
        $log->item_type = \App\Models\CmdbObject::class;
        $log->item_id = $cmdbObj->id;
        $log->created_by = Auth::id();
        $log->action_type = $actionType; // 'create', 'update', 'uploaded', 'deleted'
        $log->note = $note;
        $log->save();
    }
}