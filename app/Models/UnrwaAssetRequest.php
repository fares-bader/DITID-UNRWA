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

    /**
     * ====== Relationships (العلاقات البرمجية) ======
     */

    // 1. الموظف الذي قام بالطلب
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 2. الفئة المطلوبة (مثل: لابتوب، شاشة) - إجبارية للجميع
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // 3. الموديل المطلوب (مثل: Dell Latitude) - خاص بالمدراء
    public function assetModel()
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    // 4. الجهاز المادي (Asset) الذي تم تسليمه فعلياً لإغلاق الطلب
    public function fulfilledAsset()
    {
        return $this->belongsTo(Asset::class, 'fulfilled_asset_id');
    }
}