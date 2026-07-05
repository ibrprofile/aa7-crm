-- AA7 CRM — схема базы данных (MySQL / MariaDB, utf8mb4)

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login         VARCHAR(64)  NOT NULL,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(160) DEFAULT NULL,
    phone         VARCHAR(40)  DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','manager','viewer') NOT NULL DEFAULT 'manager',
    avatar_color  VARCHAR(7)   NOT NULL DEFAULT '#2f7d5b',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at DATETIME     DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type          ENUM('person','company') NOT NULL DEFAULT 'person',
    company_id    INT UNSIGNED DEFAULT NULL,
    name          VARCHAR(200) NOT NULL,
    legal_name    VARCHAR(200) DEFAULT NULL,
    position      VARCHAR(120) DEFAULT NULL,
    email         VARCHAR(160) DEFAULT NULL,
    phone         VARCHAR(40)  DEFAULT NULL,
    telegram      VARCHAR(80)  DEFAULT NULL,
    whatsapp      VARCHAR(40)  DEFAULT NULL,
    website       VARCHAR(200) DEFAULT NULL,
    inn           VARCHAR(20)  DEFAULT NULL,
    country       VARCHAR(80)  DEFAULT NULL,
    city          VARCHAR(120) DEFAULT NULL,
    address       VARCHAR(255) DEFAULT NULL,
    industry      VARCHAR(120) DEFAULT NULL,
    source        VARCHAR(80)  DEFAULT NULL,
    status        ENUM('lead','active','vip','archived') NOT NULL DEFAULT 'lead',
    notes         TEXT         DEFAULT NULL,
    owner_id      INT UNSIGNED DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_clients_type (type),
    KEY idx_clients_company (company_id),
    KEY idx_clients_status (status),
    KEY idx_clients_owner (owner_id),
    CONSTRAINT fk_clients_company FOREIGN KEY (company_id) REFERENCES clients (id) ON DELETE SET NULL,
    CONSTRAINT fk_clients_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    number          VARCHAR(24)  NOT NULL,
    title           VARCHAR(200) NOT NULL,
    description     TEXT         DEFAULT NULL,
    client_id       INT UNSIGNED DEFAULT NULL,
    owner_id        INT UNSIGNED DEFAULT NULL,
    status          ENUM('new','negotiation','in_progress','review','done','cancelled') NOT NULL DEFAULT 'new',
    priority        ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    amount_input    DECIMAL(14,2) NOT NULL DEFAULT 0,
    currency        ENUM('RUB','USD') NOT NULL DEFAULT 'RUB',
    fx_rate         DECIMAL(12,4) NOT NULL DEFAULT 1,
    amount_rub      DECIMAL(14,2) NOT NULL DEFAULT 0,
    paid_rub        DECIMAL(14,2) NOT NULL DEFAULT 0,
    payment_status  ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    progress        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    tags            VARCHAR(255) DEFAULT NULL,
    due_at          DATE         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_number (number),
    KEY idx_orders_client (client_id),
    KEY idx_orders_owner (owner_id),
    KEY idx_orders_status (status),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL,
    type        VARCHAR(40)  NOT NULL,
    message     VARCHAR(400) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_order (order_id),
    CONSTRAINT fk_events_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED NOT NULL,
    amount_input DECIMAL(14,2) NOT NULL DEFAULT 0,
    currency     ENUM('RUB','USD') NOT NULL DEFAULT 'RUB',
    fx_rate      DECIMAL(12,4) NOT NULL DEFAULT 1,
    amount_rub   DECIMAL(14,2) NOT NULL,
    method       VARCHAR(40)  DEFAULT NULL,
    note         VARCHAR(255) DEFAULT NULL,
    paid_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by   INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_payments_order (order_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    type        VARCHAR(40)  NOT NULL DEFAULT 'info',
    icon        VARCHAR(40)  NOT NULL DEFAULT 'bell',
    title       VARCHAR(160) NOT NULL,
    body        VARCHAR(400) DEFAULT NULL,
    link        VARCHAR(200) DEFAULT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(60)  NOT NULL,
    entity      VARCHAR(40)  DEFAULT NULL,
    entity_id   INT UNSIGNED DEFAULT NULL,
    description VARCHAR(400) DEFAULT NULL,
    ip          VARCHAR(45)  DEFAULT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_log_user (user_id),
    KEY idx_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_leads (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source      ENUM('2gis','yandex') NOT NULL,
    external_id VARCHAR(120) DEFAULT NULL,
    name        VARCHAR(200) NOT NULL,
    category    VARCHAR(160) DEFAULT NULL,
    city        VARCHAR(120) DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    phone       VARCHAR(120) DEFAULT NULL,
    website     VARCHAR(200) DEFAULT NULL,
    instagram   VARCHAR(200) DEFAULT NULL,
    whatsapp    VARCHAR(80)  DEFAULT NULL,
    rating      DECIMAL(3,1) DEFAULT NULL,
    reviews     INT UNSIGNED DEFAULT NULL,
    payload     JSON         DEFAULT NULL,
    status      ENUM('new','contacted','converted','rejected') NOT NULL DEFAULT 'new',
    created_by  INT UNSIGNED DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_leads_source (source),
    KEY idx_leads_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fx_rates (
    currency    VARCHAR(8)   NOT NULL,
    rate        DECIMAL(14,6) NOT NULL,
    fetched_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (currency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login       VARCHAR(64)  NOT NULL,
    ip          VARCHAR(45)  NOT NULL,
    success     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_login (login, created_at),
    KEY idx_attempts_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
