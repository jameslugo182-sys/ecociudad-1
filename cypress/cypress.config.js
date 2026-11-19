// Se ejecuta con: npx cypress run --config-file cypress/cypress.config.js
// o con el script cypress/ejecutar-e2e.ps1 (npm run e2e), que prepara la base de datos y el servidor.
// Cypress se instala aparte (npm install --no-save cypress) para no obligar a que el pipeline
// de integración descargue su binario de ~200 MB.
export default {
    e2e: {
        baseUrl: process.env.CYPRESS_BASE_URL || 'http://127.0.0.1:8001',
        specPattern: 'cypress/e2e/**/*.cy.js',
        supportFile: 'cypress/support/e2e.js',
        fixturesFolder: 'cypress/fixtures',
        screenshotsFolder: 'docs/pruebas/evidencias/cypress/capturas',
        videosFolder: 'docs/pruebas/evidencias/cypress/videos',
        video: false,
        viewportWidth: 1600,
        viewportHeight: 900,
        defaultCommandTimeout: 8000,
        retries: 0,
    },
};
