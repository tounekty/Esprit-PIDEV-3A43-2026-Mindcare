<?php

namespace App\Service;

use Twilio\Rest\Client;

class TwilioSmsService
{
    private Client $client;
    private string $fromNumber;
    private ?string $lastError = null;

    public function __construct(
        string $twilioAccountSid,
        string $twilioAuthToken,
        string $twilioFromNumber
    ) {
        $this->client = new Client($twilioAccountSid, $twilioAuthToken);
        $this->fromNumber = $twilioFromNumber;
    }

    /**
     * Send an SMS message
     */
    public function sendSms(string $to, string $body): bool
    {
        // Normalize phone to E.164 format
        $to = $this->normalizePhone($to);

        try {
            $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $body,
            ]);
            return true;
        } catch (\Exception $e) {
            // Store last error for debugging
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?? null;
    }

    /**
     * Normalize a phone number to E.164 format (+216 for Tunisia)
     */
    private function normalizePhone(string $phone): string
    {
        // Strip spaces, dashes, dots, parentheses
        $phone = preg_replace('/[\s\-\.\(\)]+/', '', $phone);

        // Already in international format
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // Starts with 00 (international prefix)
        if (str_starts_with($phone, '00')) {
            return '+' . substr($phone, 2);
        }

        // Local Tunisian number (8 digits) — prepend +216
        if (preg_match('/^\d{8}$/', $phone)) {
            return '+216' . $phone;
        }

        // Fallback: prepend + if it looks like it has a country code
        return '+' . $phone;
    }
}
