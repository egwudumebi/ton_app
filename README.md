# TON Giveaway Mobile App

A comprehensive React Native mobile app with Laravel backend for TON blockchain gaming and rewards.

## 🚀 Features

### Backend (Laravel API)
- **User Authentication**: Registration, login, and profile management with Laravel Sanctum
- **Wallet Management**: TON wallet integration with balance tracking
- **Game System**: Spin wheel and drop game with score tracking
- **Leaderboard**: Real-time leaderboards with caching and pagination
- **Bonus System**: Daily, weekly, and achievement-based bonuses
- **Admin Panel**: User management, crediting, and system statistics
- **Database**: Optimized schema with proper relationships and indexing

### Frontend (React Native)
- **Beautiful UI**: Modern design with Inter font and smooth animations
- **Spin Wheel Game**: Multi-layer casino-style wheel with prizes
- **Drop Game**: Catch falling TON coins (coming soon)
- **Real-time Updates**: Live score and balance synchronization
- **Offline Support**: Local storage for user data
- **API Integration**: Complete REST API integration with error handling

## 📁 Project Structure

```
TON App/
├── ton_api/                    # Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/API/
│   │   │   ├── AuthController.php
│   │   │   ├── GameController.php
│   │   │   ├── WalletController.php
│   │   │   ├── LeaderboardController.php
│   │   │   └── AdminController.php
│   │   └── Models/
│   │       ├── User.php
│   │       ├── Wallet.php
│   │       ├── Score.php
│   │       ├── Bonus.php
│   │       └── GameSession.php
│   ├── database/migrations/
│   │   ├── create_wallets_table.php
│   │   ├── create_scores_table.php
│   │   ├── create_bonuses_table.php
│   │   └── create_game_sessions_table.php
│   └── routes/api.php
└── Ton Giveaway/               # React Native Frontend
    ├── src/
    │   ├── screens/
    │   │   ├── MainScreen.js
    │   │   ├── TonWheelScreen.js
    │   │   ├── DropGameScreen.js
    │   │   ├── RegistrationScreen.js
    │   │   └── SplashScreen.js
    │   └── utils/
    │       ├── api.js
    │       └── storage.js
    └── App.tsx
```

## 🛠️ Installation & Setup

### Backend Setup

1. **Navigate to Laravel directory:**
   ```bash
   cd ton_api
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Set up environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in .env:**
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=/path/to/database.sqlite
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

6. **Start the server:**
   ```bash
   ipconfig
   php artisan serve
   php artisan serve --host=0.0.0.0 --port=8000
   php artisan serve --host=192.168.100.13 --port=8000
   ```

### Frontend Setup

1. **Navigate to React Native directory:**
   ```bash
   cd "Ton Giveaway"
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Update API base URL in `src/utils/api.js`:**
   ```javascript
   const API_BASE_URL = 'http://your-laravel-server:8000/api';
   ```

