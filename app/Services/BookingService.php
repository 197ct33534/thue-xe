<?php

// app/Services/BookingService.php
use App\Services\MomoService;
use App\Services\VNPayService;

class BookingService
{
    public function createBooking(array $data, User $user): Booking
    {
        return DB::transaction(function () use ($data, $user) {
            // 1. Tính giá
            $calc = $this->calculatePrice($data['vehicles'], $data['pickup_date'], $data['return_date'], $data['promotion_code'] ?? null);

            // 2. Tạo booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'pickup_location' => $data['pickup_location'],
                'return_location' => $data['return_location'] ?? $data['pickup_location'],
                'pickup_date' => $data['pickup_date'],
                'return_date' => $data['return_date'],
                'subtotal' => $calc['subtotal'],
                'discount_amount' => $calc['discount'],
                'deposit_amount' => $calc['deposit'],
                'total_amount' => $calc['total'],
                'promotion_id' => $calc['promotion_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            // 3. Tạo chi tiết
            foreach ($data['vehicles'] as $item) {
                BookingDetail::create([
                    'booking_id' => $booking->id,
                    'vehicle_id' => $item['vehicle_id'],
                    'price_per_day' => $item['price_per_day'],
                    'quantity' => $item['quantity'] ?? 1,
                    // total_price sẽ được trigger tự động tính
                ]);
            }

            // 4. Tạo payment deposit
            Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $data['payment_method'],
                'payment_type' => 'deposit',
                'amount' => $calc['deposit'],
                'payment_status' => 'pending',
                'notes' => 'Đặt cọc đơn thuê xe',
            ]);

            event(new BookingCreated($booking));

            return $booking;
        });
    }

    private function calculatePrice(array $vehicles, $pickup, $return, $code = null): array
    {
        $days = Carbon::parse($pickup)->diffInDays(Carbon::parse($return));
        $subtotal = 0;
        $promotion = null;
        $discount = 0;

        foreach ($vehicles as $v) {
            $subtotal += $v['price_per_day'] * ($v['quantity'] ?? 1) * $days;
        }

        if ($code) {
            $promotion = Promotion::where('code', $code)
                ->active()
                ->first();

            if ($promotion) {
                $discount = $subtotal * ($promotion->discount_percentage / 100);
            }
        }

        $total = $subtotal - $discount;
        $deposit = max($total * 0.3, 500000); // min 500k

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'deposit' => $deposit,
            'promotion_id' => $promotion?->id,
        ];
    }

    public function processPayment(Booking $booking, string $method = 'vnpay'): array
    {
        $service = $method === 'momo' ? new MomoService : new VNPayService;
        $url = $service->createPaymentUrl($booking, $booking->deposit_amount);

        return [
            'redirect_url' => $url,
            'status' => 'redirect',
        ];
    }

    public function handleCallback(Booking $booking, array $requestData, string $method = 'vnpay'): bool
    {
        $service = $method === 'momo' ? new MomoService : new VNPayService;

        if (! $service->verifyCallback(request())) {
            return false;
        }

        // Kiểm tra resultCode (Momo) hoặc vnp_ResponseCode (VNPay)
        $success = ($method === 'momo' && $requestData['resultCode'] == 0)
            || ($method === 'vnpay' && $requestData['vnp_ResponseCode'] == '00');

        if ($success) {
            $payment = $booking->payments()->where('payment_type', 'deposit')->first();
            $payment->update([
                'transaction_id' => $requestData['orderId'] ?? $requestData['vnp_TxnRef'],
                'payment_status' => 'completed',
                'payment_date' => now(),
            ]);

            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
            \Illuminate\Support\Facades\Mail::to($booking->user->email)
            ->queue(new \App\Mail\BookingConfirmedMail($booking));
            event(new BookingCreated($booking));

            return true;
        }

        $booking->payments()->where('payment_type', 'deposit')->update(['payment_status' => 'failed']);

        return false;
    }
}
