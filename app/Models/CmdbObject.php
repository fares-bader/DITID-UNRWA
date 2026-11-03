<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmdbObject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmdb_objects';

    protected $fillable = [
        'name',
        'type_id',
        'attributes',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo(CmdbObjectType::class, 'type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_cmdb_object', 'cmdb_object_id', 'asset_id')
                    ->withTimestamps();
    }
    // ====== UNRWA Customization: Object-to-Object Relationships ======
    
    public function relatedObjects()
    {
        return $this->belongsToMany(CmdbObject::class, 'cmdb_object_relations', 'parent_id', 'child_id')
                    ->withPivot('relation_type')
                    ->withTimestamps();
    }

    public function incomingRelations()
    {
        return $this->belongsToMany(CmdbObject::class, 'cmdb_object_relations', 'child_id', 'parent_id')
                    ->withPivot('relation_type')
                    ->withTimestamps();
    }
    // =================================================================
    public function files()
    {
        return $this->hasMany(CmdbFile::class, 'cmdb_object_id');
    }
}