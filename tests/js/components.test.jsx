import { fireEvent, render, screen } from '@testing-library/react';
import { createRef } from 'react';
import { describe, expect, it, vi } from 'vitest';
import ApplicationLogo from '@/Components/ApplicationLogo';
import BarrasInforme from '@/Components/BarrasInforme';
import Checkbox from '@/Components/Checkbox';
import DangerButton from '@/Components/DangerButton';
import EstadoBadge from '@/Components/EstadoBadge';
import FlashMessage from '@/Components/FlashMessage';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';

let propsDePagina = {};

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: propsDePagina }),
}));

describe('BarrasInforme', () => {
    it('muestra un mensaje cuando no hay datos', () => {
        render(<BarrasInforme titulo="Incidencias" datos={[]} />);

        expect(screen.getByText('Sin datos en el periodo seleccionado.')).toBeTruthy();
    });

    it('dibuja una barra proporcional por cada dato', () => {
        const { container } = render(
            <BarrasInforme titulo="Incidencias" color="bg-red-500" datos={[{ label: 'A', total: 10 }, { label: 'B', total: 5 }]} />,
        );
        const barras = container.querySelectorAll('.h-full');

        expect(screen.getByText('Incidencias')).toBeTruthy();
        expect(barras[0].style.width).toBe('100%');
        expect(barras[1].style.width).toBe('50%');
        expect(barras[0].className).toContain('bg-red-500');
    });

    it('usa el valor por defecto cuando se omiten los datos', () => {
        render(<BarrasInforme titulo="Vacio" />);

        expect(screen.getByText('Vacio')).toBeTruthy();
    });
});

describe('EstadoBadge', () => {
    it('aplica el estilo del estado conocido o uno neutro', () => {
        render(
            <>
                <EstadoBadge estado="Lleno" />
                <EstadoBadge estado="Desconocido" />
            </>,
        );

        expect(screen.getByText('Lleno').className).toContain('bg-red-100');
        expect(screen.getByText('Desconocido').className).toContain('bg-gray-100');
    });
});

describe('FlashMessage', () => {
    it('no muestra nada sin mensajes o tras un registro', () => {
        propsDePagina = {};
        const { container, rerender } = render(<FlashMessage />);
        expect(container.firstChild).toBeNull();

        propsDePagina = { flash: { registered: true, error: 'x' } };
        rerender(<FlashMessage />);
        expect(container.firstChild).toBeNull();
    });

    it('muestra errores y mensajes de exito', () => {
        propsDePagina = { flash: { error: 'Algo fallo' } };
        const { rerender } = render(<FlashMessage />);
        expect(screen.getByRole('alert').textContent).toBe('Algo fallo');

        propsDePagina = { flash: { success: 'Todo bien' } };
        rerender(<FlashMessage />);
        expect(screen.getByRole('alert').textContent).toBe('Todo bien');
    });
});

describe('formularios', () => {
    it('InputError solo se muestra con mensaje', () => {
        const { container, rerender } = render(<InputError message="" />);
        expect(container.firstChild).toBeNull();

        rerender(<InputError message="Campo requerido" className="mt-2" />);
        expect(screen.getByText('Campo requerido').className).toContain('mt-2');
    });

    it('InputLabel usa value o children', () => {
        const { rerender } = render(<InputLabel value="Correo" htmlFor="correo" />);
        expect(screen.getByText('Correo').getAttribute('for')).toBe('correo');

        rerender(<InputLabel>Contenido</InputLabel>);
        expect(screen.getByText('Contenido')).toBeTruthy();
    });

    it('Checkbox siempre es de tipo checkbox', () => {
        render(<Checkbox aria-label="acepto" className="extra" type="text" />);

        expect(screen.getByLabelText('acepto').getAttribute('type')).toBe('checkbox');
    });

    it('TextInput enfoca el campo y expone focus()', () => {
        const ref = createRef();
        render(<TextInput aria-label="nombre" isFocused ref={ref} />);
        const campo = screen.getByLabelText('nombre');

        expect(document.activeElement).toBe(campo);
        campo.blur();
        ref.current.focus();
        expect(document.activeElement).toBe(campo);
    });

    it('TextInput no enfoca por defecto', () => {
        render(<TextInput aria-label="otro" type="email" />);

        expect(document.activeElement).not.toBe(screen.getByLabelText('otro'));
    });
});

describe('botones', () => {
    it('PrimaryButton ejecuta onClick y respeta disabled', () => {
        const onClick = vi.fn();
        const { rerender } = render(<PrimaryButton onClick={onClick}>Guardar</PrimaryButton>);

        fireEvent.click(screen.getByText('Guardar'));
        expect(onClick).toHaveBeenCalledTimes(1);

        rerender(<PrimaryButton disabled>Guardar</PrimaryButton>);
        expect(screen.getByText('Guardar').disabled).toBe(true);
        expect(screen.getByText('Guardar').className).toContain('opacity-25');
    });

    it('SecondaryButton es de tipo button por defecto', () => {
        const { rerender } = render(<SecondaryButton>Cancelar</SecondaryButton>);
        expect(screen.getByText('Cancelar').getAttribute('type')).toBe('button');

        rerender(<SecondaryButton type="submit" disabled>Cancelar</SecondaryButton>);
        expect(screen.getByText('Cancelar').getAttribute('type')).toBe('submit');
        expect(screen.getByText('Cancelar').disabled).toBe(true);
    });

    it('DangerButton usa type button salvo que se indique otro', () => {
        const { rerender } = render(<DangerButton>Eliminar</DangerButton>);
        expect(screen.getByText('Eliminar').getAttribute('type')).toBe('button');

        rerender(<DangerButton type="submit" disabled>Eliminar</DangerButton>);
        expect(screen.getByText('Eliminar').getAttribute('type')).toBe('submit');
        expect(screen.getByText('Eliminar').className).toContain('opacity-25');
    });
});

describe('ApplicationLogo', () => {
    it('renderiza el logo accesible', () => {
        render(<ApplicationLogo className="h-8" />);

        expect(screen.getByLabelText('EcoCiudad')).toBeTruthy();
    });
});
