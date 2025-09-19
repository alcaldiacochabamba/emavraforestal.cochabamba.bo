package com.skygreen.runners;

import io.cucumber.junit.CucumberOptions;
import net.serenitybdd.cucumber.CucumberWithSerenity;
import org.junit.runner.RunWith;

@RunWith(CucumberWithSerenity.class)
@CucumberOptions(
    features = "src/test/resources/features",
    glue = "com.skygreen.stepdefinitions",
    plugin = {
        "pretty",
        "html:target/serenity-reports/html",
        "json:target/serenity-reports/serenity.json"
    },
    tags = "@smoke or @formulario or @mapa"
)
public class TestRunner {
}
