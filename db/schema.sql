-- Solicity Bank — schema (MySQL / MariaDB)
-- Import this file directly in phpMyAdmin (Import tab) against an empty
-- database, or leave it alone and let the app create these tables
-- automatically on first request — see db/connect.php. Either path
-- leaves you with the same structure.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS admins (
    id            VARCHAR(64) NOT NULL PRIMARY KEY,
    username      VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(191) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            VARCHAR(64) NOT NULL PRIMARY KEY,
    name          VARCHAR(191) NOT NULL,
    email         VARCHAR(191) NOT NULL UNIQUE,
    phone         VARCHAR(64) NULL,
    password_hash VARCHAR(255) NOT NULL,
    status        VARCHAR(16) NOT NULL DEFAULT 'active',   -- active | frozen
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounts (
    id              VARCHAR(64) NOT NULL PRIMARY KEY,
    user_id         VARCHAR(64) NOT NULL,
    type            VARCHAR(32) NOT NULL,                  -- Checking | Savings
    account_number  VARCHAR(32) NOT NULL UNIQUE,
    routing_number  VARCHAR(32) NOT NULL DEFAULT '083000108',
    balance         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status          VARCHAR(16) NOT NULL DEFAULT 'active',  -- active | frozen | closed
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cards (
    id           VARCHAR(64) NOT NULL PRIMARY KEY,
    account_id   VARCHAR(64) NOT NULL,
    card_number  VARCHAR(64) NOT NULL,
    card_holder  VARCHAR(191) NOT NULL,
    exp_month    TINYINT UNSIGNED NOT NULL,
    exp_year     SMALLINT UNSIGNED NOT NULL,
    cvv          VARCHAR(8) NOT NULL,
    card_type    VARCHAR(16) NOT NULL DEFAULT 'Debit',
    network      VARCHAR(16) NOT NULL DEFAULT 'Visa',
    status       VARCHAR(16) NOT NULL DEFAULT 'active',     -- active | frozen
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cards_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
    id            VARCHAR(64) NOT NULL PRIMARY KEY,
    account_id    VARCHAR(64) NOT NULL,
    type          VARCHAR(32) NOT NULL,                     -- deposit | withdrawal | transfer_out | transfer_in | bill_pay | adjustment | manual
    category      VARCHAR(64) NOT NULL DEFAULT 'General',
    description   VARCHAR(255) NOT NULL,
    counterparty  VARCHAR(191) NULL,
    amount        DECIMAL(14,2) NOT NULL,                    -- signed: negative = money out
    balance_after DECIMAL(14,2) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tx_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS external_transfers (
    id              VARCHAR(64) NOT NULL PRIMARY KEY,
    transaction_id  VARCHAR(64) NOT NULL,
    bank_name       VARCHAR(191) NOT NULL,
    account_name    VARCHAR(191) NOT NULL,
    account_number  VARCHAR(64) NOT NULL,
    routing_number  VARCHAR(32) NULL,
    swift_code      VARCHAR(32) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ext_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_accounts_user ON accounts(user_id);
CREATE INDEX idx_cards_account ON cards(account_id);
CREATE INDEX idx_tx_account ON transactions(account_id);
CREATE INDEX idx_tx_created ON transactions(created_at);
CREATE INDEX idx_ext_transaction ON external_transfers(transaction_id);

SET FOREIGN_KEY_CHECKS = 1;
