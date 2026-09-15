import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Modal from '@laravilt/support/components/Modal';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useSessionManagement } from '../../composables/useSessionManagement';

export default function SessionManagementSection({ panelId }: { panelId?: string }) {
    // Initialize localization
    const { trans } = useLocalization();
    const page = usePage();

    const [showModal, setShowModal] = useState(false);
    const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
    const [sessionToLogout, setSessionToLogout] = useState<string | null>(null);
    // Use the active panel (the page's top-level panelId prop), not always the 'user' panel
    const sessionManagement = useSessionManagement(panelId ?? (page.props.panelId as string | undefined) ?? 'user');
    const [sessionPassword, setSessionPassword] = useState<string>('');

    const handleOpenModal = async () => {
        setShowModal(true);
        await sessionManagement.fetchSessions();
    };

    const confirmLogoutSession = (sessionId: string) => {
        setSessionToLogout(sessionId);
        setShowLogoutConfirm(true);
    };

    const handleLogoutSession = async () => {
        if (!sessionPassword || !sessionToLogout) return;

        try {
            await sessionManagement.logoutSession(sessionToLogout, sessionPassword);
            setSessionPassword('');
            setShowLogoutConfirm(false);
            setSessionToLogout(null);
        } catch (error) {
            console.error('Failed to logout session:', error);
        }
    };

    const cancelLogoutSession = () => {
        setShowLogoutConfirm(false);
        setSessionToLogout(null);
    };

    // Ported as-is: defined in the Vue component but not wired to any button.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const handleLogoutOthers = async () => {
        if (!sessionPassword) return;

        try {
            await sessionManagement.logoutOthers(sessionPassword);
            setShowModal(false);
            setSessionPassword('');
        } catch (error) {
            console.error('Failed to logout other sessions:', error);
        }
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setSessionPassword('');
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>{trans('profile.sessions.title')}</CardTitle>
                    <CardDescription>{trans('profile.sessions.description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">{trans('profile.sessions.info')}</p>
                    <div className="mt-4 flex justify-end">
                        <Button variant="outline" onClick={handleOpenModal}>
                            {trans('profile.sessions.manage')}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Sessions Modal */}
            <Modal
                open={showModal}
                onUpdateOpen={setShowModal}
                title={trans('profile.sessions.title')}
                description={trans('profile.sessions.modal_description')}
                onClose={handleCloseModal}
                footer={
                    <Button variant="outline" onClick={handleCloseModal}>
                        {trans('common.close')}
                    </Button>
                }
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">{trans('profile.sessions.logout_info')}</p>

                    {sessionManagement.loading && sessionManagement.sessions.length === 0 ? (
                        <div className="py-8 text-center">
                            <div className="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                            <p className="mt-2 text-sm text-muted-foreground">{trans('profile.sessions.loading')}</p>
                        </div>
                    ) : sessionManagement.sessions.length > 0 ? (
                        <div className="space-y-3">
                            {sessionManagement.sessions.map((session) => (
                                <div key={session.id} className="flex items-center gap-3 rounded-lg border p-3">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-muted">
                                        <svg className="size-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fillRule="evenodd"
                                                d={sessionManagement.getDeviceIcon(session.device.device_type)}
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">
                                            {session.is_current
                                                ? trans('profile.sessions.this_device')
                                                : `${session.device.browser} - ${session.device.platform}`}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {session.ip_address} • {session.last_active_at}
                                        </p>
                                    </div>
                                    {session.is_current ? (
                                        <span className="text-xs text-green-600 dark:text-green-400">
                                            {trans('profile.sessions.active_now')}
                                        </span>
                                    ) : (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => confirmLogoutSession(session.id)}
                                            disabled={sessionManagement.loading}
                                        >
                                            {trans('common.logout')}
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : null}

                    {sessionManagement.error && <p className="text-sm text-destructive">{sessionManagement.error}</p>}
                </div>
            </Modal>

            {/* Logout Session Confirmation Dialog */}
            <Dialog open={showLogoutConfirm} onOpenChange={setShowLogoutConfirm}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{trans('profile.sessions.logout_session')}</DialogTitle>
                        <DialogDescription>{trans('profile.sessions.logout_session_confirm')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="logout-password">{trans('profile.sessions.confirm_password')}</Label>
                            <Input
                                id="logout-password"
                                value={sessionPassword}
                                onChange={(e) => setSessionPassword(e.target.value)}
                                type="password"
                                placeholder={trans('profile.sessions.password_placeholder')}
                            />
                        </div>
                        {sessionManagement.error && <p className="text-sm text-destructive">{sessionManagement.error}</p>}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={cancelLogoutSession}>
                            {trans('common.cancel')}
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleLogoutSession}
                            disabled={sessionManagement.loading || !sessionPassword}
                        >
                            {sessionManagement.loading ? trans('profile.sessions.logging_out') : trans('common.logout')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
