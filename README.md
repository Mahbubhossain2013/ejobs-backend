# eJobs Backend

**Version:** 2.0.0  
**Stack:** Laravel 12, MySQL 8.0, PHP 8.3  
**Admin Panel:** Laravel Filament v3.3  
**Auth:** Laravel Sanctum  
**Realtime:** Laravel Reverb (WebSocket)

## Project Overview

eJobs is a hybrid job portal & freelance marketplace built for the Bangladesh market. It combines traditional job board functionality with an escrow-backed freelance marketplace.

- **Candidate System:** Public portfolio, smart CV builder, AI job matching, wallet, real-time notifications
- **Employer System:** Wallet funding, escrow projects, applicant management, job advertising
- **Admin System:** Filament-based control panel for user management, financial oversight, AI configuration

## Requirements

- PHP >= 8.2
- Composer >= 2.0
- MySQL >= 8.0
- Node.js >= 22.13 (for frontend build)

## Installation

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --force

# Storage link
php artisan storage:link

# Cache optimization (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Environment Variables

### Required

| Variable | Description |
|----------|-------------|
| `APP_URL` | Backend URL (e.g., `https://admin.ejobs.bd`) |
| `FRONTEND_URL` | Frontend URL (e.g., `https://ejobs.bd`) |
| `DB_*` | Database connection |
| `MAIL_*` | Mail configuration |

### Payment Gateways

| Variable | Description |
|----------|-------------|
| `BKASH_*` | bKash merchant credentials |
| `SSLCZ_*` | SSLCommerz credentials |
| `EPS_*` | EPS payment gateway |
| `NAGAD_*` | Nagad merchant credentials |
| `ROCKET_*` | Rocket merchant credentials |

### Social Auth

| Variable | Description |
|----------|-------------|
| `GOOGLE_CLIENT_*` | Google OAuth |
| `FACEBOOK_CLIENT_*` | Facebook OAuth |

## Queue Worker

```bash
php artisan queue:work --tries=3 --timeout=90
```

## Reverb Server

```bash
php artisan reverb:start
```

## API Documentation

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - Login
- `POST /api/logout` - Logout ( Sanctum )

### Wallet
- `GET /api/candidate/wallet` - Get wallet details
- `POST /api/candidate/deposit` - Create deposit
- `POST /api/candidate/withdraw` - Create withdrawal
- `GET /api/candidate/transactions` - Transaction history

### Jobs
- `GET /api/jobs` - List jobs
- `POST /api/jobs` - Create job (employer)
- `GET /api/jobs/{id}` - Job detail
- `POST /api/jobs/{id}/apply` - Apply for job

### CV Builder
- `GET /api/cv/profile` - Get CV profile
- `POST /api/cv/profile/update` - Update CV profile
- `POST /api/cv/profile/upload-photo` - Upload photo

## Key Features

- **Payment Gateways:** bKash, Nagad, Rocket, SSLCommerz, EPS, OniPay
- **Escrow System:** Secure payment holding for freelance projects
- **AI Integration:** Job matching, career assistant, CV enhancement
- **Real-time:** Chat, notifications via Laravel Reverb
- **Admin Panel:** Filament v3 at `/admin`
- **Image Processing:** Automatic WebP conversion for all uploads

## License

Proprietary - [Nextin BD](https://nextinbd.com)
