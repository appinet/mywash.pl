<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.0.0 saved the Polish message for every language. Replace it with the
 * translation where the message was not changed by the merchant.
 * Adds the settings for moving the message out of the price block.
 */
function upgrade_module_1_0_1($module)
{
    foreach (['MOVE_PRODUCT_SELECTOR' => '', 'MOVE_PRODUCT_POSITION' => 'after', 'MOVE_LIST_SELECTOR' => '', 'MOVE_LIST_POSITION' => 'after'] as $key => $value) {
        if (Configuration::get(AppinetOmnibus::PREFIX . $key) === false) {
            Configuration::updateValue(AppinetOmnibus::PREFIX . $key, $value);
        }
    }

    require_once _PS_MODULE_DIR_ . 'appinetomnibus/classes/AppinetOmnibusTranslations.php';

    $polishDefault = AppinetOmnibusTranslations::getLabel('pl');
    $labels = [];

    foreach (Language::getLanguages(false) as $language) {
        $idLang = (int) $language['id_lang'];
        $current = (string) Configuration::get(AppinetOmnibus::PREFIX . 'LABEL', $idLang);

        $labels[$idLang] = $current === '' || $current === $polishDefault
            ? AppinetOmnibusTranslations::getLabel($language['iso_code'])
            : $current;
    }

    return Configuration::updateValue(AppinetOmnibus::PREFIX . 'LABEL', $labels);
}
