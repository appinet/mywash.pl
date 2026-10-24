<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$queries = [
    // Price history is stored as periods (date_from - date_to). A new row is
    // written only when the price changes, so the table stays small.
    'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'appinet_omnibus_price` (
        `id_appinet_omnibus_price` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_product` INT UNSIGNED NOT NULL,
        `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
        `id_shop` INT UNSIGNED NOT NULL,
        `id_currency` INT UNSIGNED NOT NULL,
        `id_group` INT UNSIGNED NOT NULL,
        `price_tax_excl` DECIMAL(20,6) NOT NULL,
        `price_tax_incl` DECIMAL(20,6) NOT NULL,
        `regular_tax_excl` DECIMAL(20,6) NOT NULL,
        `regular_tax_incl` DECIMAL(20,6) NOT NULL,
        `is_reduced` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
        `date_from` DATETIME NOT NULL,
        `date_to` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id_appinet_omnibus_price`),
        KEY `price_lookup` (`id_product`, `id_shop`, `id_currency`, `id_group`, `id_product_attribute`, `date_from`),
        KEY `date_to` (`date_to`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4',
    // Manually entered reference prices (shop default currency).
    'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'appinet_omnibus_override` (
        `id_product` INT UNSIGNED NOT NULL,
        `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
        `id_shop` INT UNSIGNED NOT NULL,
        `price_tax_excl` DECIMAL(20,6) NOT NULL,
        `price_tax_incl` DECIMAL(20,6) NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_product`, `id_product_attribute`, `id_shop`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4',
];

foreach ($queries as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
