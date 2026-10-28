// Deja las rutas de los reportes de cobertura relativas al proyecto y con "/" para que
// SonarQube las encuentre en cualquier maquina o servidor de CI:
//   - coverage.xml        (clover de PHPUnit, trae rutas absolutas)
//   - coverage/lcov.info  (lcov de Vitest, en Windows trae "\")
// Uso: node tests/herramientas/normalizar-cobertura.mjs
import fs from 'node:fs';
import path from 'node:path';

const raiz = path.resolve('.');
const aRelativa = (ruta) => path.relative(raiz, path.resolve(ruta)).split(path.sep).join('/');

function procesar(archivo, expresion, construir) {
    if (!fs.existsSync(archivo)) {
        console.log(`${archivo}: no existe, se omite`);

        return;
    }

    let total = 0;
    const contenido = fs.readFileSync(archivo, 'utf8').replace(expresion, (_, ruta) => {
        total++;

        return construir(aRelativa(ruta));
    });

    fs.writeFileSync(archivo, contenido);
    console.log(`${archivo}: ${total} archivos con ruta relativa`);
}

procesar('coverage.xml', /<file name="([^"]+)"/g, (ruta) => `<file name="${ruta}"`);
procesar('coverage/lcov.info', /^SF:(.+)$/gm, (ruta) => `SF:${ruta}`);
