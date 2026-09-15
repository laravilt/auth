import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

export interface ProfileInformationSectionProps {
    profileAction: string;
    profileSchema: any[];
    user: {
        name: string;
        email: string;
        email_verified_at?: string;
    };
}

export default function ProfileInformationSection({ profileAction, profileSchema, user }: ProfileInformationSectionProps) {
    // Initialize localization
    const { trans } = useLocalization();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{trans('profile.information.title')}</CardTitle>
                <CardDescription>{trans('profile.information.description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <Form action={profileAction} method="PATCH" className="space-y-4">
                    {({ errors, processing }) => (
                        <>
                            {profileSchema.map((field: any) => (
                                <div key={field.name} className="space-y-2">
                                    <Label htmlFor={field.name}>{field.label}</Label>
                                    <Input
                                        id={field.name}
                                        type={field.type || 'text'}
                                        name={field.name}
                                        placeholder={field.placeholder}
                                        required={field.required}
                                        defaultValue={field.value || field.defaultValue}
                                    />
                                    <InputError message={(errors as Record<string, string | undefined>)[field.name]} />
                                </div>
                            ))}

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? trans('common.saving') : trans('common.save_changes')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                {!user.email_verified_at && (
                    <p className="mt-4 text-sm text-amber-600 dark:text-amber-400">
                        {trans('profile.information.email_unverified')}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
