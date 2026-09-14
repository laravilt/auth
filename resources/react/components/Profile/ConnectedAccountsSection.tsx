import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Modal from '@laravilt/support/components/Modal';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useConnectedAccounts } from '../../composables/useConnectedAccounts';

export default function ConnectedAccountsSection() {
    // Initialize localization
    const { trans } = useLocalization();

    const [showModal, setShowModal] = useState(false);
    const connectedAccounts = useConnectedAccounts();

    const handleOpenModal = async () => {
        setShowModal(true);
        await connectedAccounts.fetchConnectedAccounts();
    };

    const handleConnectAccount = (provider: string) => {
        // Redirect to the OAuth provider
        window.location.href = `/auth/${provider}/redirect`;
    };

    const handleDisconnectAccount = async (provider: string) => {
        if (!confirm(trans('profile.connected_accounts.confirm_disconnect', { provider }))) {
            return;
        }

        try {
            await connectedAccounts.disconnectAccount(provider);
        } catch (error) {
            console.error('Failed to disconnect account:', error);
        }
    };

    const handleCloseModal = () => {
        setShowModal(false);
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>{trans('profile.connected_accounts.title')}</CardTitle>
                    <CardDescription>{trans('profile.connected_accounts.description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">{trans('profile.connected_accounts.info')}</p>
                    <div className="mt-4 flex justify-end">
                        <Button variant="outline" onClick={handleOpenModal}>
                            {trans('profile.connected_accounts.manage')}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Connected Accounts Modal */}
            <Modal
                open={showModal}
                onUpdateOpen={setShowModal}
                title={trans('profile.connected_accounts.title')}
                description={trans('profile.connected_accounts.modal_description')}
                onClose={handleCloseModal}
                footer={
                    <Button variant="outline" onClick={handleCloseModal}>
                        {trans('common.close')}
                    </Button>
                }
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">{trans('profile.connected_accounts.social_info')}</p>

                    {connectedAccounts.loading && connectedAccounts.providers.length === 0 ? (
                        /* Loading State */
                        <div className="py-8 text-center">
                            <div className="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                            <p className="mt-2 text-sm text-muted-foreground">{trans('profile.connected_accounts.loading')}</p>
                        </div>
                    ) : connectedAccounts.providers.length > 0 ? (
                        /* Providers List */
                        <div className="space-y-3">
                            {connectedAccounts.providers.map((provider) => (
                                <div key={provider.name} className="flex items-center gap-3 rounded-lg border p-3">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-muted">
                                        <svg className="size-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d={connectedAccounts.getProviderIcon(provider.name)} />
                                        </svg>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">{provider.label}</p>
                                        {provider.connected && provider.account ? (
                                            <p className="text-xs text-muted-foreground">
                                                {trans('profile.connected_accounts.connected_as')}{' '}
                                                {provider.account.name || provider.account.email}
                                            </p>
                                        ) : (
                                            <p className="text-xs text-muted-foreground">
                                                {trans('profile.connected_accounts.not_connected')}
                                            </p>
                                        )}
                                    </div>
                                    {provider.connected ? (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => handleDisconnectAccount(provider.name)}
                                            disabled={connectedAccounts.loading}
                                        >
                                            {trans('profile.connected_accounts.disconnect')}
                                        </Button>
                                    ) : (
                                        <Button
                                            size="sm"
                                            onClick={() => handleConnectAccount(provider.name)}
                                            disabled={connectedAccounts.loading}
                                        >
                                            {trans('profile.connected_accounts.connect')}
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-4 text-sm text-muted-foreground">
                            {trans('profile.connected_accounts.no_providers')}
                        </div>
                    )}

                    {connectedAccounts.error && <p className="text-sm text-destructive">{connectedAccounts.error}</p>}
                </div>
            </Modal>
        </>
    );
}
