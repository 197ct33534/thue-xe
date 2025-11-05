<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'pickup_date' => $this->pickup_date->format('Y-m-d H:i'),
            'return_date' => $this->return_date->format('Y-m-d H:i'),
            'total_days' => $this->total_days,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount_amount,
            'total' => $this->total_amount,
            'deposit' => $this->deposit_amount,
            'paid' => $this->payments->where('payment_status', 'completed')->sum('amount'),
            'details' => $this->whenLoaded('details', fn() => $this->details->map(fn($d) => [
                'vehicle' => $d->vehicle->name,
                'price_per_day' => $d->price_per_day,
                'quantity' => $d->quantity,
                'total' => $d->total_price,
            ])),
            'can_cancel' => $this->status === 'pending' && $this->pickup_date->gt(now()->addHours(24)),
        ];
    }
}