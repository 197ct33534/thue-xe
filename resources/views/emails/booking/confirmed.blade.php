@component('mail::message')
# Xác nhận đặt cọc thành công!

Chào **{{ $booking->user->name }},

Chúng tôi đã nhận được **đặt cọc {{ number_format($booking->deposit_amount) }} VNĐ** cho đơn thuê xe:

**Mã đơn:** `{{ $booking->booking_code }}`  
**Nhận xe:** {{ $booking->pickup_date->format('d/m/Y H:i') }} tại **{{ $booking->pickup_location }}**  
**Trả xe:** {{ $booking->return_date->format('d/m/Y H:i') }}  
**Tổng tiền:** {{ number_format($booking->total_amount) }} VNĐ  

@foreach($booking->details as $detail)
- {{ $detail->vehicle->name }} × {{ $detail->quantity }} ({{ number_format($detail->price_per_day) }}/ngày)
@endforeach

---

@component('mail::button', ['url' => url("/booking/{$booking->id}")])
Xem chi tiết đơn
@endcomponent

Hóa đơn chi tiết được đính kèm trong email này.

Cảm ơn bạn đã sử dụng dịch vụ!  
**CarRental Team**
@endcomponent