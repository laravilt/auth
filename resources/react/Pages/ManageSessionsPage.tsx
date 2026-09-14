import { Form, Head, router } from '@inertiajs/react';
import { Clock, LogOut, MapPin, Monitor, Smartphone, Tablet, Trash2, type LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider';
import LaraviltForm from '@laravilt/forms/components/Form';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Device {
    browser: string;
    platform: string;
    device_type: 'desktop' | 'mobile' | 'tablet';
}

interface Session {
    id: string;
    ip_address: string;
    user_agent: string;
    last_activity: number;
    last_activity_human: string;
    is_current: boolean;
    device: Device;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

export interface ManageSessionsPageProps {
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    sessions: Session[];
    currentSessionId: string;
    logoutAction: string;
    revokeAction: string;
    schema: any[];
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}

const getDeviceIcon = (deviceType: string): LucideIcon => {
    const icons: Record<string, LucideIcon> = {
        desktop: Monitor,
        mobile: Smartphone,
        tablet: Tablet,
    };
    return icons[deviceType] || Monitor;
};

export default function ManageSessionsPage({
    page,
    breadcrumbs,
    sessions,
    logoutAction,
    revokeAction,
    schema,
    clusterNavigation,
    clusterTitle,
    clusterDescription,
}: ManageSessionsPageProps) {
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

    const [showLogoutOthersDialog, setShowLogoutOthersDialog] = useState(false);
    const [showRevokeDialog, setShowRevokeDialog] = useState(false);
    const [sessionToRevoke, setSessionToRevoke] = useState<string | null>(null);

    // Close dialog when sessions change (after logout)
    const sessionsLength = sessions.length;
    useEffect(() => {
        setShowLogoutOthersDialog(false);
        setShowRevokeDialog(false);
        setSessionToRevoke(null);
    }, [sessionsLength]);

    const confirmRevokeSession = (sessionId: string) => {
        setSessionToRevoke(sessionId);
        setShowRevokeDialog(true);
    };

    const revokeSession = () => {
        if (sessionToRevoke) {
            router.delete(`${revokeAction}/${sessionToRevoke}`, {
                onFinish: () => {
                    setShowRevokeDialog(false);
                    setSessionToRevoke(null);
                },
            });
        }
    };

    const cancelRevoke = () => {
        setShowRevokeDialog(false);
        setSessionToRevoke(null);
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
                <section className="max-w-2xl">
                    {/* Page Header */}
                    <header>
                        <h3 className="mb-0.5 text-base font-medium">{page.heading}</h3>
                        {page.subheading && <p className="text-sm text-muted-foreground">{page.subheading}</p>}
                    </header>

                    {!sessions || sessions.length === 0 ? (
                        /* Empty State */
                        <div className="flex flex-col items-center justify-center py-12">
                            <Monitor className="h-12 w-12 text-muted-foreground/50 mb-4" />
                            <p className="text-muted-foreground">{trans('laravilt-auth::auth.profile.sessions.no_sessions')}</p>
                            <p className="text-sm text-muted-foreground">
                                {trans('laravilt-auth::auth.profile.sessions.no_sessions_hint')}
                            </p>
                        </div>
                    ) : (
                        /* Sessions List */
                        <div className="divide-y">
                            {sessions.map((session) => {
                                const DeviceIcon = getDeviceIcon(session.device.device_type);

                                return (
                                    <div key={session.id} className="py-6">
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-start gap-3 flex-1">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                                    <DeviceIcon className="h-5 w-5 text-primary" />
                                                </div>

                                                <div className="flex-1 space-y-2">
                                                    <div className="flex items-center gap-2">
                                                        <h4 className="font-medium">
                                                            {session.device.browser} on {session.device.platform}
                                                        </h4>
                                                        {session.is_current && (
                                                            <Badge
                                                                variant="outline"
                                                                className="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-400 dark:border-green-900"
                                                            >
                                                                {trans('laravilt-auth::auth.profile.sessions.this_device')}
                                                            </Badge>
                                                        )}
                                                    </div>

                                                    <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                                                        <div className="flex items-center gap-1.5">
                                                            <MapPin className="h-3.5 w-3.5" />
                                                            <span>{session.ip_address}</span>
                                                        </div>
                                                        <div className="flex items-center gap-1.5">
                                                            <Clock className="h-3.5 w-3.5" />
                                                            <span>
                                                                {trans('laravilt-auth::auth.profile.sessions.active')}{' '}
                                                                {session.last_activity_human}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {!session.is_current && (
                                                <Button
                                                    onClick={() => confirmRevokeSession(session.id)}
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {/* Logout Other Sessions */}
                    {sessions && sessions.length > 1 && (
                        <div className="pt-6 border-t">
                            <div className="mb-4 text-start">
                                <h4 className="font-medium mb-1">{trans('laravilt-auth::auth.profile.sessions.logout_others')}</h4>
                                <p className="text-sm text-muted-foreground">
                                    {trans('laravilt-auth::auth.profile.sessions.logout_others_desc')}
                                </p>
                            </div>

                            <Dialog open={showLogoutOthersDialog} onOpenChange={setShowLogoutOthersDialog}>
                                <DialogTrigger asChild>
                                    <Button variant="destructive" className="w-full">
                                        <LogOut className="h-4 w-4 me-2" />
                                        {trans('laravilt-auth::auth.profile.sessions.logout_others')}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader className="text-start">
                                        <DialogTitle>{trans('laravilt-auth::auth.profile.sessions.confirm_logout')}</DialogTitle>
                                        <DialogDescription>
                                            {trans('laravilt-auth::auth.profile.sessions.confirm_logout_desc')}
                                        </DialogDescription>
                                    </DialogHeader>

                                    <Form action={logoutAction} method="delete">
                                        {({ errors, processing }) => (
                                            <ErrorProvider errors={errors as Record<string, string | string[]>}>
                                                <div className="space-y-4">
                                                    <LaraviltForm schema={schema} />

                                                    <Button type="submit" variant="destructive" disabled={processing} className="w-full">
                                                        {processing
                                                            ? trans('laravilt-auth::auth.profile.sessions.logging_out')
                                                            : trans('laravilt-auth::auth.profile.sessions.confirm_and_logout')}
                                                    </Button>
                                                </div>
                                            </ErrorProvider>
                                        )}
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    )}
                </section>

                {/* Revoke Session Confirmation Dialog */}
                <Dialog open={showRevokeDialog} onOpenChange={setShowRevokeDialog}>
                    <DialogContent>
                        <DialogHeader className="text-start">
                            <DialogTitle>{trans('laravilt-auth::auth.profile.sessions.revoke_session')}</DialogTitle>
                            <DialogDescription>
                                {trans('laravilt-auth::auth.profile.sessions.revoke_session_confirm')}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex justify-end gap-2 pt-4 rtl:flex-row-reverse">
                            <Button variant="outline" onClick={cancelRevoke}>
                                {trans('laravilt-auth::auth.common.cancel')}
                            </Button>
                            <Button variant="destructive" onClick={revokeSession}>
                                {trans('laravilt-auth::auth.profile.sessions.revoke')}
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </SettingsLayout>
        </>
    );
}
