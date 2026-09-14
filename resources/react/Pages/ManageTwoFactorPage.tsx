import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, Download, Key, Shield } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider';
import LaraviltForm from '@laravilt/forms/components/Form';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout';
import { useLocalization } from '@laravilt/support/composables';

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

export interface ManageTwoFactorPageProps {
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    twoFactorEnabled: boolean;
    twoFactorMethod?: string | null;
    qrCode?: string | null;
    secret?: string | null;
    recoveryCodes?: string[] | null;
    needsConfirmation: boolean;
    enableAction: string;
    disableAction: string;
    confirmAction: string;
    cancelAction: string;
    regenerateAction: string;
    enableSchema: any[];
    confirmSchema: any[];
    disableSchema: any[];
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}

export default function ManageTwoFactorPage({
    page,
    breadcrumbs,
    twoFactorEnabled,
    twoFactorMethod,
    qrCode,
    secret,
    recoveryCodes,
    needsConfirmation,
    regenerateAction,
    enableSchema,
    confirmSchema,
    disableSchema,
    clusterNavigation,
    clusterTitle,
    clusterDescription,
}: ManageTwoFactorPageProps) {
    const { trans } = useLocalization();

    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        // Small delay to ensure smooth transition
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

    const [showRecoveryCodes, setShowRecoveryCodes] = useState(false);

    // Show confirm step if QR code is present OR if confirmation is needed (email method)
    const currentStep = qrCode || needsConfirmation ? 'confirm' : !twoFactorEnabled ? 'enable' : 'enabled';

    const downloadRecoveryCodes = () => {
        if (!recoveryCodes) return;

        const content = recoveryCodes.join('\n');
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'recovery-codes.txt';
        link.click();
        URL.revokeObjectURL(url);
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
                    <header>
                        <h3 className="mb-0.5 text-base font-medium">{page.heading}</h3>
                        {page.subheading && <p className="text-sm text-muted-foreground">{page.subheading}</p>}
                    </header>

                    {/* Enable Two-Factor */}
                    {currentStep === 'enable' && (
                        <div className="space-y-6">
                            <div className="flex items-start gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 shrink-0">
                                    <Shield className="h-5 w-5 text-primary" />
                                </div>
                                <div className="flex-1 text-start">
                                    <h4 className="font-medium">{trans('laravilt-auth::auth.profile.two_factor.enable_title')}</h4>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        {trans('laravilt-auth::auth.profile.two_factor.description')}
                                    </p>
                                </div>
                            </div>

                            <ErrorProvider errors={{}}>
                                <LaraviltForm schema={enableSchema} />
                            </ErrorProvider>
                        </div>
                    )}

                    {/* Confirm Two-Factor Setup */}
                    {currentStep === 'confirm' && (
                        <div className="space-y-6 pt-6">
                            <div className="space-y-1 text-start">
                                <h4 className="font-medium">
                                    {qrCode
                                        ? trans('laravilt-auth::auth.profile.two_factor.scan_qr_title')
                                        : twoFactorMethod === 'email'
                                          ? trans('laravilt-auth::auth.profile.two_factor.check_email')
                                          : trans('laravilt-auth::auth.profile.two_factor.verify_code')}
                                </h4>
                                <p className="text-sm text-muted-foreground">
                                    {qrCode
                                        ? trans('laravilt-auth::auth.profile.two_factor.scan_qr')
                                        : twoFactorMethod === 'email'
                                          ? trans('laravilt-auth::auth.profile.two_factor.code_sent')
                                          : trans('laravilt-auth::auth.profile.two_factor.check_email_code')}
                                </p>
                            </div>

                            {qrCode && (
                                <div className="flex flex-col items-center gap-4 py-4">
                                    <div
                                        className="rounded-lg border p-4 bg-white dark:bg-neutral-950"
                                        dangerouslySetInnerHTML={{ __html: qrCode }}
                                    />

                                    {secret && (
                                        <div className="w-full rounded-lg bg-muted p-3">
                                            <p className="text-center text-xs text-muted-foreground mb-1">
                                                {trans('laravilt-auth::auth.profile.two_factor.enter_manually')}
                                            </p>
                                            <p className="text-center font-mono text-sm font-medium" dir="ltr">
                                                {secret}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="space-y-4 pt-6 border-t dark:border-neutral-900">
                                <div className="space-y-1 text-start">
                                    <h4 className="font-medium">{trans('laravilt-auth::auth.profile.two_factor.verify_setup')}</h4>
                                    <p className="text-sm text-muted-foreground">
                                        {qrCode
                                            ? trans('laravilt-auth::auth.profile.two_factor.enter_code_app')
                                            : trans('laravilt-auth::auth.profile.two_factor.enter_code_email')}
                                    </p>
                                </div>

                                <ErrorProvider errors={{}}>
                                    <LaraviltForm schema={confirmSchema} />
                                </ErrorProvider>
                            </div>
                        </div>
                    )}

                    {/* Two-Factor Enabled */}
                    {currentStep === 'enabled' && (
                        <div className="space-y-6">
                            {/* Status */}
                            <Alert className="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
                                <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400" />
                                <AlertDescription className="text-green-800 dark:text-green-200">
                                    <span className="font-medium">{trans('laravilt-auth::auth.profile.two_factor.enabled')}</span>
                                    {twoFactorMethod && (
                                        <span className="block mt-1">
                                            {trans('laravilt-auth::auth.profile.two_factor.using_method', {
                                                method:
                                                    twoFactorMethod === 'totp'
                                                        ? trans('laravilt-auth::auth.profile.two_factor.method_totp')
                                                        : trans('laravilt-auth::auth.profile.two_factor.method_email'),
                                            })}
                                        </span>
                                    )}
                                </AlertDescription>
                            </Alert>

                            {/* Recovery Codes */}
                            {recoveryCodes && recoveryCodes.length > 0 && (
                                <div className="space-y-4">
                                    <div className="flex items-start gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500/10 shrink-0">
                                            <Key className="h-5 w-5 text-yellow-600 dark:text-yellow-500" />
                                        </div>
                                        <div className="flex-1 text-start">
                                            <h4 className="font-medium">
                                                {trans('laravilt-auth::auth.profile.two_factor.recovery_codes_title')}
                                            </h4>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                {trans('laravilt-auth::auth.profile.two_factor.recovery_codes_desc')}
                                            </p>
                                        </div>
                                    </div>

                                    {showRecoveryCodes ? (
                                        <div className="grid grid-cols-2 gap-2 rounded-lg bg-muted p-4 max-h-48 overflow-y-auto">
                                            {recoveryCodes.map((code, index) => (
                                                <code key={index} className="text-center font-mono text-sm">
                                                    {code}
                                                </code>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="rounded-lg border border-dashed p-8 text-center">
                                            <p className="text-sm text-muted-foreground">
                                                {trans('laravilt-auth::auth.profile.two_factor.click_to_view')}
                                            </p>
                                        </div>
                                    )}

                                    <div className="flex gap-2">
                                        <Button
                                            onClick={() => setShowRecoveryCodes(!showRecoveryCodes)}
                                            variant="outline"
                                            className="flex-1"
                                        >
                                            {showRecoveryCodes
                                                ? trans('laravilt-auth::auth.profile.two_factor.hide_codes')
                                                : trans('laravilt-auth::auth.profile.two_factor.show_codes')}
                                        </Button>
                                        {showRecoveryCodes && (
                                            <Button onClick={downloadRecoveryCodes} variant="outline">
                                                <Download className="h-4 w-4 me-2" />
                                                {trans('laravilt-auth::auth.profile.two_factor.download')}
                                            </Button>
                                        )}
                                    </div>

                                    <Form action={regenerateAction} method="post">
                                        {({ processing }) => (
                                            <Button type="submit" variant="ghost" size="sm" disabled={processing} className="w-full">
                                                {processing
                                                    ? trans('laravilt-auth::auth.profile.two_factor.regenerating')
                                                    : trans('laravilt-auth::auth.profile.two_factor.regenerate_codes')}
                                            </Button>
                                        )}
                                    </Form>
                                </div>
                            )}

                            {/* Disable Two-Factor */}
                            <div className="space-y-4 border-t pt-6 dark:border-neutral-900 text-start">
                                <h4 className="font-medium">{trans('laravilt-auth::auth.profile.two_factor.disable_title')}</h4>
                                <p className="text-sm text-muted-foreground">
                                    {trans('laravilt-auth::auth.profile.two_factor.disable_desc')}
                                </p>

                                <ErrorProvider errors={{}}>
                                    <LaraviltForm schema={disableSchema} />
                                </ErrorProvider>
                            </div>
                        </div>
                    )}
                </section>
            </SettingsLayout>
        </>
    );
}
