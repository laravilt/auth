import { Head } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Copy, Key } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ActionButton from '@laravilt/actions/components/ActionButton';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Token {
    id: number;
    name: string;
    abilities: string[];
    last_used_at?: string;
    expires_at?: string;
    expires_at_human?: string;
    created_at: string;
    is_expired: boolean;
    deleteAction: any;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

export interface ManageApiTokensPageProps {
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    createAction: any;
    revokeAllAction: any;
    tokens: Token[];
    availableAbilities: Record<string, string>;
    maxTokens: number;
    newToken?: string;
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}

export default function ManageApiTokensPage({
    page,
    breadcrumbs,
    createAction,
    revokeAllAction,
    tokens,
    availableAbilities,
    maxTokens,
    newToken,
    clusterNavigation,
    clusterTitle,
    clusterDescription,
}: ManageApiTokensPageProps) {
    const { trans } = useLocalization();

    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const timeout = setTimeout(() => {
            setIsLoading(false);
        }, 100);

        return () => clearTimeout(timeout);
    }, []);

    // Transform breadcrumbs to frontend format
    const transformedBreadcrumbs = !breadcrumbs
        ? []
        : breadcrumbs.map((item) => ({
              title: item.label,
              href: item.url || '#',
          }));

    const [copiedToken, setCopiedToken] = useState(false);

    const copyToken = async () => {
        if (!newToken) return;

        try {
            await navigator.clipboard.writeText(newToken);
            setCopiedToken(true);
            setTimeout(() => {
                setCopiedToken(false);
            }, 2000);
        } catch (err) {
            console.error('Failed to copy token:', err);
        }
    };

    return (
        <>
            <Head title={page.heading} />

            <SettingsLayout
                breadcrumbs={transformedBreadcrumbs}
                navigation={clusterNavigation}
                title={clusterTitle}
                description={clusterDescription}
                loading={isLoading}
            >
                <section className="max-w-2xl space-y-6">
                    {/* Page Header */}
                    <div className="flex items-center justify-between">
                        <header>
                            <h3 className="mb-0.5 text-base font-medium">{page.heading}</h3>
                            {page.subheading && <p className="text-sm text-muted-foreground">{page.subheading}</p>}
                        </header>

                        <ActionButton {...createAction} disabled={tokens.length >= maxTokens} />
                    </div>

                    {/* New Token Display */}
                    {newToken && (
                        <div>
                            <div className="rounded-lg border border-green-200 bg-green-50 p-6 dark:border-green-900 dark:bg-green-950">
                                <div className="flex items-center gap-2 mb-2">
                                    <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    <h4 className="font-medium text-green-900 dark:text-green-100">
                                        {trans('laravilt-auth::auth.profile.api_tokens.token_created')}
                                    </h4>
                                </div>
                                <p className="text-sm text-green-800 dark:text-green-200 mb-4">
                                    {trans('laravilt-auth::auth.profile.api_tokens.copy_token_warning')}
                                </p>
                                <div className="flex items-center gap-2">
                                    <code className="flex-1 rounded bg-white dark:bg-gray-900 px-3 py-2 text-sm font-mono border">
                                        {newToken}
                                    </code>
                                    <Button onClick={copyToken} variant="outline" size="sm">
                                        {!copiedToken ? (
                                            <Copy className="h-4 w-4" />
                                        ) : (
                                            <CheckCircle2 className="h-4 w-4 text-green-600" />
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Token Limit Warning */}
                    {tokens.length >= maxTokens && (
                        <div>
                            <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-6 dark:border-yellow-900 dark:bg-yellow-950">
                                <div className="flex items-start gap-2">
                                    <AlertCircle className="h-5 w-5 text-yellow-600 dark:text-yellow-500 mt-0.5" />
                                    <div>
                                        <p className="font-medium text-yellow-900 dark:text-yellow-100">
                                            {trans('laravilt-auth::auth.profile.api_tokens.limit_reached')}
                                        </p>
                                        <p className="text-sm text-yellow-800 dark:text-yellow-200">
                                            {trans('laravilt-auth::auth.profile.api_tokens.limit_reached_desc', { max: maxTokens })}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {tokens.length === 0 ? (
                        /* Empty State */
                        <div className="flex flex-col items-center justify-center py-12">
                            <Key className="h-12 w-12 text-muted-foreground/50 mb-4" />
                            <p className="text-lg font-medium mb-1">
                                {trans('laravilt-auth::auth.profile.api_tokens.no_tokens_yet')}
                            </p>
                            <p className="text-sm text-muted-foreground mb-4">
                                {trans('laravilt-auth::auth.profile.api_tokens.create_first')}
                            </p>
                        </div>
                    ) : (
                        /* Tokens List */
                        <div className="divide-y">
                            {tokens.map((token) => (
                                <div key={token.id} className="py-6">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-2">
                                                <h4 className="font-medium">{token.name}</h4>
                                                {token.is_expired ? (
                                                    <Badge variant="destructive">
                                                        {trans('laravilt-auth::auth.profile.api_tokens.expired')}
                                                    </Badge>
                                                ) : token.expires_at ? (
                                                    <Badge variant="outline">
                                                        {trans('laravilt-auth::auth.profile.api_tokens.expires')} {token.expires_at_human}
                                                    </Badge>
                                                ) : null}
                                            </div>

                                            <div className="flex flex-wrap gap-1 mb-2">
                                                {token.abilities.map((ability) => (
                                                    <Badge key={ability} variant="secondary" className="text-xs">
                                                        {ability === '*'
                                                            ? trans('laravilt-auth::auth.profile.api_tokens.full_access')
                                                            : availableAbilities[ability] || ability}
                                                    </Badge>
                                                ))}
                                            </div>

                                            <div className="flex gap-4 text-xs text-muted-foreground">
                                                <span>
                                                    {trans('laravilt-auth::auth.profile.api_tokens.created')} {token.created_at}
                                                </span>
                                                {token.last_used_at ? (
                                                    <span>
                                                        {trans('laravilt-auth::auth.profile.api_tokens.last_used')} {token.last_used_at}
                                                    </span>
                                                ) : (
                                                    <span>{trans('laravilt-auth::auth.profile.api_tokens.never_used')}</span>
                                                )}
                                            </div>
                                        </div>

                                        <ActionButton {...token.deleteAction} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Revoke All Tokens */}
                    {tokens.length > 1 && (
                        <div className="pt-6 border-t">
                            <div className="mb-4">
                                <h4 className="font-medium mb-1">{trans('laravilt-auth::auth.profile.api_tokens.revoke_all')}</h4>
                                <p className="text-sm text-muted-foreground">
                                    {trans('laravilt-auth::auth.profile.api_tokens.revoke_all_warning')}
                                </p>
                            </div>

                            {/* Vue passes variant="destructive" (outside ActionButton's variant union) and class="w-full". */}
                            <ActionButton {...revokeAllAction} {...({ variant: 'destructive', className: 'w-full' } as any)} />
                        </div>
                    )}
                </section>
            </SettingsLayout>
        </>
    );
}
