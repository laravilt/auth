import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocalization } from '@laravilt/support/composables/useLocalization';

export default function MagicLinksSection() {
    // Initialize localization
    const { trans } = useLocalization();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{trans('profile.magic_links.title')}</CardTitle>
                <CardDescription>{trans('profile.magic_links.description')}</CardDescription>
            </CardHeader>
            <CardContent>
                <p className="text-sm text-muted-foreground">{trans('profile.magic_links.info')}</p>
                <div className="mt-4 rounded-lg bg-muted p-4">
                    <p className="text-sm">{trans('profile.magic_links.ready_to_use')}</p>
                </div>
            </CardContent>
        </Card>
    );
}
