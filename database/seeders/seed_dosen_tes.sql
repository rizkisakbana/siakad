START TRANSACTION;

SET @role_dosen := (
  SELECT id_role
  FROM roles
  WHERE nama_role = 'dosen'
  LIMIT 1
);

SET @password_hash := '$2y$10$kEHcC6xGxPnJnYbb.6057ubOVCLyut2G03K3yTrxaPnDT6blMZOUu';

INSERT INTO users
  (id_role, username, password, nama_lengkap, email, no_hp, status)
SELECT
  @role_dosen,
  'dosen_tes',
  @password_hash,
  'BAYU DWI FEBRIANTO',
  'dosen.tes@atitb.ac.id',
  '080000000005',
  'aktif'
WHERE NOT EXISTS (
  SELECT 1
  FROM users
  WHERE username = 'dosen_tes'
);

SET @id_user_dosen := (
  SELECT id_user
  FROM users
  WHERE username = 'dosen_tes'
  LIMIT 1
);

UPDATE users
SET
  id_role = @role_dosen,
  password = @password_hash,
  nama_lengkap = 'BAYU DWI FEBRIANTO',
  email = 'dosen.tes@atitb.ac.id',
  no_hp = '080000000005',
  status = 'aktif'
WHERE id_user = @id_user_dosen;

UPDATE dosen
SET
  id_user = @id_user_dosen,
  email = 'dosen.tes@atitb.ac.id',
  no_hp = '080000000005',
  status = 'aktif'
WHERE id_dosen = 3;

COMMIT;
