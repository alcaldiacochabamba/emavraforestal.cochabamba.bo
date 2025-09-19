function clasificarArbol(tipo) {
    if (tipo === 'nativo') return 'Árbol Nativo';
    if (tipo === 'protegido') return 'Árbol Protegido';
    if (tipo === 'peligroso') return 'Árbol Peligroso';
    return 'Tipo Desconocido';
  }
  
  function validarQR(qr) {
    return /^tree-\d{3}$/.test(qr);
  }
  
  function calcularPorcentajeAdopcion(adoptados, total) {
    if (total === 0) return 0;
    return Math.round((adoptados / total) * 100);
  }
  
  module.exports = { clasificarArbol, validarQR, calcularPorcentajeAdopcion };
  