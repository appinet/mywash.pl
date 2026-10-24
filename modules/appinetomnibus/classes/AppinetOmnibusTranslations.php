<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Default storefront texts per language ISO code. The message can be changed
 * per language in the module configuration; the tax words are used for the
 * "net and gross" price display.
 */
class AppinetOmnibusTranslations
{
    const FALLBACK_ISO = 'en';

    const LABELS = [
        'pl' => 'Najniższa cena z %days% dni przed obniżką: %price%',
        'en' => 'Lowest price in the %days% days before the discount: %price%',
        'de' => 'Niedrigster Preis der letzten %days% Tage vor der Preissenkung: %price%',
        'fr' => 'Prix le plus bas des %days% derniers jours avant la réduction : %price%',
        'lt' => 'Mažiausia kaina per %days% d. iki nuolaidos: %price%',
        'lv' => 'Zemākā cena %days% dienās pirms atlaides: %price%',
        'cs' => 'Nejnižší cena za %days% dní před slevou: %price%',
        'sk' => 'Najnižšia cena za %days% dní pred zľavou: %price%',
        'hu' => 'A kedvezmény előtti %days% nap legalacsonyabb ára: %price%',
        'sl' => 'Najnižja cena v %days% dneh pred znižanjem: %price%',
        'el' => 'Χαμηλότερη τιμή των %days% ημερών πριν από την έκπτωση: %price%',
        'ru' => 'Самая низкая цена за %days% дней до скидки: %price%',
    ];

    const TAX_WORDS = [
        'pl' => ['excl' => 'netto', 'incl' => 'brutto'],
        'en' => ['excl' => 'net', 'incl' => 'gross'],
        'de' => ['excl' => 'netto', 'incl' => 'brutto'],
        'fr' => ['excl' => 'HT', 'incl' => 'TTC'],
        'lt' => ['excl' => 'be PVM', 'incl' => 'su PVM'],
        'lv' => ['excl' => 'bez PVN', 'incl' => 'ar PVN'],
        'cs' => ['excl' => 'bez DPH', 'incl' => 's DPH'],
        'sk' => ['excl' => 'bez DPH', 'incl' => 's DPH'],
        'hu' => ['excl' => 'nettó', 'incl' => 'bruttó'],
        'sl' => ['excl' => 'brez DDV', 'incl' => 'z DDV'],
        'el' => ['excl' => 'χωρίς ΦΠΑ', 'incl' => 'με ΦΠΑ'],
        'ru' => ['excl' => 'без НДС', 'incl' => 'с НДС'],
    ];

    // PrestaShop's own ISO codes that differ from ISO 639-1.
    const ISO_ALIASES = [
        'si' => 'sl',
        'gb' => 'en',
    ];

    public static function getLabel($isoCode)
    {
        return self::LABELS[self::normalize($isoCode)];
    }

    public static function getTaxWords($isoCode)
    {
        return self::TAX_WORDS[self::normalize($isoCode)];
    }

    /**
     * Default labels for all shop languages, indexed by id_lang.
     */
    public static function getLabelsForLanguages(array $languages)
    {
        $labels = [];

        foreach ($languages as $language) {
            $labels[(int) $language['id_lang']] = self::getLabel($language['iso_code']);
        }

        return $labels;
    }

    private static function normalize($isoCode)
    {
        $isoCode = strtolower((string) $isoCode);

        if (isset(self::ISO_ALIASES[$isoCode])) {
            $isoCode = self::ISO_ALIASES[$isoCode];
        }

        return isset(self::LABELS[$isoCode]) ? $isoCode : self::FALLBACK_ISO;
    }
}
