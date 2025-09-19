@administrador
Feature: Funcionalidad del Administrador de Árboles
  Como administrador del sistema SkyGreen
  Quiero poder gestionar la información de los árboles
  Para mantener actualizada la base de datos de reforestación

  Background:
    Given que estoy en la página del administrador

  @smoke
  Scenario: Verificar que la página del administrador se carga correctamente
    Then debo ver el título "Agregar Árbol"
    And debo ver el formulario para agregar árboles
    And debo ver el mapa interactivo

  @formulario
  Scenario: Llenar formulario de árbol con datos válidos
    When lleno el formulario con los siguientes datos:
      | campo           | valor                    |
      | especie         | Schinus molle           |
      | edad            | 5                       |
      | cuidados        | Riego semanal           |
      | estado          | nativo                  |
      | fotoUrl         | https://example.com/tree.jpg |
      | altura          | 3.5                     |
      | diametroTronco  | 25.0                    |
    Then el formulario debe estar completamente lleno
    And el botón "Agregar Árbol" debe estar habilitado

  @mapa
  Scenario: Interactuar con el mapa
    When hago clic en el mapa
    Then debe aparecer un marcador en la ubicación seleccionada
    When hago clic en "Confirmar Ubicación"
    Then debo ver el mensaje "Ubicación confirmada"