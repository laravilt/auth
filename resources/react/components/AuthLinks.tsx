import { Link } from '@inertiajs/react';
import { useLocalization } from '@laravilt/support/composables';

export interface AuthLinksProps {
    forgotPasswordUrl?: string;
    registerUrl?: string;
    loginUrl?: string;
    canResetPassword?: boolean;
    canRegister?: boolean;
    canLogin?: boolean;
    mode?: 'login' | 'register' | 'forgot-password';
}

export default function AuthLinks({ registerUrl, loginUrl, canRegister, canLogin, mode }: AuthLinksProps) {
    const { trans } = useLocalization();

    return (
        <div className="space-y-4">
            {mode === 'login' ? (
                /* Login page links */
                canRegister && registerUrl ? (
                    <div className="text-center text-sm text-muted-foreground">
                        {trans('laravilt-auth::auth.login.no_account')}{' '}
                        <Link
                            href={registerUrl}
                            className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        >
                            {trans('laravilt-auth::auth.login.sign_up')}
                        </Link>
                    </div>
                ) : null
            ) : mode === 'register' && canLogin && loginUrl ? (
                /* Register page links */
                <div className="text-center text-sm text-muted-foreground">
                    {trans('laravilt-auth::auth.register.have_account')}{' '}
                    <Link
                        href={loginUrl}
                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        {trans('laravilt-auth::auth.register.sign_in')}
                    </Link>
                </div>
            ) : mode === 'forgot-password' && canLogin && loginUrl ? (
                /* Forgot password page links */
                <div className="text-center text-sm text-muted-foreground">
                    {trans('laravilt-auth::auth.forgot_password.remember_password')}{' '}
                    <Link
                        href={loginUrl}
                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        {trans('laravilt-auth::auth.forgot_password.back_to_login')}
                    </Link>
                </div>
            ) : null}
        </div>
    );
}
