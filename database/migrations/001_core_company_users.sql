-- B2B Enterprise Portal - Çekirdek firma, adres, kullanıcı tabloları

CREATE TABLE IF NOT EXISTS `companies` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_code` VARCHAR(32) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `brand_name` VARCHAR(150) NULL,
    `tax_office` VARCHAR(100) NOT NULL,
    `tax_number` VARCHAR(20) NOT NULL UNIQUE,
    `mersis_no` VARCHAR(32) NULL,
    `tier_level` ENUM('bronze','silver','gold','platinum','vip') NOT NULL DEFAULT 'bronze',
    `price_list_id` BIGINT UNSIGNED NULL,
    `base_discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `credit_limit` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `current_balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `overdue_balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `payment_term_days` INT NOT NULL DEFAULT 0,
    `default_warehouse_id` BIGINT UNSIGNED NULL,
    `sales_rep_id` BIGINT UNSIGNED NULL,
    `status` ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
    `tax_plate_file` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(180) NULL,
    `city` VARCHAR(60) NULL,
    `district` VARCHAR(60) NULL,
    `address` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_companies_status` (`status`),
    KEY `idx_companies_tier` (`tier_level`),
    KEY `idx_companies_price_list` (`price_list_id`),
    KEY `idx_companies_sales_rep` (`sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_addresses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `address_type` ENUM('billing','shipping','both') NOT NULL DEFAULT 'shipping',
    `title` VARCHAR(100) NOT NULL,
    `contact_name` VARCHAR(120) NOT NULL,
    `contact_phone` VARCHAR(30) NOT NULL,
    `city` VARCHAR(60) NOT NULL,
    `district` VARCHAR(60) NOT NULL,
    `postal_code` VARCHAR(15) NULL,
    `full_address` TEXT NOT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_addresses_company` (`company_id`),
    CONSTRAINT `fk_addresses_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` BIGINT UNSIGNED NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(180) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin','sales_manager','finance_admin','company_owner','purchasing_manager','buyer','accountant') NOT NULL,
    `order_approval_limit` DECIMAL(15,4) NULL,
    `can_view_prices` TINYINT(1) NOT NULL DEFAULT 1,
    `can_view_finance` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_users_company` (`company_id`),
    KEY `idx_users_role` (`role`),
    CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(180) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_password_resets_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(180) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_login_attempts_email` (`email`),
    KEY `idx_login_attempts_ip` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
