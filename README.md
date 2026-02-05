# 🎬 Entertainment Tadka Telegram Bot

A multi-channel movie search and forwarding Telegram bot with smart search capabilities, "Did You Mean?" suggestions, and request management system.

## 🌟 Features

### 🔍 **Smart Search System**
- **Fuzzy Matching**: Finds movies even with typos
- **Similarity Scoring**: Ranks results by relevance
- **"Did You Mean?"**: Suggests correct movie names
- **Partial Match**: Works with partial movie names

### 📢 **Multi-Channel Support**
- **3 Public Channels**: Main, Theater Prints, Backup
- **3 Private Channels** (Optional): Hidden channel support
- **Auto-Forwarding**: Automatically forwards movies to users
- **Channel Management**: Easy channel configuration

### 📊 **User Management**
- **User Statistics**: Track searches, requests, activity
- **Points System**: Reward active users
- **Preferences**: Language and notification settings
- **Admin Controls**: Owner-only commands

### 📝 **Request System**
- **Movie Requests**: Users can request new movies
- **Request Tracking**: Status tracking (pending/approved/completed)
- **Admin Notifications**: Notify owner of new requests
- **User Request History**: Users can view their requests

### 🛠️ **Technical Features**
- **CSV Database**: Simple, editable movie database
- **Auto-Backup**: Daily automatic backups
- **Typing Indicators**: Better user experience
- **Error Logging**: Comprehensive logging system
- **Docker Support**: Easy deployment with Docker
- **Environment Configuration**: Secure configuration management

## 🚀 Quick Start

### **Prerequisites**
- PHP 8.0+ or Docker
- Telegram Bot Token from [@BotFather](https://t.me/BotFather)
- Admin access to Telegram channels

### **Option 1: Local Setup (Without Docker)**

```bash
# 1. Clone repository
git clone https://github.com/your-repo/entertainment-tadka-bot.git
cd entertainment-tadka-bot

# 2. Copy environment file
cp .env.example .env

# 3. Edit configuration
nano .env

# 4. Install PHP dependencies
sudo apt-get install php8.2 php8.2-curl php8.2-mbstring

# 5. Set permissions
chmod 755 index.php
chmod 666 movies.csv users.json bot_stats.json requests.json

# 6. Run the bot
php -S localhost:8080
