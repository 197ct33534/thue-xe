<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'category_id', 'name', 'slug', 'license_plate', 'brand', 'model', 'year', 'color',
        'seats', 'transmission', 'fuel_type', 'price_per_day', 'price_per_hour', 
        'deposit', 'mileage_limit', 'description', 'features', 'thumbnail', 'status',
        'is_featured'
    ];

    protected $casts = [
        'features' => 'array',
        'price_per_day' => 'decimal:2',
        'price_per_hour' => 'decimal:2',
        'deposit' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($vehicle) {
            $vehicle->slug = Str::slug($vehicle->name . '-' . $vehicle->license_plate);
        });
        static::updating(function ($vehicle) {
            $vehicle->slug = Str::slug($vehicle->name . '-' . $vehicle->license_plate);
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1);
    }
}