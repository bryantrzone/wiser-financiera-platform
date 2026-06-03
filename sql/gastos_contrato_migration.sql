-- Migración: campo gastos_contrato en cotizaciones
-- Ejecutar: mysql -u u106289951_wiserfinance -p u106289951_wiserfinance < sql/gastos_contrato_migration.sql

ALTER TABLE `cotizaciones`
    ADD COLUMN IF NOT EXISTS `gastos_contrato` DECIMAL(10,2) NOT NULL DEFAULT 0.00
    AFTER `monto_credito`;
