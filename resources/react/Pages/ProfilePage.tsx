import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import ApiTokensSection from '@laravilt/auth/components/Profile/ApiTokensSection';
import ConnectedAccountsSection from '@laravilt/auth/components/Profile/ConnectedAccountsSection';
import DeleteAccountSection from '@laravilt/auth/components/Profile/DeleteAccountSection';
import MagicLinksSection from '@laravilt/auth/components/Profile/MagicLinksSection';
import PasskeysSection from '@laravilt/auth/components/Profile/PasskeysSection';
import ProfileInformationSection from '@laravilt/auth/components/Profile/ProfileInformationSection';
import SessionManagementSection from '@laravilt/auth/components/Profile/SessionManagementSection';
import TwoFactorSection from '@laravilt/auth/components/Profile/TwoFactorSection';
import UpdatePasswordSection from '@laravilt/auth/components/Profile/UpdatePasswordSection';
import PanelLayout from '@laravilt/panel/layouts/PanelLayout';
import { useLocalization } from '@laravilt/support/composables';

export interface ProfilePageProps {
    page: {
        heading: string;
        profileSchema: any[];
        passwordSchema: any[];
        deleteSchema: any[];
    };
    profileAction: string;
    passwordAction: string;
    deleteAction: string;
    user: {
        name: string;
        email: string;
        email_verified_at?: string;
    };
    features?: {
        twoFactor?: boolean;
        sessionManagement?: boolean;
        apiTokens?: boolean;
        passkeys?: boolean;
        magicLinks?: boolean;
        connectedAccounts?: boolean;
        socialLogin?: boolean;
    };
    twoFactorStatus?: {
        enabled: boolean;
        confirmed: boolean;
        method: string | null;
    };
    enableAction?: any;
    disableAction?: any;
    status?: string;
}

export default function ProfilePage({
    page,
    profileAction,
    passwordAction,
    deleteAction,
    user,
    features,
    twoFactorStatus,
    enableAction,
    disableAction,
    status,
}: ProfilePageProps) {
    const { trans } = useLocalization();

    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const timeout = setTimeout(() => {
            setIsLoading(false);
        }, 100);

        return () => clearTimeout(timeout);
    }, []);

    return (
        <PanelLayout>
            <Head title={page.heading} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="container max-w-4xl mx-auto">
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold">{page.heading}</h1>
                        <p className="text-muted-foreground">{trans('profile.page.subheading')}</p>
                    </div>

                    {isLoading ? (
                        /* Loading Skeleton */
                        <div className="grid gap-6">
                            {/* Section Skeleton 1 */}
                            <div className="rounded-lg border p-6 space-y-4">
                                <Skeleton className="h-6 w-48" />
                                <Skeleton className="h-4 w-64" />
                                <div className="space-y-3 pt-4">
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                </div>
                                <Skeleton className="h-10 w-32" />
                            </div>

                            {/* Section Skeleton 2 */}
                            <div className="rounded-lg border p-6 space-y-4">
                                <Skeleton className="h-6 w-48" />
                                <Skeleton className="h-4 w-64" />
                                <div className="space-y-3 pt-4">
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                </div>
                                <Skeleton className="h-10 w-32" />
                            </div>
                        </div>
                    ) : (
                        /* Actual Content */
                        <div>
                            {/* Status Messages */}
                            {status === 'profile-updated' && (
                                <div className="mb-4 rounded-md bg-green-50 dark:bg-green-950 p-4 text-sm font-medium text-green-600 dark:text-green-400">
                                    {trans('profile.page.profile_updated')}
                                </div>
                            )}

                            {status === 'password-updated' && (
                                <div className="mb-4 rounded-md bg-green-50 dark:bg-green-950 p-4 text-sm font-medium text-green-600 dark:text-green-400">
                                    {trans('profile.page.password_updated')}
                                </div>
                            )}

                            <div className="grid gap-6">
                                {/* Profile Information */}
                                <ProfileInformationSection
                                    profileAction={profileAction}
                                    profileSchema={page.profileSchema}
                                    user={user}
                                />

                                {/* Update Password */}
                                <UpdatePasswordSection passwordAction={passwordAction} passwordSchema={page.passwordSchema} />

                                {/* Two-Factor Authentication */}
                                {features?.twoFactor && (
                                    <TwoFactorSection
                                        twoFactorStatus={twoFactorStatus}
                                        enableAction={enableAction}
                                        disableAction={disableAction}
                                    />
                                )}

                                {/* Session Management */}
                                {features?.sessionManagement && <SessionManagementSection />}

                                {/* API Tokens */}
                                {features?.apiTokens && <ApiTokensSection />}

                                {/* Passkeys */}
                                {features?.passkeys && <PasskeysSection />}

                                {/* Magic Links */}
                                {features?.magicLinks && <MagicLinksSection />}

                                {/* Connected Accounts */}
                                {(features?.connectedAccounts || features?.socialLogin) && <ConnectedAccountsSection />}

                                {/* Delete Account */}
                                <DeleteAccountSection deleteAction={deleteAction} deleteSchema={page.deleteSchema} />
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PanelLayout>
    );
}
