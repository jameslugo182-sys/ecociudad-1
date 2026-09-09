import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage, Link } from "@inertiajs/react";

export default function Dashboard({ auth, reportes }) {
    const { flash = {} } = usePage().props;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        {auth.user.rol === "personal_municipal"
                            ? "Panel de Gestión Municipal"
                            : "Mis Reportes"}
                    </h2>
                    <span className="px-3 py-1 bg-gray-800 text-white text-xs font-bold rounded-full capitalize">
                        Perfil: {auth.user.rol.replace("_", " ")}
                    </span>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {flash?.success && (
                        <div className="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                            <p className="font-bold">¡Excelente!</p>
                            <p>{flash.success}</p>
                        </div>
                    )}

                    {/* Botón para crear un nuevo reporte */}
                    <div className="flex justify-end">
                        <Link
                            href={route("reportes.create")}
                            className="bg-gray-900 text-white px-4 py-2 rounded-md hover:bg-gray-800 transition font-semibold"
                        >
                            + Nueva Incidencia
                        </Link>
                    </div>

                    {/* Lista de Reportes */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-bold text-gray-800 mb-4">
                            Historial de Incidencias
                        </h3>

                        {reportes.length === 0 ? (
                            <p className="text-gray-500">
                                Aún no has registrado ningún reporte.
                            </p>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {reportes.map((reporte) => (
                                    <div
                                        key={reporte.id}
                                        className="border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition"
                                    >
                                        <div className="flex justify-between items-start mb-2">
                                            <span
                                                className={`px-2 py-1 text-xs font-bold rounded-full ${
                                                    reporte.estado ===
                                                    "Pendiente"
                                                        ? "bg-yellow-100 text-yellow-800"
                                                        : "bg-green-100 text-green-800"
                                                }`}
                                            >
                                                {reporte.estado}
                                            </span>
                                            <span className="text-xs text-gray-400">
                                                {new Date(
                                                    reporte.created_at,
                                                ).toLocaleDateString()}
                                            </span>
                                        </div>
                                        <p className="text-gray-700 text-sm font-medium mb-3 line-clamp-2">
                                            {reporte.descripcion}
                                        </p>
                                        <div className="text-xs text-gray-500 font-mono bg-gray-50 p-2 rounded">
                                            📍 {reporte.latitud},{" "}
                                            {reporte.longitud}
                                        </div>
                                        <img
                                            src={`/storage/${reporte.foto_path}`}
                                            alt="Incidencia"
                                            className="w-full h-32 object-cover rounded-md mb-3"
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
