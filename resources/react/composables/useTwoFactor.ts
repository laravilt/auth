import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useLatest } from '@laravilt/support/composables/hooks';
import { usePanelBase } from './usePanelBase';

export interface TwoFactorData {
    secret?: string;
    qr_code?: string;
    recovery_codes?: string[];
}

export function useTwoFactor(panelId: string = 'user') {
    const base = usePanelBase(panelId);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [twoFactorData, setTwoFactorData] = useState<TwoFactorData | null>(null);
    const [showRecoveryCodes, setShowRecoveryCodes] = useState(false);

    // The Vue composable calls usePage() inside disable(); hooks must run at the top level here.
    const page = usePage();
    const pageRef = useLatest(page);

    const enable = async (method: string, password: string) => {
        setLoading(true);
        setError(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`${base}/profile/two-factor/enable`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ method, password }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to enable two-factor authentication');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            // The controller returns { message, data } where data contains the actual 2FA info
            setTwoFactorData(data.data || data);
            return data.data || data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const confirm = async (code: string) => {
        setLoading(true);
        setError(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`${base}/profile/two-factor/confirm`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ code }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to confirm two-factor authentication');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            setShowRecoveryCodes(true);

            setTwoFactorData((previous) => {
                // If recovery codes are in the current twoFactorData, use them
                if (previous?.recovery_codes) {
                    // Keep existing recovery codes from enable response
                    return previous;
                } else if (data.recovery_codes) {
                    return { recovery_codes: data.recovery_codes };
                }

                return previous;
            });

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const disable = async (password: string) => {
        setLoading(true);
        setError(null);

        try {
            // Get CSRF token from Inertia page props (shared by Laravel automatically)
            const csrfToken =
                (pageRef.current.props as any).csrf_token ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`${base}/profile/two-factor/disable`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ password }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to disable two-factor authentication');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            setTwoFactorData(null);

            // Reload the page to update the status
            router.reload();

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const regenerateRecoveryCodes = async (password: string) => {
        setLoading(true);
        setError(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`${base}/profile/two-factor/recovery-codes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ password }),
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.message || 'Failed to regenerate recovery codes');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            }

            const data = await response.json();

            setShowRecoveryCodes(true);
            setTwoFactorData({ recovery_codes: data.recovery_codes });

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const reset = () => {
        setTwoFactorData(null);
        setError(null);
        setShowRecoveryCodes(false);
    };

    return {
        loading,
        error,
        twoFactorData,
        showRecoveryCodes,
        enable,
        confirm,
        disable,
        regenerateRecoveryCodes,
        reset,
        setTwoFactorData,
        setShowRecoveryCodes,
        setError,
    };
}
