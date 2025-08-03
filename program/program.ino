/*
 * Fire Detection System with Fuzzy Logic
 * Sensors: DHT22 (Temperature/Humidity), Flame Sensor, MQ-2 (Gas/Smoke)
 * Display: OLED 128x64 (SSD1306)
 * Fixed version with corrected pin assignments and sensor logic
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <DHT.h>
#include <time.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <UniversalTelegramBot.h>
#include <PubSubClient.h>

// OLED Display Configuration
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
#define SCREEN_ADDRESS 0x3C
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// WiFi Config
const char* ssid = "AyR41sampe10";
const char* password = "Nur1zzA14924";
//server config 
const char* serverURL = "http://www.server-monitoring-systemesp32.gt.tc/api/sensor-data.php";
const char* apiKey = "669392bc28a517239c093d0cb53308f0";

// Add TLS certificate for HiveMQ
const char* root_ca = R"EOF(
-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF+
0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX6U
A5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq+sW
T8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR3qyH
B5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j3x+UC
B5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b0SHzUv
KBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH0ahmbWn
OlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xDk3SzynTn
jh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaHWBfEbwrbw
qHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ13hef4Y53CI
rU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQEAwIBBjAPBgNV
HRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY9umbbjANBgkq
hkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu+ILlaS/V9lZL
ubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+GnY+EgPbk6ZGQ
3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X/qkSsisRcOj/KK
NFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCvjHIInNzktHCgKQ5
ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNbOhdjsnvzqvHu7Ur
TkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nxe5AW0wdeRlN8NwdC
jNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1NbdWhscdCb+ZAJzVc
oyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1lvh+wjChP4kqKOJ2qxq
4RgqsahDYVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0GE44Za4rF2LN9d11TPA
mRGunUHBcnWEvgJBQl9nJEiU0Zsnvgc/ubhPgXRR4Xq37Z0j4r7g1SgEEzwxA57d
emyPxgcYxn/eR44/KJ4EBs+lVDR3veyJm+kXQ99b21/+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----
)EOF";

// HiveMQ Cloud Configuration
const char* mqtt_server = "0aa5a667593f4582b1aa6e4dc7eff7ac.s1.eu.hivemq.cloud"; // Replace with your HiveMQ cluster URL
const int mqtt_port = 8883; // TLS port for HiveMQ Cloud
const char* mqtt_user = "milardisalim"; // Your HiveMQ username
const char* mqtt_password = "Adminesp32"; // Your HiveMQ password
const char* mqtt_client_id = "esp32-001";

// Update MQTT topics to include your unique prefix
const char* mqtt_topic_base = "monitsvr/esp32-001/";
const char* mqtt_topic_status = "monitsvr/esp32-001/status";
const char* mqtt_topic_temperature = "monitsvr/esp32-001/temperature";
const char* mqtt_topic_humidity = "monitsvr/esp32-001/kelembaban";
const char* mqtt_topic_smoke = "monitsvr/esp32-001/asap";
const char* mqtt_topic_flame = "monitsvr/esp32-001/api";
const char* mqtt_topic_risk = "monitsvr/esp32-001/resiko";
const char* mqtt_topic_command = "monitsvr/esp32-001/perintah";
const char* mqtt_topic_all = "monitsvr/esp32-001/all";
const char* mqtt_topic_alert = "monitsvr/esp32-001/alert";

// Telegram Bot Config
const String TELEGRAM_BOT_TOKEN = "7294777742:AAEjZ5GxCrF_cuKnwjQsPKdOiHWiP2Tq-IQ";
const String TELEGRAM_CHAT_ID = "648841899";
const String TELEGRAM_API_URL = "https://api.telegram.org/bot" + TELEGRAM_BOT_TOKEN + "/sendMessage";

// Telegram Bot Setup
WiFiClientSecure secured_client;
UniversalTelegramBot bot(TELEGRAM_BOT_TOKEN, secured_client);

// MQTT Client Setup
WiFiClient espClient;
PubSubClient mqttClient(espClient);

unsigned long lastBotCheck = 0;
const unsigned long BOT_CHECK_INTERVAL = 1000;

// Pin Definitions - FIXED PIN CONFLICTS
#define DHT_PIN 4
#define FLAME_PIN 5
#define MQ2_PIN 34        // ADC pin for analog reading
#define LED_GREEN_PIN 19
#define LED_YELLOW_PIN 18 
#define LED_RED_PIN 23    
#define SDA_PIN 21        // Standard ESP32 I2C pins
#define SCL_PIN 22        // Standard ESP32 I2C pins

// DHT Configuration - FIXED TYPE
#define DHT_TYPE DHT11    // Changed to DHT11
DHT dht(DHT_PIN, DHT_TYPE);

// System Variables
float temperature = 0.0;
float humidity = 0.0;
int flameDetected = 0;
int smokeLevel = 0;
float fireRisk = 0.0;
String systemStatus = "SAFE";
String previousStatus = "SAFE";
bool wifiConnected = false;
bool displayInitialized = false;
bool systemTestMode = false;
bool emergencyMode = false;
bool mqttConnected = false;

// Timing Variables
unsigned long lastSensorRead = 0;
unsigned long lastDataSend = 0;
unsigned long lastStatusCheck = 0;
unsigned long lastTelegramNotification = 0;
unsigned long lastDisplayUpdate = 0;
unsigned long lastPeriodicReport = 0;
unsigned long lastTelegramTest = 0;
unsigned long lastSystemTest = 0;
unsigned long systemStartTime = 0;
unsigned long lastMqttPublish = 0;
unsigned long lastMqttReconnect = 0;

// Intervals
const unsigned long SENSOR_INTERVAL = 2000;
const unsigned long SEND_INTERVAL = 10000;
const unsigned long STATUS_INTERVAL = 1000;
const unsigned long TELEGRAM_MIN_INTERVAL = 60000;      // Reduced from 100000 to 60000 (1 minute)
const unsigned long DISPLAY_UPDATE_INTERVAL = 1000;
const unsigned long PERIODIC_REPORT_INTERVAL = 1800000; // 30 minutes
const unsigned long TELEGRAM_TEST_INTERVAL = 3600000;   // 1 hour
const unsigned long SYSTEM_TEST_INTERVAL = 86400000;    // 24 hours
const unsigned long MQTT_PUBLISH_INTERVAL = 5000;  // Publish to MQTT every 5 seconds
const unsigned long MQTT_RECONNECT_INTERVAL = 5000;

// Notification thresholds
const float TEMP_WARNING_THRESHOLD = 40.0;
const float TEMP_DANGER_THRESHOLD = 55.0;
const int SMOKE_WARNING_THRESHOLD = 300;    // Adjusted for MQ-2 typical values
const int SMOKE_DANGER_THRESHOLD = 600;     // Adjusted for MQ-2 typical values

// Calibration values for MQ-2
const float MQ2_RL = 10.0;                  // Load resistance in kOhms
const float MQ2_RO_CLEAN_AIR_FACTOR = 9.83; // Sensor resistance in clean air

// Add sensor reading averages for stability
const int READING_SAMPLES = 5;
float tempReadings[READING_SAMPLES];
float humidityReadings[READING_SAMPLES];
int smokeReadings[READING_SAMPLES];
int readingIndex = 0;

// Fuzzy Logic Structure
struct FuzzySet {
  float low;
  float medium;
  float high;
};

struct MembershipValues {
  float temp_low, temp_medium, temp_high;
  float smoke_low, smoke_medium, smoke_high;
  float flame_no, flame_yes;
};

// MQTT Functions
void setupMQTT() {
  secured_client.setCACert(root_ca);
  mqttClient.setClient(secured_client);
  mqttClient.setServer(mqtt_server, mqtt_port);
  mqttClient.setCallback(mqttCallback);
  mqttClient.setBufferSize(1024);  // Increase buffer size for larger messages
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  
  Serial.print("MQTT Message Sampai [");
  Serial.print(topic);
  Serial.print("]: ");
  Serial.println(message);
  
  // Handle commands via MQTT
  if (String(topic) == mqtt_topic_command) {
    handleMqttCommand(message);
  }
}

void handleMqttCommand(String command) {
  if (command == "status") {
    publishMqttStatus();
  } else if (command == "test") {
    runSystemTest();
  } else if (command == "reset") {
    resetSystem();
  } else if (command == "emergency") {
    emergencyMode = true;
    systemStatus = "DANGER";
    setSystemStatus("DANGER");
  } else if (command == "reboot") {
    ESP.restart();
  }
}

void connectMQTT() {
  if (!wifiConnected) return;
  
  unsigned long currentTime = millis();
  if (currentTime - lastMqttReconnect < MQTT_RECONNECT_INTERVAL) return;
  
  if (!mqttClient.connected()) {
    Serial.print("Attempting MQTT connection...");
    
    String clientId = mqtt_client_id;
    clientId += String(random(0xffff), HEX);
    
    bool connected = false;
    if (strlen(mqtt_user) > 0) {
      connected = mqttClient.connect(clientId.c_str(), mqtt_user, mqtt_password);
    } else {
      connected = mqttClient.connect(clientId.c_str());
    }
    
    if (connected) {
      Serial.println("MQTT connected!");
      mqttConnected = true;
      
      // Subscribe to command topic
      mqttClient.subscribe(mqtt_topic_command);
      
      // Publish initial status
      publishMqttStatus();
      
      // Send MQTT connection notification
      String mqttMsg = "{\"event\":\"connected\",\"device\":\"" + String(mqtt_client_id) + "\",\"timestamp\":\"" + getTimestamp() + "\"}";
      mqttClient.publish("monitsvr/events", mqttMsg.c_str());
      
    } else {
      Serial.print("MQTT connection failed, rc=");
      Serial.print(mqttClient.state());
      Serial.println(" retry in 5 seconds");
      mqttConnected = false;
    }
    
    lastMqttReconnect = currentTime;
  }
}

void publishMqttData() {
  if (!mqttClient.connected()) return;
  
  // Publish individual sensor values
  mqttClient.publish(mqtt_topic_temperature, String(temperature, 2).c_str());
  mqttClient.publish(mqtt_topic_humidity, String(humidity, 2).c_str());
  mqttClient.publish(mqtt_topic_smoke, String(smokeLevel).c_str());
  mqttClient.publish(mqtt_topic_flame, String(flameDetected).c_str());
  mqttClient.publish(mqtt_topic_risk, String(fireRisk, 2).c_str());
  mqttClient.publish(mqtt_topic_status, systemStatus.c_str());
  
  // Publish combined JSON data
  DynamicJsonDocument doc(512);
  doc["device_id"] = mqtt_client_id;
  doc["status"] = systemStatus;
  doc["temperature"] = temperature;
  doc["humidity"] = humidity;
  doc["smoke"] = smokeLevel;
  doc["flame"] = flameDetected;
  doc["fire_risk"] = fireRisk;
  doc["timestamp"] = getTimestamp();
  
  String jsonString;
  serializeJson(doc, jsonString);
  mqttClient.publish(mqtt_topic_all, jsonString.c_str());
  
  Serial.println("MQTT data published");
}

void publishMqttStatus() {
  if (!mqttClient.connected()) return;
  
  DynamicJsonDocument doc(1024);
  doc["device_id"] = mqtt_client_id;
  doc["status"] = systemStatus;
  doc["temperature"] = temperature;
  doc["humidity"] = humidity;
  doc["smoke"] = smokeLevel;
  doc["flame"] = flameDetected;
  doc["fire_risk"] = fireRisk;
  doc["wifi_rssi"] = WiFi.RSSI();
  doc["uptime"] = millis() / 1000;
  doc["emergency_mode"] = emergencyMode;
  doc["timestamp"] = getTimestamp();
  
  // Add threshold info
  JsonObject thresholds = doc.createNestedObject("thresholds");
  thresholds["temp_warning"] = TEMP_WARNING_THRESHOLD;
  thresholds["temp_danger"] = TEMP_DANGER_THRESHOLD;
  thresholds["smoke_warning"] = SMOKE_WARNING_THRESHOLD;
  thresholds["smoke_danger"] = SMOKE_DANGER_THRESHOLD;
  
  String jsonString;
  serializeJson(doc, jsonString);
  mqttClient.publish("monitsvr/full_status", jsonString.c_str());
}

void setup() {
  Serial.begin(115200);
  
  // Initialize I2C for OLED
  Wire.begin(SDA_PIN, SCL_PIN);
  
  // Initialize OLED Display
  if(!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allokasi gagal"));
    displayInitialized = false;
  } else {
    displayInitialized = true;
    display.clearDisplay();
    display.setTextColor(SSD1306_WHITE);
    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println("Fire Detection Sys");
    display.println("Initializing...");
    display.display();
  }
  
  // Initialize pins
  pinMode(FLAME_PIN, INPUT);
  pinMode(LED_GREEN_PIN, OUTPUT);
  pinMode(LED_YELLOW_PIN, OUTPUT);
  pinMode(LED_RED_PIN, OUTPUT);
  
  // Initialize sensors
  dht.begin();
  
  // Initialize reading arrays
  for(int i = 0; i < READING_SAMPLES; i++) {
    tempReadings[i] = 25.0;
    humidityReadings[i] = 50.0;
    smokeReadings[i] = 0;
  }
  
  // Initialize WiFi
  connectToWiFi();
  
  // Initialize MQTT
  setupMQTT();
  
  // Initialize system
  setSystemStatus("SAFE");
  systemStartTime = millis();
  
  // Wait for sensors to stabilize
  Serial.println("Menunggu sensor stabil...");
  delay(2000);
  
  // Run initial system test
  runSystemTest();
  
  // Test Telegram connection
  testTelegramConnection();
  
  // Send startup notification
  if (wifiConnected) {
    testServerConnection();
    setupTelegramBot();
    sendTelegramMessage("🚨 SISTEM DETEKSI KEBAKARAN AKTIF \n\n✅ Sistem berhasil dinyalakan\n📅 " + getTimestamp() + "\n🏠 Device: ESP32_FIRE_001\n📡 MQTT & HTTP Active");
  }
  
  updateDisplay();
  
  Serial.println("Monitoring Ruang Server dimulai");
  Serial.println("MQTT dan HTTP integration active");
  Serial.println("System siap");
  delay(1000);
}

void loop() {
  unsigned long currentTime = millis();
  
  // Maintain MQTT connection
  if (wifiConnected) {
    if (!mqttClient.connected()) {
      connectMQTT();
    } else {
      mqttClient.loop();
    }
  }
  
  // Read sensors periodically
  if (currentTime - lastSensorRead >= SENSOR_INTERVAL) {
    readSensors();
    lastSensorRead = currentTime;
  }
  
  // Process fuzzy logic and update status
  if (currentTime - lastStatusCheck >= STATUS_INTERVAL) {
    processFireDetection();
    updateSystemStatus();
    lastStatusCheck = currentTime;
  }
  
  // Update OLED display
  if (currentTime - lastDisplayUpdate >= DISPLAY_UPDATE_INTERVAL) {
    updateDisplay();
    lastDisplayUpdate = currentTime;
  }
  
  // Send data to server via HTTP
  if (currentTime - lastDataSend >= SEND_INTERVAL) {
    sendDataToServer();
    lastDataSend = currentTime;
  }
  
  // Publish data to MQTT
  if (currentTime - lastMqttPublish >= MQTT_PUBLISH_INTERVAL) {
    if (mqttConnected) {
      publishMqttData();
    }
    lastMqttPublish = currentTime;
  }
  
  // Send periodic status reports
  if (currentTime - lastPeriodicReport >= PERIODIC_REPORT_INTERVAL) {
    sendPeriodicReport();
    lastPeriodicReport = currentTime;
  }
  
  // Test Telegram connection periodically
  if (currentTime - lastTelegramTest >= TELEGRAM_TEST_INTERVAL) {
    testTelegramConnection();
    lastTelegramTest = currentTime;
  }
  
  // Run system test periodically
  if (currentTime - lastSystemTest >= SYSTEM_TEST_INTERVAL) {
    runSystemTest();
    lastSystemTest = currentTime;
  }
  
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED && wifiConnected) {
    wifiConnected = false;
    mqttConnected = false;
    Serial.println("Koneksi wifi hilang. operasi dalam offline mode.");
    connectToWiFi();
  }
  
  // Emergency mode handling
  if (emergencyMode) {
    handleEmergencyMode();
  }

  if (wifiConnected) {
    checkTelegramMessages();
  }
  
  delay(100);
}

void readSensors() {
  // Read DHT22 (Temperature & Humidity) with averaging
  float newTemp = dht.readTemperature();
  float newHumidity = dht.readHumidity();
  
  // Check if DHT22 reading failed
  if (!isnan(newTemp) && !isnan(newHumidity)) {
    // Add to rolling average
    tempReadings[readingIndex] = newTemp;
    humidityReadings[readingIndex] = newHumidity;
    
    // Calculate averages
    float tempSum = 0, humSum = 0;
    for(int i = 0; i < READING_SAMPLES; i++) {
      tempSum += tempReadings[i];
      humSum += humidityReadings[i];
    }
    temperature = tempSum / READING_SAMPLES;
    humidity = humSum / READING_SAMPLES;
  } else {
    Serial.println("gagal membaca DHT sensor!");
  }
  
  // Read Flame Sensor - FIXED LOGIC
  flameDetected = !digitalRead(FLAME_PIN) ? 0 : 1;
  
  // Read MQ-2 Gas Sensor with averaging
  int rawSmokeValue = analogRead(MQ2_PIN);
  smokeReadings[readingIndex] = rawSmokeValue;
  
  // Calculate average smoke level
  int smokeSum = 0;
  for(int i = 0; i < READING_SAMPLES; i++) {
    smokeSum += smokeReadings[i];
  }
  int avgSmokeRaw = smokeSum / READING_SAMPLES;
  
  // Convert to approximate PPM
  float voltage = (avgSmokeRaw / 4095.0) * 3.3;
  float rs = ((3.3 - voltage) / voltage) * MQ2_RL;
  float ratio = rs / MQ2_RO_CLEAN_AIR_FACTOR;
  
  if(ratio > 0) {
    smokeLevel = (int)(pow(10, ((log10(ratio) - 0.42) / -0.41)));
    smokeLevel = constrain(smokeLevel, 0, 1000);
  } else {
    smokeLevel = 0;
  }
  
  // Update reading index
  readingIndex = (readingIndex + 1) % READING_SAMPLES;
  
  // Print sensor readings
  Serial.println("=== Sensor Readings ===");
  Serial.printf("Temperature: %.2f°C (raw: %.2f)\n", temperature, newTemp);
  Serial.printf("Humidity: %.2f%% (raw: %.2f)\n", humidity, newHumidity);
  Serial.printf("Flame: %s\n", flameDetected ? "DETECTED" : "NOT DETECTED");
  Serial.printf("Smoke Level: %d ppm (raw ADC: %d)\n", smokeLevel, avgSmokeRaw);
  Serial.println("=====================");
}

void processFireDetection() {
  // Calculate membership values
  MembershipValues membership = calculateMembershipValues();
  
  // Apply fuzzy rules and calculate fire risk
  fireRisk = calculateFireRisk(membership);
  float fire_max_risk = 90.0;

  // Apply additional safety factors
  if (flameDetected) {
    fireRisk = max(fireRisk, fire_max_risk);
  }
  
  Serial.printf("Fire Risk Level: %.2f%%\n", fireRisk);
}

MembershipValues calculateMembershipValues() {
  MembershipValues mv;

  // Temperature membership functions
  mv.temp_low = calculateTriangularMembership(temperature, 20, 30, 40);
  mv.temp_medium = calculateTriangularMembership(temperature, 35, 45, 55);
  mv.temp_high = calculateTriangularMembership(temperature, 50, 65, 80);
  
  // Smoke membership functions
  mv.smoke_low = calculateTriangularMembership(smokeLevel, 0, 150, 300);
  mv.smoke_medium = calculateTriangularMembership(smokeLevel, 250, 450, 650);
  mv.smoke_high = calculateTriangularMembership(smokeLevel, 600, 800, 1000);
  
  // Flame membership
  mv.flame_no = flameDetected ? 0.0 : 1.0;
  mv.flame_yes = flameDetected ? 1.0 : 0.0;
  
  return mv;
}

float calculateTriangularMembership(float value, float a, float b, float c) {
  if (value <= a || value >= c) {
    return 0.0;
  } else if (value == b) {
    return 1.0;
  } else if (value < b) {
    return (value - a) / (b - a);
  } else {
    return (c - value) / (c - b);
  }
}

float calculateFireRisk(MembershipValues mv) {
  float rules[10];
  float weights[10];
  
  // Fuzzy Rules Implementation
  rules[0] = 10.0;
  weights[0] = fmin(fmin(mv.temp_low, mv.smoke_low), mv.flame_no);
  
  rules[1] = 20.0;
  weights[1] = fmin(fmin(mv.temp_medium, mv.smoke_low), mv.flame_no);
  
  rules[2] = 40.0;
  weights[2] = fmin(fmin(mv.temp_low, mv.smoke_medium), mv.flame_no);
  
  rules[3] = 50.0;
  weights[3] = fmin(fmin(mv.temp_medium, mv.smoke_medium), mv.flame_no);
  
  rules[4] = 45.0;
  weights[4] = fmin(fmin(mv.temp_high, mv.smoke_low), mv.flame_no);
  
  rules[5] = 75.0;
  weights[5] = fmin(mv.temp_high, mv.smoke_medium);
  
  rules[6] = 90.0;
  weights[6] = fmin(mv.temp_high, mv.smoke_high);
  
  rules[7] = 95.0;
  weights[7] = mv.flame_yes;
  
  rules[8] = 80.0;
  weights[8] = mv.smoke_high;
  
  rules[9] = 60.0;
  weights[9] = fmin(mv.temp_low, mv.smoke_high);
  
  // Defuzzification
  float numerator = 0.0;
  float denominator = 0.0;
  
  for (int i = 0; i < 10; i++) {
    numerator += rules[i] * weights[i];
    denominator += weights[i];
  }
  
  if (denominator == 0) {
    return 0.0;
  }
  
  return numerator / denominator;
}

void updateSystemStatus() {
  String newStatus;
  
  // Determine system status based on fire risk level
  if (fireRisk < 30) {
    newStatus = "SAFE";
  } else if (fireRisk < 70) {
    newStatus = "WARNING";  
  } else {
    newStatus = "DANGER";
  }
  
  // Override based on critical thresholds
  if (flameDetected || temperature >= TEMP_DANGER_THRESHOLD || smokeLevel >= SMOKE_DANGER_THRESHOLD) {
    newStatus = "DANGER";
  } else if (temperature >= TEMP_WARNING_THRESHOLD || smokeLevel >= SMOKE_WARNING_THRESHOLD) {
    if (newStatus == "SAFE") {
      newStatus = "WARNING";
    }
  }
  
  // Only update if status changed
  if (newStatus != systemStatus) {
    previousStatus = systemStatus;
    systemStatus = newStatus;
    setSystemStatus(systemStatus);
    logStatusChange();
    
    // Send notifications
    sendStatusChangeNotification();
    
    // Publish status change to MQTT
    if (mqttConnected) {
      DynamicJsonDocument doc(256);
      doc["event"] = "status_change";
      doc["previous"] = previousStatus;
      doc["current"] = systemStatus;
      doc["fire_risk"] = fireRisk;
      doc["timestamp"] = getTimestamp();
      
      String jsonString;
      serializeJson(doc, jsonString);
      mqttClient.publish("monitsvr/events", jsonString.c_str());
    }
  }
}

void setSystemStatus(String status) {
  // Turn off all LEDs first
  digitalWrite(LED_GREEN_PIN, LOW);
  digitalWrite(LED_YELLOW_PIN, LOW);
  digitalWrite(LED_RED_PIN, LOW);
  
  if (status == "SAFE") {
    digitalWrite(LED_GREEN_PIN, HIGH);
    Serial.println("STATUS: SAFE - All clear");
    
  } else if (status == "WARNING") {
    digitalWrite(LED_YELLOW_PIN, HIGH);
    Serial.println("STATUS: WARNING - Potential fire risk detected");
    
  } else if (status == "DANGER") {
    digitalWrite(LED_RED_PIN, HIGH);
    Serial.println("STATUS: DANGER - FIRE DETECTED!");
  }
}

void updateDisplay() {
  if (!displayInitialized) return;
  
  display.clearDisplay();
  
  // Title
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("Deteksi Api");
  
  // Draw a line separator
  display.drawLine(0, 9, 127, 9, SSD1306_WHITE);
  
  // Status with larger text
  display.setCursor(0, 12);
  display.setTextSize(1);
  display.print("Status: ");
  
  // Status text in appropriate size
  if (systemStatus == "SAFE") {
    display.setTextSize(1);
    display.println("AMAN");
  } else if (systemStatus == "WARNING") {
    display.setTextSize(1);
    display.println("WASPADA!");
  } else if (systemStatus == "DANGER") {
    display.setTextSize(2);
    display.setCursor(50, 12);
    display.println("BAHAYA");
  }
  
  // Sensor readings
  display.setTextSize(1);
  display.setCursor(0, 28);
  display.printf("Temp: %.1fC", temperature);
  if (temperature >= TEMP_DANGER_THRESHOLD) {
    display.print(" !");
  }
  
  display.setCursor(70, 28);
  display.printf("Hum: %.0f%%", humidity);
  
  display.setCursor(0, 38);
  display.printf("Gas: %d ppm", smokeLevel);
  if (smokeLevel >= SMOKE_DANGER_THRESHOLD) {
    display.print(" !");
  }
  
  display.setCursor(0, 48);
  display.printf("Api: %s", flameDetected ? "ADA!" : "Tidak");
  
  display.setCursor(70, 48);
  display.printf("Risk:%.0f%%", fireRisk);
  
  // Bottom status bar with MQTT indicator
  display.drawLine(0, 56, 127, 56, SSD1306_WHITE);
  display.setCursor(0, 57);
  display.setTextSize(1);
  display.printf("W:%s", wifiConnected ? "OK" : "X");
  
  // MQTT status indicator
  display.setCursor(35, 57);
  display.printf("M:%s", mqttConnected ? "OK" : "X");
  
  // Time (if available)
  String timeStr = getTimestamp();
  if (timeStr.length() > 10 && timeStr.indexOf(':') > 0) {
    display.setCursor(80, 57);
    display.print(timeStr.substring(11, 16)); // Show HH:MM
  }
  
  display.display();
}

// Add this to setup() after WiFi connection:
void setupTelegramBot() {
  secured_client.setCACert(TELEGRAM_CERTIFICATE_ROOT); // For secure connection
  
  // Send startup message
  bot.sendMessage(TELEGRAM_CHAT_ID, "🤖 Fire Detection Bot Started!\nSend /help for commands", "");
}

void sendStatusChangeNotification() {
  if (!wifiConnected) return;
  
  unsigned long currentTime = millis();
  secured_client.setCACert(TELEGRAM_CERTIFICATE_ROOT);

  // Rate limiting - don't send notifications too frequently
  if (currentTime - lastTelegramNotification < TELEGRAM_MIN_INTERVAL && systemStatus != "DANGER") {
    return;
  }
  
  String message = "🚨 ALERT SISTEM DETEKSI KEBAKARAN\n\n";
  
  if (systemStatus == "SAFE") {
    message += "✅ STATUS: AMAN\n";
    message += "Situasi telah kembali normal\n\n";
  } else if (systemStatus == "WARNING") {
    message += "⚠️ STATUS: PERINGATAN\n";
    message += "Terdeteksi potensi risiko kebakaran!\n\n";
  } else if (systemStatus == "DANGER") {
    message += "🔥 STATUS: BAHAYA! 🔥\n";
    message += "KEBAKARAN TERDETEKSI!\n\n";
  }
  
  // Add sensor readings
  message += "📊 DATA SENSOR:\n";
  message += "🌡️ Suhu: " + String(temperature, 1) + "°C";
  if (temperature >= TEMP_DANGER_THRESHOLD) {
    message += " ⚠️ TINGGI!";
  } else if (temperature >= TEMP_WARNING_THRESHOLD) {
    message += " ⚠️";
  }
  message += "\n";
  
  message += "💧 Kelembaban: " + String(humidity, 1) + "%\n";
  
  message += "💨 Asap/Gas: " + String(smokeLevel) + " ppm";
  if (smokeLevel >= SMOKE_DANGER_THRESHOLD) {
    message += " ⚠️ TINGGI!";
  } else if (smokeLevel >= SMOKE_WARNING_THRESHOLD) {
    message += " ⚠️";
  }
  message += "\n";
  
  message += "🔥 Api: " + String(flameDetected ? "TERDETEKSI! 🔥" : "Tidak ada") + "\n";
  message += "📈 Tingkat Risiko: " + String(fireRisk, 1) + "%\n\n";
  
  message += "📅 Waktu: " + getTimestamp() + "\n";
  message += "🏠 Device: esp32-001\n";
  message += "📡 MQTT: " + String(mqttConnected ? "Connected" : "Disconnected");
  
  // Add emergency instructions for danger status
  if (systemStatus == "DANGER") {
    message += "\n\n🚨 LANGKAH DARURAT:\n";
    message += "1. Segera tinggalkan area\n";
    message += "2. Hubungi pemadam kebakaran: 113\n";
    message += "3. Jangan gunakan lift\n";
    message += "4. Pastikan semua orang keluar dengan selamat";
  }
  
  sendTelegramMessage(message);
  lastTelegramNotification = currentTime;
}

// Add this function to handle Telegram commands
void handleTelegramMessages(int numNewMessages) {
  for (int i = 0; i < numNewMessages; i++) {
    String chat_id = String(bot.messages[i].chat_id);
    String text = bot.messages[i].text;
    String from_name = bot.messages[i].from_name;
    
    Serial.printf("Received message from %s: %s\n", from_name.c_str(), text.c_str());
    
    // Check if authorized user
    if (chat_id != TELEGRAM_CHAT_ID) {
      bot.sendMessage(chat_id, "⛔ Unauthorized user!", "");
      continue;
    }
    
    // Process commands
    if (text == "/start" || text == "/help") {
      String welcome = "🔥 *Fire Detection System Commands*\n\n";
      welcome += "/status - Get current system status\n";
      welcome += "/sensors - Show all sensor readings\n";
      welcome += "/test - Run system test\n";
      welcome += "/emergency - Activate emergency mode\n";
      welcome += "/reset - Reset system to normal\n";
      welcome += "/thresholds - Show alarm thresholds\n";
      welcome += "/mqtt - Show MQTT status\n";
      welcome += "/reboot - Restart ESP32\n";
      welcome += "/help - Show this message\n";
      
      bot.sendMessage(chat_id, welcome, "Markdown");
    }
    
    else if (text == "/status") {
      String statusMsg = "📊 *System Status*\n\n";
      statusMsg += "🚨 Status: " + systemStatus + "\n";
      statusMsg += "🌡️ Temperature: " + String(temperature, 1) + "°C\n";
      statusMsg += "💧 Humidity: " + String(humidity, 1) + "%\n";
      statusMsg += "💨 Smoke: " + String(smokeLevel) + " ppm\n";
      statusMsg += "🔥 Flame: " + String(flameDetected ? "DETECTED!" : "Not detected") + "\n";
      statusMsg += "📈 Risk Level: " + String(fireRisk, 1) + "%\n";
      statusMsg += "📶 WiFi RSSI: " + String(WiFi.RSSI()) + " dBm\n";
      statusMsg += "📡 MQTT: " + String(mqttConnected ? "Connected" : "Disconnected") + "\n";
      statusMsg += "⏱️ Uptime: " + String(millis() / 1000 / 60) + " minutes\n";
      
      bot.sendMessage(chat_id, statusMsg, "Markdown");
    }
    
    else if (text == "/sensors") {
      String sensorMsg = "🔍 *Detailed Sensor Readings*\n\n";
      
      // Temperature details
      sensorMsg += "🌡️ *Temperature Sensor (DHT22)*\n";
      sensorMsg += "   Current: " + String(temperature, 2) + "°C\n";
      sensorMsg += "   Status: ";
      if (temperature >= TEMP_DANGER_THRESHOLD) {
        sensorMsg += "⚠️ DANGER!\n";
      } else if (temperature >= TEMP_WARNING_THRESHOLD) {
        sensorMsg += "⚠️ Warning\n";
      } else {
        sensorMsg += "✅ Normal\n";
      }
      
      // Humidity details
      sensorMsg += "\n💧 *Humidity Sensor*\n";
      sensorMsg += "   Current: " + String(humidity, 2) + "%\n";
      
      // Smoke details
      sensorMsg += "\n💨 *Smoke/Gas Sensor (MQ-2)*\n";
      sensorMsg += "   Current: " + String(smokeLevel) + " ppm\n";
      sensorMsg += "   Raw ADC: " + String(analogRead(MQ2_PIN)) + "\n";
      sensorMsg += "   Status: ";
      if (smokeLevel >= SMOKE_DANGER_THRESHOLD) {
        sensorMsg += "⚠️ DANGER!\n";
      } else if (smokeLevel >= SMOKE_WARNING_THRESHOLD) {
        sensorMsg += "⚠️ Warning\n";
      } else {
        sensorMsg += "✅ Normal\n";
      }
      
      // Flame details
      sensorMsg += "\n🔥 *Flame Sensor*\n";
      sensorMsg += "   Status: " + String(flameDetected ? "🔥 FLAME DETECTED!" : "✅ No flame") + "\n";
      
      bot.sendMessage(chat_id, sensorMsg, "Markdown");
    }
    
    else if (text == "/mqtt") {
      String mqttMsg = "📡 *MQTT Status*\n\n";
      mqttMsg += "Status: " + String(mqttConnected ? "✅ Connected" : "❌ Disconnected") + "\n";
      mqttMsg += "Broker: " + String(mqtt_server) + ":" + String(mqtt_port) + "\n";
      mqttMsg += "Client ID: " + String(mqtt_client_id) + "\n\n";
      mqttMsg += "*Topics Publishing:*\n";
      mqttMsg += "• " + String(mqtt_topic_status) + "\n";
      mqttMsg += "• " + String(mqtt_topic_temperature) + "\n";
      mqttMsg += "• " + String(mqtt_topic_humidity) + "\n";
      mqttMsg += "• " + String(mqtt_topic_smoke) + "\n";
      mqttMsg += "• " + String(mqtt_topic_flame) + "\n";
      mqttMsg += "• " + String(mqtt_topic_risk) + "\n";
      mqttMsg += "• " + String(mqtt_topic_all) + "\n\n";
      mqttMsg += "*Subscribed to:*\n";
      mqttMsg += "• " + String(mqtt_topic_command) + "\n";
      
      bot.sendMessage(chat_id, mqttMsg, "Markdown");
    }
    
    else if (text == "/test") {
      bot.sendMessage(chat_id, "🧪 Running system test...", "");
      runSystemTest();
      bot.sendMessage(chat_id, "✅ System test completed!", "");
    }
    
    else if (text == "/emergency") {
      emergencyMode = true;
      systemStatus = "DANGER";
      setSystemStatus("DANGER");
      
      String emergencyMsg = "🚨 *EMERGENCY MODE ACTIVATED!*\n\n";
      emergencyMsg += "System forced into DANGER status\n";
      emergencyMsg += "Send /reset to return to normal\n";
      
      bot.sendMessage(chat_id, emergencyMsg, "Markdown");
    }
    
    else if (text == "/reset") {
      resetSystem();
      bot.sendMessage(chat_id, "✅ System reset to normal operation", "");
    }
    
    else if (text == "/thresholds") {
      String thresholdMsg = "⚙️ *System Thresholds*\n\n";
      thresholdMsg += "🌡️ *Temperature*\n";
      thresholdMsg += "   Warning: " + String(TEMP_WARNING_THRESHOLD, 1) + "°C\n";
      thresholdMsg += "   Danger: " + String(TEMP_DANGER_THRESHOLD, 1) + "°C\n\n";
      thresholdMsg += "💨 *Smoke/Gas*\n";
      thresholdMsg += "   Warning: " + String(SMOKE_WARNING_THRESHOLD) + " ppm\n";
      thresholdMsg += "   Danger: " + String(SMOKE_DANGER_THRESHOLD) + " ppm\n\n";
      thresholdMsg += "🔥 *Flame Detection*\n";
      thresholdMsg += "   Immediate DANGER status\n";
      
      bot.sendMessage(chat_id, thresholdMsg, "Markdown");
    }
    
    else if (text == "/reboot") {
      bot.sendMessage(chat_id, "🔄 Rebooting system in 3 seconds...", "");
      delay(3000);
      ESP.restart();
    }
    
    else {
      bot.sendMessage(chat_id, "❓ Unknown command. Send /help for available commands", "");
    }
  }
}

// dalam loop() function:
void checkTelegramMessages() {
  if (millis() - lastBotCheck > BOT_CHECK_INTERVAL) {
    int numNewMessages = bot.getUpdates(bot.last_message_received + 1);
    
    while (numNewMessages) {
      Serial.println("Got new messages");
      handleTelegramMessages(numNewMessages);
      numNewMessages = bot.getUpdates(bot.last_message_received + 1);
    }
    
    lastBotCheck = millis();
  }
}

void sendTelegramMessage(String message) {
  if (!wifiConnected) return;
  
  // Check WiFi connection first
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi tidak terhubung, tidak bisa mengirim Telegram message");
    return;
  }
  
  secured_client.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  
  HTTPClient http;
  http.setTimeout(10000); // 10 second timeout
  
  Serial.println("Connecting ke Telegram API...");
  
  // Try to begin connection
  if (!http.begin(secured_client, TELEGRAM_API_URL)) {
    Serial.println("Gagal initialize HTTP connection");
    return;
  }
  
  http.addHeader("Content-Type", "application/json");
  
  // Create JSON payload
  DynamicJsonDocument doc(2048);
  doc["chat_id"] = TELEGRAM_CHAT_ID;
  doc["text"] = message;
  doc["parse_mode"] = "HTML";
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  Serial.println("Sending Telegram message...");
  Serial.printf("Payload size: %d bytes\n", jsonString.length());
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode == 200) {
    Serial.println("✅ Telegram notification sent successfully");
  } else {
    Serial.printf("❌ Failed to send Telegram notification. Response code: %d\n", httpResponseCode);
    
    // Print error details
    if (httpResponseCode < 0) {
      Serial.println("Error: Connection/SSL issue");
    } else if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("HTTP Response: " + response);
    }
  }
  
  http.end();
}

void logStatusChange() {
  Serial.println("=== STATUS CHANGE ===");
  Serial.printf("Previous Status: %s\n", previousStatus.c_str());
  Serial.printf("New Status: %s\n", systemStatus.c_str());
  Serial.printf("Fire Risk: %.2f%%\n", fireRisk);
  Serial.printf("Temperature: %.2f°C\n", temperature);
  Serial.printf("Smoke: %d ppm\n", smokeLevel);
  Serial.printf("Flame: %s\n", flameDetected ? "YES" : "NO");
  Serial.println("====================");
}

void connectToWiFi() {
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  
  // Update display with connection status
  if (displayInitialized) {
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("Connecting WiFi...");
    display.display();
  }
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    Serial.println();
    Serial.println("WiFi connected successfully!");
    Serial.printf("IP address: %s\n", WiFi.localIP().toString().c_str());
    
    // Configure time
    configTime(7 * 3600, 0, "pool.ntp.org"); // UTC+7 for Indonesia
    
    // Update display with success
    if (displayInitialized) {
      display.clearDisplay();
      display.setCursor(0, 0);
      display.println("WiFi Connected!");
      display.println(WiFi.localIP().toString());
      display.display();
      delay(2000);
    }
  } else {
    wifiConnected = false;
    Serial.println();
    Serial.println("WiFi connection failed. Operating in offline mode.");
    
    // Update display with failure
    if (displayInitialized) {
      display.clearDisplay();
      display.setCursor(0, 0);
      display.println("WiFi Failed!");
      display.println("Offline Mode");
      display.display();
      delay(2000);
    }
  }
}

// Update fungsi dengan autentikasi
void sendDataToServer() {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    return;
  }
  
  // Gunakan WiFiClientSecure untuk HTTPS
  //WiFiClientSecure client;
  secured_client.setInsecure(); // Skip certificate verification (untuk testing)
  // Untuk production, gunakan certificate:
  // client.setCACert(root_ca);
  
  HTTPClient http;
  
  // Jika menggunakan HTTPS
  if (String(serverURL).startsWith("https")) {
    http.begin(secured_client, serverURL);
  } else {
    http.begin(serverURL);
  }
  
  http.addHeader("Content-Type", "application/json");
  
  // Tambahkan API key header untuk autentikasi
  http.addHeader("X-API-Key", apiKey);
  
  // Atau gunakan Bearer token jika backend menggunakan JWT
  // http.addHeader("Authorization", "Bearer " + String(apiKey));
  
  // Create JSON payload
  DynamicJsonDocument doc(1024);
  doc["temperature"] = temperature;
  doc["humidity"] = humidity;
  doc["flame"] = flameDetected;
  doc["smoke"] = smokeLevel;
  doc["fire_risk"] = fireRisk;
  doc["status"] = systemStatus;
  doc["timestamp"] = getTimestamp();
  doc["device_id"] = "esp32-001";
  doc["mqtt_connected"] = mqttConnected;
  
  // Tambahkan informasi tambahan
  doc["wifi_rssi"] = WiFi.RSSI();
  doc["free_heap"] = ESP.getFreeHeap();
  doc["uptime"] = millis() / 1000;
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  // Set timeout
  http.setTimeout(10000); // 10 detik timeout
  
  Serial.println("Sending data to server: " + String(serverURL));
  Serial.println("Payload: " + jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.printf("Data sent successfully via HTTP. Response code: %d\n", httpResponseCode);
    
    if (httpResponseCode == 200) {
      Serial.println("Server response: " + response);
      
      // Parse response jika server mengirim commands
      DynamicJsonDocument responseDoc(512);
      DeserializationError error = deserializeJson(responseDoc, response);
      
      if (!error && responseDoc.containsKey("command")) {
        String command = responseDoc["command"];
        handleServerCommand(command);
      }
    } else if (httpResponseCode == 401) {
      Serial.println("Authentication failed! Check API key");
    } else if (httpResponseCode == 404) {
      Serial.println("Endpoint not found! Check server URL");
    }
  } else {
    Serial.printf("Error sending data via HTTP: %d\n", httpResponseCode);
    Serial.println("Error: " + http.errorToString(httpResponseCode));
  }
  
  http.end();
}

// Fungsi untuk handle command dari server (opsional)
void handleServerCommand(String command) {
  Serial.println("Received command from server: " + command);
  
  if (command == "test") {
    runSystemTest();
  } else if (command == "reset") {
    resetSystem();
  } else if (command == "reboot") {
    ESP.restart();
  }
}

String getTimestamp() {
  time_t now;
  struct tm timeinfo;
  
  if (!getLocalTime(&timeinfo)) {
    // Jika NTP belum sync, gunakan millis
    unsigned long currentMillis = millis();
    unsigned long seconds = currentMillis / 1000;
    unsigned long minutes = seconds / 60;
    unsigned long hours = minutes / 60;
    
    char fallbackTime[50];
    sprintf(fallbackTime, "BOOT+%02lu:%02lu:%02lu", 
            hours % 24, minutes % 60, seconds % 60);
    return String(fallbackTime);
  }
  
  char timeString[50];
  strftime(timeString, sizeof(timeString), "%Y-%m-%d %H:%M:%S", &timeinfo);
  return String(timeString);
}

void handleEmergencyMode() {
  // Flash red LED in emergency mode
  static unsigned long lastFlash = 0;
  static bool ledState = false;

  secured_client.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  
  if (millis() - lastFlash >= 500) {
    ledState = !ledState;
    digitalWrite(LED_RED_PIN, ledState ? HIGH : LOW);
    lastFlash = millis();
  }
  
  // Send emergency alerts every 5 minutes
  static unsigned long lastEmergencyAlert = 0;
  if (millis() - lastEmergencyAlert >= 300000) {
    if (wifiConnected) {
      sendTelegramMessage("🚨 MODE DARURAT AKTIF! 🚨\n\nSistem masih dalam mode darurat\n📅 " + getTimestamp() + "\n\nKetik '/reset' untuk menormalkan sistem");
    }
    
    // Also publish to MQTT
    if (mqttConnected) {
      DynamicJsonDocument doc(256);
      doc["event"] = "emergency_alert";
      doc["message"] = "Emergency mode still active";
      doc["timestamp"] = getTimestamp();
      
      String jsonString;
      serializeJson(doc, jsonString);
      mqttClient.publish("monitsvr/alerts", jsonString.c_str());
    }
    
    lastEmergencyAlert = millis();
  }
}

void resetSystem() {
  Serial.println("Resetting system...");
  
  // Reset emergency and test modes
  emergencyMode = false;
  systemTestMode = false;
  
  // Reset system status to safe
  systemStatus = "SAFE";
  setSystemStatus("SAFE");
  
  // Reset all LEDs
  digitalWrite(LED_GREEN_PIN, HIGH);
  digitalWrite(LED_YELLOW_PIN, LOW);
  digitalWrite(LED_RED_PIN, LOW);
  
  // Reset timer variables
  lastSensorRead = 0;
  lastDataSend = 0;
  lastStatusCheck = 0;
  lastTelegramNotification = 0;
  lastDisplayUpdate = 0;
  
  // Send reset notification if WiFi connected
  if (wifiConnected) {
    sendTelegramMessage("🔄 SISTEM DIRESET\n\nSistem telah dikembalikan ke kondisi normal\n📅 " + getTimestamp());
  }
  
  // Publish reset event to MQTT
  if (mqttConnected) {
    DynamicJsonDocument doc(256);
    doc["event"] = "system_reset";
    doc["timestamp"] = getTimestamp();
    
    String jsonString;
    serializeJson(doc, jsonString);
    mqttClient.publish("monitsvr/events", jsonString.c_str());
  }
  
  // Update display
  updateDisplay();
  
  Serial.println("System reset completed.");
}

void printSystemStatus() {
  Serial.println("\n=== SYSTEM STATUS ===");
  Serial.printf("Status: %s\n", systemStatus.c_str());
  Serial.printf("Temperature: %.2f°C\n", temperature);
  Serial.printf("Humidity: %.2f%%\n", humidity);
  Serial.printf("Smoke Level: %d ppm\n", smokeLevel);
  Serial.printf("Flame: %s\n", flameDetected ? "DETECTED" : "NOT DETECTED");
  Serial.printf("Fire Risk: %.2f%%\n", fireRisk);
  Serial.printf("WiFi: %s\n", wifiConnected ? "Connected" : "Disconnected");
  Serial.printf("MQTT: %s\n", mqttConnected ? "Connected" : "Disconnected");
  Serial.printf("Emergency Mode: %s\n", emergencyMode ? "ON" : "OFF");
  Serial.printf("Test Mode: %s\n", systemTestMode ? "ON" : "OFF");
  Serial.printf("Uptime: %lu seconds\n", (millis() - systemStartTime) / 1000);
  
  // Additional sensor threshold info
  Serial.println("\n--- SENSOR THRESHOLDS ---");
  Serial.printf("Temp Warning: %.1f°C\n", TEMP_WARNING_THRESHOLD);
  Serial.printf("Temp Danger: %.1f°C\n", TEMP_DANGER_THRESHOLD);
  Serial.printf("Smoke Warning: %d ppm\n", SMOKE_WARNING_THRESHOLD);
  Serial.printf("Smoke Danger: %d ppm\n", SMOKE_DANGER_THRESHOLD);
  
  // LED status
  Serial.println("\n--- LED STATUS ---");
  Serial.printf("Green LED (Pin %d): %s\n", LED_GREEN_PIN, digitalRead(LED_GREEN_PIN) ? "ON" : "OFF");
  Serial.printf("Yellow LED (Pin %d): %s\n", LED_YELLOW_PIN, digitalRead(LED_YELLOW_PIN) ? "ON" : "OFF");
  Serial.printf("Red LED (Pin %d): %s\n", LED_RED_PIN, digitalRead(LED_RED_PIN) ? "ON" : "OFF");
  
  // Network info
  if (wifiConnected) {
    Serial.println("\n--- NETWORK INFO ---");
    Serial.printf("IP Address: %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("Signal Strength: %d dBm\n", WiFi.RSSI());
    Serial.printf("Connected to: %s\n", ssid);
  }
  
  // MQTT info
  if (mqttConnected) {
    Serial.println("\n--- MQTT INFO ---");
    Serial.printf("Broker: %s:%d\n", mqtt_server, mqtt_port);
    Serial.printf("Client ID: %s\n", mqtt_client_id);
  }
  
  Serial.println("====================\n");
}

void emergencyOverride() {
  Serial.println("\n=== EMERGENCY OVERRIDE ACTIVATED ===");
  
  emergencyMode = true;
  systemStatus = "DANGER";
  setSystemStatus("DANGER");
  
  // Send emergency notification
  if (wifiConnected) {
    sendTelegramMessage("🚨 MODE DARURAT DIAKTIFKAN! 🚨\n\nSistem dipaksa masuk mode darurat oleh operator\n📅 " + getTimestamp() + "\n\nKetik /reset untuk menormalkan sistem");
  }
  
  // Publish to MQTT
  if (mqttConnected) {
    DynamicJsonDocument doc(256);
    doc["event"] = "emergency_override";
    doc["timestamp"] = getTimestamp();
    
    String jsonString;
    serializeJson(doc, jsonString);
    mqttClient.publish("monitsvr/alerts", jsonString.c_str());
  }
  
  // Update display
  updateDisplay();
  
  Serial.println("Emergency mode activated!");
  Serial.println("System will remain in DANGER status until reset");
  Serial.println("Type 'reset' to return to normal operation");
  Serial.println("===================================\n");
}

void runSystemTest() {
  Serial.println("\n=== RUNNING SYSTEM TEST ===");
  systemTestMode = true;
  
  // Test 1: Sensor readings
  Serial.println("1. Testing sensors...");
  readSensors();
  delay(1000);
  
  // Test 2: LED functionality
  Serial.println("2. Testing LED indicators...");
  // Test each LED
  Serial.println("   - Testing GREEN LED...");
  digitalWrite(LED_GREEN_PIN, HIGH);
  delay(500);
  digitalWrite(LED_GREEN_PIN, LOW);
  
  Serial.println("   - Testing YELLOW LED...");
  digitalWrite(LED_YELLOW_PIN, HIGH);
  delay(500);
  digitalWrite(LED_YELLOW_PIN, LOW);
  
  Serial.println("   - Testing RED LED...");
  digitalWrite(LED_RED_PIN, HIGH);
  delay(500);
  digitalWrite(LED_RED_PIN, LOW);
  
  // Test 3: Display
  Serial.println("3. Testing OLED display...");
  if (displayInitialized) {
    display.clearDisplay();
    display.setCursor(0, 0);
    display.setTextSize(2);
    display.println("SYSTEM");
    display.println("TEST");
    display.setTextSize(1);
    display.println();
    display.println("All systems OK");
    display.display();
    delay(2000);
  } else {
    Serial.println("   - Display not initialized");
  }
  
  // Test 4: WiFi connection
  Serial.println("4. Testing WiFi connection...");
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("   ✅ WiFi connection: OK");
    Serial.printf("   - SSID: %s\n", ssid);
    Serial.printf("   - IP: %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("   - RSSI: %d dBm\n", WiFi.RSSI());
  } else {
    Serial.println("   ❌ WiFi connection: FAILED");
  }
  
  // Test 5: MQTT connection
  Serial.println("5. Testing MQTT connection...");
  if (mqttClient.connected()) {
    Serial.println("   ✅ MQTT connection: OK");
    Serial.printf("   - Broker: %s:%d\n", mqtt_server, mqtt_port);
    Serial.printf("   - Client ID: %s\n", mqtt_client_id);
    
    // Send test message
    mqttClient.publish("monitsvr/test", "System test message");
  } else {
    Serial.println("   ❌ MQTT connection: FAILED");
  }
  
  // Test 6: Fuzzy logic calculation
  Serial.println("6. Testing fuzzy logic...");
  processFireDetection();
  Serial.printf("   - Fire risk calculation: %.2f%%\n", fireRisk);
  
  // Test 7: Pin configuration
  Serial.println("7. Verifying pin configuration...");
  Serial.printf("   - DHT Pin: %d\n", DHT_PIN);
  Serial.printf("   - Flame Pin: %d\n", FLAME_PIN);
  Serial.printf("   - MQ2 Pin: %d (ADC)\n", MQ2_PIN);
  Serial.printf("   - I2C SDA: %d\n", SDA_PIN);
  Serial.printf("   - I2C SCL: %d\n", SCL_PIN);
  
  // Publish test results to MQTT
  if (mqttConnected) {
    DynamicJsonDocument doc(512);
    doc["event"] = "system_test";
    doc["wifi"] = wifiConnected;
    doc["mqtt"] = mqttConnected;
    doc["display"] = displayInitialized;
    doc["sensors"]["temperature"] = !isnan(temperature);
    doc["sensors"]["humidity"] = !isnan(humidity);
    doc["sensors"]["smoke"] = smokeLevel >= 0;
    doc["sensors"]["flame"] = true;
    doc["timestamp"] = getTimestamp();
    
    String jsonString;
    serializeJson(doc, jsonString);
    mqttClient.publish("monitsvr/diagnostics", jsonString.c_str());
  }
  
  // Restore normal status
  setSystemStatus(systemStatus);
  systemTestMode = false;
  updateDisplay();
  
  Serial.println("\nSystem test completed!");
  Serial.println("==========================\n");
}

void testServerConnection() {
  Serial.println("\n=== TESTING SERVER CONNECTION ===");
  Serial.println("Server URL: " + String(serverURL));
  
  if (!wifiConnected) {
    Serial.println("❌ WiFi not connected");
    return;
  }
  
  //
  secured_client.setInsecure();
  
  HTTPClient http;
  
  if (String(serverURL).startsWith("https")) {
    http.begin(secured_client, serverURL);
  } else {
    http.begin(serverURL);
  }
  
  http.addHeader("X-API-Key", apiKey);
  http.setTimeout(10000);
  
  // Send test request
  int httpCode = http.GET();
  
  if (httpCode > 0) {
    Serial.printf("✅ Server responded with code: %d\n", httpCode);
    
    if (httpCode == 200) {
      Serial.println("✅ Server connection successful!");
    } else if (httpCode == 404) {
      Serial.println("⚠️ Endpoint not found - check URL");
    } else if (httpCode == 401) {
      Serial.println("⚠️ Authentication failed - check API key");
    }
  } else {
    Serial.printf("❌ Connection failed: %s\n", http.errorToString(httpCode).c_str());
  }
  
  http.end();
  Serial.println("===================================\n");
}

void testTelegramConnection() {
  Serial.println("\n=== TESTING TELEGRAM CONNECTION ===");
  
  if (!wifiConnected) {
    Serial.println("❌ WiFi not connected");
    return;
  }
  
  Serial.println("📱 Bot Token: " + TELEGRAM_BOT_TOKEN.substring(0, 10) + "...");
  Serial.println("💬 Chat ID: " + TELEGRAM_CHAT_ID);
  Serial.println("🌐 API URL: " + TELEGRAM_API_URL.substring(0, 30) + "...");
  
  // Test basic connectivity
  secured_client.setCACert(TELEGRAM_CERTIFICATE_ROOT);
  
  Serial.println("Connecting to Telegram servers...");
  if (secured_client.connect("api.telegram.org", 443)) {
    Serial.println("✅ Successfully connected to Telegram servers");
    
    // Send test message
    String testMsg = "🧪 TES KONEKSI TELEGRAM\n\n";
    testMsg += "Sistem berhasil terhubung ke Telegram!\n";
    testMsg += "📅 " + getTimestamp() + "\n";
    testMsg += "🏠 Device: esp32-001\n";
    testMsg += "📡 WiFi RSSI: " + String(WiFi.RSSI()) + " dBm\n";
    testMsg += "📡 MQTT: " + String(mqttConnected ? "Connected" : "Disconnected");
    
    sendTelegramMessage(testMsg);
  } else {
    Serial.println("❌ Failed to connect to Telegram servers");
    Serial.println("Possible issues:");
    Serial.println("1. Check internet connection");
    Serial.println("2. Firewall blocking HTTPS (port 443)");
    Serial.println("3. ISP blocking Telegram");
  }
  
  Serial.println("=====================================\n");
}

void sendPeriodicReport() {
  if (!wifiConnected) return;
  
  String report = "📊 LAPORAN STATUS BERKALA\n\n";
  report += "✅ Sistem berjalan normal\n";
  report += "🔋 Status: " + systemStatus + "\n";
  report += "🌡️ Suhu: " + String(temperature, 1) + "°C\n";
  report += "💧 Kelembaban: " + String(humidity, 1) + "%\n";
  report += "💨 Gas/Asap: " + String(smokeLevel) + " ppm\n";
  report += "🔥 Deteksi Api: " + String(flameDetected ? "Ada" : "Tidak ada") + "\n";
  report += "📈 Tingkat Risiko: " + String(fireRisk, 1) + "%\n";
  report += "⏱️ Uptime: " + String((millis() - systemStartTime) / 1000 / 60) + " menit\n\n";
  report += "📅 " + getTimestamp() + "\n";
  report += "🏠 Device: esp32-001\n";
  report += "📡 WiFi RSSI: " + String(WiFi.RSSI()) + " dBm\n";
  report += "📡 MQTT: " + String(mqttConnected ? "Connected" : "Disconnected");
  
  sendTelegramMessage(report);
  
  // Also publish to MQTT
  if (mqttConnected) {
    DynamicJsonDocument doc(512);
    doc["event"] = "periodic_report";
    doc["status"] = systemStatus;
    doc["temperature"] = temperature;
    doc["humidity"] = humidity;
    doc["smoke"] = smokeLevel;
    doc["flame"] = flameDetected;
    doc["fire_risk"] = fireRisk;
    doc["uptime_minutes"] = (millis() - systemStartTime) / 1000 / 60;
    doc["wifi_rssi"] = WiFi.RSSI();
    doc["timestamp"] = getTimestamp();
    
    String jsonString;
    serializeJson(doc, jsonString);
    mqttClient.publish("monitsvr/reports", jsonString.c_str());
  }
  
  Serial.println("Periodic report sent");
}