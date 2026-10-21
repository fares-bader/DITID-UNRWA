<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnrwaAssetRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unrwa_asset_requests';

    protected $fillable = [
        'user_id',
        'category_id',
        'model_id',
        'quantity',
        'request_tier',
        'notes',
        'status',
        'fulfilled_asset_id',
        'fulfilled_at',
    ];

  

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function assetModel()
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    public function fulfilledAsset()
    {
        return $this->belongsTo(Asset::class, 'fulfilled_asset_id');
    }
}