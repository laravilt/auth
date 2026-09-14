import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

export interface DeleteAccountSectionProps {
    deleteAction: string;
    deleteSchema: any[];
}

export default function DeleteAccountSection({ deleteAction, deleteSchema }: DeleteAccountSectionProps) {
    // Initialize localization
    const { trans } = useLocalization();

    return (
        <Card className="border-destructive">
            <CardHeader>
                <CardTitle className="text-destructive">{trans('profile.delete.title')}</CardTitle>
                <CardDescription>{trans('profile.delete.description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    action={deleteAction}
                    method="DELETE"
                    className="space-y-4"
                    // Vue binds `@submit="(e) => { if (!confirm(trans('profile.delete.confirm'))) e.preventDefault(); }"`,
                    // but @inertiajs/vue3's <Form> spreads attrs and then overrides onSubmit, so that confirm never runs.
                    // Ported as-is (no confirmation). Same in React: <Form> owns onSubmit.
                >
                    {({ errors, processing }) => (
                        <>
                            {deleteSchema.map((field: any) => (
                                <div key={field.name} className="space-y-2">
                                    <Label htmlFor={field.name}>{field.label}</Label>
                                    <Input
                                        id={field.name}
                                        type={field.type || 'password'}
                                        name={field.name}
                                        placeholder={field.placeholder}
                                        required={field.required}
                                    />
                                    <InputError message={(errors as Record<string, string | undefined>)[field.name]} />
                                </div>
                            ))}

                            <div className="flex justify-end">
                                <Button type="submit" variant="destructive" disabled={processing}>
                                    {processing ? trans('profile.delete.deleting') : trans('profile.delete.delete')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
