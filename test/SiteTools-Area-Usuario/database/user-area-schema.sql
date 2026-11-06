-- SiteTools: tabelas complementares da área do usuário.
-- NÃO cria users e NÃO altera as tabelas existentes.
-- user_id não possui FK intencionalmente: a PK de users já existe
-- e este módulo não presume seu tipo exato.

CREATE TABLE IF NOT EXISTS tool_access_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tool_id BIGINT UNSIGNED NOT NULL,
    access_mode ENUM('free','temporary') NOT NULL DEFAULT 'free',
    guest_allowed TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tool_access_rule_tool (tool_id),
    KEY idx_tool_access_mode (access_mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_tool_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tool_id BIGINT UNSIGNED NOT NULL,
    access_type ENUM('free','temporary') NOT NULL DEFAULT 'temporary',
    status ENUM('active','expired','cancelled','suspended') NOT NULL DEFAULT 'active',
    starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'manual',
    payment_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_tool (user_id, tool_id),
    KEY idx_user_status_expiry (user_id, status, expires_at),
    KEY idx_access_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_tool_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    tool_id BIGINT UNSIGNED NULL,
    tool_slug VARCHAR(150) NOT NULL,
    status ENUM('started','completed','failed') NOT NULL DEFAULT 'completed',
    processing_time_ms INT UNSIGNED NULL,
    file_size_bytes BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_usage (user_id, created_at),
    KEY idx_user_usage_tool (user_id, tool_slug),
    KEY idx_usage_tool_date (tool_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tool_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_favorite (user_id, tool_id),
    KEY idx_favorites_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tool_id BIGINT UNSIGNED NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available','deleted','expired') NOT NULL DEFAULT 'available',
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_files (user_id, status, created_at),
    KEY idx_user_files_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tool_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(60) NOT NULL,
    provider_transaction_id VARCHAR(190) NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'BRL',
    status ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_provider_transaction (provider, provider_transaction_id),
    KEY idx_user_payments (user_id, created_at),
    KEY idx_payment_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
