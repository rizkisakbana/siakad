-- Opsional: redaksi data sensitif NeoFeeder yang sudah terlanjur masuk database.
-- PERINGATAN:
-- - Jalankan hanya setelah mencatat ulang kredensial resmi di tempat aman.
-- - Setelah script ini dijalankan, isi ulang password NeoFeeder lewat halaman pengaturan.
-- - Token akan dibuat ulang saat test koneksi berikutnya.

UPDATE `neofeeder_config`
SET
  `password` = '',
  `token` = NULL,
  `status` = 'disconnected';

UPDATE `neofeeder_log`
SET
  `request_payload` = REGEXP_REPLACE(
    REGEXP_REPLACE(
      REGEXP_REPLACE(`request_payload`, '("password"\\s*:\\s*")[^"]+(")', '\\1***MASKED***\\2'),
      '("token"\\s*:\\s*")[^"]+(")', '\\1***MASKED***\\2'
    ),
    '[A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,}',
    '***EMAIL_MASKED***'
  ),
  `response_payload` = REGEXP_REPLACE(
    REGEXP_REPLACE(
      REGEXP_REPLACE(`response_payload`, '("password"\\s*:\\s*")[^"]+(")', '\\1***MASKED***\\2'),
      '("token"\\s*:\\s*")[^"]+(")', '\\1***MASKED***\\2'
    ),
    '[A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,}',
    '***EMAIL_MASKED***'
  );
