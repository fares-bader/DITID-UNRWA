@extends('layouts/default')
@section('title', 'CMDB Configuration Items')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
        @endif
        
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-server"></i> Configuration Items (CIs)</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('cmdb.objects.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Configuration Item</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-striped snipe-table" id="cmdbObjectsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name (Identifier)</th>
                            <th>Type (Category)</th>
                            <th>Attributes</th>
                            <th>Linked Assets</th>
                            <th>Dependencies</th>
                            <th class="text-center">Files</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($objects as $obj)
                            <tr>
                                <td>{{ $obj->id }}</td>
                                <td style="font-weight: bold; font-size: 15px;">{{ $obj->name }}</td>
                                <td><span class="label label-info"><i class="{{ $obj->type->icon }}"></i> {{ $obj->type->name }}</span></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-default" data-toggle="popover" title="Configuration Details" data-html="true" 
                                            data-content="
                                            <table class='table table-condensed'>
                                            @foreach($obj->attributes ?? [] as $key => $val)
                                                <tr><td><strong>{{ $key }}:</strong></td><td>{{ $val }}</td></tr>
                                            @endforeach
                                            </table>
                                            ">
                                        <i class="fas fa-eye"></i> View Data
                                    </button>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs {{ $obj->assets->count() > 0 ? 'btn-success' : 'btn-default' }}" data-toggle="modal" data-target="#manageLinksModal{{ $obj->id }}">
                                        <i class="fas fa-desktop"></i> {{ $obj->assets->count() }} Assets
                                    </button>
                                </td>
                                <td>
                                    @php $relationsCount = $obj->relatedObjects->count() + $obj->incomingRelations->count(); @endphp
                                    <button type="button" class="btn btn-xs {{ $relationsCount > 0 ? 'bg-purple' : 'btn-default' }}" data-toggle="modal" data-target="#manageRelationsModal{{ $obj->id }}" style="{{ $relationsCount > 0 ? 'color:white;' : '' }}">
                                        <i class="fas fa-project-diagram"></i> {{ $relationsCount }} Relations
                                    </button>
                                </td>
                                <td class="text-center">
                                    <!-- ====== UNRWA: Attachments Button ====== -->
                                    <button type="button" class="btn btn-xs {{ $obj->files->count() > 0 ? 'bg-yellow' : 'btn-default' }}" data-toggle="modal" data-target="#manageFilesModal{{ $obj->id }}">
                                        <i class="fas fa-paperclip"></i> {{ $obj->files->count() }}
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-sm bg-navy" data-toggle="modal" data-target="#linkModal{{ $obj->id }}" title="Link to Physical Asset">
                                        <i class="fas fa-plus-circle"></i> Asset
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ====== ALL MODALS OUTSIDE THE TABLE TO PREVENT HTML DOM HOISTING BUGS ====== -->
@foreach($objects as $obj)

    <div class="modal fade" id="linkModal{{ $obj->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy" style="color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-link"></i> Link Asset to: {{ $obj->name }}</h4>
                </div>
                <form action="{{ route('cmdb.objects.link') }}" method="POST">
                    @csrf
                    <input type="hidden" name="object_id" value="{{ $obj->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Physical Assets</label>
                            <select class="form-control select2" style="width: 100%;" name="asset_ids[]" multiple="multiple" data-placeholder="-- Search & Select Assets --" required>
                                @foreach($assetsList as $asset)
                                    <option value="{{ $asset->id }}">{{ $asset->asset_tag }} - {{ $asset->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-navy"><i class="fas fa-save"></i> Save Links</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageLinksModal{{ $obj->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success" style="color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-desktop"></i> Assets Linked to: {{ $obj->name }}</h4>
                </div>
                <div class="modal-body">
                    @if($obj->assets->count() > 0)
                        <table class="table table-striped">
                            <thead><tr><th>Asset Tag</th><th>Name / Model</th><th class="text-right">Action</th></tr></thead>
                            <tbody>
                                @foreach($obj->assets as $linkedAsset)
                                    <tr>
                                        <td style="font-weight:bold;">{{ $linkedAsset->asset_tag }}</td>
                                        <td>{{ $linkedAsset->name ?: $linkedAsset->model->name }}</td>
                                        <td class="text-right">
                                            <form action="{{ route('cmdb.objects.unlink') }}" method="POST" onsubmit="return confirm('Are you sure you want to unlink this asset?');">
                                                @csrf
                                                <input type="hidden" name="object_id" value="{{ $obj->id }}">
                                                <input type="hidden" name="asset_id" value="{{ $linkedAsset->id }}">
                                                <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-unlink"></i> Unlink</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-warning text-center">No physical assets linked yet.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageRelationsModal{{ $obj->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-purple" style="color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-project-diagram"></i> Dependencies for: {{ $obj->name }}</h4>
                </div>
                <div class="modal-body" style="background-color: #f4f4f4;">
                    <div class="box box-solid">
                        <div class="box-body">
                            <form action="{{ route('cmdb.objects.link-object') }}" method="POST" class="form-inline text-center">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $obj->id }}">
                                <div class="form-group" style="width: 45%;">
                                    <select class="form-control select2" style="width: 100%;" name="child_ids[]" multiple="multiple" data-placeholder="Search & Select Configurations..." required>
                                        @foreach($allObjectsList as $otherObj)
                                            @if($otherObj->id != $obj->id)
                                                <option value="{{ $otherObj->id }}">{{ $otherObj->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group" style="width: 25%;">
                                    <input type="text" class="form-control" name="relation_type" placeholder="Relation (e.g. Connected To)" style="width: 100%;">
                                </div>
                                <button type="submit" class="btn bg-purple"><i class="fas fa-link"></i> Link Configs</button>
                            </form>
                        </div>
                    </div>
                    <div class="box box-solid" style="margin-bottom: 0;">
                        <div class="box-body">
                            @php $relationsCount = $obj->relatedObjects->count() + $obj->incomingRelations->count(); @endphp
                            @if($relationsCount > 0)
                                <table class="table table-bordered">
                                    <thead class="bg-gray"><tr><th>Direction</th><th>Configuration Name</th><th>Relation Type</th><th class="text-right">Action</th></tr></thead>
                                    <tbody>
                                        @foreach($obj->relatedObjects as $childObj)
                                            <tr>
                                                <td class="text-primary"><i class="fas fa-arrow-right"></i> Connects To</td>
                                                <td style="font-weight:bold;">{{ $childObj->name }}</td>
                                                <td><span class="label label-default">{{ $childObj->pivot->relation_type }}</span></td>
                                                <td class="text-right">
                                                    <form action="{{ route('cmdb.objects.unlink-object') }}" method="POST" onsubmit="return confirm('Remove relation?');">
                                                        @csrf
                                                        <input type="hidden" name="parent_id" value="{{ $obj->id }}"><input type="hidden" name="child_id" value="{{ $childObj->id }}">
                                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-unlink"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @foreach($obj->incomingRelations as $parentObj)
                                            <tr>
                                                <td class="text-warning"><i class="fas fa-arrow-left"></i> Depended On By</td>
                                                <td style="font-weight:bold;">{{ $parentObj->name }}</td>
                                                <td><span class="label label-default">{{ $parentObj->pivot->relation_type }}</span></td>
                                                <td class="text-right">
                                                    <form action="{{ route('cmdb.objects.unlink-object') }}" method="POST" onsubmit="return confirm('Remove relation?');">
                                                        @csrf
                                                        <input type="hidden" name="parent_id" value="{{ $parentObj->id }}"><input type="hidden" name="child_id" value="{{ $obj->id }}">
                                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-unlink"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center text-muted" style="padding: 20px;"><i class="fas fa-project-diagram fa-2x"></i><br>No configurations linked to this item yet.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manageFilesModal{{ $obj->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-yellow" style="color: white;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-paperclip"></i> Attachments for: {{ $obj->name }}</h4>
                </div>
                <div class="modal-body" style="background-color: #f4f4f4;">
                    
                    <div class="box box-solid">
                        <div class="box-body">
                            <form action="{{ route('cmdb.objects.upload-file') }}" method="POST" enctype="multipart/form-data" class="form-inline">
                                @csrf
                                <input type="hidden" name="object_id" value="{{ $obj->id }}">
                                <div class="form-group" style="width: 35%;">
                                    <input type="file" class="form-control" name="file" required style="width: 100%;">
                                </div>
                                <div class="form-group" style="width: 45%;">
                                    <input type="text" class="form-control" name="notes" placeholder="Notes (e.g. Backup v1.2 or Network Diagram)" style="width: 100%;">
                                </div>
                                <button type="submit" class="btn btn-warning" style="background-color: #f39c12; border-color: #e67e22; color: white;"><i class="fas fa-upload"></i> Upload</button>
                            </form>
                        </div>
                    </div>

                    <div class="box box-solid" style="margin-bottom: 0;">
                        <div class="box-body">
                            @if($obj->files->count() > 0)
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-gray">
                                        <tr>
                                            <th>File Name</th>
                                            <th>Ext</th>
                                            <th>Notes</th>
                                            <th>Uploader</th>
                                            <th>Date</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($obj->files as $file)
                                            <tr>
                                                <td style="font-weight:bold;">{{ $file->original_name }}</td>
                                                <td><span class="label label-default">{{ strtoupper($file->extension) }}</span></td>
                                                <td>{{ $file->notes ?: '-' }}</td>
                                                <td>{{ $file->uploader ? $file->uploader->display_name : 'System' }}</td>
                                                <td>{{ $file->created_at->format('Y-m-d H:i') }}</td>
                                                <td class="text-right">
                                                    <a href="{{ route('cmdb.objects.download-file', $file->id) }}" class="btn btn-xs btn-primary" title="Download File"><i class="fas fa-download"></i></a>
                                                    <form action="{{ route('cmdb.objects.delete-file') }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete this file?');">
                                                        @csrf
                                                        <input type="hidden" name="file_id" value="{{ $file->id }}">
                                                        <button type="submit" class="btn btn-xs btn-danger" title="Delete File"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center text-muted" style="padding: 20px;"><i class="fas fa-folder-open fa-2x"></i><br>No files attached yet.</div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endforeach
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        $('#cmdbObjectsTable').DataTable({"order": []});
        $('[data-toggle="popover"]').popover({ trigger: "focus" });
        
        $('.modal').on('shown.bs.modal', function () {
            $(this).find('.select2').select2({
                dropdownParent: $(this),
                placeholder: $(this).find('.select2').data('placeholder') || "-- Search & Select --",
                allowClear: true
            });
        });
    });
</script>
@endsection