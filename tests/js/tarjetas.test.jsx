import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CargoMaestroRow from '@/Components/CargoMaestroRow';
import CatalogoItemRow from '@/Components/CatalogoItemRow';
import CuentaColaboradorCard from '@/Components/CuentaColaboradorCard';
import ReporteCard from '@/Components/ReporteCard';
import RolMaestroCard from '@/Components/RolMaestroCard';
import TareaLimpiezaCard from '@/Components/TareaLimpiezaCard';
import TipoMantenimientoRow from '@/Components/TipoMantenimientoRow';

const inertia = vi.hoisted(() => ({
    router: { patch: vi.fn() },
    post: vi.fn(),
    put: vi.fn(),
    errores: {},
    procesando: false,
}));

vi.mock('@inertiajs/react', () => ({
    router: inertia.router,
    useForm: (inicial) => {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        const [data, setDatos] = useState(inicial);

        return {
            data,
            errors: inertia.errores,
            processing: inertia.procesando,
            setData: (campo, valor) => setDatos((actual) => ({ ...actual, [campo]: valor })),
            post: inertia.post,
            put: inertia.put,
            reset: () => setDatos(inicial),
        };
    },
}));

const tabla = (fila) => render(<table><tbody>{fila}</tbody></table>);

beforeEach(() => {
    inertia.router.patch.mockReset();
    inertia.post.mockReset();
    inertia.put.mockReset();
    inertia.errores = {};
    inertia.procesando = false;
});

describe('ReporteCard', () => {
    it('muestra los datos basicos con valores por defecto', () => {
        render(<ReporteCard reporte={{ id: 7, created_at: '2026-10-27T10:00:00Z', estado: 'Pendiente', descripcion: 'Basura acumulada' }} />);

        expect(screen.getByText('Reporte #7')).toBeTruthy();
        expect(screen.getByText('Por clasificar')).toBeTruthy();
        expect(screen.getByText('Por calcular')).toBeTruthy();
        expect(screen.queryByRole('img')).toBeNull();
    });

    it('muestra fotos, responsable, evidencia y contenido adicional', () => {
        render(
            <ReporteCard
                reporte={{
                    id: 8,
                    created_at: '2026-10-27T10:00:00Z',
                    estado: 'Asignado',
                    descripcion: 'Desborde',
                    tipo_incidencia: 'Tacho lleno',
                    prioridad: 1,
                    foto_url: '/foto.jpg',
                    evidencia_url: '/evidencia.jpg',
                    responsable: { name: 'Ana' },
                }}
            >
                <button type="button">Accion</button>
            </ReporteCard>,
        );

        expect(screen.getByText('Alta')).toBeTruthy();
        expect(screen.getByText('Tacho lleno')).toBeTruthy();
        expect(screen.getByText('Ana')).toBeTruthy();
        expect(screen.getByAltText('Incidencia 8').getAttribute('src')).toBe('/foto.jpg');
        expect(screen.getByAltText('Evidencia del reporte 8')).toBeTruthy();
        expect(screen.getByText('Accion')).toBeTruthy();
    });
});

