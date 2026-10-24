<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$queries = [
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'appinet_omnibus_price`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'appinet_omnibus_override`',
];

foreach ($queries as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
