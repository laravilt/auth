import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useLocalization } from '@laravilt/support/composables';
import { useLatest } from '@laravilt/support/composables/hooks';

export interface OtpResendHookProps {
    resendUrl: string;
    expiresAt?: number | null;
}

export default function OtpResendHook({ resendUrl, expiresAt }: OtpResendHookProps) {
    const { trans } = useLocalization();

    const [isResending, setIsResending] = useState(false);
    const [countdown, setCountdown] = useState(0);
    const [isExpired, setIsExpired] = useState(false);

    const expiresAtRef = useLatest(expiresAt);

    useEffect(() => {
        // Calculate remaining time
        const updateCountdown = () => {
            const currentExpiresAt = expiresAtRef.current;

            if (!currentExpiresAt) {
                setIsExpired(true);
                return;
            }

            const now = Math.floor(Date.now() / 1000);
            const remaining = currentExpiresAt - now;

            if (remaining <= 0) {
                setCountdown(0);
                setIsExpired(true);
            } else {
                setCountdown(remaining);
                setIsExpired(false);
            }
        };

        updateCountdown();
        const intervalId: number | null = window.setInterval(updateCountdown, 1000);

        return () => {
            if (intervalId) {
                clearInterval(intervalId);
            }
        };
    }, [expiresAtRef]);

    // Format countdown as mm:ss
    const minutes = Math.floor(countdown / 60);
    const seconds = countdown % 60;
    const formattedCountdown = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

    // Resend OTP
    const resendOtp = () => {
        setIsResending(true);
        router.post(
            resendUrl,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsResending(false);
                },
            },
        );
    };

    return (
        <div className="flex flex-col items-center gap-4 text-center">
            {!isExpired && countdown > 0 ? (
                /* Countdown Timer */
                <div className="text-sm text-muted-foreground">
                    {trans('laravilt-auth::auth.otp.expires_in')}{' '}
                    <span className="font-mono font-medium text-foreground">{formattedCountdown}</span>
                </div>
            ) : (
                /* Expired Message */
                <div className="text-sm text-destructive">{trans('laravilt-auth::auth.otp.code_expired')}</div>
            )}

            {/* Resend Section */}
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <span>{trans('laravilt-auth::auth.otp.didnt_receive')}</span>
                <Button
                    type="button"
                    variant="link"
                    size="sm"
                    className="h-auto p-0 text-primary cursor-pointer"
                    disabled={isResending}
                    onClick={resendOtp}
                >
                    {isResending ? trans('laravilt-auth::auth.otp.resending') : trans('laravilt-auth::auth.otp.resend')}
                </Button>
            </div>
        </div>
    );
}
