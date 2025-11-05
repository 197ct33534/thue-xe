<?php

// app/Services/BookingService.php
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

    public function pay(Booking $booking, Request $request, BookingService $service)
    {
        $this->authorize('view', $booking);

        $method = $request->input('method', 'vnpay'); // 'vnpay' hoặc 'momo'

        if ($booking->status !== 'pending') {
            return response()->json([
                'error' => 'Chỉ có thể thanh toán đơn đang ở trạng thái pending.',
            ], 400);
        }

        try {
            $result = $service->processPayment($booking, $method);

            return response()->json([
                'message' => 'Chuyển hướng đến cổng thanh toán',
                'redirect_url' => $result['redirect_url'],
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Khởi tạo thanh toán thất bại: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý callback từ VNPay/Momo
     */
    public function callback(Request $request, BookingService $service)
    {
        $method = $request->route('method'); // 'vnpay' hoặc 'momo'

        // Lấy booking_code từ query hoặc body
        $txnRef = $request->input('vnp_TxnRef') ?? $request->input('orderId');
        if (! $txnRef) {
            return response()->json(['error' => 'Missing transaction reference'], 400);
        }

        // Lấy booking_code từ đầu chuỗi: BK00000001_1234567890 → BK00000001
        $bookingCode = explode('_', $txnRef)[0] ?? null;
        if (! $bookingCode) {
            return response()->json(['error' => 'Invalid transaction format'], 400);
        }

        $booking = Booking::where('booking_code', $bookingCode)->first();
        if (! $booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $success = $service->handleCallback($booking, $request->all(), $method);

        $redirectUrl = $success
            ? env('APP_FRONTEND_URL')."/booking/{$booking->id}/success"
            : env('APP_FRONTEND_URL')."/booking/{$booking->id}/failed";

        return redirect()->away($redirectUrl);
    }

    /**
     * IPN cho Momo (webhook)
     */
    public function ipn(Request $request, BookingService $service)
    {
        $method = $request->route('method');
        if ($method !== 'momo') {
            return response()->json(['status' => 'invalid_method'], 400);
        }

        $orderId = $request->input('orderId');
        if (! $orderId) {
            return response()->json(['status' => 'missing_orderId'], 400);
        }

        $bookingCode = explode('_', $orderId)[0] ?? null;
        $booking = Booking::where('booking_code', $bookingCode)->first();
        if (! $booking) {
            return response()->json(['status' => 'booking_not_found'], 404);
        }

        $service->handleCallback($booking, $request->all(), $method);

        return response()->json(['status' => 'ok']);
    }

    public function updateStatus(Booking $booking, UpdateBookingStatusRequest $request)
    {
        $this->authorize('update', $booking); // Dùng policy

        $data = $request->validated();

        // Chỉ cho phép từ confirmed → in_progress
        if ($booking->status !== 'confirmed' && $data['status'] === 'in_progress') {
            return response()->json(['error' => 'Chỉ đơn confirmed mới được giao xe'], 400);
        }

        $booking->update([
            'status' => $data['status'],
            'driver_id' => $data['driver_id'] ?? $booking->driver_id,
            'admin_notes' => $data['admin_notes'] ?? $booking->admin_notes,
            'confirmed_at' => $booking->confirmed_at ?? now(),
        ]);

        // Gửi thông báo cho khách (tùy chọn)
        if ($data['status'] === 'in_progress') {
            \Illuminate\Support\Facades\Mail::to($booking->user->email)
                ->queue(new \App\Mail\CarHandoverMail($booking));
        }

        return new BookingResource($booking);
    }
}