describe('filas de maestros', () => {
    it('CargoMaestroRow edita y cambia de estado', () => {
        const onEditar = vi.fn();
        const cargo = { id: 3, nombre: 'Conductor', descripcion: '', contratos_count: 4, activo: true };
        const { unmount } = tabla(<CargoMaestroRow cargo={cargo} onEditar={onEditar} />);

        expect(screen.getByText('—')).toBeTruthy();
        fireEvent.click(screen.getByText('Editar'));
        expect(onEditar).toHaveBeenCalledWith(cargo);

        fireEvent.click(screen.getByText('Desactivar'));
        expect(inertia.router.patch).toHaveBeenCalledWith(
            '/administracion.contratos.maestros.cargos.estado',
            { activo: false },
            { preserveScroll: true },
        );
        unmount();

        tabla(<CargoMaestroRow cargo={{ ...cargo, activo: false, descripcion: 'Maneja' }} onEditar={onEditar} />);
        expect(screen.getByText('Inactivo')).toBeTruthy();
        expect(screen.getByText('Activar')).toBeTruthy();
    });

    it('CatalogoItemRow usa el contador configurado', () => {
        const onEditar = vi.fn();
        const item = { id: 5, nombre: 'Area Norte', descripcion: 'Zona', activo: false, total: 9 };

        tabla(<CatalogoItemRow item={item} routeNames={{ estado: 'catalogo.estado' }} conteoCampo="total" onEditar={onEditar} />);

        expect(screen.getByText('9')).toBeTruthy();
        fireEvent.click(screen.getByText('Editar'));
        expect(onEditar).toHaveBeenCalledWith(item);
        fireEvent.click(screen.getByText('Activar'));
        expect(inertia.router.patch).toHaveBeenCalledWith('/catalogo.estado', { activo: true }, { preserveScroll: true });
    });

    it('CatalogoItemRow asume cero cuando no hay contador', () => {
        tabla(<CatalogoItemRow item={{ id: 1, nombre: 'Sede', activo: true }} routeNames={{ estado: 'x' }} onEditar={vi.fn()} />);

        expect(screen.getByText('0')).toBeTruthy();
        expect(screen.getByText('—')).toBeTruthy();
    });

    it('RolMaestroCard oculta el cambio de estado en roles protegidos', () => {
        const rol = { id: 1, nombre: 'Admin', codigo: 'admin', modulos: ['a', 'b'], activo: true, protegido: true };
        const { unmount } = tabla(<RolMaestroCard rol={rol} onEditar={vi.fn()} />);

        expect(screen.getByText('a')).toBeTruthy();
        expect(screen.queryByText('Desactivar')).toBeNull();
        unmount();

        tabla(<RolMaestroCard rol={{ ...rol, protegido: false, activo: false, descripcion: 'Rol' }} onEditar={vi.fn()} />);
        fireEvent.click(screen.getByText('Activar'));
        expect(inertia.router.patch).toHaveBeenCalledWith(
            '/administracion.contratos.maestros.roles.estado',
            { activo: true },
            { preserveScroll: true },
        );
    });
});

describe('CuentaColaboradorCard', () => {
    const roles = [{ codigo: 'area_limpieza', nombre: 'Area de limpieza' }];

    it('muestra el colaborador, contrato y rol', () => {
        const cuenta = {
            id: 9,
            name: 'Cuenta',
            email: 'a@b.pe',
            rol: 'area_limpieza',
            activo: true,
            colaborador: { nombre_completo: 'Ana Perez', documento: '123', contratos: [{ numero: 'C-1', cargo: { nombre: 'Recolector' } }] },
        };
        const onEditar = vi.fn();

        tabla(<CuentaColaboradorCard cuenta={cuenta} roles={roles} onEditar={onEditar} />);

        expect(screen.getByText('Ana Perez')).toBeTruthy();
        expect(screen.getByText('Area de limpieza')).toBeTruthy();
        expect(screen.getByText('Recolector')).toBeTruthy();
        fireEvent.click(screen.getByText('Editar'));
        expect(onEditar).toHaveBeenCalledWith(cuenta);
    });

    it('usa valores por defecto sin colaborador y bloquea el boton mientras cambia el estado', () => {
        inertia.router.patch.mockImplementation((ruta, datos, opciones) => {
            opciones.onStart();
        });
        tabla(<CuentaColaboradorCard cuenta={{ id: 2, name: 'Sin datos', email: 'x@y.pe', rol: 'otro', activo: false }} roles={roles} onEditar={vi.fn()} />);

        expect(screen.getByText('Sin cargo')).toBeTruthy();
        expect(screen.getByText('Sin contrato')).toBeTruthy();
        expect(screen.getByText('otro')).toBeTruthy();

        fireEvent.click(screen.getByText('Activar'));
        expect(screen.getByText('Activar').disabled).toBe(true);
    });

    it('rehabilita el boton al terminar la peticion', () => {
        inertia.router.patch.mockImplementation((ruta, datos, opciones) => {
            opciones.onStart();
            opciones.onFinish();
        });
        tabla(<CuentaColaboradorCard cuenta={{ id: 2, name: 'N', email: 'x@y.pe', rol: 'otro', activo: true }} roles={roles} onEditar={vi.fn()} />);

        fireEvent.click(screen.getByText('Desactivar'));
        expect(screen.getByText('Desactivar').disabled).toBe(false);
    });
});

