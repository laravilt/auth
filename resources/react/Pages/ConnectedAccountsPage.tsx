import { Head } from '@inertiajs/react';
import { Facebook, Github, Link as LinkIcon, Linkedin, Mail, Twitter, type LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ActionButton from '@laravilt/actions/components/ActionButton';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout';

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Account {
    id: string;
    provider: string;
    provider_id: string;
    name?: string;
    email?: string;
    avatar?: string;
    created_at: string;
}

interface Provider {
    name: string;
    label: string;
    connected: boolean;
    account?: Account;
    connectAction?: any;
    disconnectAction?: any;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

export interface ConnectedAccountsPageProps {
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    providers: Provider[];
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}

const getProviderIcon = (provider: string): LucideIcon => {
    const icons: Record<string, LucideIcon> = {
        github: Github,
        google: Mail,
        facebook: Facebook,
        twitter: Twitter,
        linkedin: Linkedin,
    };
    return icons[provider.toLowerCase()] || LinkIcon;
};

export default function ConnectedAccountsPage({
    page,
    breadcrumbs,
    providers,
    clusterNavigation,
    clusterTitle,
    clusterDescription,
}: ConnectedAccountsPageProps) {
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
                <section className="max-w-2xl">
                    {/* Page Header */}
                    <header>
                        <h3 className="mb-0.5 text-base font-medium">{page.heading}</h3>
                        {page.subheading && <p className="text-sm text-muted-foreground">{page.subheading}</p>}
                    </header>

                    {!providers || providers.length === 0 ? (
                        /* Empty State */
                        <div className="flex flex-col items-center justify-center py-12">
                            <LinkIcon className="h-12 w-12 text-muted-foreground/50 mb-4" />
                            <p className="text-muted-foreground">No social providers are currently configured.</p>
                        </div>
                    ) : (
                        /* Providers List */
                        <div className="divide-y">
                            {providers.map((provider) => {
                                const ProviderIcon = getProviderIcon(provider.name);

                                return (
                                    <div key={provider.name} className="py-6">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <ProviderIcon className="h-8 w-8 text-muted-foreground" />
                                                <div>
                                                    <h4 className="font-medium">{provider.label}</h4>
                                                    {provider.connected && provider.account ? (
                                                        <p className="text-sm text-muted-foreground">
                                                            Connected as {provider.account.name || provider.account.email}
                                                        </p>
                                                    ) : (
                                                        <p className="text-sm text-muted-foreground">Not connected</p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-3">
                                                {provider.connected && (
                                                    <Badge
                                                        variant="outline"
                                                        className="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400 dark:border-green-900"
                                                    >
                                                        Connected
                                                    </Badge>
                                                )}

                                                {provider.connectAction && (
                                                    <Button asChild size="sm">
                                                        <a href={provider.connectAction.url}>{provider.connectAction.label}</a>
                                                    </Button>
                                                )}
                                                {provider.disconnectAction && (
                                                    <ActionButton {...provider.disconnectAction} size="sm" />
                                                )}
                                            </div>
                                        </div>

                                        {/* Connected Account Details */}
                                        {provider.connected && provider.account && (
                                            <div className="mt-4 pt-4 flex items-center gap-3">
                                                {provider.account.avatar && (
                                                    <Avatar className="h-10 w-10">
                                                        <AvatarImage src={provider.account.avatar} />
                                                        <AvatarFallback>
                                                            {provider.account.name?.charAt(0).toUpperCase() || '?'}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                )}
                                                <div className="flex-1 min-w-0">
                                                    {provider.account.name && (
                                                        <p className="text-sm font-medium truncate">{provider.account.name}</p>
                                                    )}
                                                    {provider.account.email && (
                                                        <p className="text-xs text-muted-foreground truncate">
                                                            {provider.account.email}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-muted-foreground">
                                                        Connected {provider.account.created_at}
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </section>
            </SettingsLayout>
        </>
    );
}
