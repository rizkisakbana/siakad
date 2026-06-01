# Database

Folder ini dipakai untuk menyimpan migration resmi SIAKAD ATITB.

## Urutan Tahap 1

1. Backup database berjalan.
2. Pastikan kredensial NeoFeeder sudah dipindahkan ke `.env` atau disimpan ulang lewat halaman pengaturan.
3. Jalankan migration:

```sql
SOURCE database/migrations/001_neofeeder_foundation.sql;
```

## Catatan Keamanan

- Jangan commit file `.env`.
- Jangan commit dump produksi yang berisi token, password, NIK, email, nomor HP, atau payload NeoFeeder mentah.
- Gunakan `neofeeder_log` untuk audit teknis, dan `sync_queue` untuk proses batch/retry.
