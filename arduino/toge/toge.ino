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
String serverControl = "http://192.168.100.101:8000/api/device-control";
String serverSensor  = "http://192.168.100.101:8000/api/sensors";

// ====== PIN DEFINISI ======
#define DHTPIN D7
#define DHTTYPE DHT22
#define LDRPIN D0
#define RELAY1 D3   //Kipas
#define RELAY2 D4   // Pompa Air
#define SERVO1PIN D5
#define SERVO2PIN D6

// ====== OBJEK ======
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x26, 20, 4);
RTC_DS3231 rtc;
Servo servo1, servo2;

// ====== VARIABEL ======
unsigned long lastUpdate = 0;
int updateInterval = 10000;
float suhu = 0, kelembapan = 0;
int cahaya = 0;

String jadwal[10];
int jumlahJadwal = 0;
int batasSuhuKipas = 32;

bool menyiram = false;
unsigned long waktuMulaiSiram = 0;
unsigned long durasiSiram = 0;

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
    ambilPerintahDariServer();
  }

  if (menyiram && (millis() - waktuMulaiSiram >= durasiSiram)) {
    digitalWrite(RELAY1, HIGH); // matikan pompa
    digitalWrite(RELAY2, HIGH); // matikan kipas 
    menyiram = false;
    Serial.println("Penyiraman otomatis SELESAI!");
    lcd.setCursor(0, 3);
    lcd.print("Siram selesai     ");
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

void kirimDataSensorKeServer() {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;
    http.begin(client, serverSensor);
    http.addHeader("Content-Type", "application/json");

    DateTime now = rtc.now();
    char waktu[20];
    sprintf(waktu, "%04d-%02d-%02d %02d:%02d:%02d", now.year(), now.month(), now.day(), now.hour(), now.minute(), now.second());

    StaticJsonDocument<200> doc;
    doc["temperature"] = suhu;
    doc["humidity"] = kelembapan;
    doc["light_level"] = cahaya;
    doc["measured_at"] = waktu;

    String requestBody;
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
    
    if (!menyiram && jadwal[i] == String(currentTime)) {
      Serial.println("Penyiraman otomatis AKTIF!");
      digitalWrite(RELAY1, LOW); // nyalakan pompa
      digitalWrite(RELAY2, LOW); // nyalakan kipas
      lcd.setCursor(0, 3);
      lcd.print("Sirami otomatis!");
      menyiram = true;
      waktuMulaiSiram = millis();
      break;
    }
  }

  // === Kipas Otomatis ===
  // if (suhu > batasSuhuKipas) {
  //   digitalWrite(RELAY2, LOW);
  //   Serial.println("Kipas ON (otomatis)");
  // } else {
  //   digitalWrite(RELAY2, HIGH);
  //   Serial.println("Kipas OFF (otomatis)");
  // }
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
        jumlahJadwal = 0;
        if (doc.containsKey("schedules")) {
          for (JsonVariant waktu : doc["schedules"].as<JsonArray>()) {
            jadwal[jumlahJadwal++] = waktu.as<String>();
          }
        }

        if (doc.containsKey("fan_temp_limit")) {
          batasSuhuKipas = doc["fan_temp_limit"];
        }

        if (doc.containsKey("watering")) {
          durasiSiram = (unsigned long) doc["watering"] * 60UL * 1000UL;

          Serial.print("Durasi siram (ms): ");
          Serial.println(durasiSiram);
        }

        String communication = doc["communication"];

        if (communication == "1") {
          kendaliOtomatis();
        } else {
          int relay1 = doc["relay"][0];
          int relay2 = doc["relay"][1];
          digitalWrite(RELAY1, relay1 ? LOW : HIGH);
          digitalWrite(RELAY2, relay2 ? LOW : HIGH);

          int servoPos = doc["servo_cover"];
          if (servoPos) {
            servo1.write(90);
            servo2.write(90);
          } else {
            servo1.write(0);
            servo2.write(0);
          }

          lcd.setCursor(0, 3);
          lcd.print("R1:");
          lcd.print(relay1);
          lcd.print(" R2:");
          lcd.print(relay2);
          lcd.print(" S:");
          lcd.print(servoPos ? "Terbuka" : "Tertutup");
        }
      }
    } else {
      Serial.println("Gagal ambil data API");
    }
    http.end();
  }
}
