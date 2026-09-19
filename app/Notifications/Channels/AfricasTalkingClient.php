<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricasTalkingClient
{
    /**
     * @param  array<int, string>  $recipients  E.164 or local format; AT accepts both.
     * @return array  Raw AT response.
     */
    public function send(array $recipients, string $message): array
    {
        if (empty($recipients)) {
            return ['SMSMessageData' => ['Recipients' => []]];
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'apiKey' => config('alerts.sms.api_key'),
                'Accept' => 'application/json',
            ])
            ->asForm()
            ->post($this->endpoint(), [
                'username' => config('alerts.sms.username'),
                'to' => implode(',', $recipients),
                'message' => $message,
                'from' => config('alerts.sms.from'),
            ]);

        if ($response->failed()) {
            Log::error('Africa\'s Talking send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException(
                'Africa\'s Talking returned ' . $response->status()
            );
        }

        return $response->json();
    }

    protected function endpoint(): string
    {
        // Sandbox endpoint only works with sandbox credentials and
        // accepts a fixed set of test numbers. Don't route real traffic
        // through it.
        return app()->isProduction()
            ? 'https://api.africastalking.com/version1/messaging'
            : 'https://api.sandbox.africastalking.com/version1/messaging';
    }
}