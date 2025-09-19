function calcularDistancia(coord1, coord2) {
    const R = 6371; // Tierra en km
    const dLat = (coord2.lat - coord1.lat) * (Math.PI / 180);
    const dLon = (coord2.lon - coord1.lon) * (Math.PI / 180);
    const a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(coord1.lat * (Math.PI / 180)) *
      Math.cos(coord2.lat * (Math.PI / 180)) *
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }
  
  function filtrarArbolesPorUbicacion(arboles, ubicacionUsuario, distanciaMaxKm) {
    return arboles.filter(arbol => {
      const distancia = calcularDistancia(arbol.coordenadas, ubicacionUsuario);
      return distancia <= distanciaMaxKm;
    });
  }
  
  module.exports = { filtrarArbolesPorUbicacion };
  