import { usuarios } from '../support/e2e';

describe('Módulo de reportes operativos', () => {
    beforeEach(() => {
        cy.iniciarSesion(usuarios.administrador);
    });

    it('E2E-11: los indicadores del resumen coinciden con las incidencias registradas', () => {
        cy.visit('/administracion/incidencias');
        cy.contains('p', 'Total')
            .next()
            .invoke('text')
            .then((totalPanel) => {
                cy.visit('/administracion/reportes');
                cy.contains('h2', 'Resumen operativo').should('be.visible');
                cy.contains('Incidencias del periodo').next().should('have.text', totalPanel.trim());
                cy.contains('Tasa de atención').should('be.visible');
                cy.contains('Contratos vigentes').should('be.visible');
                cy.screenshot('E2E-11-resumen-operativo');
            });
    });

    it('E2E-12: el reporte de personal y contratos resume los contratos registrados', () => {
        cy.visit('/administracion/reportes/personal');

        cy.contains('h2', 'Personal y contratos').should('be.visible');
        cy.contains('Contratos vigentes').next().invoke('text').should('match', /^\d+ \/ \d+$/);
        cy.contains('Contratos por estado').should('be.visible');
        cy.screenshot('E2E-12-reporte-personal-y-contratos');
    });

    it('E2E-13: el reporte de incidencias muestra el detalle y permite descargar el PDF', () => {
        cy.visit('/administracion/reportes/incidencias');
        cy.contains('Incidencias').should('be.visible');
        cy.screenshot('E2E-13-reporte-incidencias');

        cy.request('/administracion/reportes/incidencias/pdf').then((respuesta) => {
            expect(respuesta.status).to.eq(200);
            expect(respuesta.headers['content-type']).to.include('application/pdf');
        });
    });
});
