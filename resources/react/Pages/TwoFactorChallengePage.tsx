import { Head, Link, router, usePage } from '@inertiajs/react';
import { Fingerprint, Hash, KeyRound, Mail, type LucideIcon } from 'lucide-react';
import { useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import ErrorProvider from '@laravilt/forms/components/ErrorProvider';
import Form from '@laravilt/forms/components/Form';
import { useNotification } from '@laravilt/notifications/composables/useNotification';
import CardLayout from '@laravilt/panel/layouts/CardLayout';
import { useLocalization } from '@laravilt/support/composables';

export interface TwoFactorChallengePageProps {
    page: {
        heading: string;
        subheading?: string;
        headerActions?: any[];
        actionUrl: string;
    };
    schema: any[];
    hasTwoFactorRecovery?: boolean;
    recoveryUrl?: string;
    hasPasskeys?: boolean;
    passkeyLoginOptionsUrl?: string;
    passkeyLoginUrl?: string;
    hasMagicLinks?: boolean;
    magicLinkSendUrl?: string;
    userTwoFactorMethod?: string;
    resendUrl?: string;
}

type AuthMethod = 'code' | 'passkey' | 'magic-link' | 'recovery';

// Helper functions
function base64urlDecode(base64url: string): ArrayBuffer {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + ((4 - (base64.length % 4)) % 4), '=');
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

function arrayBufferToBase64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

export default function TwoFactorChallengePage(props: TwoFactorChallengePageProps) {
    const { schema, recoveryUrl } = props;
    const { trans } = useLocalization();

    const [selectedMethod, setSelectedMethod] = useState<AuthMethod | null>(null);
    const [confirmedMethod, setConfirmedMethod] = useState<AuthMethod | null>(null);
    const [sendingMagicLink, setSendingMagicLink] = useState(false);
    const [resendingCode, setResendingCode] = useState(false);
    const { notify } = useNotification();
    const formRendererRef = useRef<any>(null);
    const page = usePage();
    const [processing, setProcessing] = useState(false);

    // Passkey login handler
    const handlePasskeyLogin = async () => {
        if (!props.passkeyLoginOptionsUrl || !props.passkeyLoginUrl) {
            return;
        }

        try {
            console.log('Starting passkey login...');

            const optionsResponse = await fetch(props.passkeyLoginOptionsUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!optionsResponse.ok) {
                throw new Error(`Failed to fetch WebAuthn options: ${optionsResponse.status}`);
            }

            const options = await optionsResponse.json();

            options.challenge = base64urlDecode(options.challenge);
            if (options.allowCredentials) {
                options.allowCredentials = options.allowCredentials.map((cred: any) => ({
                    ...cred,
                    id: base64urlDecode(cred.id),
                }));
            }

            const credential = (await navigator.credentials.get({
                publicKey: options,
            })) as PublicKeyCredential;

            if (!credential) {
                throw new Error('No credential received');
            }

            const assertionResponse = credential.response as AuthenticatorAssertionResponse;
            const assertionData = {
                id: credential.id,
                rawId: arrayBufferToBase64url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64url(assertionResponse.clientDataJSON),
                    authenticatorData: arrayBufferToBase64url(assertionResponse.authenticatorData),
                    signature: arrayBufferToBase64url(assertionResponse.signature),
                    userHandle: assertionResponse.userHandle ? arrayBufferToBase64url(assertionResponse.userHandle) : null,
                },
            };

            const response = await fetch(props.passkeyLoginUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(assertionData),
            });

            if (!response.ok) {
                throw new Error(`Login failed: ${response.status}`);
            }

            const result = await response.json();

            if (result.redirect) {
                window.location.href = result.redirect;
            }
        } catch (error) {
            console.error('Passkey login failed:', error);
            notify({
                type: 'error',
                message: 'Failed to login with passkey: ' + (error as Error).message,
            });
        }
    };

    // Magic link handler
    const handleSendMagicLink = async () => {
        if (!props.magicLinkSendUrl || sendingMagicLink) {
            return;
        }

        setSendingMagicLink(true);

        try {
            const response = await fetch(props.magicLinkSendUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to send magic link');
            }

            await response.json();
            notify({
                type: 'success',
                message: trans('laravilt-auth::auth.two_factor_challenge.magic_link_sent'),
            });
        } catch (error) {
            console.error('Failed to send magic link:', error);
            notify({
                type: 'error',
                message: trans('laravilt-auth::auth.two_factor_challenge.magic_link_error') + ': ' + (error as Error).message,
            });
        } finally {
            setSendingMagicLink(false);
        }
    };

    // Resend 2FA email code handler
    const handleResendCode = async () => {
        if (!props.resendUrl || resendingCode) {
            return;
        }

        setResendingCode(true);

        try {
            const response = await fetch(props.resendUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || trans('laravilt-auth::auth.two_factor_challenge.resend_error'));
            }

            await response.json();
            notify({
                type: 'success',
                message: trans('laravilt-auth::auth.two_factor_challenge.code_resent'),
            });
        } catch (error) {
            console.error('Failed to resend code:', error);
            notify({
                type: 'error',
                message: (error as Error).message,
            });
        } finally {
            setResendingCode(false);
        }
    };

    // Handle continue button click
    const handleContinue = () => {
        if (!selectedMethod) return;

        // If it's a form method, show the form
        if (selectedMethod === 'code' || selectedMethod === 'recovery') {
            setConfirmedMethod(selectedMethod);
            return;
        }

        // If it's an action method, execute immediately
        if (selectedMethod === 'passkey') {
            handlePasskeyLogin();
        } else if (selectedMethod === 'magic-link') {
            handleSendMagicLink();
        }
    };

    // Handle back button
    const handleBack = () => {
        setConfirmedMethod(null);
        setSelectedMethod(null);
    };

    // Handle form submit
    const handleFormSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (processing) return;

        // Get form data from Form
        let data: Record<string, any> = {};
        if (formRendererRef.current && typeof formRendererRef.current.getFormData === 'function') {
            data = formRendererRef.current.getFormData();
        } else {
            console.error('Form ref not available');
            return;
        }

        setProcessing(true);

        // Submit via router to the current page (POST route)
        router.post(window.location.pathname, data, {
            preserveState: (page) => Object.keys(page.props.errors || {}).length > 0,
            preserveScroll: true,
            onError: (errors) => {
                console.error('Validation errors:', errors);
            },
            onSuccess: (page) => {
                // Check for redirect in response
                const redirect = (page?.props as any)?.redirect;
                if (redirect) {
                    window.location.href = redirect;
                }
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    // Count available authentication methods
    const availableMethods: Array<{ id: AuthMethod; label: string; description: string; icon: LucideIcon }> = [
        {
            id: 'code',
            label: trans('laravilt-auth::auth.two_factor_challenge.authenticator_code'),
            description: trans('laravilt-auth::auth.two_factor_challenge.authenticator_desc'),
            icon: Hash,
        },
    ];

    if (props.hasPasskeys) {
        availableMethods.push({
            id: 'passkey',
            label: trans('laravilt-auth::auth.two_factor_challenge.passkey'),
            description: trans('laravilt-auth::auth.two_factor_challenge.passkey_desc'),
            icon: Fingerprint,
        });
    }

    if (props.hasMagicLinks) {
        availableMethods.push({
            id: 'magic-link',
            label: trans('laravilt-auth::auth.two_factor_challenge.magic_link'),
            description: trans('laravilt-auth::auth.two_factor_challenge.magic_link_desc'),
            icon: Mail,
        });
    }

    if (props.hasTwoFactorRecovery) {
        availableMethods.push({
            id: 'recovery',
            label: trans('laravilt-auth::auth.two_factor_challenge.recovery_code'),
            description: trans('laravilt-auth::auth.two_factor_challenge.recovery_code_desc'),
            icon: KeyRound,
        });
    }

    // Check if user is using email 2FA
    const isEmailTwoFactor = props.userTwoFactorMethod === 'email';

    return (
        <CardLayout
            title={trans('laravilt-auth::auth.two_factor_challenge.title')}
            description={!confirmedMethod ? trans('laravilt-auth::auth.two_factor_challenge.choose_method') : undefined}
        >
            <Head title={trans('laravilt-auth::auth.two_factor_challenge.title')} />

            <div className="space-y-4">
                {/* Back Button (shown when a method has been confirmed) */}
                {confirmedMethod && (
                    <button
                        onClick={handleBack}
                        type="button"
                        className="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <svg className="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        {trans('laravilt-auth::auth.two_factor_challenge.back_to_methods')}
                    </button>
                )}

                {/* Method Selection Screen (only show when no method is confirmed) */}
                {!confirmedMethod && (
                    <div className="flex flex-col gap-3">
                        <div className="grid grid-cols-1 gap-2">
                            {availableMethods.map((method) => {
                                const Icon = method.icon;

                                return (
                                    <button
                                        key={method.id}
                                        type="button"
                                        onClick={() => setSelectedMethod(method.id)}
                                        className={[
                                            'relative flex flex-col items-start gap-2 rounded-lg border-2 p-4 text-start transition-all hover:bg-accent',
                                            selectedMethod === method.id ? 'border-primary bg-accent' : '',
                                            selectedMethod !== method.id ? 'border-muted' : '',
                                        ]
                                            .filter(Boolean)
                                            .join(' ')}
                                    >
                                        <div className="flex items-center gap-3 w-full">
                                            <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary shrink-0">
                                                <Icon className="w-5 h-5" />
                                            </div>
                                            <div className="flex-1 min-w-0 text-start">
                                                <p className="font-medium text-sm leading-none mb-1">{method.label}</p>
                                                <p className="text-xs text-muted-foreground">{method.description}</p>
                                            </div>
                                            {selectedMethod === method.id && (
                                                <div className="h-4 w-4 rounded-full bg-primary flex items-center justify-center shrink-0">
                                                    <svg
                                                        className="h-3 w-3 text-primary-foreground"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Continue Button */}
                        <Button onClick={handleContinue} disabled={!selectedMethod} className="w-full" size="lg">
                            {trans('laravilt-auth::auth.common.continue')}
                        </Button>
                    </div>
                )}

                {/* Authenticator Code Form */}
                {confirmedMethod === 'code' && (
                    <div>
                        <form onSubmit={handleFormSubmit} className="flex flex-col gap-4">
                            <ErrorProvider errors={(page.props.errors as Record<string, string | string[]>) || {}}>
                                <Form ref={formRendererRef} schema={schema} />

                                <Button type="submit" className="w-full" disabled={processing}>
                                    {processing
                                        ? trans('laravilt-auth::auth.two_factor_challenge.verify_loading')
                                        : trans('laravilt-auth::auth.two_factor_challenge.verify_button')}
                                </Button>
                            </ErrorProvider>
                        </form>

                        {/* Resend Code (only for email 2FA) */}
                        {isEmailTwoFactor && (
                            <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground mt-4">
                                <span>{trans('laravilt-auth::auth.two_factor_challenge.didnt_receive')}</span>
                                <button
                                    type="button"
                                    className="text-primary hover:underline cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled={resendingCode}
                                    onClick={handleResendCode}
                                >
                                    {resendingCode
                                        ? trans('laravilt-auth::auth.two_factor_challenge.resending')
                                        : trans('laravilt-auth::auth.two_factor_challenge.resend')}
                                </button>
                            </div>
                        )}
                    </div>
                )}

                {/* Recovery Code Form */}
                {confirmedMethod === 'recovery' && (
                    <div className="flex flex-col gap-4 items-center py-4">
                        <div className="text-center space-y-2">
                            <p className="text-sm text-muted-foreground">
                                {trans('laravilt-auth::auth.two_factor_challenge.use_emergency_code')}
                            </p>
                        </div>
                        <Button asChild variant="outline" className="w-full" size="lg">
                            <Link href={recoveryUrl as string}>
                                <svg className="w-5 h-5 me-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                                {trans('laravilt-auth::auth.two_factor_challenge.enter_recovery')}
                            </Link>
                        </Button>
                    </div>
                )}
            </div>
        </CardLayout>
    );
}