4. **Start the app:**
   ```bash
   npx expo start
   ```

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/profile` - Get user profile
- `PUT /api/auth/profile` - Update user profile

### Games
- `POST /api/games/start` - Start a game session
- `POST /api/games/complete` - Complete a game session
- `GET /api/games/stats` - Get user game statistics
- `GET /api/games/sessions` - Get recent game sessions
- `GET /api/games/config` - Get game configuration

### Wallet
- `GET /api/wallet` - Get wallet information
- `PUT /api/wallet/ton-address` - Update TON address
- `GET /api/wallet/transactions` - Get transaction history
- `GET /api/wallet/stats` - Get wallet statistics
- `GET /api/wallet/bonuses` - Get available bonuses
- `POST /api/wallet/bonuses/claim` - Claim a bonus

### Leaderboard
- `GET /api/leaderboard` - Get leaderboard
- `GET /api/leaderboard/position` - Get user position
- `GET /api/leaderboard/global-stats` - Get global statistics
- `GET /api/leaderboard/top-players` - Get top players

### Admin (Protected)
- `POST /api/admin/credit-user` - Credit user wallet
- `POST /api/admin/create-bonus` - Create bonus for users
- `GET /api/admin/user-stats` - Get user statistics
- `GET /api/admin/system-stats` - Get system statistics
- `GET /api/admin/search-users` - Search users

## 🎮 Game Features

### Spin Wheel Game
- **Multi-layer Design**: 3 concentric rings with different prize pools
- **Dynamic Prizes**: Gems, diamonds, and TON rewards
- **Smooth Animations**: 4-second spin animation with easing
- **Score Calculation**: Points based on prize value and ring level
- **Real-time Sync**: Immediate balance updates after each spin

### Drop Game (Coming Soon)
- **Coin Catching**: Control basket to catch falling TON coins
- **Obstacle Avoidance**: Dodge obstacles to maintain lives
- **Progressive Difficulty**: Speed increases with level
- **Touch Controls**: Smooth touch-based basket movement
- **Score Multipliers**: Different difficulty levels with multipliers

## 💾 Database Schema

### Users Table
- `id`, `name`, `email`, `username`, `password`
- `phone`, `avatar`, `created_at`, `updated_at`

### Wallets Table
- `id`, `user_id`, `ton_address`
- `balance` (TON), `gems`, `diamonds`
- `created_at`, `updated_at`

### Scores Table
- `id`, `user_id`, `game_type`
- `score`, `total_score`, `games_played`
- `wins`, `losses`, `achievements` (JSON)
- `last_played_at`, `created_at`, `updated_at`

### Bonuses Table
- `id`, `user_id`, `type`, `title`, `description`
- `ton_amount`, `gems_amount`, `diamonds_amount`
- `is_claimed`, `claimed_at`, `expires_at`
- `metadata` (JSON), `created_at`, `updated_at`

### Game Sessions Table
- `id`, `user_id`, `game_type`, `score`
- `duration`, `game_data` (JSON)
- `ton_earned`, `gems_earned`, `diamonds_earned`
- `status`, `started_at`, `completed_at`
- `created_at`, `updated_at`

## 🔧 Configuration

### Laravel Configuration
- **CORS**: Configured for mobile app access
- **Sanctum**: API token authentication
- **Caching**: Redis/Memcached for leaderboards
- **Queue**: Background job processing
- **Logging**: Comprehensive error logging

### React Native Configuration
- **API Base URL**: Configurable server endpoint
- **Token Storage**: Secure AsyncStorage for auth tokens
- **Error Handling**: Global error interceptor
- **Offline Support**: Local data persistence
- **Animations**: Smooth 60fps animations

## 🚀 Performance Optimizations

### Backend
- **Database Indexing**: Optimized queries with proper indexes
- **Caching**: Redis caching for leaderboards and stats
- **Pagination**: Efficient data loading with pagination
- **Eager Loading**: Reduced N+1 queries
- **API Response**: Lightweight JSON responses

### Frontend
- **Lazy Loading**: Screen-based code splitting
- **Image Optimization**: Compressed assets
- **Animation Performance**: Native driver usage
- **Memory Management**: Proper cleanup and unmounting
- **Network Optimization**: Request batching and caching

## 🔒 Security Features

- **JWT Authentication**: Secure token-based auth
- **Input Validation**: Comprehensive request validation
- **SQL Injection Protection**: Eloquent ORM usage
- **XSS Protection**: Output sanitization
- **Rate Limiting**: API request throttling
- **CORS Protection**: Cross-origin request control

## 📱 Mobile Features

- **Responsive Design**: Works on all screen sizes
- **Touch Gestures**: Intuitive touch controls
- **Haptic Feedback**: Vibration feedback for interactions
- **Sound Effects**: Audio feedback for game events
- **Offline Mode**: Basic functionality without internet
- **Push Notifications**: Bonus and reward notifications

## 🎨 UI/UX Design

- **Modern Design**: Clean, minimalist interface
- **Inter Font**: Professional typography
- **Gradient Backgrounds**: Beautiful color schemes
- **Smooth Animations**: 60fps animations
- **Intuitive Navigation**: Easy-to-use navigation
- **Accessibility**: Screen reader support

## 🔄 Real-time Features

- **Live Leaderboards**: Real-time score updates
- **Instant Balance**: Immediate wallet updates
- **Live Notifications**: Real-time bonus alerts
- **WebSocket Support**: Real-time game updates
- **Auto-sync**: Background data synchronization

## 📊 Analytics & Monitoring

- **Game Statistics**: Comprehensive game analytics
- **User Behavior**: User interaction tracking
- **Performance Metrics**: App performance monitoring
- **Error Tracking**: Comprehensive error logging
- **Usage Analytics**: Feature usage statistics

## 🚀 Deployment

### Backend Deployment
1. Set up production server
2. Configure environment variables
3. Run database migrations
4. Set up SSL certificates
5. Configure web server (Nginx/Apache)
6. Set up monitoring and logging

### Frontend Deployment
1. Build production APK/IPA
2. Configure app signing
3. Upload to app stores
4. Set up CI/CD pipeline
5. Configure crash reporting
6. Set up analytics tracking

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## 🔮 Future Enhancements

- **Multiplayer Games**: Real-time multiplayer functionality
- **NFT Integration**: NFT rewards and marketplace
- **Social Features**: Friends, chat, and social sharing
- **Tournaments**: Competitive tournament system
- **Advanced Analytics**: Detailed user analytics
- **AI Integration**: Smart game recommendations
- **Blockchain Integration**: Direct TON blockchain integration
- **Cross-platform**: Web and desktop versions
