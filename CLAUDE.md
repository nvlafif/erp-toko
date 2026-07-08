# CLAUDE.md — Panduan AI Agent untuk Project Web ERP Toko/Minimarket

> Dokumen ini adalah panduan kerja utama untuk AI agent dan pengembang yang akan melanjutkan project ERP toko/minimarket ini. Dokumen ini disesuaikan dengan progres backend yang saat ini sudah ada di repository.

---

## 1. Ringkasan Project

Project ini adalah aplikasi backend Laravel 11 untuk sistem ERP toko/minimarket dengan fokus utama:
- autentikasi pengguna,
- master data barang,
- transaksi penjualan,
- hold transaksi,
- retur barang,
- kontrol akses berdasarkan role.

Target saat ini adalah mematangkan backend terlebih dahulu sebelum frontend dibangun.

### Status implementasi saat ini
- Backend Laravel 11 sudah aktif.
- API autentikasi dengan Sanctum sudah tersedia.
- Master data kategori, supplier, unit, dan produk sudah ada.
- Proses transaksi penjualan sudah dibuat dengan service layer dan transaction database.
- Fitur hold transaksi dan checkout dari hold transaction sudah tersedia.
- Fitur retur barang sudah tersedia.
- Middleware role sudah diterapkan untuk membatasi akses endpoint.

### Yang belum selesai / prioritas berikutnya
- integrasi log aktivitas yang konsisten,
- modul biaya operasional,
- modul approval akun,
- notifikasi,
- laporan keuangan,
- testing otomatis,
- dokumentasi API yang lebih lengkap.

---

## 2. Arsitektur Backend Saat Ini

### Struktur folder utama
- app/Http/Controllers/Api untuk endpoint API
- app/Http/Requests untuk validasi request
- app/Http/Resources untuk format response JSON
- app/Models untuk entity/domain model
- app/Services untuk logika bisnis kompleks
- routes/api.php untuk definisi endpoint
- database/migrations untuk skema database

### Konvensi yang sudah dipakai
- Controller dibuat tipis dan fokus pada request/response.
- Validasi input menggunakan Form Request.
- Logika bisnis yang kompleks ditempatkan di service class.
- Operasi stok dan transaksi dibungkus dalam database transaction.
- Response JSON disusun secara konsisten dengan field success, message, data, dan meta.

### Implementasi yang sudah ada
- AuthController: login, register, me, logout
- CategoryController: CRUD master kategori
- SupplierController: CRUD master supplier
- UnitController: CRUD master unit
- ProductController: CRUD produk dengan filter dan pagination
- TransactionController: daftar, buat, detail transaksi
- HoldTransactionController: simpan, tampilkan, checkout, hapus hold transaksi
- ProductReturnController: daftar, buat, detail retur

---

## 3. Endpoint API Saat Ini

Route API saat ini menggunakan auth Sanctum dan middleware role.

### Endpoint publik
- POST /api/auth/login
- POST /api/auth/register (owner only)

### Endpoint terproteksi
- GET /api/auth/me
- POST /api/auth/logout

### Master data
- GET /api/categories
- POST /api/categories (owner/admin_gudang)
- GET /api/categories/{category}
- PUT /api/categories/{category}
- DELETE /api/categories/{category}

- GET /api/suppliers
- POST /api/suppliers (owner/admin_gudang)
- GET /api/suppliers/{supplier}
- PUT /api/suppliers/{supplier}
- DELETE /api/suppliers/{supplier}

- GET /api/units
- POST /api/units (owner/admin_gudang)
- GET /api/units/{unit}
- PUT /api/units/{unit}
- DELETE /api/units/{unit}

- GET /api/products
- POST /api/products (owner/admin_gudang)
- GET /api/products/{product}
- PUT /api/products/{product}
- DELETE /api/products/{product}

### Transaksi
- GET /api/transactions
- POST /api/transactions
- GET /api/transactions/{transaction}

### Hold transaksi
- GET /api/hold-transactions
- POST /api/hold-transactions
- GET /api/hold-transactions/{holdTransaction}
- POST /api/hold-transactions/{holdTransaction}/checkout
- DELETE /api/hold-transactions/{holdTransaction}

