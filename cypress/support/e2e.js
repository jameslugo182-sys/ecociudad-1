// Credenciales de los usuarios sembrados por database/seeders (ver cypress/ejecutar-e2e.ps1).
export const usuarios = {
    administrador: {
        email: 'admin.e2e@ecociudad.pe',
        password: 'E2e-Admin2026!',
    },
    ciudadano: { email: 'ciudadano.demo1@ecociudad.pe', password: 'Demo2026!' },
};

// Inicia sesión por la pantalla real de login y conserva la sesión entre pruebas.
Cypress.Commands.add('iniciarSesion', (usuario) => {
    cy.session(
        usuario.email,
        () => {
            cy.visit('/login');
            cy.get('#email').type(usuario.email);
            cy.get('#password').type(usuario.password, { log: false });
            cy.contains('button', 'Ingresar').click();
            cy.location('pathname').should('not.eq', '/login');
        },
        { cacheAcrossSpecs: true },
    );
});

// Las capturas son del área visible: las de página completa fallan de forma intermitente con el servidor PHP integrado.
Cypress.Screenshot.defaults({ capture: 'viewport' });

// Ventana amplia para que las capturas muestren las pantallas completas.
beforeEach(() => {
    cy.viewport(1600, 900);
});

// Evita que Leaflet o recursos externos (mapas) interrumpan una prueba por errores ajenos al módulo.
Cypress.on('uncaught:exception', (error) => !/ResizeObserver|Leaflet|tile/i.test(error.message));
