import { useEffect, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { AuthSocialProvider } from '../types';

export interface SocialLoginProps {
    providers?: AuthSocialProvider[];
    redirectUrl?: string;
    children?: ReactNode;
}

export default function SocialLogin({ providers, redirectUrl, children }: SocialLoginProps) {
    const enabledProviders = providers || [];

    useEffect(() => {
        console.log('SocialLogin mounted', {
            providers,
            redirectUrl,
            enabledProviders,
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const getProviderUrl = (provider: AuthSocialProvider) => {
        if (!redirectUrl) {
            return `/auth/${provider.name}/redirect`;
        }

        // Replace :provider placeholder with actual provider name
        return redirectUrl.replace(':provider', provider.name);
    };

    if (enabledProviders.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            {children != null && (
                <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                        <span className="w-full border-t" />
                    </div>
                    <div className="relative flex justify-center text-xs uppercase">
                        <span className="bg-background px-2 text-muted-foreground">{children}</span>
                    </div>
                </div>
            )}

            <div className="flex items-center justify-center gap-3">
                {enabledProviders.map((provider) => (
                    <a
                        key={provider.name}
                        href={getProviderUrl(provider)}
                        title={provider.label}
                        className={cn(
                            'group relative inline-flex h-10 w-10 items-center justify-center rounded-md border transition-all hover:scale-110 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                            provider.colorClasses,
                        )}
                    >
                        <svg className="size-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d={provider.icon} />
                        </svg>

                        {/* Tooltip */}
                        <span className="pointer-events-none absolute -top-10 left-1/2 -translate-x-1/2 rounded-md bg-gray-900 px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                            {provider.label}
                        </span>
                    </a>
                ))}
            </div>
        </div>
    );
}
