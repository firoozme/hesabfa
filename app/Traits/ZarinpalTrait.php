<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use App\Models\SubscriptionPayment;
use Filament\Notifications\Notification;

trait ZarinpalTrait
{
    private $merchantId = 'ed8ee450-0f29-4b09-a38a-83d7dc30c5d8';
    private $paymentRequestUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
    private $paymentVerifyUrl = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';

    public $redirectUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';

    private $currency = 'IRT';
    public $amount;
    public $description;
    public $callbackUrl;
    public $metaData;
    public $authority;
    public $statusCode;
    public $error = null;

    public function paymentRequest()
    {
        $data = [
            'merchant_id' => $this->merchantId,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'description' => $this->description,
            'callback_url' => $this->callbackUrl,
            'metadata' => $this->metaData,
        ];

        $response = Http::asJson()->acceptJson()->withoutVerifying()->post($this->paymentRequestUrl, $data);
        $paymentResponse = $response->json();

        if (isset($paymentResponse['data']['code']) && $paymentResponse['data']['code'] === 100) {
            $this->authority = $paymentResponse['data']['authority'];

            SubscriptionPayment::create([
                'authority' => $this->authority,
                'subscription_id' => $this->metaData['subscriptionId'],
                'company_id' => $this->metaData['companyId'],
                'amount' => $this->amount,
                'description' => $this->description,
                'transaction_type' => $this->metaData['transactionType'],
                'transaction_date' => now(),
                'payment_gateway' => 'zarinpal',
                'status' => 'pending',
            ]);

            return true;
        }

        $this->error = $paymentResponse['errors'] ?? 'خطا در درخواست پرداخت';
        return false;
    }

    public function paymentVerify()
    {
        $data = [
            'merchant_id' => $this->merchantId,
            'amount' => $this->amount,
            'authority' => $this->authority,
        ];

        $response = Http::asJson()->acceptJson()->withoutVerifying()->post($this->paymentVerifyUrl, $data);
        $paymentResponse = $response->json();
        $this->statusCode = $paymentResponse['data']['code'] ?? null;

        if ($this->statusCode === 100 || $this->statusCode === 101) {
            $payment = SubscriptionPayment::where('authority', $this->authority)->first();

            $payment->update([
                'transaction_date' => now(),
                'ref_id' => $paymentResponse['data']['ref_id'] ?? null,
                'card_pan' => $paymentResponse['data']['card_pan'] ?? null,
                'card_hash' => $paymentResponse['data']['card_hash'] ?? null,
                'status' => 'successful',
            ]);

            return true;
        }

        $payment = SubscriptionPayment::where('authority', $this->authority)->first();
        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'error_code' => $this->statusCode,
            ]);
        }

        $this->error = $paymentResponse['errors'] ?? 'خطا در تأیید پرداخت';
        return false;
    }
}