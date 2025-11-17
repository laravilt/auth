<?php

namespace Laravilt\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OTPService
{
    protected int $otpLength = 6;

    protected int $otpExpiry = 5; // minutes

    /**
     * Generate and send OTP to phone.
     */
    public function send(string $phone): bool
    {
        $otp = $this->generate();

        // Store OTP in cache
        Cache::put(
            $this->getCacheKey($phone),
            $otp,
            now()->addMinutes($this->otpExpiry)
        );

        // Send SMS (integrate with your SMS provider)
        // SMS::send($phone, "Your OTP code is: {$otp}");

        return true;
    }

    /**
     * Verify OTP.
     */
    public function verify(string $phone, string $otp): bool
    {
        $key = $this->getCacheKey($phone);
        $storedOtp = Cache::get($key);

        if (! $storedOtp || $storedOtp !== $otp) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    /**
     * Generate a random OTP.
     */
    protected function generate(): string
    {
        return str_pad(
            (string) random_int(0, pow(10, $this->otpLength) - 1),
            $this->otpLength,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Get cache key for phone.
     */
    protected function getCacheKey(string $phone): string
    {
        return "otp.{$phone}";
    }

    /**
     * Set OTP length.
     */
    public function setLength(int $length): static
    {
        $this->otpLength = $length;

        return $this;
    }

    /**
     * Set OTP expiry in minutes.
     */
    public function setExpiry(int $minutes): static
    {
        $this->otpExpiry = $minutes;

        return $this;
    }
}
