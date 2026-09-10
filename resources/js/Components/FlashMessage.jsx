import { usePage } from '@inertiajs/react';

export default function FlashMessage() {
    const { flash = {} } = usePage().props;

    if (flash.registered) {
        return null;
    }

    if (flash.error) {
        return (
            <div
                role="alert"
                className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-sm"
            >
                {flash.error}
            </div>
        );
    }

    if (flash.success) {
        return (
            <div
                role="alert"
                className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm"
            >
                {flash.success}
            </div>
        );
    }

    return null;
}
