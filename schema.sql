-- ============================================================
-- Schema Database: db_inquiry
-- Kompatibel dengan MySQL 5.0+ / MariaDB
-- ============================================================

-- Opsional: Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `db_inquiry` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `db_inquiry`;

-- Buat tabel inquiries
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `telepon` VARCHAR(20) NOT NULL,
    `pesan` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
