import Modal from '@/Components/Modal';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function SuccessDialog() {
    const { flash = {} } = usePage().props;
    const [visible, setVisible] = useState(false);
    const [copiado, setCopiado] = useState(false);
    const token = flash.device_token || '';

    useEffect(() => {
        setVisible(Boolean(flash.registered));
        setCopiado(false);
    }, [flash.registered, flash.device_token]);

    if (!visible) {
        return null;
    }

    const copiarToken = async () => {
        if (!token) {
            return;
        }

        try {
            await navigator.clipboard.writeText(token);
            setCopiado(true);
        } catch {
            setCopiado(false);
        }
    };

    return (
        <Modal show={visible} maxWidth="sm" onClose={() => setVisible(false)}>
            <div className="bg-white px-8 py-10 text-center">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500 text-4xl font-black text-white shadow-sm">
                    ✓
                </div>
                <h3 className="mt-6 text-xl font-bold tracking-tight text-slate-900">
                    {token ? 'Token del ESP32' : 'Registrado Exitosamente'}
                </h3>
                {flash.success && (
                    <p className="mt-2 text-sm text-slate-500">{flash.success}</p>
                )}
                {token && (
                    <div className="mt-5 space-y-3 text-left">
                        <p className="break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-xs text-slate-800">
                            {token}
                        </p>
                        <button
                            type="button"
                            onClick={copiarToken}
                            className="w-full rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100"
                        >
                            {copiado ? 'Token copiado' : 'Copiar token'}
                        </button>
                    </div>
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
