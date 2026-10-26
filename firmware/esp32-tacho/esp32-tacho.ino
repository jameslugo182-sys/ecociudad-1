/*
 * EcoCiudad — firmware de ejemplo para ESP32 + HC-SR04.
 *
 * 1. Copia este sketch a Arduino IDE (placa ESP32).
 * 2. En el módulo Tachos IoT registra el punto, copia el token (solo se muestra una vez)
 *    y el código (TCH001, TCH002, …).
 * 3. Completa WIFI_SSID, WIFI_PASS, APP_URL (IP LAN o dominio público; nunca 127.0.0.1),
 *    TACHO_CODIGO y DEVICE_TOKEN.
 * 4. Cablea: TRIG -> GPIO 5, ECHO -> GPIO 18, VCC 5V, GND común.
 *    Si el ESP32 es 3.3 V, usa divisor en ECHO o un módulo compatible.
 *
 * POST /api/iot/tachos/{codigo}/lecturas
 * Authorization: Bearer <token>
 * {"distancia_cm": 23.4}
 */

#include <WiFi.h>
#include <HTTPClient.h>

const char* WIFI_SSID = "TU_WIFI";
const char* WIFI_PASS = "TU_CLAVE";

// Ejemplo: "http://192.168.1.20:8000"  (php artisan serve en la PC)
// Producción: "https://ecociudad.ejemplo.pe"  sin barra final
const char* APP_URL = "http://192.168.1.20:8000";
const char* TACHO_CODIGO = "TCH001";
const char* DEVICE_TOKEN = "pega_aqui_el_token_plano";

const int PIN_TRIG = 5;
const int PIN_ECHO = 18;
const unsigned long INTERVALO_MS = 10000;

unsigned long ultimaEnvio = 0;

float medirDistanciaCm() {
    digitalWrite(PIN_TRIG, LOW);
    delayMicroseconds(2);
    digitalWrite(PIN_TRIG, HIGH);
    delayMicroseconds(10);
    digitalWrite(PIN_TRIG, LOW);

    unsigned long duracion = pulseIn(PIN_ECHO, HIGH, 30000);
    if (duracion == 0) {
        return -1;
    }

    return duracion * 0.0343 / 2.0;
}

void enviarLectura(float distanciaCm) {
    if (WiFi.status() != WL_CONNECTED) {
        return;
    }

    HTTPClient http;
    String url = String(APP_URL) + "/api/iot/tachos/" + TACHO_CODIGO + "/lecturas";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", String("Bearer ") + DEVICE_TOKEN);

    String cuerpo = "{\"distancia_cm\":" + String(distanciaCm, 1) + "}";
    int codigo = http.POST(cuerpo);
    Serial.print("HTTP ");
    Serial.print(codigo);
    Serial.print(" ");
    Serial.println(http.getString());
    http.end();
}

void setup() {
    Serial.begin(115200);
    pinMode(PIN_TRIG, OUTPUT);
    pinMode(PIN_ECHO, INPUT);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASS);
    Serial.print("WiFi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println();
    Serial.println(WiFi.localIP());
}

void loop() {
    unsigned long ahora = millis();
    if (ahora - ultimaEnvio < INTERVALO_MS && ultimaEnvio != 0) {
        delay(200);
        return;
    }

    float distancia = medirDistanciaCm();
    if (distancia < 2 || distancia > 400) {
        Serial.println("Lectura ultrasónica inválida");
        delay(2000);
        return;
    }

    Serial.print("Distancia cm: ");
    Serial.println(distancia, 1);
    enviarLectura(distancia);
    ultimaEnvio = ahora;
}
