import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';

// Ziggy no existe en el entorno de pruebas: devolvemos el nombre de la ruta.
globalThis.route = (nombre) => `/${nombre}`;

afterEach(() => {
    cleanup();
});
