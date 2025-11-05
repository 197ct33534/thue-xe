<?php
class BookingDetail extends Model
{
    protected $fillable = [
        'booking_id', 'vehicle_id', 'price_per_day', 'quantity'
    ];
    public function booking() { return $this->belongsTo(Booking::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
}