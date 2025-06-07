<?php

namespace App\Services\Sms;

use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;

class KavenegarService
{
    protected $api;
    protected $sender;

    public function __construct()
    {
        $this->api = new KavenegarApi(config('sms.kavenegar.api_key'));
        $this->sender = config('sms.kavenegar.sender');
    }

    /**
     * ارسال پیامک به یک یا چند گیرنده
     *
     * @param string|array $receptor
     * @param string $message
     * @return array
     * @throws \Exception
     */
    public function send($receptor, string $message): array
    {
        // try {
        //     $receptors = is_array($receptor) ? $receptor : [$receptor];
        //     $result = $this->api->Send($this->sender, $receptors, $message);

        //     return [
        //         'success' => true,
        //         'data' => $result,
        //     ];
        // } catch (ApiException $e) {
        //     throw new \Exception('خطا در ارسال پیامک: ' . $e->errorMessage());
        // } catch (HttpException $e) {
        //     throw new \Exception('خطا در ارتباط با سرویس پیامک: ' . $e->errorMessage());
        // }
        return [];
    }
}