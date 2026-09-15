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
import { arrayBufferToBase64url, prepareCreationOptions, usePasskeys } from '../../composables/usePasskeys';

export default function PasskeysSection({ panelId }: { panelId?: string }) {
    // Initialize localization
    const { trans } = useLocalization();
    const page = usePage();

    const [showModal, setShowModal] = useState(false);
    const [showRegisterModal, setShowRegisterModal] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [passkeyToDelete, setPasskeyToDelete] = useState<string | null>(null);
    // Use the active panel (the page's top-level panelId prop), not always the 'user' panel
    const passkeys = usePasskeys(panelId ?? (page.props.panelId as string | undefined) ?? 'user');
    const [passkeyName, setPasskeyName] = useState<string>('');

    const handleOpenModal = async () => {
        setShowModal(true);
        await passkeys.fetchPasskeys();
    };

    const handleOpenRegisterModal = () => {
        setShowRegisterModal(true);
        setPasskeyName('');
    };

    const handleRegisterPasskey = async () => {
        if (!passkeyName) return;

        try {
            // Get registration options from the server
            const options = await passkeys.getRegistrationOptions();

            console.log('Raw options from server:', JSON.stringify(options, null, 2));

            // Prepare options by converting base64 strings to ArrayBuffers
            const publicKeyOptions = prepareCreationOptions(options);

            console.log('Prepared options:', publicKeyOptions);

            // Start WebAuthn registration
            const credential = (await navigator.credentials.create({
                publicKey: publicKeyOptions,
            })) as PublicKeyCredential;

            if (!credential) {
                throw new Error('Failed to create credential');
            }

            const response = credential.response as AuthenticatorAttestationResponse;

            // Register the passkey with the server using base64url encoding
            await passkeys.registerPasskey(passkeyName, {
                id: credential.id,
                rawId: arrayBufferToBase64url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: arrayBufferToBase64url(response.clientDataJSON),
                    attestationObject: arrayBufferToBase64url(response.attestationObject),
                },
            });

            setShowRegisterModal(false);
            setPasskeyName('');
        } catch (error: any) {
            console.error('Failed to register passkey:', error);
            passkeys.setError(error.message || 'Failed to register passkey. Please try again.');
        }
    };

    const confirmDeletePasskey = (passkeyId: string) => {
        setPasskeyToDelete(passkeyId);
        setShowDeleteConfirm(true);
    };

    const handleDeletePasskey = async () => {
        if (!passkeyToDelete) return;

        try {
            await passkeys.deletePasskey(passkeyToDelete);
            setShowDeleteConfirm(false);
            setPasskeyToDelete(null);
        } catch (error) {
            console.error('Failed to delete passkey:', error);
        }
    };

    const cancelDeletePasskey = () => {
        setShowDeleteConfirm(false);
        setPasskeyToDelete(null);
    };

    const handleCloseModal = () => {
        setShowModal(false);
    };

    const handleCloseRegisterModal = () => {
        setShowRegisterModal(false);
        setPasskeyName('');
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>{trans('profile.passkeys.title')}</CardTitle>
                    <CardDescription>{trans('profile.passkeys.description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">{trans('profile.passkeys.info')}</p>
                    <div className="mt-4 flex justify-end">
                        <Button onClick={handleOpenModal}>{trans('profile.passkeys.manage')}</Button>
                    </div>
                </CardContent>
            </Card>

            {/* Passkeys Modal */}
            <Modal
                open={showModal}
                onUpdateOpen={setShowModal}
                title={trans('profile.passkeys.title')}
                description={trans('profile.passkeys.modal_description')}
                onClose={handleCloseModal}
                footer={
                    <>
                        <Button variant="outline" onClick={handleCloseModal}>
                            {trans('common.close')}
                        </Button>
                        <Button onClick={handleOpenRegisterModal} disabled={passkeys.loading}>
                            {trans('profile.passkeys.register_new')}
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">{trans('profile.passkeys.biometric_info')}</p>

                    {passkeys.loading && passkeys.passkeys.length === 0 ? (
                        /* Loading State */
                        <div className="py-8 text-center">
                            <div className="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                            <p className="mt-2 text-sm text-muted-foreground">{trans('profile.passkeys.loading')}</p>
                        </div>
                    ) : passkeys.passkeys.length > 0 ? (
                        /* Passkeys List */
                        <div className="space-y-2">
                            <p className="text-sm font-medium">{trans('profile.passkeys.your_passkeys')}</p>
                            {passkeys.passkeys.map((passkey) => (
                                <div key={passkey.id} className="flex items-center gap-3 rounded-lg border p-3">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-muted">
                                        <svg className="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                            />
                                        </svg>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">{passkey.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {trans('profile.passkeys.created')} {passkey.created_at}
                                            {passkey.last_used_at ? (
                                                <span>
                                                    {' '}
                                                    • {trans('profile.passkeys.last_used')} {passkey.last_used_at}
                                                </span>
                                            ) : (
                                                <span> • {trans('profile.passkeys.never_used')}</span>
                                            )}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => confirmDeletePasskey(passkey.id)}
                                        disabled={passkeys.loading}
                                    >
                                        {trans('common.delete')}
                                    </Button>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-4 text-sm text-muted-foreground">
                            {trans('profile.passkeys.no_passkeys')}
                        </div>
                    )}

                    {passkeys.error && <p className="text-sm text-destructive">{passkeys.error}</p>}
                </div>
            </Modal>

            {/* Register Passkey Modal */}
            <Modal
                open={showRegisterModal}
                onUpdateOpen={setShowRegisterModal}
                title={trans('profile.passkeys.register_title')}
                description={trans('profile.passkeys.register_description')}
                onClose={handleCloseRegisterModal}
                footer={
                    <>
                        <Button variant="outline" onClick={handleCloseRegisterModal}>
                            {trans('common.cancel')}
                        </Button>
                        <Button onClick={handleRegisterPasskey} disabled={passkeys.loading || !passkeyName}>
                            {passkeys.loading ? trans('profile.passkeys.registering') : trans('profile.passkeys.register')}
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">{trans('profile.passkeys.name_hint')}</p>

                    <div className="space-y-2">
                        <Label htmlFor="passkey-name">{trans('profile.passkeys.passkey_name')}</Label>
                        <Input
                            id="passkey-name"
                            value={passkeyName}
                            onChange={(e) => setPasskeyName(e.target.value)}
                            type="text"
                            placeholder={trans('profile.passkeys.name_placeholder')}
                        />
                    </div>

                    {passkeys.error && <p className="text-sm text-destructive">{passkeys.error}</p>}
                </div>
            </Modal>

            {/* Delete Passkey Confirmation Dialog */}
            <Dialog open={showDeleteConfirm} onOpenChange={setShowDeleteConfirm}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{trans('profile.passkeys.delete_title')}</DialogTitle>
                        <DialogDescription>{trans('profile.passkeys.confirm_delete')}</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={cancelDeletePasskey}>
                            {trans('common.cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDeletePasskey} disabled={passkeys.loading}>
                            {passkeys.loading ? trans('common.deleting') : trans('common.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
