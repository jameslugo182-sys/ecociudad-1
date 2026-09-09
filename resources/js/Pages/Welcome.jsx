import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';

const soluciones = [
    {
        numero: '01',
        titulo: 'Participación ciudadana',
        descripcion: 'Un canal sencillo para reportar incidencias con fotografía y ubicación, consultar su avance y recibir la confirmación de atención.',
        enlaces: ['Reportes georreferenciados', 'Seguimiento de estados', 'Notificaciones de cierre'],
        color: 'bg-[#dff5e9]',
    },
    {
        numero: '02',
        titulo: 'Operación institucional',
        descripcion: 'Herramientas para centralizar incidencias, organizar recursos y coordinar el trabajo de los equipos responsables en campo.',
        enlaces: ['Clasificación y prioridades', 'Vehículos y cuadrillas', 'Rutas de atención'],
        color: 'bg-[#e6eefc]',
    },
    {
        numero: '03',
        titulo: 'Administración y control',
        descripcion: 'Gestión de colaboradores, contratos, cuentas, permisos e indicadores desde una plataforma adaptable a cada institución.',
        enlaces: ['Contratos y usuarios', 'Roles configurables', 'Métricas operativas'],
        color: 'bg-[#f3eadc]',
    },
];

const pasos = [
    ['1', 'Reportar', 'La persona registra la incidencia y comparte su ubicación.'],
    ['2', 'Organizar', 'La institución valida, prioriza y asigna los recursos.'],
    ['3', 'Atender', 'El equipo sigue su ruta y ejecuta el trabajo en campo.'],
    ['4', 'Verificar', 'La evidencia cierra el caso y mantiene la trazabilidad.'],
];

