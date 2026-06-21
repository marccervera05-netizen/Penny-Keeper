-- =============================================================
-- PennyKeeper - Migració v1.1
-- Redisseny del mòdul d'inversions.
--
-- Executa aquest fitxer a phpMyAdmin sobre pennykeeper_db.
-- =============================================================

USE pennykeeper_db;

-- Afegim el camp isRecurring i resetem currentValue
-- perquè ara el valor actual es calcularà des de investment_values
ALTER TABLE investments
    ADD COLUMN isRecurring TINYINT(1) NOT NULL DEFAULT 0 AFTER startDate;

-- -------------------------------------------------------------
-- Taula: investment_contributions
-- Cada aportació de diners feta a una inversió.
-- Això substitueix el camp initialAmount com a valor estàtic.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS investment_contributions (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    investmentId INT UNSIGNED  NOT NULL,
    userId       INT UNSIGNED  NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    date         DATE          NOT NULL,
    notes        VARCHAR(200)  NULL,
    createdAt    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_inv_contrib_investment
        FOREIGN KEY (investmentId) REFERENCES investments(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_inv_contrib_user
        FOREIGN KEY (userId) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Taula: investment_values
-- Historial del valor de mercat de la inversió, un registre per mes.
-- L'usuari actualitza el valor actual manualment cada vegada que vol.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS investment_values (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    investmentId INT UNSIGNED  NOT NULL,
    value        DECIMAL(12,2) NOT NULL,
    date         DATE          NOT NULL,
    createdAt    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_inv_values_investment
        FOREIGN KEY (investmentId) REFERENCES investments(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Migra les dades existents al nou model:
-- La initialAmount passa a ser una aportació inicial,
-- i el currentValue passa a ser el primer valor registrat.
-- -------------------------------------------------------------
INSERT INTO investment_contributions (investmentId, userId, amount, date, notes)
SELECT id, userId, initialAmount, startDate, 'Aportació inicial (migrada)'
FROM investments
WHERE initialAmount > 0;

INSERT INTO investment_values (investmentId, value, date)
SELECT id, currentValue, CURDATE()
FROM investments
WHERE currentValue > 0;