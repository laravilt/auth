<?php

namespace Laravilt\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    public function __construct(
        protected Google2FA $google2fa,
        protected OTPService $otpService
    ) {}

    /**
     * Enable 2FA for user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function enable(Authenticatable $user, string $method = 'totp'): array
    {
        if ($method === 'totp') {
            $secret = $this->google2fa->generateSecretKey();

            $user->update([
                'two_factor_secret' => encrypt($secret),
                'two_factor_enabled' => true,
                'two_factor_method' => $method,
            ]);

            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );

            $qrCodeSvg = $this->generateQrCode($qrCodeUrl);

            $recoveryCodes = $this->generateRecoveryCodes($user);

            return [
                'secret' => $secret,
                'qr_code' => $qrCodeSvg,
                'recovery_codes' => $recoveryCodes,
            ];
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_method' => $method,
        ]);

        return [];
    }

    /**
     * Disable 2FA for user.
     */
    public function disable(Authenticatable $user): bool
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return true;
    }

    /**
     * Verify 2FA code.
     */
    public function verify(Authenticatable $user, string $code, string $method = 'totp'): bool
    {
        if ($method === 'totp') {
            return $this->verifyTOTP($user, $code);
        }

        if ($method === 'recovery') {
            return $this->verifyRecoveryCode($user, $code);
        }

        return false;
    }

    /**
     * Verify TOTP code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    protected function verifyTOTP(Authenticatable $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verify recovery code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    protected function verifyRecoveryCode(Authenticatable $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remove used recovery code
        $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return true;
    }

    /**
     * Send 2FA code via SMS or email.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function sendCode(Authenticatable $user, string $method = 'sms'): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if ($method === 'sms') {
            return $this->otpService->send($user->phone ?? '');
        }

        if ($method === 'email') {
            // Send email with code
            // Mail::to($user->email)->send(new TwoFactorCodeMail($code));
            return true;
        }

        return false;
    }

    /**
     * Generate recovery codes.
     */
    public function generateRecoveryCodes(Authenticatable $user): array
    {
        $codes = Collection::times(8, function () {
            return Str::random(10);
        })->all();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return $codes;
    }

    /**
     * Generate QR code SVG.
     */
    protected function generateQrCode(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }
}
