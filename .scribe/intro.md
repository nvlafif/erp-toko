# ERP Toko/Minimarket API Documentation

## Introduction

Selamat datang di dokumentasi API ERP Toko/Minimarket. Sistem ini dirancang untuk mengelola operasional toko retail dan minimarket dengan fitur lengkap mulai dari manajemen inventaris, transaksi penjualan, hingga pelaporan keuangan.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

### Fitur Utama

- **Autentikasi & Manajemen User**: Login berbasis role (Owner, Admin Gudang, Kasir)
- **Master Data**: Kategori, Supplier, Unit, dan Produk dengan barcode
- **Transaksi Penjualan**: Proses checkout dengan validasi stok dan pembayaran
- **Hold Transaksi**: Tahan transaksi tanpa mengurangi stok, bisa checkout nanti
- **Retur Barang**: Proses retur dengan restorasi stok otomatis
- **Inventory Management**: Track pergerakan stok, adjustment stok manual
- **Operational Costs**: Catat biaya operasional (sewa, listrik, dll)
- **Activity Logs**: Log semua aktivitas penting untuk audit trail
- **Notifikasi & Alerts**: Alert stok rendah, notifikasi in-app
- **Laporan Keuangan**: Ringkasan penjualan, dashboard, laporan keuangan

### Authentication

API menggunakan **Laravel Sanctum** untuk autentikasi. Setiap request yang memerlukan autentikasi harus include header:
```
Authorization: Bearer {token}
```

Token didapat dari endpoint `/api/auth/login` setelah berhasil login.

### Role-Based Access Control

Akses endpoint diatur berdasarkan role user:
- **Owner**: Akses penuh, bisa create/edit/delete master data dan laporan
- **Admin Gudang**: Bisa manage produk dan stok
- **Kasir**: Bisa lakukan transaksi penjualan dan retur

### Response Format

Semua response mengikuti format JSON konsisten:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* resource data */ },
  "meta": { /* pagination info jika applicable */ }
}
```

### Error Handling

Jika terjadi error, response akan terlihat seperti:

```json
{
  "success": false,
  "message": "Error message here",
  "errors": { /* validation errors jika ada */ }
}
```

HTTP Status Codes:
- `200 OK`: Request berhasil
- `201 Created`: Resource berhasil dibuat
- `400 Bad Request`: Input tidak valid
- `401 Unauthorized`: Tidak terautentikasi
- `403 Forbidden`: Tidak punya akses
- `404 Not Found`: Resource tidak ditemukan
- `500 Internal Server Error`: Server error

<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

