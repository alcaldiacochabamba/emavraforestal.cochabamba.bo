const { filtrarArbolesPorUbicacion } = require('./funciones');

describe('filtrarArbolesPorUbicacion', () => {
  const arboles = [
    { id: 1, nombre: 'Quebracho', coordenadas: { lat: -17.3895, lon: -66.1568 } }, // 
    { id: 2, nombre: 'Jacarandá', coordenadas: { lat: -17.3912, lon: -66.1458 } }, // Aprox. 1 km
    { id: 3, nombre: 'Ciruelo', coordenadas: { lat: -17.4100, lon: -66.1430 } },   // Aprox. 3 km
    { id: 4, nombre: 'Eucalipto', coordenadas: { lat: -17.4700, lon: -66.1400 } }  // Aprox. 9 km
  ];

  const ubicacionUsuario = { lat: -17.3895, lon: -66.1568 }; // Cbba

  test('Retorna árboles dentro de 2 km', () => {
    const resultado = filtrarArbolesPorUbicacion(arboles, ubicacionUsuario, 2);
    expect(resultado.map(a => a.id)).toEqual([1, 2]);
  });

  test('Retorna árboles dentro de 5 km', () => {
    const resultado = filtrarArbolesPorUbicacion(arboles, ubicacionUsuario, 5);
    expect(resultado.map(a => a.id)).toEqual([1, 2, 3]);
  });

  test('Retorna todos los árboles dentro de 10 km', () => {
    const resultado = filtrarArbolesPorUbicacion(arboles, ubicacionUsuario, 10);
    expect(resultado.map(a => a.id)).toEqual([1, 2, 3, 4]);
  });

  test('Retorna vacío si no hay árboles dentro de 0.1 km', () => {
    const resultado = filtrarArbolesPorUbicacion(arboles, ubicacionUsuario, 0.1);
    expect(resultado.map(a => a.id)).toEqual([1]);
  });
});
