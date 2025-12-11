<?php

namespace Laravilt\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WebauthnCredential extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webauthn_credentials';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'alias',
        'counter',
        'rp_id',
        'origin',
        'transports',
        'aaguid',
        'public_key',
        'attestation_format',
        'certificates',
        'disabled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transports' => 'array',
        'certificates' => 'array',
        'disabled_at' => 'datetime',
        'counter' => 'integer',
    ];

    /**
     * Get the authenticatable model that owns the credential.
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias for authenticatable relationship.
     */
    public function user(): MorphTo
    {
        return $this->authenticatable();
    }

    /**
     * Check if the credential is disabled.
     */
    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    /**
     * Disable the credential.
     */
    public function disable(): bool
    {
        return $this->update(['disabled_at' => now()]);
    }

    /**
     * Enable the credential.
     */
    public function enable(): bool
    {
        return $this->update(['disabled_at' => null]);
    }

    /**
     * Update the counter value.
     */
    public function updateCounter(int $counter): bool
    {
        return $this->update(['counter' => $counter]);
    }

    /**
     * Get the credential's display name.
     */
    public function getDisplayName(): string
    {
        return $this->alias ?? 'Passkey';
    }
}
