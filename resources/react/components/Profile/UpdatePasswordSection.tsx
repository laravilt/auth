import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

export interface UpdatePasswordSectionProps {
    passwordAction: string;
    passwordSchema: any[];
}

export default function UpdatePasswordSection({ passwordAction, passwordSchema }: UpdatePasswordSectionProps) {
    // Initialize localization
    const { trans } = useLocalization();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{trans('profile.password.title')}</CardTitle>
                <CardDescription>{trans('profile.password.description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <Form action={passwordAction} method="PUT" className="space-y-4">
                    {({ errors, processing }) => (
                        <>
                            {passwordSchema.map((field: any) => (
                                <div key={field.name} className="space-y-2">
                                    <Label htmlFor={field.name}>{field.label}</Label>
                                    <Input
                                        id={field.name}
                                        type={field.type || 'password'}
                                        name={field.name}
                                        placeholder={field.placeholder}
                                        required={field.required}
                                        autoComplete="new-password"
                                    />
                                    <InputError message={(errors as Record<string, string | undefined>)[field.name]} />
                                </div>
                            ))}

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? trans('profile.password.updating') : trans('profile.password.update')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
