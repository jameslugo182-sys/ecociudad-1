import { Head, useForm } from "@inertiajs/react";
import ModuleLayout from "@/Layouts/ModuleLayout";
import { useState, useEffect } from "react";

export default function Create() {
    const navegacion = [
        {
            label: "Mis incidencias",
            href: route("reportes.index"),
            active: "reportes.index",
            icon: "01",
        },
        {
            label: "Registrar incidencia",
            href: route("reportes.create"),
            active: "reportes.create",
            icon: "02",
        },
    ];
    const { data, setData, post, processing, errors } = useForm({
        descripcion: "",
        latitud: "",
        longitud: "",
        foto: null,
        tipo_incidencia: "",
    });

    const [mensajeGps, setMensajeGps] = useState("Obtener mi ubicación exacta");
    const [gpsExito, setGpsExito] = useState(false);

    // Estado para manejar la selección rápida
    const [opcionSeleccionada, setOpcionSeleccionada] = useState("");

    // Catálogo rápido de problemas comunes
    const opcionesProblema = [
        "Contenedor desbordado",
        "Acumulación de bolsas de basura",
        "Desmonte de construcción en vía pública",
        "Residuos en áreas verdes o parques",
        "Otros",
    ];

    // Efecto para sincronizar la opción seleccionada con la descripción real a guardar
    useEffect(() => {
        setData("tipo_incidencia", opcionSeleccionada);

        if (opcionSeleccionada !== "Otros") {
            setData("descripcion", opcionSeleccionada);
        } else {
            setData("descripcion", "");
        }
    }, [opcionSeleccionada]);

    const obtenerUbicacion = () => {
        setMensajeGps("Ubicando por satélite...");
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setData((valoresPrevios) => ({
                        ...valoresPrevios,
                        latitud: position.coords.latitude,
                        longitud: position.coords.longitude,
                    }));
                    setGpsExito(true);
                    setMensajeGps("¡Ubicación capturada con éxito!");
                },
                (error) => {
                    setMensajeGps("Error al obtener ubicación. Activa tu GPS.");
                    console.error(error);
                },
                { enableHighAccuracy: true },
            );
        } else {
            setMensajeGps("Tu navegador no soporta GPS.");
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("reportes.store"));
    };

    return (
        <ModuleLayout moduleName="Incidencias registradas" items={navegacion}>
            <Head title="Nuevo Reporte" />

            <div className="py-10 bg-gray-50 min-h-screen">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6 md:p-10 border border-gray-100">
                        <div className="mb-8 text-center">
                            <h3 className="text-xl font-bold text-gray-800 mb-2">
                                Ayúdanos a mantener limpia nuestra ciudad
                            </h3>
                            <p className="text-gray-500 text-sm">
                                Completa estos 3 rápidos pasos para generar un
                                reporte georreferenciado.
                            </p>
                        </div>

                        <form
                            onSubmit={handleSubmit}
                            className="space-y-8"
                            encType="multipart/form-data"
                        >
                            {/* PASO 1: Fotografía (Diseño Drag&Drop simulado) */}
                            <div className="space-y-3">
                                <label className="block text-sm font-bold text-gray-700">
                                    1. Sube una fotografía 📸
                                </label>
                                <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-green-500 hover:bg-green-50 transition-colors">
                                    <div className="space-y-1 text-center">
                                        <div className="flex text-sm text-gray-600 justify-center">
                                            <label
                                                htmlFor="file-upload"
                                                className="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500"
                                            >
                                                <span>
                                                    Selecciona un archivo
                                                </span>
                                                <input
                                                    id="file-upload"
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={(e) =>
                                                        setData(
                                                            "foto",
                                                            e.target.files[0],
                                                        )
                                                    }
                                                    className="sr-only"
                                                    required
                                                />
                                            </label>
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            {data.foto ? (
                                                <span className="font-bold text-green-600">
                                                    {data.foto.name}
                                                </span>
                                            ) : (
                                                "PNG, JPG o WEBP hasta 10MB"
                                            )}
                                        </p>
                                    </div>
                                </div>
                                {errors.foto && (
                                    <div className="text-red-500 text-xs font-semibold">
                                        {errors.foto}
                                    </div>
                                )}
                            </div>

                            <hr className="border-gray-100" />

                            {/* PASO 2: GPS */}
                            <div className="space-y-3">
                                <label className="block text-sm font-bold text-gray-700">
                                    2. Ubicación GPS 📍
                                </label>
                                <div
                                    className={`p-5 rounded-xl border ${gpsExito ? "bg-green-50 border-green-200" : "bg-blue-50 border-blue-200"} flex flex-col items-center justify-center transition-all`}
                                >
                                    <button
                                        type="button"
                                        onClick={obtenerUbicacion}
                                        className={`w-full sm:w-auto px-6 py-3 font-bold text-white rounded-lg shadow-sm transition-all transform hover:scale-105 ${gpsExito ? "bg-green-600 shadow-green-200" : "bg-blue-600 hover:bg-blue-700 shadow-blue-200"}`}
                                    >
                                        {gpsExito ? "✓ " : "📍 "}
                                        {mensajeGps}
                                    </button>

                                    {data.latitud && data.longitud && (
                                        <div className="mt-4 flex gap-4 text-xs font-mono bg-white px-4 py-2 rounded-md shadow-sm border border-gray-100 text-gray-600">
                                            <span>Lat: {data.latitud}</span>
                                            <span>Lng: {data.longitud}</span>
                                        </div>
                                    )}
                                </div>
                                {(errors.latitud || errors.longitud) && (
                                    <div className="text-red-500 text-xs font-semibold">
                                        Es obligatorio capturar la ubicación
                                        para optimizar la ruta.
                                    </div>
                                )}
                            </div>

                            <hr className="border-gray-100" />

                            {/* PASO 3: Opciones Dinámicas */}
                            <div className="space-y-3">
                                <label className="block text-sm font-bold text-gray-700">
                                    3. ¿Qué tipo de incidencia es? 🗑️
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {opcionesProblema.map((opcion) => (
                                        <button
                                            key={opcion}
                                            type="button"
                                            onClick={() =>
                                                setOpcionSeleccionada(opcion)
                                            }
                                            className={`px-4 py-3 text-sm font-medium rounded-lg border text-left transition-all ${
                                                opcionSeleccionada === opcion
                                                    ? "bg-green-600 text-white border-green-600 shadow-md"
                                                    : "bg-white text-gray-700 border-gray-200 hover:border-green-400 hover:bg-green-50"
                                            }`}
                                        >
                                            {opcion}
                                        </button>
                                    ))}
                                </div>

                                {/* Aparece solo si se selecciona "Otros" */}
                                {opcionSeleccionada === "Otros" && (
                                    <div className="mt-4 animate-fade-in-down">
                                        <textarea
                                            rows="3"
                                            value={data.descripcion}
                                            onChange={(e) =>
                                                setData(
                                                    "descripcion",
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Por favor, detalla brevemente el problema..."
                                            className="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                                            required
                                        />
                                    </div>
                                )}
                                {errors.descripcion && (
                                    <div className="text-red-500 text-xs font-semibold">
                                        {errors.descripcion}
                                    </div>
                                )}
                            </div>

                            {/* Botón de Envío */}
                            <div className="pt-6">
                                <button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        !data.latitud ||
                                        !data.foto ||
                                        !data.descripcion
                                    }
                                    className="w-full px-6 py-4 bg-gray-900 text-white text-lg font-bold rounded-xl shadow-lg hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    {processing
                                        ? "Procesando envío..."
                                        : "Enviar Reporte al Municipio"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
