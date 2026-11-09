# LIA247 On-Demand Multi-Service Super App Backend API

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Stripe Connect](https://img.shields.io/badge/Payments-Stripe_Connect-635BFF?style=for-the-badge&logo=stripe)](https://stripe.com)
[![WebSockets](https://img.shields.io/badge/Real--Time-Laravel_Reverb-FF2D20?style=for-the-badge)](https://laravel.com/docs/reverb)
[![Twilio](https://img.shields.io/badge/Twilio-SMS_&_OTP-F22F46?style=for-the-badge&logo=twilio)](https://twilio.com)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg?style=for-the-badge)](#license)

The central powerhouse backend API for **LIA247**, an all-in-one on-demand multi-service platform combining **Food Ordering & Restaurant Delivery**, **Ride-Hailing & Logistics Dispatch**, and **Multi-Vendor Merchant Management**. It provides tailored API ecosystems for Customers, Vendors, Riders, and Admins.

---

## Multi-Persona Architecture

1. **Customer Mobile Application**:
   - Nearby restaurants and stores discovery with location radius filtering.
   - Food ordering with customizable variants and toppings.
   - Ride-hailing booking with transport types (Economy, Comfort, Delivery).
   - In-app digital wallet top-up, discounts, and voucher redemptions.
   - Live order / trip tracking and in-app chat with rider and vendor.
2. **Vendor / Restaurant Web & Mobile Portal**:
   - Menu engineering (categories, items, modifier groups, and toppings).
   - Real-time incoming order audio alerts and order dispatch management.
   - Revenue analytics, transaction history, and Stripe Connect payouts.
3. **Rider / Driver Mobile Application**:
   - Online/Offline status switch with real-time GPS geolocation broadcasting.
   - Instant trip and delivery dispatch requests with accept/reject timers.
   - Trip navigation, earnings wallet, and direct withdrawal payouts.
4. **Administrative Back-Office**:
   - Moderation of businesses, drivers, commission rates, and dispute resolution.

---

## Key Features

- **Real-Time Communication & WebSockets**:
  - Powered by Laravel Reverb and Redis (`predis/predis`).
  - Order participant tracking (`Customer <-> Vendor <-> Rider`).
  - Live chat channels and support tickets.
- **Financial Architecture & Stripe Connect**:
  - Multi-party payment splitting and merchant onboarding via Stripe Connect.
  - Driver & vendor digital wallet (`Wallet`, `WalletTransaction`) with automated payout processing.
- **Geolocation & Matching Engine**:
  - Distance calculation and automatic nearest-driver dispatch.
  - Live vehicle tracking and delivery status transitions.
- **Push & SMS Notifications**:
  - Twilio SMS for instant OTP verification and dispatch alerts.
  - Firebase Cloud Messaging (FCM) for background order notifications.

---

## Tech Stack

- **Framework**: Laravel 11 / 12 (PHP 8.2+)
- **Payments**: Stripe Connect & Custom Payouts (`stripe/stripe-php`)
- **Real-Time**: Laravel Reverb & Pusher Protocol
- **SMS & Communications**: Twilio PHP SDK
- **Mailing**: Brevo (Sendinblue) HTTP API (`symfony/brevo-mailer`)
- **Push Notifications**: Firebase Cloud Messaging (`kreait/laravel-firebase`)
- **Authentication**: JWT Auth (`php-open-source-saver/jwt-auth`), Laravel Sanctum
- **Database & Cache**: MySQL 8.x & Redis
- **Data Moderation**: Spatie Laravel Permissions & Yajra DataTables

---

## Directory Structure

```plaintext
lia247_backend/
├── app/
│   ├── Enums/                          # OrderStatus, RideStatus, TransportType
│   ├── Http/
│   │   ├── Controllers/API/V1/
│   │   │   ├── Customer/               # Restaurants, Carts, Orders, Rides, Chat
│   │   │   ├── Vendor/                 # Menu, Categories, Orders, Wallet
│   │   │   ├── Rider/                  # Dispatch, Navigation, Deliveries, Earnings
│   │   │   └── Webhook/                # Stripe webhook event listeners
│   │   ├── Middleware/                 # Role checks, JWT verification
│   │   └── Requests/                   # Validation schemas for orders and rides
│   ├── Models/                         # Order, Ride, Item, Business, Wallet, User...
│   ├── Notifications/                  # Firebase FCM and Brevo mailers
│   └── Services/                       # Stripe Connect, Wallet Engine, Distance Service
├── config/                             # App configurations
├── database/
│   ├── migrations/                     # Schema definitions
│   └── seeders/                        # Transport categories, initial admin users
├── routes/
│   ├── api.php                         # Master API router
│   ├── customer/api.php                # Customer mobile endpoints
│   ├── vendor/api.php                  # Vendor web/mobile endpoints
│   ├── rider/api.php                   # Rider mobile endpoints
│   └── channels.php                    # Reverb WebSocket channels
└── tests/                              # Automated feature test suites
```

---

## Getting Started

### Prerequisites

- PHP >= 8.2
- Composer 2.x
- MySQL 8.x
- Redis server
- Stripe & Twilio credentials

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/mainuromayer/lia247_backend.git
   cd lia247_backend
   ```

2. **Install Composer packages**:
   ```bash
   composer install
   ```

3. **Setup Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Update `.env`**:
   ```env
   APP_NAME="LIA247"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lia247_backend
   DB_USERNAME=root
   DB_PASSWORD=

   # Stripe Connect Credentials
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...

   # Reverb WebSocket
   REVERB_APP_ID=
   REVERB_APP_KEY=
   REVERB_APP_SECRET=
   REVERB_HOST="localhost"
   REVERB_PORT=8080
   REVERB_SCHEME="http"
   ```

5. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

6. **Start Application & Reverb Server**:
   ```bash
   php artisan serve
   php artisan reverb:start
   ```

---

## Core API Endpoints

| Method | Endpoint | Persona | Description |
|:---|:---|:---:|:---|
| `POST` | `/api/v1/customer/auth/login` | Customer | Mobile customer login |
| `GET` | `/api/v1/customer/restaurants` | Customer | Fetch nearby restaurants by latitude/longitude |
| `POST` | `/api/v1/customer/order/create` | Customer | Place multi-item food delivery order |
| `POST` | `/api/v1/customer/ride/request` | Customer | Request ride with pickup & dropoff coordinates |
| `GET` | `/api/v1/vendor/orders` | Vendor | Real-time incoming kitchen orders |
| `POST` | `/api/v1/vendor/menu/item` | Vendor | Create item with topping groups & price variants |
| `POST` | `/api/v1/vendor/wallet/withdraw` | Vendor | Request withdrawal to connected Stripe bank account |
| `POST` | `/api/v1/rider/status/toggle` | Rider | Toggle online/offline availability |
| `POST` | `/api/v1/rider/order/{id}/accept` | Rider | Accept delivery dispatch request |

---

## Author & Maintainer

- **Mainur Rahaman** ([@mainuromayer](https://github.com/mainuromayer))
- Email: [mainuromayer@gmail.com](mailto:mainuromayer@gmail.com)

---

## License

This project is proprietary and confidential. All rights reserved.
