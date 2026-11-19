import { usuarios } from '../support/e2e';

describe('Acceso y autenticación de usuarios', () => {
    it('E2E-01: un ciudadano con credenciales válidas ingresa al sistema', () => {
        cy.visit('/login');
        cy.get('#email').type(usuarios.ciudadano.email);
        cy.get('#password').type(usuarios.ciudadano.password, { log: false });
        cy.screenshot('E2E-01-login-ciudadano-formulario');
        cy.contains('button', 'Ingresar').click();

        cy.location('pathname').should('not.eq', '/login');
        cy.contains('Incidencias').should('be.visible');
        cy.screenshot('E2E-01-login-ciudadano-panel');
    });

    it('E2E-02: las credenciales incorrectas se rechazan y no se inicia sesión', () => {
        cy.visit('/login');
        cy.get('#email').type(usuarios.ciudadano.email);
        cy.get('#password').type('clave-incorrecta', { log: false });
        cy.contains('button', 'Ingresar').click();

        cy.location('pathname').should('eq', '/login');
        cy.get('.text-red-600').should('be.visible');
        cy.screenshot('E2E-02-login-credenciales-incorrectas');
    });

    it('E2E-03: un invitado que abre el módulo de contratos es enviado al login', () => {
        cy.visit('/administracion/contratos');

        cy.location('pathname').should('eq', '/login');
        cy.screenshot('E2E-03-invitado-redirigido-a-login');
    });

    it('E2E-04: un ciudadano no puede abrir el módulo de contratos (403)', () => {
        cy.iniciarSesion(usuarios.ciudadano);
        cy.request({ url: '/administracion/contratos', failOnStatusCode: false })
            .its('status')
            .should('eq', 403);

        cy.visit('/administracion/contratos', { failOnStatusCode: false });
        cy.contains('403').should('be.visible');
        cy.screenshot('E2E-04-ciudadano-sin-acceso-a-contratos');
    });
});
