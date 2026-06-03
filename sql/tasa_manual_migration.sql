-- Migración: soporte de tasa manual en cotizaciones
-- Ejecutar: mysql -u usuario -p base_datos < sql/tasa_manual_migration.sql

ALTER TABLE cotizaciones
  MODIFY tasa_id INT NULL,
  ADD COLUMN tasa_anual_custom DECIMAL(5,4) NULL AFTER tasa_id;
