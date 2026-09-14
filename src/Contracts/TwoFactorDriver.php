<?php

namespace Laravilt\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface TwoFactorDriver
{
    /**
     * Get the driver name.
     */
    public function getName(): string;

    /**
     * Get the driver label for display.
     */
    public function getLabel(): string;

    /**
     * Get the driver icon.
     */
    public function getIcon(): string;

    /**
     * Enable two-factor authentication for the user.
     *
     * @param  Authenticatable&Model  $user
     * @return array Returns setup data (QR code, secret, etc.)
     */
    public function enable(Authenticatable $user): array;

    /**
     * Verify the two-factor code.
     *
     * @param  Authenticatable&Model  $user
     */
    public function verify(Authenticatable $user, string $code): bool;

    /**
     * Send the two-factor code (for email/SMS drivers).
     *
     * @param  Authenticatable&Model  $user
     */
    public function send(Authenticatable $user): bool;

    /**
     * Check if this driver requires sending a code.
     */
    public function requiresSending(): bool;

    /**
     * Check if this driver requires confirmation before enabling.
     */
    public function requiresConfirmation(): bool;
}
