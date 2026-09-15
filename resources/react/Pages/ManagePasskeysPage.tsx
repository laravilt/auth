import { Head, router } from '@inertiajs/react';
import { AlertCircle, Fingerprint, Key, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SettingsLayout from '@laravilt/panel/layouts/SettingsLayout';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

interface PageData {
    heading: string;
    subheading?: string | null;
}

interface Passkey {
    id: string;
    name: string;
    created_at: string;
    last_used_at?: string;
    deleteAction?: any;
}

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

export interface ManagePasskeysPageProps {
    page: PageData;
    breadcrumbs?: BreadcrumbItem[];
    passkeys: Passkey[];
    registerOptionsUrl: string;
    registerUrl: string;
    canRegister: boolean;
    maxPasskeys: number;
    clusterNavigation?: any[];
    clusterTitle?: string;
    clusterDescription?: string;
}

// Check if a string is a valid hex string
function isHexString(str: string): boolean {
    return /^[0-9a-fA-F]+$/.test(str) && str.length % 2 === 0;
}

// Convert hex string to Uint8Array
function hexToUint8Array(hex: string): Uint8Array {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }
    return bytes;
}

// Convert base64url, base64, or hex string to Uint8Array
function stringToUint8Array(input: string): Uint8Array {
    if (!input || typeof input !== 'string') {
        throw new Error(`Invalid input: ${typeof input}`);
    }

    // Check if it's a hex string (like user.id from Laragear)
    if (isHexString(input)) {
        return hexToUint8Array(input);
    }

    // Otherwise treat as base64url/base64
    const base64 = input.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + ((4 - (base64.length % 4)) % 4), '=');
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
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

// Prepare WebAuthn creation options
function prepareWebAuthnOptions(options: any): PublicKeyCredentialCreationOptions {
    const prepared: PublicKeyCredentialCreationOptions = {
        rp: options.rp,
        challenge: stringToUint8Array(options.challenge) as BufferSource,
        user: {
            ...options.user,
            id: stringToUint8Array(options.user.id) as BufferSource,
        },
        pubKeyCredParams: options.pubKeyCredParams,
    };

    if (options.timeout) {
        prepared.timeout = options.timeout;
    }

    if (options.attestation) {
        prepared.attestation = options.attestation;
    }

    if (options.authenticatorSelection) {
        prepared.authenticatorSelection = options.authenticatorSelection;
    }

    // Handle excludeCredentials - convert each id to Uint8Array
    if (options.excludeCredentials && Array.isArray(options.excludeCredentials)) {
        prepared.excludeCredentials = options.excludeCredentials
            .filter((cred: any) => cred && cred.id)
            .map((cred: any) => ({
                type: cred.type || 'public-key',
                id: stringToUint8Array(cred.id) as BufferSource,
                transports: cred.transports,
            }));
    }

    return prepared;
}

