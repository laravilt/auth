/**
 * Laravilt Auth Package
 *
 * Complete authentication system with 8 methods:
 * - Standard Login/Registration
 * - OTP Email Verification
 * - Two-Factor Authentication (TOTP, Email)
 * - Password Reset
 * - Social Authentication (GitHub, Google, etc.)
 * - Passkey Authentication (WebAuthn)
 * - Magic Links
 * - Connected Accounts
 *
 * @package Laravilt\Auth
 */

// Export all components
export * from './components';
export * from './Pages';
export * from './layouts';
export * from './composables';

// Export types
export type { AuthUser, AuthPanel, Auth2FAMethod, AuthSocialProvider } from './types';

/**
 * The Vue entry registers no global `laravilt-*` components, so register() is a no-op.
 * It exists only so hosts can call `register()` on every package entry uniformly.
 */
export default {
    register(): void {
        // Intentionally empty — mirrors the Vue entry, which has no install() registrations.
    },
};
