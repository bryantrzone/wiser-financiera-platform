-- ============================================================
-- Wiser Financiera — Esquema de base de datos
-- Base de datos: wiser_financiera
-- ============================================================

CREATE DATABASE IF NOT EXISTS `wiser_financiera`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `wiser_financiera`;

-- ------------------------------------------------------------
-- Usuarios y sesiones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT            AUTO_INCREMENT PRIMARY KEY,
  `full_name`      VARCHAR(150)   NOT NULL,
  `email`          VARCHAR(150)   NOT NULL UNIQUE,
  `password`       VARCHAR(255)   NOT NULL,
  `role`           ENUM('admin','vendor','client') NOT NULL DEFAULT 'vendor',
  `active`         TINYINT(1)     NOT NULL DEFAULT 1,
  `last_login`     DATETIME       NULL,
  `reset_token`    VARCHAR(64)    NULL,
  `reset_expires`  DATETIME       NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id`         VARCHAR(128) PRIMARY KEY,
  `user_id`    INT          NOT NULL,
  `expires`    DATETIME     NOT NULL,
  `data`       TEXT,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Catálogos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `catalogo_tipo_equipo` (
  `id`     INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `activo` TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `catalogo_marcas` (
  `id`     INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `activo` TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `catalogo_modelos` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `tipo_equipo_id`  INT          NOT NULL,
  `marca_id`        INT          NOT NULL,
  `nombre`          VARCHAR(200) NOT NULL,
  `descripcion`     TEXT         NULL,
  `activo`          TINYINT(1)   NOT NULL DEFAULT 1,
  FOREIGN KEY (`tipo_equipo_id`) REFERENCES `catalogo_tipo_equipo`(`id`),
  FOREIGN KEY (`marca_id`)       REFERENCES `catalogo_marcas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `catalogo_plazos` (
  `id`     INT        AUTO_INCREMENT PRIMARY KEY,
  `meses`  INT        NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `catalogo_monedas` (
  `id`          INT           AUTO_INCREMENT PRIMARY KEY,
  `codigo`      VARCHAR(3)    NOT NULL UNIQUE,
  `nombre`      VARCHAR(50)   NOT NULL,
  `tipo_cambio` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
  `activo`      TINYINT(1)    NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Cotizaciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cotizacion_header` (
  `id`                  INT            AUTO_INCREMENT PRIMARY KEY,
  `folio`               VARCHAR(25)    NOT NULL UNIQUE,
  `user_id`             INT            NOT NULL,
  `estado`              ENUM('borrador','enviada','aceptada','rechazada','vencida') NOT NULL DEFAULT 'borrador',
  `tipo_financiamiento` ENUM('arrendamiento_financiero','arrendamiento_puro','credito_simple') NOT NULL DEFAULT 'arrendamiento_financiero',
  `cliente_nombre`      VARCHAR(200)   NULL,
  `cliente_empresa`     VARCHAR(200)   NULL,
  `cliente_rfc`         VARCHAR(20)    NULL,
  `cliente_email`       VARCHAR(150)   NULL,
  `cliente_telefono`    VARCHAR(25)    NULL,
  `moneda`              VARCHAR(3)     NOT NULL DEFAULT 'MXN',
  `tipo_cambio`         DECIMAL(10,4)  NOT NULL DEFAULT 1.0000,
  `notas`               TEXT           NULL,
  `fecha_vencimiento`   DATE           NULL,
  `created_at`          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cotizacion_detail` (
  `id`               INT            AUTO_INCREMENT PRIMARY KEY,
  `cotizacion_id`    INT            NOT NULL,
  `tipo_equipo`      VARCHAR(100)   NULL,
  `marca`            VARCHAR(100)   NULL,
  `modelo`           VARCHAR(200)   NULL,
  `descripcion`      VARCHAR(500)   NULL,
  `cantidad`         INT            NOT NULL DEFAULT 1,
  `costo_unitario`   DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `anticipo_pct`     DECIMAL(5,2)   NOT NULL DEFAULT 0,
  `anticipo_monto`   DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `plazo_meses`      INT            NOT NULL DEFAULT 24,
  `tasa_anual`       DECIMAL(6,4)   NOT NULL DEFAULT 0,
  `residual_pct`     DECIMAL(5,2)   NOT NULL DEFAULT 20,
  `residual_monto`   DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `seguro_pct`       DECIMAL(6,4)   NOT NULL DEFAULT 0,
  `pago_seguro`      DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `pago_equipo`      DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `subtotal_mensual` DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `iva_mensual`      DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `pago_mensual`     DECIMAL(15,2)  NOT NULL DEFAULT 0,
  `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizacion_header`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Datos semilla
-- ------------------------------------------------------------
INSERT INTO `catalogo_tipo_equipo` (`nombre`) VALUES
  ('Montacargas'), ('Grúa'), ('Tractocamión'), ('Autobús'),
  ('Equipo de construcción'), ('Maquinaria agrícola'), ('Otro');

INSERT INTO `catalogo_marcas` (`nombre`) VALUES
  ('Caterpillar'), ('John Deere'), ('Toyota'), ('Hyster'),
  ('Crown'), ('Komatsu'), ('Volvo'), ('Terex'), ('JCB'), ('Otra');

INSERT INTO `catalogo_plazos` (`meses`) VALUES (12),(18),(24),(36),(48),(60);

INSERT INTO `catalogo_monedas` (`codigo`, `nombre`, `tipo_cambio`) VALUES
  ('MXN', 'Peso mexicano', 1.0000),
  ('USD', 'Dólar estadounidense', 17.5000);

-- Usuario admin por defecto (contraseña: Admin2025!)
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
  ('Administrador', 'admin@wiserfinanciera.mx',
   '$2y$10$zhBofQiy0s.J1llCRrxrXuQ6Vib/mz8b9ROMc5VUKCszPxwGp.lnW', 'admin');
