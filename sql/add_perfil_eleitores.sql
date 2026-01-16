-- Migration: add 'perfil' column to eleitores
ALTER TABLE `eleitores`
    ADD COLUMN `perfil` VARCHAR(32) NOT NULL DEFAULT 'vereador' AFTER `foto`;
