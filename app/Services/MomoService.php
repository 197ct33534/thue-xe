<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MomoService
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'partnerCode' => env('MOMO_PARTNER_CODE'),
            'accessKey' => env('MOMO_ACCESS_KEY'),
            'secretKey' => env('MOMO_SECRET_KEY'),
            'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
            'redirectUrl' => env('MOMO_RETURN_URL'),
            'ipnUrl' => env('MOMO_IPN_URL'),
        ];
    }

    public function createPaymentUrl($booking, $amount): string
    {
        $orderId = $booking->booking_code . '_' . time();
        $requestId = Str::random(10);
        $orderInfo = "Thanh toan dat coc don thue xe: {$booking->booking_code}";
        $amount = (int)$amount;
        $requestType = "captureWallet";
        $extraData = "";

        $rawHash = "accessKey={$this->config['accessKey']}&amount=$amount&extraData=$extraData&ipnUrl={$this->config['ipnUrl']}&orderId=$orderId&orderInfo=$orderInfo&partnerCode={$this->config['partnerCode']}&redirectUrl={$this->config['redirectUrl']}&requestId=$requestId&requestType=$requestType";
        $signature = hash_hmac("sha256", $rawHash, $this->config['secretKey']);

        $data = [
            'partnerCode' => $this->config['partnerCode'],
            'partnerName' => "CarRental",
            'storeId' => "CarRentalStore",
            'requestType' => $requestType,
            'ipnUrl' => $this->config['ipnUrl'],
            'redirectUrl' => $this->config['redirectUrl'],
            'orderId' => $orderId,
            'amount' => $amount,
            'lang' => 'vi',
            'autoCapture' => true,
            'orderInfo' => $orderInfo,
            'requestId' => $requestId,
            'extraData' => $extraData,
            'signature' => $signature,
        ];

        $response = Http::post($this->config['endpoint'], $data);
        $json = $response->json();

        if ($response->failed() || !isset($json['payUrl'])) {
            throw new \Exception('Momo payment initiation failed');
        }

        // Lưu orderId
        \App\Models\Payment::where('booking_id', $booking->id)
            ->where('payment_type', 'deposit')
            ->update(['transaction_id' => $orderId]);

        return $json['payUrl'];
    }

    public function verifyCallback($request): bool
    {
        $signature = $request->input('signature');
        $data = $request->except('signature');
        ksort($data);
        $raw = http_build_query($data);
        $expected = hash_hmac('sha256', $raw, $this->config['secretKey']);

        return hash_equals($expected, $signature);
    }
}