<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VNPayService
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'vnp_TmnCode' => env('VNPAY_TMN_CODE'),
            'vnp_HashSecret' => env('VNPAY_HASH_SECRET'),
            'vnp_Url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'vnp_ReturnUrl' => env('VNPAY_RETURN_URL'),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_Locale' => 'vn',
            'vnp_CurrCode' => 'VND',
        ];
    }

    public function createPaymentUrl($booking, $amount): string
    {
        $vnp_TxnRef = $booking->booking_code . '_' . time();
        $vnp_OrderInfo = "Thanh toan dat coc don thue xe: {$booking->booking_code}";
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $amount * 100; // VNPay dùng đơn vị 100
        $vnp_IpAddr = request()->ip();

        $inputData = [
            'vnp_Version' => $this->config['vnp_Version'],
            'vnp_Command' => $this->config['vnp_Command'],
            'vnp_TmnCode' => $this->config['vnp_TmnCode'],
            'vnp_Amount' => $vnp_Amount,
            'vnp_CurrCode' => $this->config['vnp_CurrCode'],
            'vnp_TxnRef' => $vnp_TxnRef,
            'vnp_OrderInfo' => $vnp_OrderInfo,
            'vnp_OrderType' => $vnp_OrderType,
            'vnp_Locale' => $this->config['vnp_Locale'],
            'vnp_ReturnUrl' => $this->config['vnp_ReturnUrl'],
            'vnp_IpAddr' => $vnp_IpAddr,
            'vnp_CreateDate' => now()->format('YmdHis'),
        ];

        ksort($inputData);
        $query = http_build_query($inputData);
        $hashData = $query;
        $vnpSecureHash = hash_hmac('sha512', $hashData, $this->config['vnp_HashSecret']);
        $vnp_Url = $this->config['vnp_Url'] . "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        // Lưu txn_ref vào payment
        \App\Models\Payment::where('booking_id', $booking->id)
            ->where('payment_type', 'deposit')
            ->update(['transaction_id' => $vnp_TxnRef]);

        return $vnp_Url;
    }

    public function verifyCallback($request): bool
    {
        $vnp_HashSecret = $this->config['vnp_HashSecret'];
        $inputData = $request->all();
        $returnData = [];

        foreach ($inputData as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $returnData[$key] = $value;
            }
        }

        $vnp_SecureHash = $returnData['vnp_SecureHash'];
        unset($returnData['vnp_SecureHash']);
        ksort($returnData);
        $hashData = http_build_query($returnData);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }
}