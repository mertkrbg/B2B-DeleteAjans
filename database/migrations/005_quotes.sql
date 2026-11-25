-- Teklif talepleri (RFQ) ve kalemleri

CREATE TABLE IF NOT EXISTS `quotes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quote_number` VARCHAR(32) NOT NULL UNIQUE,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('requested','reviewing','offered','accepted','rejected','expired') NOT NULL DEFAULT 'requested',
    `valid_until` DATE NULL,
    `target_total` DECIMAL(15,4) NULL,
    `offered_total` DECIMAL(15,4) NULL,
    `customer_note` TEXT NULL,
    `admin_note` TEXT NULL,
    `converted_order_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_quotes_company` (`company_id`),
    KEY `idx_quotes_status` (`status`),
    CONSTRAINT `fk_quotes_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quotes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quotes_order` FOREIGN KEY (`converted_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quote_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `current_net_price` DECIMAL(15,4) NOT NULL,
    `requested_price` DECIMAL(15,4) NULL,
    `offered_price` DECIMAL(15,4) NULL,
    `offered_discount_rate` DECIMAL(5,2) NULL,
    KEY `idx_quote_items_quote` (`quote_id`),
    CONSTRAINT `fk_quote_items_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quote_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
