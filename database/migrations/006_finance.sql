-- Cari hesap hareketleri ve ödeme/dekont bildirimleri

CREATE TABLE IF NOT EXISTS `current_account_transactions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `transaction_no` VARCHAR(32) NOT NULL UNIQUE,
    `type` ENUM('invoice_debit','payment_credit','credit_note','opening_balance','cheque_receipt') NOT NULL,
    `reference_type` ENUM('order','payment','manual') NOT NULL DEFAULT 'manual',
    `reference_id` BIGINT UNSIGNED NULL,
    `description` VARCHAR(255) NOT NULL,
    `debit_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `credit_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `running_balance` DECIMAL(15,4) NOT NULL,
    `due_date` DATE NULL,
    `receipt_file` VARCHAR(255) NULL,
    `status` ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'confirmed',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cat_company_created` (`company_id`, `created_at`),
    KEY `idx_cat_due_status` (`due_date`, `status`),
    CONSTRAINT `fk_cat_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cat_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `payment_method` ENUM('bank_transfer','credit_card','cheque','other') NOT NULL DEFAULT 'bank_transfer',
    `bank_name` VARCHAR(120) NULL,
    `reference_no` VARCHAR(100) NULL,
    `receipt_file` VARCHAR(255) NULL,
    `note` TEXT NULL,
    `status` ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by` BIGINT UNSIGNED NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_payment_notif_company` (`company_id`),
    KEY `idx_payment_notif_status` (`status`),
    CONSTRAINT `fk_payment_notif_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_notif_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_notif_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
