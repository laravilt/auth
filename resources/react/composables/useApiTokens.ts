import { useState } from 'react';
import { usePanelBase } from './usePanelBase';

export interface ApiToken {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
    plain_text_token?: string;
}

export function useApiTokens(panelId: string = 'user') {
    const base = usePanelBase(panelId);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [tokens, setTokens] = useState<ApiToken[]>([]);
    const [newToken, setNewToken] = useState<ApiToken | null>(null);

    const fetchTokens = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/api-tokens`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to fetch API tokens');
            }

            setTokens(data.tokens);
            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const createToken = async (name: string, abilities: string[]) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/api-tokens`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ name, abilities }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to create API token');
            }

            setNewToken(data.token);
            setTokens((previous) => [data.token, ...previous]);

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const updateToken = async (tokenId: number, abilities: string[]) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/api-tokens/${tokenId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ abilities }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to update API token');
            }

            // Update the token in the list
            setTokens((previous) => {
                const index = previous.findIndex((t) => t.id === tokenId);

                if (index === -1) {
                    return previous;
                }

                const next = [...previous];
                next[index] = data.token;

                return next;
            });

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const deleteToken = async (tokenId: number) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`${base}/profile/api-tokens/${tokenId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete API token');
            }

            // Remove the token from the list
            setTokens((previous) => previous.filter((t) => t.id !== tokenId));

            return data;
        } catch (err: any) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const clearNewToken = () => {
        setNewToken(null);
    };

    return {
        loading,
        error,
        tokens,
        newToken,
        fetchTokens,
        createToken,
        updateToken,
        deleteToken,
        clearNewToken,
        setError,
    };
}
