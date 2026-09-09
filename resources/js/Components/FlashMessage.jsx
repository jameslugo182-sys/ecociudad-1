import { usePage } from '@inertiajs/react';

export default function FlashMessage() {
    const { flash = {} } = usePage().props;
    const message = flash.success || flash.error;

    if (!message) {
        return null;
    }

    const isSuccess = Boolean(flash.success);

    return (
        <div
            role="alert"
            className={`rounded-xl border px-4 py-3 text-sm font-medium shadow-sm ${
                isSuccess
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800'
            }`}
        >
            {message}
        </div>
    );
}
