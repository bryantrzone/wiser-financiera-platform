-- ============================================================
-- Wiser Financiera — Módulo de Amortización
-- Migración: tablas nuevas sin afectar las existentes
-- ============================================================

USE `u106289951_wiserfinance`;

-- ------------------------------------------------------------
-- Clientes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
  `id`        INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre`    VARCHAR(200)  NOT NULL,
  `empresa`   VARCHAR(200)  NULL,
  `rfc`       VARCHAR(20)   NULL,
  `email`     VARCHAR(150)  NULL,
  `telefono`  VARCHAR(25)   NULL,
  `activo`    TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tasas de interés
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasas` (
  `id`           INT            AUTO_INCREMENT PRIMARY KEY,
  `descripcion`  VARCHAR(50)    NOT NULL,
  `tasa_anual`   DECIMAL(5,4)   NOT NULL,
  `tasa_mensual` DECIMAL(6,5)   NOT NULL,
  `activo`       TINYINT(1)     NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tasas` (`descripcion`, `tasa_anual`, `tasa_mensual`) VALUES
  ('+ 100 MIL', 0.1800, 0.01500),
  ('+ 50 MIL',  0.2000, 0.01670),
  ('- 50 MIL',  0.2400, 0.02000)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ------------------------------------------------------------
-- Productos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
  `id`                INT            AUTO_INCREMENT PRIMARY KEY,
  `nombre`            VARCHAR(50)    NOT NULL,
  `plazo_min_meses`   INT            NOT NULL DEFAULT 1,
  `plazo_max_meses`   INT            NOT NULL DEFAULT 60,
  `monto_minimo`      DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `comision_apertura` DECIMAL(5,4)   NULL,
  `tipo_tasa`         VARCHAR(10)    NOT NULL DEFAULT 'Fija',
  `activo`            TINYINT(1)     NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `productos` (`nombre`, `plazo_min_meses`, `plazo_max_meses`, `monto_minimo`, `comision_apertura`, `tipo_tasa`) VALUES
  ('Wiser Emprendedor', 1,  60,  5000.00, 0.0300, 'Fija'),
  ('Wiser Cero',        0, 240, 3000.00, NULL,   'Fija')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ------------------------------------------------------------
-- Cotizaciones (encabezado)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cotizaciones` (
  `id`                INT            AUTO_INCREMENT PRIMARY KEY,
  `credito_no`        VARCHAR(20)    NOT NULL UNIQUE,
  `cliente_id`        INT            NOT NULL,
  `user_id`           INT            NOT NULL,
  `fecha_inicio`      DATE           NOT NULL,
  `monto_credito`     DECIMAL(12,2)  NOT NULL,
  `plazo_meses`       INT            NOT NULL,
  `plazo_dias`        INT            NOT NULL,
  `tasa_id`           INT            NOT NULL,
  `producto_id`       INT            NOT NULL,
  `moneda`            VARCHAR(30)    NOT NULL DEFAULT 'Pesos Mexicanos',
  `pago_mensual`      DECIMAL(12,6)  NOT NULL,
  `total_intereses`   DECIMAL(12,6)  NOT NULL,
  `total_a_pagar`     DECIMAL(12,6)  NOT NULL,
  `fecha_limite_pago` DATE           NOT NULL,
  `created_at`        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`)  REFERENCES `clientes`(`id`),
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`),
  FOREIGN KEY (`tasa_id`)     REFERENCES `tasas`(`id`),
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Periodos de amortización
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cotizacion_periodos` (
  `id`                INT            AUTO_INCREMENT PRIMARY KEY,
  `cotizacion_id`     INT            NOT NULL,
  `periodo`           INT            NOT NULL,
  `fecha_inicio_mes`  DATE           NOT NULL,
  `fecha_vencimiento` DATE           NOT NULL,
  `fecha_corte`       DATE           NOT NULL,
  `dias`              INT            NOT NULL DEFAULT 30,
  `saldo_insoluto`    DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `pago_capital`      DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `interes_ordinario` DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `iva_interes`       DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `importe_comision`  DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `excedente_pagado`  DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `pago_anticipado`   DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `pago_calculado`    DECIMAL(12,6)  NOT NULL DEFAULT 0,
  `pago_integrado`    DECIMAL(12,6)  NOT NULL DEFAULT 0,
  FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
