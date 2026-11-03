<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmdbObjectType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmdb_object_types';

    protected $fillable = [
        'name',
        'icon',
        'description',
        'schema_definition',
    ];

    protected $casts = [
        'schema_definition' => 'array',
    ];

    public function objects()
    {
        return $this->hasMany(CmdbObject::class, 'type_id');
    }
}