<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{
    protected $table = 'vehicle_categories';

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'display_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Tự động tạo slug từ name
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = \Str::slug($category->name);
        });
        static::updating(function ($category) {
            $category->slug = \Str::slug($category->name);
        });
    }
}