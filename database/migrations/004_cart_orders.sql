-- Sepet, sipariş, sipariş kalemleri ve durum geçmişi

CREATE TABLE IF NOT EXISTS `carts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `cart_name` VARCHAR(150) NOT NULL DEFAULT 'Aktif Sepet',
    `is_template` TINYINT(1) NOT NULL DEFAULT 0,
    `po_number` VARCHAR(64) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_carts_company_user` (`company_id`, `user_id`),
    CONSTRAINT `fk_carts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cart_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `warehouse_id` BIGINT UNSIGNED NULL,
    `quantity` INT NOT NULL,
    `unit_list_price` DECIMAL(15,4) NOT NULL,
    `applied_discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `unit_net_price` DECIMAL(15,4) NOT NULL,
    `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `line_total_net` DECIMAL(15,4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cart_items_cart` (`cart_id`),
    CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `fk_cart_items_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(32) NOT NULL UNIQUE,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `created_by_user_id` BIGINT UNSIGNED NOT NULL,
    `approved_by_user_id` BIGINT UNSIGNED NULL,
    `shipping_address_id` BIGINT UNSIGNED NOT NULL,
    `billing_address_id` BIGINT UNSIGNED NOT NULL,
    `customer_po_number` VARCHAR(64) NULL,
    `payment_method` ENUM('open_account','bank_transfer','credit_card','cheque') NOT NULL,
    `payment_term_days` INT NOT NULL DEFAULT 0,
    `status` ENUM('pending_company_approval','pending_admin_approval','approved','preparing','partially_shipped','shipped','delivered','cancelled','rejected') NOT NULL DEFAULT 'pending_company_approval',
    `subtotal_list` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `total_discount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `subtotal_net` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `shipping_cost` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `total_vat` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `grand_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `cargo_company` VARCHAR(100) NULL,
    `tracking_number` VARCHAR(100) NULL,
    `invoice_number` VARCHAR(64) NULL,
    `customer_note` TEXT NULL,
    `admin_note` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_orders_company_created` (`company_id`, `created_at`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_number` (`order_number`),
    CONSTRAINT `fk_orders_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
    CONSTRAINT `fk_orders_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_orders_approved_by` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_orders_ship_addr` FOREIGN KEY (`shipping_address_id`) REFERENCES `company_addresses` (`id`),
    CONSTRAINT `fk_orders_bill_addr` FOREIGN KEY (`billing_address_id`) REFERENCES `company_addresses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `warehouse_id` BIGINT UNSIGNED NULL,
    `sku_snapshot` VARCHAR(64) NOT NULL,
    `name_snapshot` VARCHAR(255) NOT NULL,
    `quantity` INT NOT NULL,
    `shipped_quantity` INT NOT NULL DEFAULT 0,
    `unit_list_price` DECIMAL(15,4) NOT NULL,
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `unit_net_price` DECIMAL(15,4) NOT NULL,
    `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `line_vat` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `line_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    KEY `idx_order_items_order` (`order_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `fk_order_items_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `old_status` VARCHAR(40) NULL,
    `new_status` VARCHAR(40) NOT NULL,
    `comment` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_order_history_order` (`order_id`),
    CONSTRAINT `fk_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
