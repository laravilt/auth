import { usePage } from '@inertiajs/vue3';

/**
 * URL prefix for a panel's routes.
 *
 * Panel routes live under the panel's path, which can differ from its id, so
 * building URLs from the id breaks for any panel configured with a custom path.
 * The panel package shares the current panel (id + path) with every page; use
 * its path when it is the panel being asked for, and fall back to the id.
 */
export function usePanelBase(panelId: string): string {
    const panel = (usePage().props as { panel?: { id?: string; path?: string } }).panel;
    const path = panel?.id === panelId && panel.path ? panel.path : panelId;

    return `/${path.replace(/^\/+|\/+$/g, '')}`;
}
