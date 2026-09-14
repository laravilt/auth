import { useLocalization } from '@laravilt/support/composables';
import AuthLinks from './AuthLinks';
import SocialLogin from './SocialLogin';

export interface BottomAuthHookProps {
    forgotPasswordUrl?: string;
    registerUrl?: string;
    loginUrl?: string;
    canResetPassword?: boolean;
    canRegister?: boolean;
    canLogin?: boolean;
    mode?: 'login' | 'register' | 'forgot-password';
    socialProviders?: any[];
    socialRedirectUrl?: string;
}

export default function BottomAuthHook({
    forgotPasswordUrl,
    registerUrl,
    loginUrl,
    canResetPassword,
    canRegister,
    canLogin,
    mode,
    socialProviders,
    socialRedirectUrl,
}: BottomAuthHookProps) {
    const { trans } = useLocalization();

    return (
        <div className="space-y-6">
            {/* Auth Links */}
            <AuthLinks
                forgotPasswordUrl={forgotPasswordUrl}
                registerUrl={registerUrl}
                loginUrl={loginUrl}
                canResetPassword={canResetPassword}
                canRegister={canRegister}
                canLogin={canLogin}
                mode={mode}
            />

            {/* Social Login (with divider if there are providers) */}
            {socialProviders && socialProviders.length > 0 && (
                <div>
                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t"></span>
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-background px-2 text-muted-foreground">
                                {trans('laravilt-auth::auth.social.or_continue_with')}
                            </span>
                        </div>
                    </div>

                    <div className="mt-4">
                        <SocialLogin providers={socialProviders} redirectUrl={socialRedirectUrl} />
                    </div>
                </div>
            )}
        </div>
    );
}
