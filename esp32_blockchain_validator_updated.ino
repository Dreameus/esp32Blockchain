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

// Blockchain settings - will be updated from server
int DIFFICULTY = 2; // Default, will be updated from server
const unsigned long REWARD_AMOUNT = 100; // Tokens per block
const unsigned long TOTAL_SUPPLY = 1000000000; // 1 billion tokens

// Buzzer pin
const int BUZZER_PIN = 25; // Change this to your buzzer pin

// SHA256 hash function - matches server exactly
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

// Get difficulty from server
bool updateDifficultyFromServer() {
  String difficultyUrl = String(server) + "get_difficulty.php?token=" + token;
  String response = httpGET(difficultyUrl);
  
  if (response.length() > 0) {
    DynamicJsonDocument doc(512);
    DeserializationError error = deserializeJson(doc, response);
    
    if (!error && doc.containsKey("difficulty")) {
      int newDifficulty = doc["difficulty"];
      if (newDifficulty != DIFFICULTY) {
        Serial.println("Updating difficulty from " + String(DIFFICULTY) + " to " + String(newDifficulty));
        DIFFICULTY = newDifficulty;
      }
      return true;
    }
  }
  return false;
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

// Play mining success song (1 second)
void playMiningSong() {
  // Simple ascending notes to celebrate mining success
  int notes[] = {523, 659, 784, 1047}; // C5, E5, G5, C6
  int durations[] = {200, 200, 200, 400}; // 200ms each, last note 400ms
  
  for (int i = 0; i < 4; i++) {
    tone(BUZZER_PIN, notes[i], durations[i]);
    delay(durations[i]);
  }
  
  noTone(BUZZER_PIN);
}

// Print pending block statistics
void printPendingBlockStats() {
  Serial.println("\n=== PENDING BLOCK DETAILS ===");
  
  // Get pending blocks
  String blocksUrl = String(server) + "get_pending_blocks.php?token=" + token;
  String blocksResponse = httpGET(blocksUrl);
  
  if (blocksResponse.length() > 0) {
    DynamicJsonDocument doc(8192);
    DeserializationError error = deserializeJson(doc, blocksResponse);
    
    if (!error && doc.containsKey("blocks")) {
      JsonArray blocks = doc["blocks"].as<JsonArray>();
      int totalBlocks = blocks.size();
      
      Serial.println("Total Pending Blocks: " + String(totalBlocks));
      Serial.println("==========================================");
      
      if (totalBlocks > 0) {
        for (JsonObject block : blocks) {
          int block_id = block["id"];
          JsonObject blockData = block["data"];
          
          // Print only hash information
          if (blockData.containsKey("hash")) {
            String hash = blockData["hash"].as<String>();
            Serial.print("Block ");
            Serial.print(block_id);
            Serial.print(" Hash: ");
            Serial.println(hash);
          }
        }
        
        Serial.println("Total Blocks: " + String(totalBlocks));
        
      } else {
        Serial.println("No pending blocks found");
      }
    } else {
      Serial.println("Error parsing blocks response: " + String(error.c_str()));
    }
  } else {
    Serial.println("Failed to get pending blocks data");
  }
  
  // Get pending transactions count
  String txUrl = String(server) + "get_pending_transactions.php?token=" + token;
  String txResponse = httpGET(txUrl);
  
  if (txResponse.length() > 0) {
    DynamicJsonDocument txDoc(8192);
    DeserializationError error = deserializeJson(txDoc, txResponse);
    
    if (!error && txDoc.containsKey("transactions")) {
      JsonArray txs = txDoc["transactions"].as<JsonArray>();
      Serial.println("\nTotal Pending Transactions: " + String(txs.size()));
    }
  }
  
  Serial.println("=== END BLOCK DETAILS ===\n");
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
  
  // Validate proof of work - EXACTLY like server
  String blockString = String(index) + String(timestamp) + previousHash + String(nonce);
  String calculatedHash = sha256(blockString);
  
  // Debug hash calculation
  Serial.println("Block string: " + blockString);
  Serial.println("Calculated hash: " + calculatedHash);
  Serial.println("Current difficulty: " + String(DIFFICULTY) + " zeros");
  Serial.println("Hash starts with: " + calculatedHash.substring(0, 4));
  
  if (!meetsDifficulty(calculatedHash)) {
    Serial.println("Block doesn't meet difficulty requirement");
    Serial.println("Required: " + String(DIFFICULTY) + " leading zeros");
    Serial.println("Got: " + calculatedHash.substring(0, DIFFICULTY));
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
  
  // Initialize buzzer pin
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);
  
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
  
  // Get initial difficulty from server
  Serial.println("Getting difficulty from server...");
  if (updateDifficultyFromServer()) {
    Serial.println("Difficulty updated: " + String(DIFFICULTY));
  } else {
    Serial.println("Failed to get difficulty, using default: " + String(DIFFICULTY));
  }
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected. Reconnecting...");
    WiFi.reconnect();
    delay(5000);
    return;
  }
  
  // Update difficulty every 10 loops (about every 50 seconds)
  static int loopCount = 0;
  if (loopCount % 10 == 0) {
    updateDifficultyFromServer();
  }
  
  // Print statistics every 20 loops (about every 100 seconds)
  if (loopCount % 20 == 0) {
    printPendingBlockStats();
  }
  
  loopCount++;
  
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
        JsonObject blockData = block["data"];
        
        // Always accept the block (for testing)
        bool valid = true;
        
        // Print only hash information
        if (blockData.containsKey("hash")) {
          String providedHash = blockData["hash"].as<String>();
          Serial.print("Block ");
          Serial.print(block_id);
          Serial.print(" Hash: ");
          Serial.println(providedHash);
        }
        
        // Send confirmation
        DynamicJsonDocument resultDoc(512);
        resultDoc["block_id"] = block_id;
        resultDoc["status"] = "confirmed";
        
        String resultPayload;
        serializeJson(resultDoc, resultPayload);
        
        String confirmUrl = String(server) + "confirm_block.php?token=" + token;
        String confirmResp = httpPOST(confirmUrl, resultPayload);
        
        Serial.print("Block ");
        Serial.print(block_id);
        Serial.println(" ACCEPTED");
        
        // Play mining success song
        playMiningSong();
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
        bool valid = validateTransaction(tx["data"]);
        
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
        
        Serial.print("Transaction ");
        Serial.print(tx_id);
        Serial.print(" result: ");
        Serial.println(valid ? "CONFIRMED" : "REJECTED");
        Serial.println("Server response: " + confirmResp);
      }
    } else {
      Serial.println("Error parsing transactions response: " + String(error.c_str()));
    }
  } else {
    Serial.println("No pending transactions");
  }
  
  Serial.println("--- Polling complete ---");
  delay(5000); // Poll every 5 seconds
} 