-- =============================================================
-- PennyKeeper - Base de dades
-- Versió: 1.0
-- =============================================================

CREATE DATABASE IF NOT EXISTS pennykeeper_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pennykeeper_db;

-- -------------------------------------------------------------
-- Taula: users
-- Emmagatzema els comptes d'usuari de l'aplicació.
-- -------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)     NOT NULL,
    email         VARCHAR(150)    NOT NULL,
    passwordHash  VARCHAR(255)    NOT NULL,
    currency      CHAR(3)         NOT NULL DEFAULT 'EUR',
    createdAt     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email    (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Taula: categories
-- Categories compartides per ingressos i despeses.
-- El camp `type` indica a quin flux pertany cada categoria.
-- -------------------------------------------------------------
CREATE TABLE categories (
    id        INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    userId    INT UNSIGNED        NOT NULL,
    name      VARCHAR(80)         NOT NULL,
    type      ENUM('income','expense') NOT NULL,
    icon      VARCHAR(50)         NULL,

    PRIMARY KEY (id),
    CONSTRAINT fk_categories_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Taula: incomes
-- Ingressos de l'usuari, tant fixos mensuals com puntuals.
-- -------------------------------------------------------------
CREATE TABLE incomes (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    userId       INT UNSIGNED  NOT NULL,
    categoryId   INT UNSIGNED  NULL,
    description  VARCHAR(150)  NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    isRecurring  TINYINT(1)    NOT NULL DEFAULT 0,
    date         DATE          NOT NULL,
    notes        TEXT          NULL,
    createdAt    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_incomes_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_incomes_category
        FOREIGN KEY (categoryId) REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Taula: expenses
-- Despeses de l'usuari, tant fixes mensuals com puntuals.
-- -------------------------------------------------------------
CREATE TABLE expenses (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    userId       INT UNSIGNED  NOT NULL,
    categoryId   INT UNSIGNED  NULL,
    description  VARCHAR(150)  NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    isRecurring  TINYINT(1)    NOT NULL DEFAULT 0,
    date         DATE          NOT NULL,
    notes        TEXT          NULL,
    createdAt    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_expenses_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_expenses_category
        FOREIGN KEY (categoryId) REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Taula: saving_plans
-- Plans d'estalvi amb objectiu i data límit opcional.
-- -------------------------------------------------------------
CREATE TABLE saving_plans (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    userId        INT UNSIGNED  NOT NULL,
    name          VARCHAR(100)  NOT NULL,
    targetAmount  DECIMAL(12,2) NOT NULL,
    savedAmount   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deadline      DATE          NULL,
    status        ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    notes         TEXT          NULL,
    createdAt     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_saving_plans_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Taula: saving_contributions
-- Cada aportació feta a un pla d'estalvi concret.
-- -------------------------------------------------------------
CREATE TABLE saving_contributions (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    planId       INT UNSIGNED  NOT NULL,
    userId       INT UNSIGNED  NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    date         DATE          NOT NULL,
    notes        VARCHAR(200)  NULL,
    createdAt    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_contributions_plan
        FOREIGN KEY (planId) REFERENCES saving_plans(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_contributions_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Taula: investments
-- Inversions registrades per l'usuari.
-- -------------------------------------------------------------
CREATE TABLE investments (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    userId        INT UNSIGNED  NOT NULL,
    name          VARCHAR(100)  NOT NULL,
    type          ENUM('stocks','crypto','funds','real_estate','other') NOT NULL DEFAULT 'other',
    initialAmount DECIMAL(12,2) NOT NULL,
    currentValue  DECIMAL(12,2) NOT NULL,
    startDate     DATE          NOT NULL,
    notes         TEXT          NULL,
    createdAt     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_investments_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- =============================================================
-- Dades inicials: categories per defecte
-- S'inseriran per cada usuari nou via PHP, però aquí
-- tenim la referència de les categories base del sistema.
-- =============================================================

-- (Les categories es creen dinàmicament per usuari des de PHP)
-- Referència de categories per defecte:
--
-- INCOME:  Salari, Freelance, Inversions, Altres ingressos
-- EXPENSE: Habitatge, Alimentació, Transport, Salut,
--          Oci, Subscripcions, Roba, Estalvi, Altres despeses