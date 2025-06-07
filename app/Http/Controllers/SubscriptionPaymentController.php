<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Filament\Notifications\Notification;
use App\Services\Payment\SubscriptionPaymentService;

class SubscriptionPaymentController extends Controller
{
    public function callback(Request $request, SubscriptionPaymentService $paymentService)
    {
        $authority = $request->input('Authority');
        $subscriptionId = $request->input('subscription_id');

        try {
            return $paymentService->verifyPayment($authority, $subscriptionId);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا')
                ->body('خطا در پردازش پرداخت: ' . $e->getMessage())
                ->danger()
                ->send();
            // return redirect()->route('filament.company.pages.pricing');
            dump($e->getMessage());
        }
    }
}