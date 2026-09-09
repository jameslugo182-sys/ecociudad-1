import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gradient-to-br from-emerald-50 via-white to-slate-100 px-4 pt-8 sm:justify-center sm:pt-0">
            <Link href="/" className="flex items-center gap-3">
                <ApplicationLogo className="h-14 w-14" />
                <div>
                    <p className="text-2xl font-extrabold tracking-tight text-slate-900">EcoCiudad</p>
                    <p className="text-xs font-semibold text-emerald-700">Municipalidad de El Tambo</p>
                </div>
            </Link>

            <div className="mt-7 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-xl shadow-slate-200/60 sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