export default function ManagePasskeysPage({
    page,
    breadcrumbs,
    passkeys,
    registerOptionsUrl,
    registerUrl,
    canRegister,
    maxPasskeys,
    clusterNavigation,
    clusterTitle,
    clusterDescription,
}: ManagePasskeysPageProps) {
    const { trans } = useLocalization();

    const [isPageLoading, setIsPageLoading] = useState(true);

    useEffect(() => {
        const timeout = setTimeout(() => {
            setIsPageLoading(false);
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

    const [showModal, setShowModal] = useState(false);
    const [passkeyName, setPasskeyName] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    // Handle passkey registration
    const handlePasskeyRegistration = async () => {
        if (!passkeyName.trim()) {
            alert('Please enter a name for your passkey');
            return;
        }

        setIsLoading(true);
        const formData = { name: passkeyName };
        try {
            console.log('Starting passkey registration with name:', formData.name);

            // Get WebAuthn creation options from server
            const optionsResponse = await fetch(registerOptionsUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!optionsResponse.ok) {
                const text = await optionsResponse.text();
                console.error('Failed to fetch options:', optionsResponse.status, text);
                throw new Error(`Failed to fetch WebAuthn options: ${optionsResponse.status}`);
            }

            const options = await optionsResponse.json();

            console.log('Received WebAuthn options:', options);

            // Prepare options by converting strings to ArrayBuffers
            const publicKeyOptions = prepareWebAuthnOptions(options);

            // Create credential using WebAuthn API
            console.log('Calling navigator.credentials.create...');
            const credential = (await navigator.credentials.create({
                publicKey: publicKeyOptions,
            })) as PublicKeyCredential;

            if (!credential) {
                throw new Error('No credential received');
            }

            console.log('Credential created:', credential.id);

            // Prepare credential data for server
            const attestationResponse = credential.response as AuthenticatorAttestationResponse;
            const credentialData = {
                id: credential.id,
                rawId: arrayBufferToBase64url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64url(attestationResponse.clientDataJSON),
                    attestationObject: arrayBufferToBase64url(attestationResponse.attestationObject),
                },
            };

            console.log('Sending credential to server...');

            // Send credential to server
            // Merge the credential data with the name at root level
            router.post(
                registerUrl,
                {
                    ...credentialData,
                    name: formData.name,
                },
                {
                    preserveState: false,
                    preserveScroll: false,
                    onFinish: () => {
                        setIsLoading(false);
                        setShowModal(false);
                        setPasskeyName('');
                    },
                },
            );
        } catch (error) {
            console.error('Passkey registration failed:', error);
            alert('Failed to register passkey: ' + (error as Error).message);
            setIsLoading(false);
        }
    };

    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [passkeyToDelete, setPasskeyToDelete] = useState<string | null>(null);

    const confirmDeletePasskey = (passkeyId: string) => {
        setPasskeyToDelete(passkeyId);
        setShowDeleteDialog(true);
    };

    const removePasskey = () => {
        if (passkeyToDelete) {
            // Only strip the trailing /register segment; the host or panel path may also contain "/register"
            router.delete(`${registerUrl.replace(/\/register$/, '')}/${passkeyToDelete}`, {
                preserveState: false,
                preserveScroll: false,
                onFinish: () => {
                    setShowDeleteDialog(false);
                    setPasskeyToDelete(null);
                },
            });
        }
    };

    const cancelDelete = () => {
        setShowDeleteDialog(false);
        setPasskeyToDelete(null);
    };

    return (
        <>
            <Head title={page.heading} />

            <SettingsLayout
                breadcrumbs={transformedBreadcrumbs}
                navigation={clusterNavigation}
                title={clusterTitle}
                description={clusterDescription}
                loading={isPageLoading}
            >
                <section className="max-w-2xl space-y-6">
                    {/* Page Header */}
                    <div className="flex items-center justify-between">
                        <header>
                            <h3 className="mb-0.5 text-base font-medium">{page.heading}</h3>
                            {page.subheading && <p className="text-sm text-muted-foreground">{page.subheading}</p>}
                        </header>

                        <Dialog open={showModal} onOpenChange={setShowModal}>
                            <Button onClick={() => setShowModal(true)} disabled={!canRegister || passkeys.length >= maxPasskeys}>
                                <Plus className="h-4 w-4 me-2" />
                                {trans('laravilt-auth::auth.profile.passkeys.register_new')}
                            </Button>

                            <DialogContent>
                                <DialogHeader className="text-start">
                                    <div className="flex items-center justify-center w-12 h-12 mb-4 rounded-full bg-primary/10">
                                        <Key className="h-6 w-6 text-primary" />
                                    </div>
                                    <DialogTitle>{trans('laravilt-auth::auth.profile.passkeys.register_title')}</DialogTitle>
                                    <DialogDescription>
                                        {trans('laravilt-auth::auth.profile.passkeys.register_description')}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="space-y-4 py-4">
                                    <div className="space-y-2 text-start">
                                        <Label htmlFor="passkey-name">{trans('laravilt-auth::auth.profile.passkeys.passkey_name')}</Label>
                                        <Input
                                            id="passkey-name"
                                            value={passkeyName}
                                            onChange={(e) => setPasskeyName(e.target.value)}
                                            placeholder={trans('laravilt-auth::auth.profile.passkeys.name_placeholder')}
                                            onKeyUp={(e) => {
                                                if (e.key === 'Enter') {
                                                    handlePasskeyRegistration();
                                                }
                                            }}
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            {trans('laravilt-auth::auth.profile.passkeys.name_hint')}
                                        </p>
                                    </div>

                                    <div className="flex justify-end gap-2 rtl:flex-row-reverse">
                                        <Button variant="outline" onClick={() => setShowModal(false)} disabled={isLoading}>
                                            {trans('laravilt-auth::auth.common.cancel')}
                                        </Button>
                                        <Button onClick={handlePasskeyRegistration} disabled={isLoading || !passkeyName.trim()}>
                                            {isLoading
                                                ? trans('laravilt-auth::auth.profile.passkeys.registering')
                                                : trans('laravilt-auth::auth.profile.passkeys.register')}
                                        </Button>
                                    </div>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>

                    {/* Info Alert */}
                    <div>
                        <Card className="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950">
                            <CardContent>
                                <div className="flex items-start gap-2">
                                    <Fingerprint className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                                    <div>
                                        <p className="font-medium text-blue-900 dark:text-blue-100 mb-1">
                                            {trans('laravilt-auth::auth.profile.passkeys.what_are_passkeys')}
                                        </p>
                                        <p className="text-sm text-blue-800 dark:text-blue-200">
                                            {trans('laravilt-auth::auth.profile.passkeys.what_are_passkeys_desc')}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Passkey Limit Warning */}
                    {passkeys.length >= maxPasskeys && (
                        <div>
                            <Card className="border-yellow-200 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950">
                                <CardContent className="pt-6">
                                    <div className="flex items-start gap-2">
                                        <AlertCircle className="h-5 w-5 text-yellow-600 dark:text-yellow-500 mt-0.5" />
                                        <div>
                                            <p className="font-medium text-yellow-900 dark:text-yellow-100">
                                                {trans('laravilt-auth::auth.profile.passkeys.limit_reached')}
                                            </p>
                                            <p className="text-sm text-yellow-800 dark:text-yellow-200">
                                                {trans('laravilt-auth::auth.profile.passkeys.limit_reached_desc', { max: maxPasskeys })}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {passkeys.length === 0 ? (
                        /* Empty State */
                        <div>
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <Fingerprint className="h-12 w-12 text-muted-foreground/50 mb-4" />
                                    <p className="text-lg font-medium mb-1">
                                        {trans('laravilt-auth::auth.profile.passkeys.no_passkeys_yet')}
                                    </p>
                                    <p className="text-sm text-muted-foreground mb-4">
                                        {trans('laravilt-auth::auth.profile.passkeys.add_passkey_desc')}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    ) : (
                        /* Passkeys List */
                        <div className="space-y-3">
                            {passkeys.map((passkey) => (
                                <Card key={passkey.id}>
                                    <CardContent className="p-6">
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-start gap-3 flex-1">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                                    <Fingerprint className="h-5 w-5 text-primary" />
                                                </div>
                                                <div className="flex-1">
                                                    <h4 className="font-medium mb-1">{passkey.name}</h4>
                                                    <div className="flex gap-4 text-xs text-muted-foreground">
                                                        <span>
                                                            {trans('laravilt-auth::auth.profile.passkeys.added')} {passkey.created_at}
                                                        </span>
                                                        {passkey.last_used_at ? (
                                                            <span>
                                                                {trans('laravilt-auth::auth.profile.passkeys.last_used')}{' '}
                                                                {passkey.last_used_at}
                                                            </span>
                                                        ) : (
                                                            <span>{trans('laravilt-auth::auth.profile.passkeys.never_used')}</span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <Button
                                                onClick={() => confirmDeletePasskey(passkey.id)}
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>

                {/* Delete Passkey Confirmation Dialog */}
                <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                    <DialogContent>
                        <DialogHeader className="text-start">
                            <DialogTitle>{trans('laravilt-auth::auth.profile.passkeys.delete_title')}</DialogTitle>
                            <DialogDescription>{trans('laravilt-auth::auth.profile.passkeys.confirm_delete')}</DialogDescription>
                        </DialogHeader>
                        <div className="flex justify-end gap-2 pt-4 rtl:flex-row-reverse">
                            <Button variant="outline" onClick={cancelDelete}>
                                {trans('laravilt-auth::auth.common.cancel')}
                            </Button>
                            <Button variant="destructive" onClick={removePasskey}>
                                {trans('laravilt-auth::auth.common.delete')}
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </SettingsLayout>
        </>
    );
}
