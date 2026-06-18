# Ordering — Login (React + Tailwind + Laravel + MySQL)

A decoupled login system:

- **backend/** — Laravel 12 API with Sanctum token auth, MySQL (`ordering` database)
- **frontend/** — React 19 + Vite + Tailwind v4 SPA (login, register, protected dashboard)

## Prerequisites

- XAMPP running **MySQL/MariaDB** (Apache not required — Laravel uses its own dev server)
- PHP 8.2+, Composer, Node 20+

## Backend (port 8000)

```bash
cd backend
php artisan serve --port=8000
```

The `ordering` database and a test user are already created. To recreate them:

```bash
php artisan migrate:fresh --seed
```

Seeded login: **test@example.com** / **password**

### API endpoints

| Method | Path           | Auth     | Purpose                  |
| ------ | -------------- | -------- | ------------------------ |
| POST   | /api/register  | —        | Create user, return token|
| POST   | /api/login     | —        | Authenticate, return token|
| GET    | /api/user      | Bearer   | Current user             |
| POST   | /api/logout    | Bearer   | Revoke current token     |

## Frontend (port 5173)

```bash
cd frontend
npm run dev
```

Open http://localhost:5173 — you'll land on the login page. The API base URL is set in
`frontend/.env` (`VITE_API_URL`).

## How auth works

The React app posts credentials to the Laravel API, stores the returned Sanctum token in
`localStorage`, and attaches it as a `Bearer` token on every request (see
`frontend/src/lib/api.js`). CORS is restricted to the frontend origin in `backend/config/cors.php`.
