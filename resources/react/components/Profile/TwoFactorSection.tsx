import { Form, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ActionButton from '@laravilt/actions/components/ActionButton';
import LaraviltForm from '@laravilt/forms/components/Form';
import Modal from '@laravilt/support/components/Modal';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useTwoFactor } from '../../composables/useTwoFactor';

interface TwoFactorProvider {
    name: string;
    label: string;
    icon: string;
    requiresSending: boolean;
    requiresConfirmation: boolean;
}

interface TwoFactorAction {
    name: string;
    label?: string;
    color?: string;
    icon?: string;
    url?: string;
    openUrlInNewTab?: boolean;
    requiresConfirmation?: boolean;
    modalHeading?: string;
    modalDescription?: string;
    modalSubmitActionLabel?: string;
    modalCancelActionLabel?: string;
    modalFormSchema?: any[];
    isDisabled?: boolean;
    isOutlined?: boolean;
    size?: string;
}

export interface TwoFactorSectionProps {
    twoFactorStatus?: {
        enabled: boolean;
        confirmed: boolean;
        method: string | null;
        available_providers?: TwoFactorProvider[];
        schemas?: {
            enable: any[];
            confirm: any[];
            disable: any[];
        };
        actions?: {
            enable: string | TwoFactorAction;
            confirm: string;
            disable: string | TwoFactorAction;
        };
        setup_data?: {
            qr_code?: string;
            secret?: string;
            recovery_codes?: string[];
            method?: string;
        };
    };
    enableAction?: TwoFactorAction;
    disableAction?: TwoFactorAction;
    panelId?: string;
}

const cancelButtonClass =
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2';

