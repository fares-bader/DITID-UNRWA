<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmdbFile extends Model
{
    protected $table = 'cmdb_files';

    protected $fillable = [
        'cmdb_object_id', 'filename', 'original_name', 'extension', 'notes', 'created_by'
    ];

    public function object()
    {
        return $this->belongsTo(CmdbObject::class, 'cmdb_object_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}