### Retur
- GET /api/returns
- POST /api/returns
- GET /api/returns/{productReturn}

---

## 4. Model dan Database yang Saat Ini Digunakan

### Model yang sudah ada
- User
- Category
- Supplier
- Unit
- Product
- Transaction
- TransactionDetail
- HoldTransaction
- HoldTransactionDetail
- ProductReturn
- ProductReturnDetail
- OperatingCost
- ActivityLog

### Skema utama yang sudah ada
- users
- categories
- suppliers
- units
- products
- transactions
- transaction_details
- returns
- return_details
- operating_costs
- activity_logs
- hold_transactions
- hold_transaction_details

### Kolom utama produk yang saat ini dipakai
- barcode
- product_name
- category_id
- supplier_id
- stock
- expired_date
- unit_id
- purchase_price
- selling_price
- is_active

### Kolom utama transaksi yang saat ini dipakai
- user_id
- transaction_date
- total_payment
- customer_money
- change_money

---

## 5. Aturan Bisnis yang Sudah Diimplementasikan

### Produk
- produk bisa dicari berdasarkan nama atau barcode,
- filter berdasarkan kategori, supplier, unit, status stok, dan expired date,
- produk bisa dinonaktifkan tanpa langsung dihapus,
- validasi unique barcode diterapkan.

### Transaksi penjualan
- transaksi diproses dalam satu database transaction,
- stok dicek sebelum transaksi diproses,
- stok dikurangi saat transaksi berhasil,
- customer money wajib cukup untuk membayar total,
- detail transaksi disimpan sebagai snapshot harga jual.

### Hold transaksi
- transaksi bisa ditahan tanpa mengurangi stok,
- saat checkout, sistem membuat transaksi baru dari item hold dan menghapus data hold.

### Retur barang
- retur hanya boleh dilakukan untuk item yang ada di transaksi terkait,
- retur menghitung sisa kuantitas yang belum dikembalikan,
- saat retur berhasil, stok produk bertambah kembali.

---

## 6. Prinsip Pengembangan yang Wajib Dipakai

1. Selalu baca dokumen ini sebelum mengubah backend.
2. Jaga konsistensi endpoint dan format response JSON.
3. Gunakan Form Request untuk semua validasi input.
4. Tempatkan logika bisnis kompleks di service class.
5. Gunakan database transaction untuk operasi yang memengaruhi stok atau saldo.
6. Jangan mengubah role-based access secara sembarangan.
7. Selalu pertimbangkan dampak terhadap stok saat menambah fitur baru.
8. Setelah mengubah fitur bisnis, tambahkan test yang relevan.

---

## 7. Prioritas Backend Selanjutnya

### Prioritas 1 — Penguatan fondasi backend
- tambah test untuk alur transaksi, hold transaksi, retur barang,
- pastikan response error konsisten di semua controller,
- tambahkan dokumentasi endpoint.

### Prioritas 2 — Modul bisnis yang belum ada
- modul biaya operasional,
- modul approval akun,
- modul log aktivitas,
- modul notifikasi,
- modul laporan keuangan.

### Prioritas 3 — Kualitas operasional
- history stock movement,
- audit trail perubahan data master,
- pagination/filtering yang lebih kaya,
- soft delete atau status yang lebih jelas untuk data sensitif.

---

## 8. Rekomendasi implementasi berikutnya

Urutan kerja yang paling masuk akal untuk project ini adalah:
1. tambah test backend untuk skenario inti,
2. perkuat logging dan audit,
3. implementasikan modul biaya operasional,
4. implementasikan approval akun dan status user,
5. tambahkan notifikasi dan laporan,
6. baru setelah itu siap untuk frontend.

---

## 9. Catatan penting untuk AI agent

Jika Anda menambahkan fitur baru, pastikan:
- endpoint ada di routes/api.php,
- controller hanya menangani request dan response,
- service menangani logika bisnis,
- request class menangani validasi,
- model dan migration sesuai dengan kebutuhan,
- perubahan yang memengaruhi stok atau transaksi selalu aman secara atomic.

Dokumen ini harus terus diperbarui setiap kali ada perubahan desain atau implementasi utama.
