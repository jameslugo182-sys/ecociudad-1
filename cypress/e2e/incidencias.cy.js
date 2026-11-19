import { usuarios } from '../support/e2e';

// Texto único para localizar después la incidencia creada por la prueba.
const descripcion = 'Contenedor desbordado';
const ubicacion = { latitude: -12.0651, longitude: -75.2049 };

function simularGps(win) {
    cy.stub(win.navigator.geolocation, 'getCurrentPosition').callsFake((exito) => exito({ coords: ubicacion }));
}

describe('Incidencias registradas', () => {
    it('E2E-05: el formulario del ciudadano no permite enviar datos incompletos', () => {
        cy.iniciarSesion(usuarios.ciudadano);
        cy.visit('/reportes/crear');

        cy.contains('button', 'Enviar Reporte al Municipio').should('be.disabled');
        cy.get('#file-upload').selectFile('cypress/fixtures/incidencia.png', { force: true });
        cy.contains('button', descripcion).click();
        // Falta la ubicación GPS: el envío sigue bloqueado.
        cy.contains('button', 'Enviar Reporte al Municipio').should('be.disabled');
        cy.screenshot('E2E-05-formulario-incompleto-bloqueado');
    });

    it('E2E-06: el ciudadano registra una incidencia con foto, GPS y tipo', () => {
        cy.iniciarSesion(usuarios.ciudadano);
        cy.visit('/reportes/crear', { onBeforeLoad: simularGps });

        cy.get('#file-upload').selectFile('cypress/fixtures/incidencia.png', { force: true });
        cy.get('#ubicacion-gps').click();
        cy.contains('Ubicación capturada con éxito').should('be.visible');
        cy.contains('button', descripcion).click();
        cy.screenshot('E2E-06-formulario-completo');
        cy.contains('button', 'Enviar Reporte al Municipio').should('be.enabled').click();

        cy.location('pathname').should('eq', '/reportes');
        cy.contains('Mis reportes').should('be.visible');
        cy.contains(descripcion).should('be.visible');
        cy.contains('Pendiente').should('be.visible');
        cy.screenshot('E2E-06-incidencia-registrada');
    });

    it('E2E-07: el administrador ve la incidencia y no puede cerrarla sin responsable', () => {
        cy.iniciarSesion(usuarios.administrador);
        cy.visit('/administracion/incidencias');

        cy.contains('h2', 'Incidencias registradas').should('be.visible');
        cy.get('input[type="search"]').type('Ciudadano Demo 1');
        cy.get('select').select('Pendiente');
        cy.contains('button', 'Filtrar').click();

        cy.location('search').should('include', 'estado=Pendiente');
        cy.get('tbody tr').should('have.length.greaterThan', 0);
        cy.get('tbody tr').first().within(() => {
            cy.contains('Pendiente').should('be.visible');
            cy.contains('button', 'Marcar atendido').should('be.disabled');
        });
        cy.screenshot('E2E-07-panel-incidencias-filtrado');
    });
});
