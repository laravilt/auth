import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Modal from '@laravilt/support/components/Modal';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useApiTokens } from '../../composables/useApiTokens';

export default function ApiTokensSection({ panelId }: { panelId?: string }) {
    // Initialize localization
    const { trans } = useLocalization();
    const page = usePage();

    const [showModal, setShowModal] = useState(false);
    const [showTokenCreated, setShowTokenCreated] = useState(false);
    // Use the active panel (the page's top-level panelId prop), not always the 'user' panel
    const apiTokens = useApiTokens(panelId ?? (page.props.panelId as string | undefined) ?? 'user');
    const [tokenName, setTokenName] = useState<string>('');
    const [tokenAbilities, setTokenAbilities] = useState<string[]>(['read']);

    const handleOpenModal = async () => {
        setShowModal(true);
        setShowTokenCreated(false);
        await apiTokens.fetchTokens();
    };

    const handleCreateToken = async () => {
        if (!tokenName) return;

        try {
            await apiTokens.createToken(tokenName, tokenAbilities);
            setShowTokenCreated(true);
            setTokenName('');
            setTokenAbilities(['read']);
        } catch (error) {
            console.error('Failed to create token:', error);
        }
    };

    const handleDeleteToken = async (tokenId: number) => {
        if (!confirm(trans('profile.api_tokens.confirm_delete'))) {
            return;
        }

        try {
            await apiTokens.deleteToken(tokenId);
        } catch (error) {
            console.error('Failed to delete token:', error);
        }
    };

    const handleCloseTokenCreated = () => {
        setShowTokenCreated(false);
        apiTokens.clearNewToken();
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setShowTokenCreated(false);
        setTokenName('');
        setTokenAbilities(['read']);
    };

    const copyToken = (token: string) => {
        navigator.clipboard.writeText(token);
    };

    const toggleAbility = (ability: string) => {
        setTokenAbilities((previous) =>
            previous.includes(ability) ? previous.filter((a) => a !== ability) : [...previous, ability],
        );
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>{trans('profile.api_tokens.title')}</CardTitle>
                    <CardDescription>{trans('profile.api_tokens.description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">{trans('profile.api_tokens.info')}</p>
                    <div className="mt-4 flex justify-end">
                        <Button onClick={handleOpenModal}>{trans('profile.api_tokens.manage')}</Button>
                    </div>
                </CardContent>
            </Card>

            {/* API Tokens Modal */}
            <Modal
                open={showModal}
                onUpdateOpen={setShowModal}
                title={trans('profile.api_tokens.title')}
                description={trans('profile.api_tokens.modal_description')}
                onClose={handleCloseModal}
                footer={
                    <>
                        <Button variant="outline" onClick={handleCloseModal}>
                            {trans('common.close')}
                        </Button>

                        {!showTokenCreated ? (
                            <Button
                                onClick={handleCreateToken}
                                disabled={apiTokens.loading || !tokenName || tokenAbilities.length === 0}
                            >
                                {apiTokens.loading ? trans('profile.api_tokens.creating') : trans('profile.api_tokens.create')}
                            </Button>
                        ) : (
                            <Button onClick={handleCloseTokenCreated}>{trans('profile.api_tokens.copied_token')}</Button>
                        )}
                    </>
                }
            >
                {!showTokenCreated ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">{trans('profile.api_tokens.third_party_info')}</p>

                        {/* Token List */}
                        {apiTokens.loading && apiTokens.tokens.length === 0 ? (
                            <div className="py-8 text-center">
                                <div className="inline-block size-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
                                <p className="mt-2 text-sm text-muted-foreground">{trans('profile.api_tokens.loading')}</p>
                            </div>
                        ) : apiTokens.tokens.length > 0 ? (
                            <div className="space-y-2">
                                <p className="text-sm font-medium">{trans('profile.api_tokens.your_tokens')}</p>
                                {apiTokens.tokens.map((token) => (
                                    <div key={token.id} className="flex items-center gap-3 rounded-lg border p-3">
                                        <div className="flex-1">
                                            <p className="text-sm font-medium">{token.name}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {trans('profile.api_tokens.abilities')}: {token.abilities.join(', ')}
                                                {token.last_used_at ? (
                                                    <span>
                                                        {' '}
                                                        • {trans('profile.api_tokens.last_used')} {token.last_used_at}
                                                    </span>
                                                ) : (
                                                    <span> • {trans('profile.api_tokens.never_used')}</span>
                                                )}
                                            </p>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => handleDeleteToken(token.id)}
                                            disabled={apiTokens.loading}
                                        >
                                            {trans('common.delete')}
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-4 text-sm text-muted-foreground">
                                {trans('profile.api_tokens.no_tokens')}
                            </div>
                        )}

                        {/* Create Token Form */}
                        <div className="space-y-4 pt-4 border-t">
                            <p className="text-sm font-medium">{trans('profile.api_tokens.create_new')}</p>

                            <div className="space-y-2">
                                <Label htmlFor="token-name">{trans('profile.api_tokens.token_name')}</Label>
                                <Input
                                    id="token-name"
                                    value={tokenName}
                                    onChange={(e) => setTokenName(e.target.value)}
                                    type="text"
                                    placeholder={trans('profile.api_tokens.token_name_placeholder')}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>{trans('profile.api_tokens.permissions')}</Label>
                                <div className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="read"
                                            checked={tokenAbilities.includes('read')}
                                            onCheckedChange={() => toggleAbility('read')}
                                        />
                                        <Label htmlFor="read" className="font-normal cursor-pointer">
                                            {trans('profile.api_tokens.permission_read')}
                                        </Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="create"
                                            checked={tokenAbilities.includes('create')}
                                            onCheckedChange={() => toggleAbility('create')}
                                        />
                                        <Label htmlFor="create" className="font-normal cursor-pointer">
                                            {trans('profile.api_tokens.permission_create')}
                                        </Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="update"
                                            checked={tokenAbilities.includes('update')}
                                            onCheckedChange={() => toggleAbility('update')}
                                        />
                                        <Label htmlFor="update" className="font-normal cursor-pointer">
                                            {trans('profile.api_tokens.permission_update')}
                                        </Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="delete"
                                            checked={tokenAbilities.includes('delete')}
                                            onCheckedChange={() => toggleAbility('delete')}
                                        />
                                        <Label htmlFor="delete" className="font-normal cursor-pointer">
                                            {trans('profile.api_tokens.permission_delete')}
                                        </Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {apiTokens.error && <p className="text-sm text-destructive">{apiTokens.error}</p>}
                    </div>
                ) : (
                    /* Token Created Success View */
                    <div className="space-y-4">
                        <div className="rounded-lg bg-amber-50 dark:bg-amber-950 p-4">
                            <p className="text-sm font-medium text-amber-900 dark:text-amber-100">
                                {trans('profile.api_tokens.save_token')}
                            </p>
                            <p className="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                {trans('profile.api_tokens.save_token_warning')}
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label>{trans('profile.api_tokens.new_token')}</Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    value={apiTokens.newToken?.plain_text_token ?? ''}
                                    readOnly
                                    className="font-mono text-sm"
                                />
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => copyToken(apiTokens.newToken?.plain_text_token || '')}
                                >
                                    <svg className="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </>
    );
}
