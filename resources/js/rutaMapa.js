export const BASE_MUNICIPAL = [-12.0504, -75.2215];

export function posicionesDeRuta(ruta, paradas = []) {
    if (Array.isArray(ruta?.geometria) && ruta.geometria.length >= 2) {
        return ruta.geometria.map((punto) => [Number(punto[0]), Number(punto[1])]);
    }

    const stops = ruta?.reportes ?? paradas;

    return stops
        .filter((reporte) => reporte.latitud != null && reporte.longitud != null)
        .map((reporte) => [Number(reporte.latitud), Number(reporte.longitud)]);
}
