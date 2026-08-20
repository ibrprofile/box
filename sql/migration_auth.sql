-- Добавляем таблицы для новой системы авторизации:
-- Выполните на существующей БД командой: mysql kayford < sql/migration_auth.sql

-- 1. Коды подтверждения почты
CREATE TABLE IF NOT EXISTS email_verification_codes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(190) NOT NULL,
    code        CHAR(6) NOT NULL,
    purpose     ENUM('register','login') NOT NULL DEFAULT 'register',
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_purpose (email, purpose),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Социальные аккаунты (OAuth)
CREATE TABLE IF NOT EXISTS social_accounts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    provider        ENUM('vk','yandex') NOT NULL,
    provider_uid        VARCHAR(190) NOT NULL,
    access_token    TEXT NULL,
    refresh_token   TEXT NULL,
    email           VARCHAR(190) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_provider_uid (provider, provider_uid),
    KEY idx_social_user (user_id),
    CONSTRAINT fk_social_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