function ArrowIcon() {
    return (
        <svg viewBox="0 0 20 20" fill="none" className="h-4 w-4" aria-hidden="true">
            <path d="M4 10h12M11 5l5 5-5 5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export default function Welcome({ auth, heroImage }) {
    const destinoPrincipal = auth.user ? route('dashboard') : route('register');

    return (
        <>
            <Head title="EcoCiudad | Gestión urbana conectada">
                <meta
                    name="description"
                    content="Plataforma adaptable para conectar a la ciudadanía con la gestión institucional de incidencias, equipos y rutas."
                />
            </Head>

            <div className="min-h-screen bg-white font-sans text-[#171717]">
                <header className="relative z-50 bg-white">
                    <div className="mx-auto flex h-[72px] max-w-[1280px] items-center px-5 sm:px-8 lg:px-10">
                        <a href="#inicio" className="flex shrink-0 items-center gap-2.5">
                            <ApplicationLogo className="h-9 w-9" />
                            <span className="text-[21px] font-semibold tracking-[-0.04em]">EcoCiudad</span>
                        </a>

                        <nav className="ml-14 hidden items-center gap-8 text-[13px] font-medium text-neutral-700 lg:flex">
                            <a href="#plataforma" className="transition hover:text-black">Plataforma</a>
                            <a href="#soluciones" className="transition hover:text-black">Soluciones</a>
                            <a href="#como-funciona" className="transition hover:text-black">Cómo funciona</a>
                            <a href="#instituciones" className="transition hover:text-black">Instituciones</a>
                        </nav>

                        <div className="ml-auto flex items-center gap-4">
                            {auth.user ? (
                                <Link href={route('dashboard')} className="text-[13px] font-semibold hover:text-emerald-700">
                                    Ir al panel
                                </Link>
                            ) : (
                                <Link href={route('login')} className="text-[13px] font-semibold hover:text-emerald-700">
                                    Acceso
                                </Link>
                            )}
                            <Link
                                href={destinoPrincipal}
                                className="rounded-md bg-[#181818] px-5 py-3 text-[13px] font-semibold text-white transition hover:bg-emerald-700"
                            >
                                {auth.user ? 'Continuar' : 'Empecemos'}
                            </Link>
                            <span className="hidden items-center gap-1.5 text-xs font-medium text-neutral-600 sm:flex">
                                <span className="text-base">◎</span> ES
                            </span>
                        </div>
                    </div>
                </header>

                <main>
                    <section id="inicio" className="overflow-hidden" style={{ backgroundColor: '#78b7cf' }}>
                        <div className="relative mx-auto flex min-h-[calc(100vh-72px)] max-w-[1280px] flex-col px-5 sm:px-8 lg:px-10">
                            <div className="relative flex flex-1 items-center py-16 lg:min-h-[610px] lg:py-10">
                                <div className="relative z-10 max-w-[570px]">
                                    <a
                                        href="#plataforma"
                                        className="inline-flex items-center gap-3 rounded-full bg-white/65 px-4 py-2 text-[11px] font-medium backdrop-blur-sm transition hover:bg-white"
                                    >
                                        Una plataforma para ciudades más limpias <ArrowIcon />
                                    </a>

                                    <h1 className="mt-8 text-[46px] font-medium leading-[1.06] tracking-[-0.045em] sm:text-[58px] lg:text-[66px]">
                                        Gestión urbana para ciudades que avanzan
                                    </h1>
                                    <p className="mt-6 max-w-[510px] text-[15px] leading-7 text-neutral-800/80">
                                        Una única plataforma para conectar a las personas con sus instituciones,
                                        atender incidencias y coordinar servicios urbanos con información clara.
                                    </p>
                                    <Link
                                        href={destinoPrincipal}
                                        className="mt-7 inline-flex items-center gap-3 rounded-md bg-[#191919] px-6 py-3.5 text-[13px] font-semibold text-white transition hover:bg-emerald-800"
                                    >
                                        {auth.user ? 'Abrir la plataforma' : 'Comenzar ahora'} <ArrowIcon />
                                    </Link>
                                </div>

                                <div className="pointer-events-none mt-10 w-full lg:absolute lg:right-[1%] lg:top-1/2 lg:mt-0 lg:w-[56%] lg:-translate-y-1/2">
                                    <img
                                        src={heroImage}
                                        alt="Sistema urbano conectado con rutas, vehículos y puntos de atención"
                                        className="mx-auto w-full max-w-[650px] object-contain"
                                    />
                                </div>
                            </div>

                            <div className="relative z-10 grid border-t border-black/15 py-7 sm:grid-cols-2 lg:grid-cols-4">
                                {[
                                    ['◉', '24/7', 'canal ciudadano'],
                                    ['⌖', '100%', 'seguimiento trazable'],
                                    ['↗', '1', 'plataforma integral'],
                                    ['▦', '5', 'módulos configurables'],
                                ].map(([icono, valor, label]) => (
                                    <div key={label} className="flex items-center gap-4 border-black/15 py-4 sm:px-5 sm:odd:border-r lg:border-r lg:first:pl-0 lg:last:border-r-0">
                                        <span className="text-3xl font-light">{icono}</span>
                                        <div>
                                            <p className="text-[34px] font-medium leading-none tracking-[-0.04em]">{valor}</p>
                                            <p className="mt-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-700">{label}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section id="plataforma" className="mx-auto max-w-[1280px] px-5 py-24 sm:px-8 lg:px-10 lg:py-32">
                        <div className="grid gap-10 lg:grid-cols-[0.85fr_1.15fr]">
                            <p className="text-[12px] font-semibold uppercase tracking-[0.14em] text-neutral-500">
                                Soluciones de EcoCiudad
                            </p>
                            <div>
                                <h2 className="max-w-3xl text-[38px] font-medium leading-[1.12] tracking-[-0.035em] sm:text-[50px]">
                                    Todo lo necesario para conectar participación, operación y resultados
                                </h2>
                                <p className="mt-6 max-w-2xl text-[16px] leading-7 text-neutral-600">
                                    Diseñada para municipalidades de cualquier tamaño, la plataforma reúne el trabajo ciudadano e institucional en un mismo flujo.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section id="soluciones" className="border-y border-neutral-200 bg-[#f5f4f0]">
                        <div className="mx-auto grid max-w-[1280px] gap-px bg-neutral-300 lg:grid-cols-3">
                            {soluciones.map((solucion) => (
                                <article key={solucion.titulo} className="flex min-h-[540px] flex-col bg-[#f5f4f0] p-7 sm:p-10">
                                    <div className={`flex h-36 items-end justify-between rounded-lg p-6 ${solucion.color}`}>
                                        <span className="text-[12px] font-semibold uppercase tracking-[0.12em]">Solución {solucion.numero}</span>
                                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white text-xl">↗</span>
                                    </div>
                                    <h3 className="mt-9 text-[28px] font-medium tracking-[-0.025em]">{solucion.titulo}</h3>
                                    <p className="mt-4 text-[15px] leading-7 text-neutral-600">{solucion.descripcion}</p>
                                    <div className="mt-auto pt-9">
                                        {solucion.enlaces.map((enlace) => (
                                            <div key={enlace} className="flex items-center justify-between border-t border-neutral-300 py-3 text-[13px] font-medium">
                                                {enlace} <ArrowIcon />
                                            </div>
                                        ))}
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section id="instituciones" className="mx-auto max-w-[1280px] px-5 py-24 sm:px-8 lg:px-10 lg:py-32">
                        <div className="grid items-center gap-16 lg:grid-cols-2">
                            <div className="overflow-hidden rounded-xl bg-[#79b8cf] p-8 sm:p-12">
                                <div className="mx-auto max-w-md rounded-lg bg-white p-5 shadow-xl">
                                    <div className="flex items-center justify-between border-b border-neutral-200 pb-4">
                                        <div>
                                            <p className="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Operación del día</p>
                                            <p className="mt-1 font-semibold">Estado de servicios urbanos</p>
                                        </div>
                                        <span className="rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-semibold text-emerald-800">Actualizado</span>
                                    </div>
                                    <div className="mt-5 grid grid-cols-3 gap-3">
                                        {[['24', 'Pendientes'], ['08', 'En ruta'], ['142', 'Atendidas']].map(([valor, label]) => (
                                            <div key={label} className="rounded-md bg-neutral-100 p-4">
                                                <p className="text-2xl font-semibold">{valor}</p>
                                                <p className="mt-1 text-[9px] uppercase tracking-wide text-neutral-500">{label}</p>
                                            </div>
                                        ))}
                                    </div>
                                    <div className="mt-4 h-52 rounded-md bg-[#e7eee9] p-4">
                                        <div className="flex h-full items-center justify-center rounded border border-dashed border-emerald-700/30 text-center">
                                            <div>
                                                <span className="text-3xl text-emerald-700">⌖</span>
                                                <p className="mt-2 text-xs font-semibold">Mapa operativo y rutas</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p className="text-[12px] font-semibold uppercase tracking-[0.14em] text-neutral-500">Para la institución</p>
                                <h2 className="mt-6 text-[42px] font-medium leading-[1.1] tracking-[-0.04em] sm:text-[52px]">
                                    Control claro de cada servicio, equipo y atención
                                </h2>
                                <p className="mt-6 text-[16px] leading-7 text-neutral-600">
                                    Configura roles, administra contratos y vehículos, asigna rutas y consulta indicadores sin depender de procesos aislados.
                                </p>
                                <div className="mt-9 grid gap-x-8 sm:grid-cols-2">
                                    {['Incidencias centralizadas', 'Flota y cuadrillas', 'Rutas optimizadas', 'Métricas operativas'].map((item) => (
                                        <div key={item} className="flex items-center gap-3 border-t border-neutral-300 py-4 text-sm font-medium">
                                            <span className="text-emerald-700">●</span> {item}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="como-funciona" className="bg-[#1d1d1b] py-24 text-white lg:py-32">
                        <div className="mx-auto max-w-[1280px] px-5 sm:px-8 lg:px-10">
                            <div className="grid gap-10 lg:grid-cols-2">
                                <div>
                                    <p className="text-[12px] font-semibold uppercase tracking-[0.14em] text-white/50">Cómo funciona</p>
                                    <h2 className="mt-6 max-w-xl text-[42px] font-medium leading-[1.1] tracking-[-0.04em] sm:text-[52px]">
                                        Un proceso conectado desde el reporte hasta la solución
                                    </h2>
                                </div>
                                <p className="max-w-xl self-end text-[16px] leading-7 text-white/60">
                                    La misma información acompaña cada caso durante todo su ciclo, facilitando el trabajo institucional y la transparencia para las personas.
                                </p>
                            </div>

                            <div className="mt-16 grid border-l border-t border-white/20 sm:grid-cols-2 lg:grid-cols-4">
                                {pasos.map(([numero, titulo, descripcion]) => (
                                    <article key={numero} className="min-h-64 border-b border-r border-white/20 p-7">
                                        <span className="text-sm text-[#78b7cf]">{numero.padStart(2, '0')}</span>
                                        <h3 className="mt-16 text-xl font-medium">{titulo}</h3>
                                        <p className="mt-3 text-sm leading-6 text-white/55">{descripcion}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="bg-[#f5f4f0] px-5 py-24 sm:px-8">
                        <div className="mx-auto max-w-[1280px] rounded-xl bg-[#78b7cf] px-7 py-16 sm:px-12 lg:flex lg:items-end lg:justify-between lg:px-16">
                            <div className="max-w-3xl">
                                <p className="text-[12px] font-semibold uppercase tracking-[0.14em]">Una plataforma adaptable</p>
                                <h2 className="mt-6 text-[40px] font-medium leading-[1.08] tracking-[-0.04em] sm:text-[54px]">
                                    Una mejor gestión urbana comienza con información conectada
                                </h2>
                            </div>
                            <Link
                                href={destinoPrincipal}
                                className="mt-9 inline-flex shrink-0 items-center gap-3 rounded-md bg-[#191919] px-6 py-4 text-[13px] font-semibold text-white lg:ml-12 lg:mt-0"
                            >
                                {auth.user ? 'Abrir plataforma' : 'Empecemos'} <ArrowIcon />
                            </Link>
                        </div>
                    </section>
                </main>

                <footer className="bg-white">
                    <div className="mx-auto flex max-w-[1280px] flex-col gap-7 px-5 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                        <div className="flex items-center gap-2.5">
                            <ApplicationLogo className="h-8 w-8" />
                            <span className="text-lg font-semibold tracking-[-0.03em]">EcoCiudad</span>
                        </div>
                        <p className="text-xs text-neutral-500">Participación ciudadana y gestión institucional en una sola plataforma.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
