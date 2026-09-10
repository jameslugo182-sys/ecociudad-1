import Modal from '@/Components/Modal';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function SuccessDialog() {
    const { flash = {} } = usePage().props;
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        setVisible(Boolean(flash.registered));
    }, [flash.registered]);

    if (!visible) {
        return null;
    }

    return (
        <Modal show={visible} maxWidth="sm" onClose={() => setVisible(false)}>
            <div className="bg-white px-8 py-10 text-center">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500 text-4xl font-black text-white shadow-sm">
                    ✓
                </div>
                <h3 className="mt-6 text-xl font-bold tracking-tight text-slate-900">
                    Registrado Exitosamente
                </h3>
                {flash.success && (
                    <p className="mt-2 text-sm text-slate-500">{flash.success}</p>
                )}
                <button
                    type="button"
                    onClick={() => setVisible(false)}
                    className="mt-8 w-full rounded-lg bg-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-slate-800 transition hover:bg-slate-400"
                >
                    OK
                </button>
            </div>
        </Modal>
    );
}
