-- ============================================================
-- Schema Database: SIAKAD (Sistem Informasi Akademik)
-- Kompatibel dengan MySQL 5.0+ / MariaDB / MySQL 8.0
-- ============================================================

-- Opsional: Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `db_inquiry` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `db_inquiry`;

-- Hapus tabel lama jika ada (urutan: tabel anak dulu)
DROP TABLE IF EXISTS `nilai`;
DROP TABLE IF EXISTS `krs`;
DROP TABLE IF EXISTS `mata_kuliah`;
DROP TABLE IF EXISTS `mahasiswa`;
DROP TABLE IF EXISTS `dosen`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `feature_flags`;
DROP TABLE IF EXISTS `inquiries`;

-- ============================================================
-- 1. Tabel Users (Login & Autentikasi)
-- ============================================================
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'dosen', 'mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    `nama` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 2. Tabel Mahasiswa
-- ============================================================
CREATE TABLE `mahasiswa` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nim` VARCHAR(20) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `telepon` VARCHAR(20) DEFAULT NULL,
    `jurusan` VARCHAR(100) NOT NULL,
    `angkatan` INT(4) NOT NULL,
    `alamat` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nim` (`nim`),
    KEY `fk_mhs_user` (`user_id`),
    CONSTRAINT `fk_mhs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 3. Tabel Dosen
-- ============================================================
CREATE TABLE `dosen` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nip` VARCHAR(20) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `telepon` VARCHAR(20) DEFAULT NULL,
    `bidang_keahlian` VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nip` (`nip`),
    KEY `fk_dosen_user` (`user_id`),
    CONSTRAINT `fk_dosen_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 4. Tabel Mata Kuliah
-- ============================================================
CREATE TABLE `mata_kuliah` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `kode_mk` VARCHAR(10) NOT NULL,
    `nama_mk` VARCHAR(100) NOT NULL,
    `sks` INT(1) NOT NULL DEFAULT 3,
    `semester` INT(2) NOT NULL DEFAULT 1,
    `dosen_id` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kode_mk` (`kode_mk`),
    KEY `fk_mk_dosen` (`dosen_id`),
    CONSTRAINT `fk_mk_dosen` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 5. Tabel KRS (Kartu Rencana Studi)
-- ============================================================
CREATE TABLE `krs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `mahasiswa_id` INT(11) NOT NULL,
    `mata_kuliah_id` INT(11) NOT NULL,
    `semester` INT(2) NOT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_krs` (`mahasiswa_id`, `mata_kuliah_id`, `tahun_ajaran`),
    KEY `fk_krs_mhs` (`mahasiswa_id`),
    KEY `fk_krs_mk` (`mata_kuliah_id`),
    CONSTRAINT `fk_krs_mhs` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_krs_mk` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 6. Tabel Nilai
-- ============================================================
CREATE TABLE `nilai` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `krs_id` INT(11) NOT NULL,
    `tugas` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `uts` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `uas` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `nilai_akhir` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `grade` VARCHAR(2) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nilai_krs` (`krs_id`),
    CONSTRAINT `fk_nilai_krs` FOREIGN KEY (`krs_id`) REFERENCES `krs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 7. Tabel Feature Flags
-- ============================================================
CREATE TABLE `feature_flags` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `flag_name` VARCHAR(50) NOT NULL,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `description` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_flag_name` (`flag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- ============================================================
-- SEEDER DATA
-- ============================================================

-- Users (password di-hash dengan MD5 untuk kompatibilitas PHP 5)
-- admin123 = 0192023a7bbd73250516f069df18b500
INSERT INTO `users` (`username`, `password_hash`, `role`, `nama`, `created_at`) VALUES
('admin',   MD5('admin123'),  'admin',      'Administrator',     NOW()),
('dosen1',  MD5('dosen123'),  'dosen',      'Dr. Budi Hartono',  NOW()),
('dosen2',  MD5('dosen123'),  'dosen',      'Prof. Siti Aminah', NOW()),
('mhs1',    MD5('mhs123'),    'mahasiswa',  'Andi Pratama',      NOW()),
('mhs2',    MD5('mhs123'),    'mahasiswa',  'Rina Wulandari',    NOW()),
('mhs3',    MD5('mhs123'),    'mahasiswa',  'Fajar Nugroho',     NOW());

