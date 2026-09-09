import { Link, Head } from "@inertiajs/react";

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Inicio - EcoCiudad" />
            <div className="min-h-screen bg-gray-50 font-sans">
                {/* Menú de Navegación superior */}
                <header className="flex items-center justify-between px-8 py-5 bg-white shadow-sm">
                    <div className="flex items-center gap-2">
                        {/* Aquí luego pueden poner el logo que diseñen */}
                        <div className="w-8 h-8 bg-green-600 rounded-md"></div>
                        <span className="text-2xl font-bold text-gray-800">
                            EcoCiudad
                        </span>
                    </div>

                    <nav>
                        {auth.user ? (
                            <Link
                                href={route("dashboard")}
                                className="text-gray-700 hover:text-green-600 font-semibold transition"
                            >
                                Ir al Panel
                            </Link>
                        ) : (
                            <div className="flex items-center gap-4">
                                <Link
                                    href={route("login")}
                                    className="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-green-600 transition"
                                >
                                    Iniciar Sesión
                                </Link>
                                <Link
                                    href={route("register")}
                                    className="px-5 py-2.5 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition shadow-md"
                                >
                                    Registrarse
                                </Link>
                            </div>
                        )}
                    </nav>
                </header>

                {/* Sección Principal (Hero Section) */}
                <main className="flex flex-col items-center justify-center text-center px-4 mt-24">
                    <h1 className="text-5xl font-extrabold text-gray-900 mb-6 tracking-tight max-w-4xl">
                        Gestión Inteligente de Residuos para{" "}
                        <span className="text-green-600">El Tambo</span>
                    </h1>

                    <p className="text-xl text-gray-600 max-w-2xl mb-10 leading-relaxed">
                        Plataforma participativa para Huancayo. Reporta
                        acumulaciones de residuos sólidos con tu ubicación GPS y
                        ayudamos a optimizar las rutas de limpieza mediante
                        inteligencia artificial para mantener nuestras calles
                        limpias.
                    </p>

                    <div className="flex gap-4">
                        <Link
                            href={route("login")}
                            className="px-8 py-3 text-lg font-semibold text-white bg-gray-900 rounded-lg shadow-lg hover:bg-gray-800 transition"
                        >
                            Hacer un Reporte
                        </Link>
                        <a
                            href="#como-funciona"
                            className="px-8 py-3 text-lg font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition"
                        >
                            ¿Cómo funciona?
                        </a>
                    </div>
                </main>
            </div>
        </>
    );
}
