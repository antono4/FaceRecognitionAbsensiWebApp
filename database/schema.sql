-- ============================================================
--  SKEMA DATABASE: Sistem Absensi Online dengan Face Recognition
--  Database : MySQL / MariaDB
--  Charset  : utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_absensi
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE db_absensi;

-- ------------------------------------------------------------
-- Tabel users
-- Menyimpan akun admin & karyawan sekaligus face descriptor-nya
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama            VARCHAR(100) NOT NULL,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  role            ENUM('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  face_descriptor TEXT         NULL COMMENT 'String JSON berisi array 128 vektor wajah (face-api.js)',
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel absensi
-- Satu baris per karyawan per tanggal (jam_masuk & jam_pulang)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS absensi (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  tanggal     DATE NOT NULL,
  jam_masuk   TIME NULL,
  jam_pulang  TIME NULL,
  status      ENUM('hadir','terlambat') NOT NULL DEFAULT 'hadir',
  foto_bukti  VARCHAR(255) NULL COMMENT 'Nama file foto snapshot di folder uploads/',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_tanggal (user_id, tanggal),
  CONSTRAINT fk_absensi_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Akun admin default
-- username : admin
-- password : admin123  (ganti segera setelah login pertama)
-- ------------------------------------------------------------
INSERT INTO users (nama, username, password, role) VALUES
('Administrator', 'admin', '$2y$10$MooecCtLLaVoytH38XCNhOpzrbMCRbP8ppQILFxdWG8uPLUfdvDVu', 'admin')
ON DUPLICATE KEY UPDATE id = id;