-- Dosen
INSERT INTO `dosen` (`nip`, `user_id`, `nama`, `email`, `telepon`, `bidang_keahlian`) VALUES
('198501012010011001', 2, 'Dr. Budi Hartono',  'budi.hartono@univ.ac.id',  '081234567001', 'Rekayasa Perangkat Lunak'),
('197802152005012001', 3, 'Prof. Siti Aminah', 'siti.aminah@univ.ac.id',   '081234567002', 'Basis Data & Sistem Informasi');

-- Mahasiswa
INSERT INTO `mahasiswa` (`nim`, `user_id`, `nama`, `email`, `telepon`, `jurusan`, `angkatan`, `alamat`) VALUES
('2024001001', 4, 'Andi Pratama',    'andi.pratama@mail.com',    '081234567101', 'Teknik Informatika', 2024, 'Jl. Merdeka No. 10, Jakarta'),
('2024001002', 5, 'Rina Wulandari',  'rina.wulandari@mail.com',  '081234567102', 'Teknik Informatika', 2024, 'Jl. Sudirman No. 20, Bandung'),
('2023001003', 6, 'Fajar Nugroho',   'fajar.nugroho@mail.com',   '081234567103', 'Sistem Informasi',   2023, 'Jl. Ahmad Yani No. 5, Surabaya');

-- Mata Kuliah
INSERT INTO `mata_kuliah` (`kode_mk`, `nama_mk`, `sks`, `semester`, `dosen_id`) VALUES
('IF101', 'Algoritma & Pemrograman',     3, 1, 1),
('IF102', 'Basis Data',                   3, 2, 2),
('IF201', 'Struktur Data',                3, 3, 1),
('IF202', 'Pemrograman Web',              3, 4, 1),
('SI301', 'Sistem Informasi Manajemen',   3, 5, 2);

-- KRS (Contoh: Semester Ganjil 2024/2025)
INSERT INTO `krs` (`mahasiswa_id`, `mata_kuliah_id`, `semester`, `tahun_ajaran`, `status`, `created_at`) VALUES
(1, 1, 1, '2024/2025 Ganjil', 'approved', NOW()),
(1, 2, 1, '2024/2025 Ganjil', 'approved', NOW()),
(2, 1, 1, '2024/2025 Ganjil', 'approved', NOW()),
(2, 2, 1, '2024/2025 Ganjil', 'approved', NOW()),
(3, 3, 3, '2024/2025 Ganjil', 'approved', NOW()),
(3, 5, 3, '2024/2025 Ganjil', 'pending',  NOW());

-- Nilai (Contoh untuk beberapa KRS yang approved)
INSERT INTO `nilai` (`krs_id`, `tugas`, `uts`, `uas`, `nilai_akhir`, `grade`, `created_at`) VALUES
(1, 85.00, 80.00, 90.00, 85.50, 'A', NOW()),
(2, 70.00, 75.00, 80.00, 75.50, 'B', NOW()),
(3, 90.00, 85.00, 88.00, 87.70, 'A', NOW());

-- Feature Flags (Default)
INSERT INTO `feature_flags` (`flag_name`, `is_enabled`, `description`) VALUES
('krs_registration',    1, 'Mengaktifkan/menonaktifkan pendaftaran KRS oleh mahasiswa'),
('grade_input',         1, 'Mengaktifkan/menonaktifkan input nilai oleh dosen'),
('student_registration', 1, 'Mengaktifkan/menonaktifkan pendaftaran mahasiswa baru'),
('show_transcript',     1, 'Mengaktifkan/menonaktifkan akses transkrip oleh mahasiswa');
