import { useState } from 'react';
import { usePanelBase } from './usePanelBase';

export interface Session {
    id: string;
    ip_address: string;
    is_current: boolean;
    device: {
        browser: string;
        platform: string;
        device_type: string;
    };
    last_activity: number;
    last_active_at: string;
}

export function useSessionManagement(panelId: string = 'user') {
    const base = usePanelBase(panelId);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [sessions, setSessions] = useState<Session[]>([]);

    const fetchSessions = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/sessions`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to fetch sessions');
            }

            setSessions(data.sessions ?? []);
            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const logoutSession = async (sessionId: string, password: string) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/sessions/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ password }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to logout session');
            }

            // Remove the session from the list
            setSessions((previous) => previous.filter((s) => s.id !== sessionId));

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const logoutOthers = async (password: string) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/sessions/others`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ password }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to logout other sessions');
            }

            // Keep only the current session
            setSessions((previous) => previous.filter((s) => s.is_current));

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const getDeviceIcon = (deviceType: string) => {
        switch (deviceType) {
            case 'desktop':
                return 'M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z';
            case 'tablet':
                return 'M7 4a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2H7zm0 2h10v12H7V6zm5 10a1 1 0 100-2 1 1 0 000 2z';
            case 'mobile':
                return 'M7 2a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2H7zm0 2h10v16H7V4zm5 14a1 1 0 100-2 1 1 0 000 2z';
            default:
                return 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
        }
    };

    return {
        loading,
        error,
        sessions,
        fetchSessions,
        logoutSession,
        logoutOthers,
        getDeviceIcon,
        setError,
    };
}