describe('TipoMantenimientoRow', () => {
    const tipo = { id: 4, nombre: 'Frenos', descripcion: '', activo: true, mantenimientos_count: 2 };

    it('cambia el estado del tipo', () => {
        render(<TipoMantenimientoRow tipo={tipo} />);

        expect(screen.getByText('Sin descripción')).toBeTruthy();
        fireEvent.click(screen.getByText('Desactivar'));
        expect(inertia.router.patch).toHaveBeenCalledWith(
            '/administracion.vehiculos.maestros.tipos.estado',
            { activo: false },
            { preserveScroll: true },
        );
    });

    it('permite editar, guardar y cancelar', () => {
        inertia.put.mockImplementation((ruta, opciones) => opciones.onSuccess());
        const { container } = render(<TipoMantenimientoRow tipo={{ ...tipo, descripcion: 'Revision', activo: false }} />);

        fireEvent.click(screen.getByText('Editar'));
        fireEvent.change(container.querySelector('input'), { target: { value: 'Frenos y luces' } });
        fireEvent.change(container.querySelector('textarea'), { target: { value: 'Completo' } });
        fireEvent.click(screen.getByText('Cancelar'));
        expect(screen.getByText('Activar')).toBeTruthy();

        fireEvent.click(screen.getByText('Editar'));
        fireEvent.submit(container.querySelector('form'));
        expect(inertia.put).toHaveBeenCalledTimes(1);
        expect(inertia.put.mock.calls[0][0]).toBe('/administracion.vehiculos.maestros.tipos.update');
        expect(screen.getByText('Revision')).toBeTruthy();
    });
});

describe('TareaLimpiezaCard', () => {
    const base = { id: 11, estado: 'Asignado', descripcion: 'Residuos', latitud: -12.06, longitud: -75.21, user: { name: 'Luis' } };

    it('permite iniciar la atencion de una tarea asignada', () => {
        inertia.router.patch.mockImplementation((ruta, datos, opciones) => {
            opciones.onStart();
            opciones.onFinish();
        });
        render(<TareaLimpiezaCard reporte={{ ...base, ruta_orden: 3, foto_url: '/f.jpg', tipo_incidencia: 'Basura' }} />);

        expect(screen.getByText('3')).toBeTruthy();
        expect(screen.getByText('Basura')).toBeTruthy();
        fireEvent.click(screen.getByText('Iniciar atención'));
        expect(inertia.router.patch).toHaveBeenCalledWith('/limpieza.reportes.iniciar', {}, expect.objectContaining({ preserveScroll: true }));
        expect(screen.getByText('Iniciar atención').disabled).toBe(false);
    });

    it('registra la evidencia y limpia el formulario al terminar', () => {
        inertia.post.mockImplementation((ruta, opciones) => opciones.onSuccess());
        const { container } = render(<TareaLimpiezaCard reporte={{ ...base, estado: 'En atención' }} />);
        const archivo = new File(['x'], 'foto.png', { type: 'image/png' });

        expect(screen.getByText('Residuos')).toBeTruthy();
        expect(screen.queryByText('Iniciar atención')).toBeNull();
        expect(screen.getByText('Registrar evidencia y cerrar').disabled).toBe(true);

        fireEvent.change(container.querySelector('input[type="file"]'), { target: { files: [archivo] } });
        expect(screen.getByText('Registrar evidencia y cerrar').disabled).toBe(false);

        fireEvent.submit(container.querySelector('form'));
        expect(inertia.post).toHaveBeenCalledWith('/limpieza.reportes.evidencia', expect.objectContaining({ forceFormData: true }));
        expect(screen.getByText('Registrar evidencia y cerrar').disabled).toBe(true);
    });

    it('muestra errores y el estado de subida', () => {
        inertia.errores = { evidencia: 'Archivo invalido' };
        inertia.procesando = true;
        render(<TareaLimpiezaCard reporte={{ ...base, estado: 'En atención' }} />);

        expect(screen.getByText('Archivo invalido')).toBeTruthy();
        expect(screen.getByText('Subiendo evidencia…')).toBeTruthy();
    });

    it('muestra la evidencia de una tarea finalizada', () => {
        render(<TareaLimpiezaCard reporte={{ ...base, estado: 'Atendido', evidencia_url: '/e.jpg', ruta_orden: 2 }} />);

        expect(screen.getByAltText('Evidencia del reporte 11')).toBeTruthy();
        expect(screen.queryByText('Registrar evidencia y cerrar')).toBeNull();
        expect(screen.queryByText('2')).toBeNull();
    });
});
