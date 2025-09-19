const {
    clasificarArbol,
    validarQR,
    calcularPorcentajeAdopcion,
  } = require('./testSuite');
  
  // TODO1
  describe('clasificarArbol', () => {
    test('Retorna etiqueta correcta para árbol nativo', () => {
      expect(clasificarArbol('nativo')).toBe('Árbol Nativo');
    });
  
    test('Retorna etiqueta correcta para árbol protegido', () => {
      expect(clasificarArbol('protegido')).toBe('Árbol Protegido');
    });
  
    test('Retorna etiqueta correcta para árbol peligroso', () => {
      expect(clasificarArbol('peligroso')).toBe('Árbol Peligroso');
    });
  
    test('Retorna desconocido para tipo no válido', () => {
      expect(clasificarArbol('fantasma')).toBe('Tipo Desconocido');
    });
  });
  
  // TODO2
  describe('validarQR', () => {
    test('Código válido pasa la validación', () => {
      expect(validarQR('tree-123')).toBe(true);
    });
  
    test('Código inválido no pasa la validación', () => {
      expect(validarQR('plant-456')).toBe(false);
    });
  
    test('Formato incorrecto no pasa la validación', () => {
      expect(validarQR('tree123')).toBe(false);
    });
  });
  
  // TODO3
  describe('calcularPorcentajeAdopcion', () => {
    test('Calcula porcentaje correctamente', () => {
      expect(calcularPorcentajeAdopcion(25, 100)).toBe(25);
    });
  
    test('Retorna 0 cuando el total es 0', () => {
      expect(calcularPorcentajeAdopcion(5, 0)).toBe(0);
    });
  
    test('Redondea el resultado', () => {
      expect(calcularPorcentajeAdopcion(7, 30)).toBe(23); // (7/30)*100 ≈ 23.33
    });
  });
  