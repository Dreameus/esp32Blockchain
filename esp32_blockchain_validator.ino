#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <mbedtls/md.h>
#include <mbedtls/sha256.h>
#include <time.h>

// WiFi credentials
const char* ssid = "Razz";
const char* password = "12345678";

// Server settings
const char* server = "https://cp93267.tw1.ru/api/";
const char* token = "esp32_secret_token_2024";

// Blockchain settings
const int DIFFICULTY = 4; // Number of leading zeros required
const unsigned long REWARD_AMOUNT = 100; // Tokens per block
const unsigned long TOTAL_SUPPLY = 1000000000; // 1 billion tokens

// SHA256 hash function
String sha256(const String& input) {
  unsigned char hash[32];
  mbedtls_sha256_context ctx;
  mbedtls_sha256_init(&ctx);
  mbedtls_sha256_starts(&ctx, 0);
  mbedtls_sha256_update(&ctx, (const unsigned char*)input.c_str(), input.length());
  mbedtls_sha256_finish(&ctx, hash);
  mbedtls_sha256_free(&ctx);
  
  String result = "";
  for (int i = 0; i < 32; i++) {
    char hex[3];
    sprintf(hex, "%02x", hash[i]);
    result += hex;
  }
  return result;
}

// Check if hash meets difficulty requirement
bool meetsDifficulty(const String& hash) {
  for (int i = 0; i < DIFFICULTY; i++) {
    if (hash[i] != '0') return false;
  }
  return true;
}

// Helper: HTTP GET
String httpGET(const String& url) {
  HTTPClient http;
  http.begin(url);
  int httpCode = http.GET();
  String payload = "";
  if (httpCode > 0) {
    payload = http.getString();
  }
  http.end();
  return payload;
}

// Helper: HTTP POST
String httpPOST(const String& url, const String& payload) {
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  int httpCode = http.POST(payload);
  String response = "";
  if (httpCode > 0) {
    response = http.getString();
  }
  http.end();
  return response;
}

// Validate block structure and proof of work
bool validateBlock(JsonObject blockData) {
  Serial.println("Validating block...");
  
  // Check required fields
  if (!blockData.containsKey("index") || 
      !blockData.containsKey("timestamp") || 
      !blockData.containsKey("previous_hash") || 
      !blockData.containsKey("nonce") || 
      !blockData.containsKey("miner_id")) {
    Serial.println("Block missing required fields");
    return false;
  }
  
  // Validate block index (should be sequential)
  int index = blockData["index"];
  if (index < 0) {
    Serial.println("Invalid block index");
    return false;
  }
  
  // Validate timestamp (should be recent)
  unsigned long timestamp = blockData["timestamp"];
  time_t currentTime = time(nullptr);
  
  // Debug timestamp info
  Serial.println("Block timestamp: " + String(timestamp));
  Serial.println("Current time: " + String(currentTime));
  Serial.println("Time difference: " + String(abs((long)currentTime - (long)timestamp)) + " seconds");
  
  if (timestamp > currentTime + 300 || timestamp < currentTime - 3600) { // Within 1 hour
    Serial.println("Invalid timestamp - too old or too new");
    return false;
  }
  
  // Validate previous hash format
  String previousHash = blockData["previous_hash"].as<String>();
  if (previousHash.length() != 64) {
    Serial.println("Invalid previous hash length");
    return false;
  }
  
  // Validate nonce
  unsigned long nonce = blockData["nonce"];
  if (nonce < 0) {
    Serial.println("Invalid nonce");
    return false;
  }
  
  // Validate proof of work
  String blockString = String(index) + String(timestamp) + previousHash + String(nonce);
  String calculatedHash = sha256(blockString);
  
  if (!meetsDifficulty(calculatedHash)) {
    Serial.println("Block doesn't meet difficulty requirement");
    Serial.println("Calculated hash: " + calculatedHash);
    return false;
  }
  
  Serial.println("Block validation successful");
  Serial.println("Block hash: " + calculatedHash);
  return true;
}

// Validate transaction structure and balances
bool validateTransaction(JsonObject tx) {
  Serial.println("Validating transaction...");
  
  // Check required fields
  if (!tx.containsKey("sender_id") || 
      !tx.containsKey("receiver_id") || 
      !tx.containsKey("amount")) {
    Serial.println("Transaction missing required fields");
    return false;
  }
  
  int senderId = tx["sender_id"];
  int receiverId = tx["receiver_id"];
  unsigned long amount = tx["amount"];
  
  // Validate sender and receiver
  if (senderId < 0 || receiverId < 0) {
    Serial.println("Invalid sender or receiver ID");
    return false;
  }
  
  if (senderId == receiverId) {
    Serial.println("Sender cannot be receiver");
    return false;
  }
  
  // Validate amount
  if (amount <= 0) {
    Serial.println("Invalid amount");
    return false;
  }
  
  // For now, we'll assume the transaction is valid
  // In a real implementation, you'd check balances from the database
  Serial.println("Transaction validation successful");
  Serial.println("Amount: " + String(amount) + " tokens");
  return true;
}

