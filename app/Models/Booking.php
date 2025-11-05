<?php
class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'driver_id', 'promotion_id', 'pickup_location', 'return_location',
        'pickup_date', 'return_date', 'deposit_amount', 'total_amount',
        'status', 'notes', 'cancellation_reason'
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // static::creating(function ($booking) {
        //     $booking->booking_code = 'BK' . str_pad(DB::raw('NEXTVAL(\'booking_code_seq\')'), 8, '0', STR_PAD_LEFT);
        //     $booking->total_days = $booking->pickup_date->diffInDays($booking->return_date);
        // });
    }
    public function scopeOverlaps($query, $pickup, $return)
    {
        return $query->where(function ($q) use ($pickup, $return) {
            $q->whereBetween('pickup_date', [$pickup, $return])
              ->orWhereBetween('return_date', [$pickup, $return])
              ->orWhereRaw('? BETWEEN pickup_date AND return_date', [$pickup])
              ->orWhereRaw('? BETWEEN pickup_date AND return_date', [$return]);
        });
    }
    public function details() { return $this->hasMany(BookingDetail::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function promotion() { return $this->belongsTo(Promotion::class); }
}