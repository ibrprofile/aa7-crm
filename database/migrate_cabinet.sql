-- AA7 CRM — Cabinet & Chat migration
-- Run after schema.sql. Safe to run multiple times (IF NOT EXISTS).

SET FOREIGN_KEY_CHECKS = 0;

-- Пароль и логин для входа клиента в кабинет.
-- Хранится отдельно, чтобы не трогать таблицу clients.
CREATE TABLE IF NOT EXISTS client_credentials (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id       INT UNSIGNED NOT NULL,
    login           VARCHAR(80)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    last_login_at   DATETIME     DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cred_client (client_id),
    UNIQUE KEY uq_cred_login  (login),
    CONSTRAINT fk_cred_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сессии кабинета (отдельно от сессий CRM).
CREATE TABLE IF NOT EXISTS client_sessions (
    id          VARCHAR(64)  NOT NULL,
    client_id   INT UNSIGNED NOT NULL,
    ip          VARCHAR(45)  DEFAULT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_csess_client  (client_id),
    KEY idx_csess_expires (expires_at),
    CONSTRAINT fk_csess_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сообщения чата. Работают в двух направлениях:
--   from_client = 0  → сообщение от администратора/менеджера CRM
--   from_client = 1  → сообщение от клиента из кабинета
CREATE TABLE IF NOT EXISTS order_messages (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    client_id       INT UNSIGNED NOT NULL,
    from_client     TINYINT(1)   NOT NULL DEFAULT 0,
    sender_id       INT UNSIGNED DEFAULT NULL,   -- users.id (только если from_client=0)
    body            TEXT         NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_msg_order  (order_id),
    KEY idx_msg_client (client_id),
    CONSTRAINT fk_msg_order  FOREIGN KEY (order_id)  REFERENCES orders  (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вложения сообщений. Файл хранится в uploads/, путь — относительный.
CREATE TABLE IF NOT EXISTS order_files (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    client_id       INT UNSIGNED DEFAULT NULL,
    message_id      INT UNSIGNED DEFAULT NULL,
    uploaded_by     INT UNSIGNED DEFAULT NULL,   -- users.id (если загрузил менеджер)
    from_client     TINYINT(1)   NOT NULL DEFAULT 0,
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) NOT NULL,
    mime_type       VARCHAR(120) DEFAULT NULL,
    size_bytes      INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_file_order   (order_id),
    KEY idx_file_message (message_id),
    CONSTRAINT fk_file_order   FOREIGN KEY (order_id)   REFERENCES orders         (id) ON DELETE CASCADE,
    CONSTRAINT fk_file_message FOREIGN KEY (message_id) REFERENCES order_messages (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
