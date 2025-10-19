#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <DHT.h>
#include <Servo.h>
#include <RTClib.h>
#include <ArduinoJson.h>

// ====== KONFIGURASI WIFI ======
const char* ssid = "Herry Hotspot";
const char* password = "halwatulinka";

// ====== KONFIGURASI SERVER ======
String serverControl = "http://192.168.1.109:8000/api/device-control";
String serverSensor  = "http://192.168.1.109:8000/api/sensors";

// ====== PIN DEFINISI ======
#define DHTPIN D7
#define DHTTYPE DHT22
#define LDRPIN D0
#define RELAY1 D3   // Pompa Air
#define RELAY2 D4   // Kipas
#define SERVO1PIN D5
#define SERVO2PIN D6

// ====== OBJEK ======
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x27, 20, 4);
RTC_DS3231 rtc;
Servo servo1, servo2;

// ====== VARIABEL ======
unsigned long lastUpdate = 0;
int updateInterval = 10000; // 10 detik
float suhu = 0, kelembapan = 0;
int cahaya = 0;

String jadwal[10]; // max 10 waktu
int jumlahJadwal = 0;
int batasSuhuKipas = 32; // default 32°C

void setup() {
  Serial.begin(115200);
  dht.begin();
  Wire.begin(D2, D1);
  lcd.begin();
  lcd.backlight();

  if (!rtc.begin()) {
    Serial.println("RTC tidak terdeteksi!");
  }

  pinMode(LDRPIN, INPUT);
  pinMode(RELAY1, OUTPUT);
  pinMode(RELAY2, OUTPUT);
  digitalWrite(RELAY1, HIGH);
  digitalWrite(RELAY2, HIGH);

  servo1.attach(SERVO1PIN);
  servo2.attach(SERVO2PIN);
  servo1.write(0);
  servo2.write(0);

  lcd.setCursor(0, 0);
  lcd.print("Koneksi WiFi...");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  lcd.clear();
  lcd.print("WiFi Connected!");
  delay(1000);

  ambilPerintahDariServer();
}

void loop() {
  if (millis() - lastUpdate > updateInterval) {
    lastUpdate = millis();
    bacaSensorDanTampilkan();
    kirimDataSensorKeServer();   
    kendaliOtomatis();
    ambilPerintahDariServer();
  }
}

// ====== FUNGSI ======

void bacaSensorDanTampilkan() {
  suhu = dht.readTemperature();
  kelembapan = dht.readHumidity();
  cahaya = digitalRead(LDRPIN);
  DateTime now = rtc.now();

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(now.timestamp(DateTime::TIMESTAMP_TIME));
  lcd.setCursor(0, 1);
  lcd.print("T:");
  lcd.print(suhu);
  lcd.print("C H:");
  lcd.print(kelembapan);
  lcd.print("%");
  lcd.setCursor(0, 2);
  lcd.print("Cahaya:");
  lcd.print(cahaya == HIGH ? "Terang" : "Redup");
}

// === Kirim data sensor ke server Laravel ===
void kirimDataSensorKeServer() {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;
    http.begin(client, serverSensor);
    http.addHeader("Content-Type", "application/json");

    // ambil waktu dari RTC
    DateTime now = rtc.now();
    char waktu[20];
    sprintf(waktu, "%04d-%02d-%02d %02d:%02d:%02d",
            now.year(), now.month(), now.day(), now.hour(), now.minute(), now.second());

    // buat JSON body
    StaticJsonDocument<200> doc;
    doc["temperature"] = suhu;
    doc["humidity"] = kelembapan;
    doc["light_level"] = cahaya;
    doc["measured_at"] = waktu;

    String requestBody;
    Serial.println(requestBody);
    serializeJson(doc, requestBody);

    int httpCode = http.POST(requestBody);
    if (httpCode > 0) {
      Serial.println("Data sensor terkirim ke server:");
      Serial.println(requestBody);
    } else {
      Serial.println("Gagal kirim data sensor!");
    }

    http.end();
  }
}

void kendaliOtomatis() {
  DateTime now = rtc.now();
  char currentTime[6];
  sprintf(currentTime, "%02d:%02d", now.hour(), now.minute());

  // === Penyiraman Otomatis ===
  for (int i = 0; i < jumlahJadwal; i++) {
    if (jadwal[i] == String(currentTime)) {
      Serial.println("Penyiraman otomatis AKTIF!");
      digitalWrite(RELAY1, LOW); // nyalakan pompa
      lcd.setCursor(0, 3);
      lcd.print("Sirami otomatis!");
      break;
    }
  }

  // === Kipas Otomatis ===
  if (suhu > batasSuhuKipas) {
    digitalWrite(RELAY2, LOW);
    Serial.println("Kipas ON (otomatis)");
  }
}

void ambilPerintahDariServer() {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;
    http.begin(client, serverControl);

    int httpCode = http.GET();
    if (httpCode == 200) {
      String payload = http.getString();
      Serial.println(payload);

      StaticJsonDocument<512> doc;
      DeserializationError error = deserializeJson(doc, payload);
      if (!error) {
        // === Relay manual ===
        int relay1 = doc["relay"][0];
        int relay2 = doc["relay"][1];
        digitalWrite(RELAY1, relay1 ? LOW : HIGH);
        digitalWrite(RELAY2, relay2 ? LOW : HIGH);

        // === Servo ===
        int servoPos = doc["servo_cover"];
        if(servoPos){
          servo1.write(90);
          servo2.write(90);
        }else{
          servo1.write(0);
          servo2.write(0);
        }

        // === Jadwal Penyiraman ===
        jumlahJadwal = 0;
        if (doc.containsKey("schedule")) {
          for (JsonVariant waktu : doc["schedule"].as<JsonArray>()) {
            jadwal[jumlahJadwal++] = waktu.as<String>();
          }
        }

        // === Batas suhu kipas ===
        if (doc.containsKey("fan_temp_limit")) {
          batasSuhuKipas = doc["fan_temp_limit"];
        }

        lcd.setCursor(0, 3);
        lcd.print("R1:");
        lcd.print(relay1);
        lcd.print(" R2:");
        lcd.print(relay2);
        lcd.print(" S:");
        if(servoPos){
          lcd.print("Terbuka");
        }else{
          lcd.print("Tertutup");
        }
      }
    } else {
      Serial.println("Gagal ambil data API");
    }
    http.end();
  }
}