export default function TwoFactorSection({ twoFactorStatus, enableAction, panelId }: TwoFactorSectionProps) {
    // Initialize localization
    const { trans } = useLocalization();
    const page = usePage();

    const is2FAEnabled = twoFactorStatus?.enabled || false;
    const [showModal, setShowModal] = useState(false);
    // Use the active panel (the page's top-level panelId prop), not always the 'user' panel
    const twoFactor = useTwoFactor(panelId ?? (page.props.panelId as string | undefined) ?? 'user');
    const [twoFactorStep, setTwoFactorStep] = useState<'enable' | 'setup' | 'verify' | 'recovery' | 'disable'>('enable');
    const [selectedMethod, setSelectedMethod] = useState<string>('totp');

    const selectedProvider = twoFactorStatus?.available_providers?.find((p) => p.name === selectedMethod);

    // Watch for setup data from session (after enable form submission) — immediate + deep.
    const setupData = twoFactorStatus?.setup_data;
    const setupDataKey = JSON.stringify(setupData ?? null);

    useEffect(() => {
        if (setupData && setupData.method) {
            // Update selected method from session
            setSelectedMethod(setupData.method);

            // Store in composable
            twoFactor.setTwoFactorData({
                qr_code: setupData.qr_code,
                secret: setupData.secret,
                recovery_codes: setupData.recovery_codes,
            });

            // Determine next step based on provider
            const provider = twoFactorStatus?.available_providers?.find((p) => p.name === setupData.method);

            if (provider?.requiresConfirmation) {
                setTwoFactorStep('setup');
            } else if (provider?.requiresSending) {
                setTwoFactorStep('verify');
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [setupDataKey]);

    // Default to the first available provider, unless the effect above restored a method from setup data.
    useEffect(() => {
        if (!setupData?.method && twoFactorStatus?.available_providers && twoFactorStatus.available_providers.length > 0) {
            setSelectedMethod(twoFactorStatus.available_providers[0].name);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleEnableSuccess = () => {
        // The Inertia form will reload the page automatically with session data
        // The watch above will handle showing the next step
    };

    const handleConfirmSuccess = () => {
        setTwoFactorStep('recovery');
    };

    const handleDisableSuccess = () => {
        setShowModal(false);
        router.reload();
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setTwoFactorStep(is2FAEnabled ? 'disable' : 'enable');
        twoFactor.reset();
    };

    const handleOpenModal = () => {
        setTwoFactorStep(is2FAEnabled ? 'disable' : 'enable');
        setShowModal(true);
    };

    const handleFinishRecoveryCodes = () => {
        setShowModal(false);
        router.reload();
    };

    const renderStep = () => {
        // Step 1: Enable 2FA (select method and password using Form)
        if (twoFactorStep === 'enable' && twoFactorStatus?.schemas?.enable) {
            return (
                <div>
                    <Form
                        action={(twoFactorStatus.actions?.enable || '/two-factor/enable') as string}
                        method="POST"
                        onSuccess={handleEnableSuccess}
                    >
                        {({ processing }) => (
                            <>
                                <div className="space-y-4">
                                    <LaraviltForm schema={twoFactorStatus.schemas!.enable} />
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <button type="button" className={cancelButtonClass} onClick={handleCloseModal}>
                                        {trans('common.cancel')}
                                    </button>
                                    <Button type="submit" disabled={processing || twoFactor.loading}>
                                        {processing || twoFactor.loading
                                            ? trans('profile.two_factor.enabling')
                                            : trans('profile.two_factor.continue')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            );
        }

        // Step 2: Scan QR code (only for TOTP)
        if (twoFactorStep === 'setup' && selectedProvider?.requiresConfirmation) {
            return (
                <div className="space-y-4">
                    <div className="text-center">
                        <p className="text-sm text-muted-foreground mb-4">{trans('profile.two_factor.scan_qr')}</p>
                        {twoFactor.twoFactorData?.qr_code && (
                            <div
                                className="flex justify-center"
                                dangerouslySetInnerHTML={{ __html: twoFactor.twoFactorData.qr_code }}
                            ></div>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="secret">{trans('profile.two_factor.enter_manually')}</Label>
                        <Input id="secret" value={twoFactor.twoFactorData?.secret ?? ''} readOnly className="font-mono" />
                    </div>

                    <Form
                        action={twoFactorStatus?.actions?.confirm || '/two-factor/confirm'}
                        method="POST"
                        onSuccess={handleConfirmSuccess}
                    >
                        {({ processing }) => (
                            <>
                                <div className="space-y-4">
                                    <LaraviltForm schema={twoFactorStatus?.schemas?.confirm || []} />
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <button type="button" className={cancelButtonClass} onClick={handleCloseModal}>
                                        {trans('common.cancel')}
                                    </button>
                                    <Button type="submit" disabled={processing || twoFactor.loading}>
                                        {processing || twoFactor.loading
                                            ? trans('profile.two_factor.verifying')
                                            : trans('profile.two_factor.verify_code')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            );
        }

        // Step 2b: Verify email code (for Email 2FA)
        if (twoFactorStep === 'verify' && selectedProvider?.requiresSending) {
            return (
                <div className="space-y-4">
                    <div className="rounded-lg bg-blue-50 dark:bg-blue-950 p-4">
                        <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                            {trans('profile.two_factor.check_email')}
                        </p>
                        <p className="text-xs text-blue-700 dark:text-blue-300 mt-1">{trans('profile.two_factor.code_sent')}</p>
                    </div>

                    <Form
                        action={twoFactorStatus?.actions?.confirm || '/two-factor/confirm'}
                        method="POST"
                        onSuccess={handleConfirmSuccess}
                    >
                        {({ processing }) => (
                            <>
                                <div className="space-y-4">
                                    <LaraviltForm schema={twoFactorStatus?.schemas?.confirm || []} />
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <button type="button" className={cancelButtonClass} onClick={handleCloseModal}>
                                        {trans('common.cancel')}
                                    </button>
                                    <Button type="submit" disabled={processing || twoFactor.loading}>
                                        {processing || twoFactor.loading
                                            ? trans('profile.two_factor.verifying')
                                            : trans('profile.two_factor.verify_code')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            );
        }

        // Step 3: Show recovery codes
        if (twoFactorStep === 'recovery') {
            return (
                <div className="space-y-4">
                    <div className="rounded-lg bg-amber-50 dark:bg-amber-950 p-4">
                        <p className="text-sm font-medium text-amber-900 dark:text-amber-100">
                            {trans('profile.two_factor.save_recovery')}
                        </p>
                        <p className="text-xs text-amber-700 dark:text-amber-300 mt-1">
                            {trans('profile.two_factor.recovery_warning')}
                        </p>
                    </div>

                    <div className="grid grid-cols-2 gap-2 p-4 bg-muted rounded-lg font-mono text-sm">
                        {twoFactor.twoFactorData?.recovery_codes?.map((code) => <div key={code}>{code}</div>)}
                    </div>

                    <div className="flex justify-end pt-4">
                        <Button onClick={handleFinishRecoveryCodes}>{trans('profile.two_factor.saved_codes')}</Button>
                    </div>
                </div>
            );
        }

        // Disable 2FA using Form
        if (twoFactorStep === 'disable' && twoFactorStatus?.schemas?.disable) {
            return (
                <div>
                    <p className="text-sm text-muted-foreground mb-4">{trans('profile.two_factor.enter_password')}</p>

                    <Form
                        action={(twoFactorStatus.actions?.disable || '/two-factor/disable') as string}
                        method="POST"
                        onSuccess={handleDisableSuccess}
                    >
                        {({ processing }) => (
                            <>
                                <div className="space-y-4">
                                    <LaraviltForm schema={twoFactorStatus.schemas!.disable} />
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <button type="button" className={cancelButtonClass} onClick={handleCloseModal}>
                                        {trans('common.cancel')}
                                    </button>
                                    <Button variant="destructive" type="submit" disabled={processing || twoFactor.loading}>
                                        {processing || twoFactor.loading
                                            ? trans('profile.two_factor.enabling')
                                            : trans('profile.two_factor.disable')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            );
        }

        return null;
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>{trans('profile.two_factor.title')}</CardTitle>
                    <CardDescription>{trans('profile.two_factor.description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    {is2FAEnabled ? (
                        <div className="space-y-3">
                            <div className="flex items-center gap-2">
                                <div className="flex size-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
                                    <svg
                                        className="size-5 text-green-600 dark:text-green-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p className="text-sm font-medium">{trans('profile.two_factor.enabled')}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {trans('profile.two_factor.method', {
                                            method: twoFactorStatus?.method?.toUpperCase() || 'TOTP',
                                        })}
                                    </p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">{trans('profile.two_factor.not_enabled')}</p>
                    )}
                    <div className="mt-4 flex justify-end">
                        {!is2FAEnabled && enableAction ? (
                            // Vue also bound `@success="handleEnableSuccess"`, which ActionButton never emits (no-op).
                            <ActionButton {...(enableAction as any)} />
                        ) : (
                            <Button variant={is2FAEnabled ? 'outline' : 'default'} onClick={handleOpenModal}>
                                {is2FAEnabled ? trans('profile.two_factor.manage') : trans('profile.two_factor.enable')}
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Two-Factor Modal */}
            <Modal
                open={showModal}
                onUpdateOpen={setShowModal}
                title={is2FAEnabled ? trans('profile.two_factor.disable_title_short') : trans('profile.two_factor.enable_title_short')}
                onClose={handleCloseModal}
            >
                {renderStep()}
            </Modal>
        </>
    );
}
