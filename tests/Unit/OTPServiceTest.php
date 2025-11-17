<?php

use Laravilt\Auth\Services\OTPService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('can generate and send OTP', function () {
    $service = new OTPService();

    $result = $service->send('+1234567890');

    expect($result)->toBeTrue();
});

it('can verify OTP', function () {
    $service = new OTPService();
    $phone = '+1234567890';

    // Manually set OTP in cache for testing
    Cache::put("otp.{$phone}", '123456', now()->addMinutes(5));

    $result = $service->verify($phone, '123456');

    expect($result)->toBeTrue();
    expect(Cache::has("otp.{$phone}"))->toBeFalse(); // OTP should be removed after verification
});

it('fails verification with wrong OTP', function () {
    $service = new OTPService();
    $phone = '+1234567890';

    Cache::put("otp.{$phone}", '123456', now()->addMinutes(5));

    $result = $service->verify($phone, '654321');

    expect($result)->toBeFalse();
});

it('fails verification with expired OTP', function () {
    $service = new OTPService();
    $phone = '+1234567890';

    // No OTP in cache (expired)
    $result = $service->verify($phone, '123456');

    expect($result)->toBeFalse();
});
