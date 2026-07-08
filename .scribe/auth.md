# Authenticating requests

API ini menggunakan **Bearer Token Authentication** berbasis Laravel Sanctum.

## Login

Untuk mendapatkan token, gunakan endpoint `/api/auth/login` dengan kredensial user:

```json
{
  "username": "admin",
  "password": "password"
}
```

Response akan berisi token:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { /* user data */ },
    "token": "1|abcdef123456..."
  }
}
```

## Menggunakan Token

Setiap request yang memerlukan autentikasi harus include header:

```
Authorization: Bearer {token}
```

Contoh menggunakan curl:

```bash
curl -H "Authorization: Bearer 1|abcdef123456..." \
     http://localhost/api/auth/me
```

## Logout

Untuk logout dan invalidate token, gunakan endpoint `/api/auth/logout` dengan method POST dan token yang sama.
