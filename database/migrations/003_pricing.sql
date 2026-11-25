-- Fiyat listeleri, kademeli iskonto ve döviz kurları

CREATE TABLE IF NOT EXISTS `price_lists` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(40) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `currency` ENUM('TRY','USD','EUR') NOT NULL DEFAULT 'TRY',
    `discount_modifier` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `valid_from` DATE NULL,
    `valid_until` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `price_list_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `price_list_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `special_price` DECIMAL(15,4) NOT NULL,
    `extra_discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY `uq_price_list_product` (`price_list_id`, `product_id`),
    CONSTRAINT `fk_pli_list` FOREIGN KEY (`price_list_id`) REFERENCES `price_lists` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pli_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_tier_prices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `min_qty` INT NOT NULL,
    `max_qty` INT NULL,
    `discount_rate` DECIMAL(5,2) NOT NULL,
    KEY `idx_tier_product` (`product_id`),
    CONSTRAINT `fk_tier_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `currency_rates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `currency_code` ENUM('USD','EUR') NOT NULL UNIQUE,
    `buying_rate` DECIMAL(10,4) NOT NULL,
    `selling_rate` DECIMAL(10,4) NOT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
