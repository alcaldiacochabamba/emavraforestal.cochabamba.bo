package com.skygreen.pages;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.support.FindBy;
import org.openqa.selenium.support.PageFactory;
import org.openqa.selenium.support.ui.Select;
import org.openqa.selenium.support.ui.WebDriverWait;
import org.openqa.selenium.support.ui.ExpectedConditions;
import java.time.Duration;

public class AdministradorPage {
    
    private WebDriver driver;
    private WebDriverWait wait;
    
    @FindBy(tagName = "h1")
    private WebElement titulo;
    
    @FindBy(name = "especie")
    private WebElement especieInput;
    
    @FindBy(name = "edad")
    private WebElement edadInput;
    
    @FindBy(name = "cuidados")
    private WebElement cuidadosInput;
    
    @FindBy(name = "estado")
    private WebElement estadoSelect;
    
    @FindBy(name = "fotoUrl")
    private WebElement fotoUrlInput;
    
    @FindBy(name = "altura")
    private WebElement alturaInput;
    
    @FindBy(name = "diametroTronco")
    private WebElement diametroInput;
    
    @FindBy(xpath = "//button[contains(text(), 'Confirmar Ubicación')]")
    private WebElement confirmarUbicacionBtn;
    
    @FindBy(id = "agregarArbolBtn")
    private WebElement agregarArbolBtn;
    
    @FindBy(id = "map")
    private WebElement mapa;
    
    public AdministradorPage(WebDriver driver) {
        this.driver = driver;
        this.wait = new WebDriverWait(driver, Duration.ofSeconds(10));
        PageFactory.initElements(driver, this);
    }
    
    public void navegarAAdministrador() {
        driver.get("http://localhost/SkyGreen%20-%20Cucumber/administrador.php");
        wait.until(ExpectedConditions.visibilityOf(titulo));
    }
    
    public String obtenerTitulo() {
        return titulo.getText();
    }
    
    public boolean esFormularioVisible() {
        return especieInput.isDisplayed() && 
               edadInput.isDisplayed() && 
               cuidadosInput.isDisplayed();
    }
    
    public boolean esMapaVisible() {
        return mapa.isDisplayed();
    }
    
    public void llenarEspecie(String especie) {
        especieInput.clear();
        especieInput.sendKeys(especie);
    }
    
    public void llenarEdad(String edad) {
        edadInput.clear();
        edadInput.sendKeys(edad);
    }
    
    public void llenarCuidados(String cuidados) {
        cuidadosInput.clear();
        cuidadosInput.sendKeys(cuidados);
    }
    
    public void seleccionarEstado(String estado) {
        Select select = new Select(estadoSelect);
        select.selectByValue(estado);
    }
    
    public void llenarFotoUrl(String url) {
        fotoUrlInput.clear();
        fotoUrlInput.sendKeys(url);
    }
    
    public void llenarAltura(String altura) {
        alturaInput.clear();
        alturaInput.sendKeys(altura);
    }
    
    public void llenarDiametro(String diametro) {
        diametroInput.clear();
        diametroInput.sendKeys(diametro);
    }
    
    public void hacerClicEnMapa() {
        // Simular clic en el centro del mapa
        mapa.click();
    }
    
    public void confirmarUbicacion() {
        confirmarUbicacionBtn.click();
    }
    
    public boolean estaHabilitadoBotonAgregar() {
        return agregarArbolBtn.isEnabled();
    }
    
    public String obtenerTextoAlerta() {
        try {
            Thread.sleep(1000); // Esperar un poco para que aparezca la alerta
            return driver.switchTo().alert().getText();
        } catch (Exception e) {
            return "";
        }
    }
    
    public void aceptarAlerta() {
        try {
            driver.switchTo().alert().accept();
        } catch (Exception e) {
            // No hay alerta
        }
    }
}