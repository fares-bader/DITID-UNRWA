@extends('layouts/default')
@section('title', 'CMDB Object Types')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check"></i> {{ session('success') }}</div>
        @endif
        
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fas fa-sitemap"></i> CMDB Object Types</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('cmdb.types.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create New Type</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-striped snipe-table" id="cmdbTypesTable">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Type Name</th>
                            <th>Description</th>
                            <th>Fields Count</th>
                            <th>Associated Objects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($types as $type)
                            <tr>
                                <td><i class="{{ $type->icon }} fa-2x text-primary"></i></td>
                                <td style="font-weight: bold; font-size: 15px;">{{ $type->name }}</td>
                                <td>{{ $type->description ?: 'N/A' }}</td>
                                <td><span class="badge bg-green">{{ count($type->schema_definition ?? []) }} Fields</span></td>
                                <td><span class="badge bg-blue">{{ $type->objects_count }} Objects</span></td>
                                <td>
                                    <!-- سنبرمج هذه الأزرار لاحقاً -->
                                    <button class="btn btn-sm btn-info" disabled><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger" disabled><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(document).ready(function() {
        $('#cmdbTypesTable').DataTable({"order": []});
    });
</script>
@endsection