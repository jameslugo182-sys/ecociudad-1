import { usuarios } from '../support/e2e';

const sufijo = String(Date.now()).slice(-6);
const contrato = {
    documento: `7${sufijo}1`,
    numero: `E2E-${sufijo}`,
    nombres: 'Rosa',
    apellidoPaterno: 'Quispe',
    apellidoMaterno: 'Huaman',
};

const escribir = (etiqueta, valor) => cy.contains('label', etiqueta).find('input').clear().type(valor);

function completarFormulario(datos) {
    escribir('DNI / documento', datos.documento);
    escribir('Nombres', datos.nombres);
    escribir('Apellido paterno', datos.apellidoPaterno);
    escribir('Apellido materno', datos.apellidoMaterno);
    cy.contains('label', 'Sexo').find('select').select('Femenino');
    escribir('Teléfono *', '987654321');
    escribir('Dirección', 'Jr. Real 123, El Tambo');
    escribir('Número de contrato', datos.numero);
    cy.contains('label', 'Cargo *').find('select').select(1);
    cy.contains('label', 'Inicio del contrato').find('input').type('2026-01-01');
    cy.contains('label', 'Fin del contrato').find('input').type('2026-12-31');
    escribir('Remuneración mensual', '1900');
    cy.contains('label', 'Área / unidad').find('select').select(1);
}

describe('Gestión de contratos', () => {
    beforeEach(() => {
        cy.iniciarSesion(usuarios.administrador);
        cy.visit('/administracion/contratos');
        cy.contains('h2', 'Registro de colaboradores').should('be.visible');
    });

    it('E2E-08: el administrador registra un contrato y aparece en el listado', () => {
        cy.contains('button', '+ Nuevo contrato').click();
        cy.contains('h3', 'Nuevo contrato').should('be.visible');
        completarFormulario(contrato);
        cy.screenshot('E2E-08-formulario-contrato-completo', { capture: 'viewport' });
        cy.contains('button', 'Guardar contrato').click();

        cy.contains('Contrato registrado correctamente.').should('be.visible');
        cy.screenshot('E2E-08-contrato-registrado', { capture: 'viewport' });
        cy.contains('button', 'OK').click();

        cy.get('input[type="search"]').type(contrato.numero);
        cy.contains('button', 'Buscar').click();
        cy.contains('td', contrato.numero).should('be.visible');
        cy.contains('td', contrato.documento).should('be.visible');
        cy.screenshot('E2E-08-contrato-en-listado', { capture: 'viewport' });
    });

    it('E2E-09: el sistema rechaza un contrato con número repetido', () => {
        cy.contains('button', '+ Nuevo contrato').click();
        completarFormulario({
            ...contrato,
            documento: `6${sufijo}2`,
            nombres: 'Luis',
            apellidoPaterno: 'Mamani',
            apellidoMaterno: 'Rojas',
        });
        cy.contains('button', 'Guardar contrato').click();

        cy.contains('Ya existe un contrato con ese número.').should('be.visible');
        cy.contains('h3', 'Nuevo contrato').should('be.visible');
        cy.screenshot('E2E-09-numero-de-contrato-repetido', { capture: 'viewport' });
    });

    it('E2E-10: la búsqueda por documento devuelve solo el colaborador buscado', () => {
        cy.get('tbody tr').should('have.length.greaterThan', 1);
        cy.get('input[type="search"]').type(contrato.documento);
        cy.contains('button', 'Buscar').click();

        cy.location('search').should('include', contrato.documento);
        cy.get('tbody tr').should('have.length', 1);
        cy.contains('td', contrato.numero).should('be.visible');
        cy.screenshot('E2E-10-busqueda-por-documento', { capture: 'viewport' });
    });
});