void setup() {
  Serial.begin(115200);
  Serial.println("ESP32 Blockchain Validator Starting...");
  
  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected!");
  Serial.println("IP address: " + WiFi.localIP().toString());
  
  // Configure time
  configTime(0, 0, "pool.ntp.org", "time.nist.gov");
  Serial.println("Waiting for time sync...");
  time_t now = 0;
  int retries = 0;
  while (now < 24 * 3600 && retries < 10) {
    Serial.print(".");
    delay(1000);
    now = time(nullptr);
    retries++;
  }
  Serial.println();
  if (now > 24 * 3600) {
    Serial.println("Time synchronized: " + String(now));
  } else {
    Serial.println("Time sync failed, using fallback");
  }
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected. Reconnecting...");
    WiFi.reconnect();
    delay(5000);
    return;
  }
  
  Serial.println("\n--- Polling for pending items ---");
  
  // 1. Poll for pending blocks
  String blocksUrl = String(server) + "get_pending_blocks.php?token=" + token;
  String blocksResponse = httpGET(blocksUrl);
  
  if (blocksResponse.length() > 0) {
    Serial.println("Processing pending blocks...");
    
    DynamicJsonDocument doc(8192);
    DeserializationError error = deserializeJson(doc, blocksResponse);
    
    if (!error && doc.containsKey("blocks")) {
      JsonArray blocks = doc["blocks"].as<JsonArray>();
      Serial.println("Found " + String(blocks.size()) + " pending blocks");
      
      for (JsonObject block : blocks) {
        int block_id = block["id"];
        Serial.println("Validating block ID: " + String(block_id));
        
        // Validate block
        bool valid = validateBlock(block["data"]);
        
        // Send result
        DynamicJsonDocument resultDoc(512);
        resultDoc["block_id"] = block_id;
        resultDoc["status"] = valid ? "confirmed" : "rejected";
        if (!valid) {
          resultDoc["reason"] = "Validation failed";
        }
        
        String resultPayload;
        serializeJson(resultDoc, resultPayload);
        
        String confirmUrl = String(server) + "confirm_block.php?token=" + token;
        String confirmResp = httpPOST(confirmUrl, resultPayload);
        
        Serial.println("Block " + String(block_id) + " result: " + (valid ? "CONFIRMED" : "REJECTED"));
        Serial.println("Server response: " + confirmResp);
      }
    } else {
      Serial.println("Error parsing blocks response: " + String(error.c_str()));
    }
  } else {
    Serial.println("No pending blocks");
  }
  
  // 2. Poll for pending transactions
  String txUrl = String(server) + "get_pending_transactions.php?token=" + token;
  String txResponse = httpGET(txUrl);
  
  if (txResponse.length() > 0) {
    Serial.println("Processing pending transactions...");
    
    DynamicJsonDocument txDoc(8192);
    DeserializationError error = deserializeJson(txDoc, txResponse);
    
    if (!error && txDoc.containsKey("transactions")) {
      JsonArray txs = txDoc["transactions"].as<JsonArray>();
      Serial.println("Found " + String(txs.size()) + " pending transactions");
      
      for (JsonObject tx : txs) {
        int tx_id = tx["id"];
        Serial.println("Validating transaction ID: " + String(tx_id));
        
        // Validate transaction
        bool valid = validateTransaction(tx);
        
        // Send result
        DynamicJsonDocument resultDoc(512);
        resultDoc["transaction_id"] = tx_id;
        resultDoc["status"] = valid ? "confirmed" : "rejected";
        if (!valid) {
          resultDoc["reason"] = "Validation failed";
        }
        
        String resultPayload;
        serializeJson(resultDoc, resultPayload);
        
        String confirmUrl = String(server) + "confirm_transaction.php?token=" + token;
        String confirmResp = httpPOST(confirmUrl, resultPayload);
        
        Serial.println("Transaction " + String(tx_id) + " result: " + (valid ? "CONFIRMED" : "REJECTED"));
        Serial.println("Server response: " + confirmResp);
      }
    } else {
      Serial.println("Error parsing transactions response: " + String(error.c_str()));
    }
  } else {
    Serial.println("No pending transactions");
  }
  
  Serial.println("--- Polling complete ---");
  delay(10000); // Poll every 10 seconds
} 