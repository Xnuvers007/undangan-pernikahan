CREATE DATABASE IF NOT EXISTS db_undangan_minang
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_undangan_minang;

CREATE TABLE IF NOT EXISTS rsvp_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guest_name VARCHAR(120) NOT NULL,
    attendance_status ENUM('Hadir', 'Tidak Hadir', 'Masih Ragu') NOT NULL,
    message TEXT NOT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rsvp_created_at (created_at),
    INDEX idx_rsvp_attendance (attendance_status),
    INDEX idx_rsvp_approved_created (is_approved, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guest_slug VARCHAR(120) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visits_guest (guest_slug),
    INDEX idx_visits_time (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;