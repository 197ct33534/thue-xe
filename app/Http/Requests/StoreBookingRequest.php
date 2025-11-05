<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pickup_location' => 'required|string',
            'return_location' => 'nullable|string',
            'pickup_date' => 'required|date|after:now',
            'return_date' => 'required|date|after:pickup_date',
            'vehicles' => 'required|array|min:1',
            'vehicles.*.vehicle_id' => 'required|exists:vehicles,id',
            'vehicles.*.price_per_day' => 'required|numeric|min:0',
            'vehicles.*.quantity' => 'nullable|integer|min:1',
            'promotion_code' => 'nullable|string|exists:promotions,code',
            'payment_method' => 'required|in:cash,bank_transfer,momo,vnpay,credit_card',
            'notes' => 'nullable|string',
            'vehicles' => 'required|array|min:1',
            'vehicles.*.vehicle_id' => [
                'required',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $pickup = $this->input('pickup_date');
                    $return = $this->input('return_date');

                    $exists = Booking::where('status', '!=', 'cancelled')
                        ->whereHas('details', function ($q) use ($value) {
                            $q->where('vehicle_id', $value);
                        })
                        ->where(function ($q) use ($pickup, $return) {
                            $q->whereBetween('pickup_date', [$pickup, $return])
                            ->orWhereBetween('return_date', [$pickup, $return])
                            ->orWhereRaw('? BETWEEN pickup_date AND return_date', [$pickup])
                            ->orWhereRaw('? BETWEEN pickup_date AND return_date', [$return]);
                        })
                        ->exists();

                    if ($exists) {
                        $fail("Xe này đã được đặt trong khoảng thời gian bạn chọn.");
                    }
                }
            ],
        ];
    }
}
