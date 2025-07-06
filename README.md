# ESP32 Blockchain Network

A revolutionary decentralized blockchain network powered by ESP32 microcontrollers for real hardware mining and cryptocurrency transactions.

## 🌟 Features

### Core Blockchain Features
- **Real Hardware Mining**: Mine cryptocurrency using actual ESP32 microcontrollers
- **Proof of Work Consensus**: Secure blockchain with adjustable difficulty
- **ESP32 Token**: Native cryptocurrency with 1 billion total supply
- **Instant Rewards**: Earn 100 tokens per successfully mined block
- **User Transactions**: Send and receive tokens between users
- **Real-time Validation**: ESP32 devices validate all blocks and transactions

### Web Interface
- **Modern Neon Theme**: Beautiful cyberpunk-inspired UI
- **User Authentication**: Secure registration and login system
- **Mining Dashboard**: Real-time mining with animations and statistics
- **Transaction System**: Send tokens to other users
- **Leaderboard**: Track top miners and their achievements
- **Rewards System**: Earn rewards for sending tokens
- **Newsletter Subscription**: Stay updated with network news

### Technical Features
- **Dynamic Difficulty**: Automatically adjusts based on network activity
- **NTP Time Sync**: ESP32 devices sync with network time
- **RESTful API**: Complete API for blockchain operations
- **MySQL Database**: Persistent storage for all blockchain data
- **Responsive Design**: Works on desktop and mobile devices

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+ with MySQL support
- MySQL/MariaDB database
- ESP32 development board
- Arduino IDE with ESP32 board support

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd esp32-blockchain
   ```

2. **Configure database**
   - Create a MySQL database
   - Copy `config.example.php` to `config.php`
   - Update database credentials in `config.php`

3. **Run setup scripts**
   ```bash
   # Set up database tables
   php setup.php
   
   # Set up newsletter subscribers table
   php setup_newsletter.php
   ```

4. **Upload ESP32 code**
   - Open `esp32_blockchain_validator_updated.ino` in Arduino IDE
   - Update WiFi credentials and server URL
   - Upload to your ESP32 device

5. **Start mining**
   - Visit your website
   - Register an account
   - Start mining from the dashboard

## 📁 Project Structure

```
esp32-blockchain/
├── index.php                 # Landing page
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # Logout handler
├── dashboard.php             # Main mining dashboard
├── rewards.php               # Rewards system
├── leaderboard.php           # Mining leaderboard
├── subscribe.php             # Newsletter subscription
├── config.php                # Database configuration
├── setup.php                 # Database setup
├── setup_newsletter.php      # Newsletter table setup
├── api/                      # API endpoints
│   ├── submit_block.php      # Block submission
│   ├── submit_transaction.php # Transaction submission
│   ├── confirm_block.php     # ESP32 block confirmation
│   ├── get_difficulty.php    # Get current difficulty
│   └── get_latest_block.php  # Get latest block info
├── esp32_blockchain_validator_updated.ino  # ESP32 code
└── README.md                 # This file
```

## 🔧 Configuration

### Database Configuration (`config.php`)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'esp32_blockchain');

// Blockchain settings
define('DIFFICULTY', 2);           // Mining difficulty (leading zeros)
define('REWARD_AMOUNT', 100);      // Tokens per block
define('TOTAL_SUPPLY', 1000000000); // Total ESP32 tokens
```

### ESP32 Configuration
Update these values in the Arduino code:
```cpp
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverUrl = "http://your-domain.com/api/";
```

## 🎯 How It Works

### Mining Process
1. **Block Creation**: Web interface creates new block with current timestamp
2. **Hash Calculation**: SHA256 hash of block data + nonce
3. **Difficulty Check**: Hash must start with required number of zeros
4. **ESP32 Validation**: ESP32 device validates block before acceptance
5. **Reward Distribution**: Miner receives 100 ESP32 tokens

### Transaction Flow
1. **User Input**: Sender enters receiver address and amount
2. **Balance Check**: System verifies sufficient balance
3. **Transaction Creation**: New transaction added to pending pool
4. **ESP32 Validation**: ESP32 validates transaction
5. **Block Inclusion**: Transaction included in next mined block

### ESP32 Integration
- **Polling**: ESP32 polls server every 5 seconds for new blocks/transactions
- **Validation**: Validates blocks using same SHA256 algorithm as web interface
- **Time Sync**: Uses NTP to get accurate timestamps
- **Serial Output**: Provides detailed logging for debugging

## 🛠️ API Endpoints

### Block Operations
- `POST /api/submit_block.php` - Submit mined block
- `POST /api/confirm_block.php` - ESP32 block confirmation
- `GET /api/get_latest_block.php` - Get latest block info

### Transaction Operations
- `POST /api/submit_transaction.php` - Submit new transaction
- `POST /api/confirm_transaction.php` - ESP32 transaction confirmation

### Network Info
- `GET /api/get_difficulty.php` - Get current mining difficulty
- `GET /api/get_leaderboard.php` - Get mining leaderboard

## 🎨 Customization

### Theme Colors
The neon theme uses these primary colors:
- Primary Green: `#00ff88`
- Primary Cyan: `#00ffff`
- Primary Pink: `#ff0088`
- Background: Dark gradients

### Difficulty Adjustment
For testing, you can lower the difficulty:
```php
define('DIFFICULTY', 1);  // Only 1 leading zero required
```

For production, increase difficulty:
```php
define('DIFFICULTY', 4);  // 4 leading zeros required
```

## 🔍 Troubleshooting

### Common Issues

**Mining not finding blocks:**
- Check difficulty setting (lower for testing)
- Verify ESP32 is connected and validating
- Check browser console for errors

**ESP32 rejecting blocks:**
- Ensure ESP32 has correct server URL
- Check ESP32 serial output for errors
- Verify time synchronization is working

**Database errors:**
- Run setup scripts again
- Check database credentials
- Ensure MySQL is running

### Debug Tools
- `mining_test.php` - Test mining functionality
- `test_esp32_validation.php` - Test ESP32 validation
- `debug_esp32_validation.php` - Debug ESP32 logic
- `test_api_endpoints.php` - Test API endpoints

## 📊 Network Statistics

- **Total Supply**: 1,000,000,000 ESP32 tokens
- **Block Reward**: 100 tokens per block
- **Difficulty**: Adjustable (currently 2)
- **Block Time**: Variable based on difficulty
- **Transaction Fee**: 0 (free transactions)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the MIT License.

## 🆘 Support

For support and questions:
- Check the troubleshooting section
- Review ESP32 serial output
- Test API endpoints individually
- Ensure all setup scripts have been run

## 🚀 Future Plans

- [ ] Mobile app for mining
- [ ] Advanced reward system
- [ ] Smart contracts
- [ ] Cross-chain integration
- [ ] Advanced analytics dashboard
- [ ] Community governance system

---

**Happy Mining! 🎉**

Join the future of decentralized mining with ESP32 Blockchain Network! 