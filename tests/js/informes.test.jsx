import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AdminReporteRow from '@/Components/AdminReporteRow';
import InformeCabecera from '@/Components/InformeCabecera';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';

const inertia = vi.hoisted(() => ({
    router: { patch: vi.fn() },
    get: vi.fn(),
    patch: vi.fn(),
    errores: {},
    procesando: false,
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }) => <a href={href} {...props}>{children}</a>,
    router: inertia.router,
    useForm: (inicial) => {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        const [data, setDatos] = useState(inicial);

        return {
            data,
            errors: inertia.errores,
            processing: inertia.procesando,
            setData: (campo, valor) => setDatos((actual) => ({ ...actual, [campo]: valor })),
            get: inertia.get,
            patch: inertia.patch,
        };
    },
}));

const periodo = { fecha_desde: '2026-10-01', fecha_hasta: '2026-10-31', etiqueta: 'octubre', estado: 'Pendiente' };

beforeEach(() => {
    inertia.router.patch.mockReset();
    inertia.get.mockReset();
    inertia.patch.mockReset();
    inertia.errores = {};
    inertia.procesando = false;
});

describe('InformeCabecera', () => {
    it('muestra el titulo, el PDF y aplica el filtro del periodo', () => {
        const { container } = render(
            <InformeCabecera
                titulo="Incidencias"
                descripcion="Resumen."
                periodo={periodo}
                rutaFiltro="/filtro"
                pdfUrl="/pdf"
                estados={['Pendiente', 'Atendido']}
            />,
        );
        const [desde, hasta] = container.querySelectorAll('input[type="date"]');

        expect(screen.getByText('Incidencias')).toBeTruthy();
        expect(screen.getByText('Descargar PDF').getAttribute('href')).toBe('/pdf');

        fireEvent.change(desde, { target: { value: '2026-10-05' } });
        fireEvent.change(hasta, { target: { value: '2026-10-20' } });
        fireEvent.change(container.querySelector('select'), { target: { value: 'Atendido' } });
        fireEvent.submit(container.querySelector('form'));

        expect(inertia.get).toHaveBeenCalledWith('/filtro', { preserveScroll: true });
    });

    it('oculta el selector de estado y muestra errores de fechas', () => {
        inertia.errores = { fecha_hasta: 'Fecha invalida' };
        const { container } = render(
            <InformeCabecera titulo="Rutas" descripcion="Flota." periodo={{ ...periodo, estado: null }} rutaFiltro="/r" pdfUrl="/p" />,
        );

        expect(container.querySelector('select')).toBeNull();
        expect(screen.getByText('Fecha invalida')).toBeTruthy();
    });

    it('Kpi muestra el valor y la nota opcional', () => {
        const { Kpi } = InformeCabecera;
        const { rerender } = render(<Kpi label="Total" value={12} nota="Ultimo mes" />);

        expect(screen.getByText('12')).toBeTruthy();
        expect(screen.getByText('Ultimo mes')).toBeTruthy();

        rerender(<Kpi label="Total" value={3} />);
        expect(screen.queryByText('Ultimo mes')).toBeNull();
    });
});

describe('AdminReporteRow', () => {
    const reporte = {
        id: 21,
        descripcion: 'Basura en la via',
        created_at: '2026-10-27T10:00:00Z',
        estado: 'Asignado',
        prioridad: 2,
        assigned_to: 5,
        foto_url: '/foto.jpg',
        latitud: -12.06,
        longitud: -75.21,
        user: { name: 'Carlos', email: 'c@ecociudad.pe' },
    };
    const routeNames = { asignar: 'reportes.asignar', estado: 'reportes.estado' };
    const areas = [{ id: 5, name: 'Limpieza 1' }];
    const fila = (props) => render(
        <table>
            <tbody>
                <AdminReporteRow reporte={reporte} areasLimpieza={areas} routeNames={routeNames} {...props} />
            </tbody>
        </table>,
    );

    it('asigna un responsable', () => {
        const { container } = fila();

        expect(screen.getByText('Media')).toBeTruthy();
        fireEvent.change(screen.getByLabelText('Responsable del reporte 21'), { target: { value: '5' } });
        fireEvent.submit(container.querySelector('form'));

        expect(inertia.patch).toHaveBeenCalledWith('/reportes.asignar', { preserveScroll: true });
    });

    it('marca el reporte como atendido y bloquea el boton mientras tanto', () => {
        inertia.router.patch.mockImplementation((ruta, datos, opciones) => opciones.onStart());
        fila();

        fireEvent.click(screen.getByText('Marcar atendido'));
        expect(inertia.router.patch).toHaveBeenCalledWith('/reportes.estado', { estado: 'Atendido' }, expect.objectContaining({ preserveScroll: true }));
        expect(screen.getByText('Marcar atendido').disabled).toBe(true);
    });

    it('rehabilita el boton al finalizar la peticion', () => {
        inertia.router.patch.mockImplementation((ruta, datos, opciones) => {
            opciones.onStart();
            opciones.onFinish();
        });
        fila();

        fireEvent.click(screen.getByText('Marcar atendido'));
        expect(screen.getByText('Marcar atendido').disabled).toBe(false);
    });

    it('muestra valores por defecto, errores y oculta la asignacion', () => {
        inertia.errores = { assigned_to: 'Responsable invalido' };
        const { unmount } = fila({
            reporte: { ...reporte, prioridad: null, assigned_to: null, foto_url: null, tipo_incidencia: null },
        });

        expect(screen.getByText('Sin prioridad')).toBeTruthy();
        expect(screen.getByText('Por clasificar')).toBeTruthy();
        expect(screen.getByText('Responsable invalido')).toBeTruthy();
        expect(screen.getByText('Marcar atendido').disabled).toBe(true);
        unmount();

        fila({ reporte: { ...reporte, estado: 'Atendido' }, mostrarAsignacion: false });
        expect(screen.queryByText('Asignar')).toBeNull();
        expect(screen.queryByText('Marcar atendido')).toBeNull();
        expect(screen.getByText('Ver ubicación')).toBeTruthy();
    });
});

describe('enlaces de navegacion', () => {
    it('NavLink resalta el enlace activo', () => {
        const { rerender } = render(<NavLink href="/a" active>Inicio</NavLink>);
        expect(screen.getByText('Inicio').className).toContain('border-indigo-400');

        rerender(<NavLink href="/a" className=" extra">Inicio</NavLink>);
        expect(screen.getByText('Inicio').className).toContain('border-transparent');
    });

    it('ResponsiveNavLink resalta el enlace activo', () => {
        const { rerender } = render(<ResponsiveNavLink href="/b" active>Perfil</ResponsiveNavLink>);
        const activo = screen.getByText('Perfil').className;

        rerender(<ResponsiveNavLink href="/b">Perfil</ResponsiveNavLink>);
        expect(screen.getByText('Perfil').className).not.toBe(activo);
    });
});
