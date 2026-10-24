<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppinetOmnibusPriceRepository
{
    const TABLE = 'appinet_omnibus_price';
    const OVERRIDE_TABLE = 'appinet_omnibus_override';

    private $shopCurrencies = [];

    public static function key($idProductAttribute, $idCurrency, $idGroup)
    {
        return (int) $idProductAttribute . '-' . (int) $idCurrency . '-' . (int) $idGroup;
    }

    /**
     * Currently open periods of a product, indexed by key().
     */
    public function getOpenRows($idProduct, $idShop)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE . '`
            WHERE `id_product` = ' . (int) $idProduct . '
                AND `id_shop` = ' . (int) $idShop . '
                AND `date_to` IS NULL'
        );

        $result = [];

        foreach ($rows ?: [] as $row) {
            $result[self::key($row['id_product_attribute'], $row['id_currency'], $row['id_group'])] = $row;
        }

        return $result;
    }

    public function openPeriod(array $data, $dateFrom)
    {
        return Db::getInstance()->insert(self::TABLE, [
            'id_product' => (int) $data['id_product'],
            'id_product_attribute' => (int) $data['id_product_attribute'],
            'id_shop' => (int) $data['id_shop'],
            'id_currency' => (int) $data['id_currency'],
            'id_group' => (int) $data['id_group'],
            'price_tax_excl' => (float) $data['price_tax_excl'],
            'price_tax_incl' => (float) $data['price_tax_incl'],
            'regular_tax_excl' => (float) $data['regular_tax_excl'],
            'regular_tax_incl' => (float) $data['regular_tax_incl'],
            'is_reduced' => (int) $data['is_reduced'],
            'date_from' => pSQL($dateFrom),
        ]);
    }

    public function closePeriod($idPeriod, $dateTo)
    {
        return Db::getInstance()->update(
            self::TABLE,
            ['date_to' => pSQL($dateTo)],
            '`id_appinet_omnibus_price` = ' . (int) $idPeriod
        );
    }

    public function closeProduct($idProduct, $dateTo)
    {
        return Db::getInstance()->update(
            self::TABLE,
            ['date_to' => pSQL($dateTo)],
            '`id_product` = ' . (int) $idProduct . ' AND `date_to` IS NULL'
        );
    }

    /**
     * Full history of one price series, oldest first.
     */
    public function getHistory($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE . '`
            WHERE `id_product` = ' . (int) $idProduct . '
                AND `id_shop` = ' . (int) $idShop . '
                AND `id_currency` = ' . (int) $idCurrency . '
                AND `id_group` = ' . (int) $idGroup . '
                AND `id_product_attribute` = ' . (int) $idProductAttribute . '
            ORDER BY `date_from` ASC, `id_appinet_omnibus_price` ASC'
        ) ?: [];
    }

    public function getProductHistory($idProduct, $idShop, $idCurrency, $idGroup, $limit = 100)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE . '`
            WHERE `id_product` = ' . (int) $idProduct . '
                AND `id_shop` = ' . (int) $idShop . '
                AND `id_currency` = ' . (int) $idCurrency . '
                AND `id_group` = ' . (int) $idGroup . '
            ORDER BY `date_from` DESC, `id_appinet_omnibus_price` DESC
            LIMIT ' . (int) $limit
        ) ?: [];
    }

    public function getReducedOpenRows($idShop, $idCurrency, $idGroup, $limit)
    {
        return Db::getInstance()->executeS(
            'SELECT `id_product`, `id_product_attribute` FROM `' . _DB_PREFIX_ . self::TABLE . '`
            WHERE `id_shop` = ' . (int) $idShop . '
                AND `id_currency` = ' . (int) $idCurrency . '
                AND `id_group` = ' . (int) $idGroup . '
                AND `is_reduced` = 1
                AND `date_to` IS NULL
            ORDER BY `id_product` ASC, `id_product_attribute` ASC
            LIMIT ' . (int) $limit
        ) ?: [];
    }

    public function deleteClosedBefore($date)
    {
        return Db::getInstance()->delete(
            self::TABLE,
            '`date_to` IS NOT NULL AND `date_to` < \'' . pSQL($date) . '\''
        );
    }

    public function countRows()
    {
        return (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . self::TABLE . '`');
    }

    /**
     * Products whose specific price started or ended in the given period.
     * A specific price for all products (id_product = 0) requires a full run.
     */
    public function getSpecificPriceBoundaryProducts($dateFrom, $dateTo)
    {
        $from = pSQL($dateFrom);
        $to = pSQL($dateTo);

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT `id_product` FROM `' . _DB_PREFIX_ . 'specific_price`
            WHERE (`from` > \'' . $from . '\' AND `from` <= \'' . $to . '\')
                OR (`to` > \'' . $from . '\' AND `to` <= \'' . $to . '\')'
        ) ?: [];

        $result = ['all_products' => false, 'ids' => []];

        foreach ($rows as $row) {
            if ((int) $row['id_product'] === 0) {
                $result['all_products'] = true;
            } else {
                $result['ids'][] = (int) $row['id_product'];
            }
        }

        return $result;
    }

    /**
     * Next batch of product/shop pairs after the cursor, used by full runs.
     */
    public function getProductShopBatch($afterIdShop, $afterIdProduct, $limit)
    {
        return Db::getInstance()->executeS(
            'SELECT `id_shop`, `id_product` FROM `' . _DB_PREFIX_ . 'product_shop`
            WHERE `id_shop` > ' . (int) $afterIdShop . '
                OR (`id_shop` = ' . (int) $afterIdShop . ' AND `id_product` > ' . (int) $afterIdProduct . ')
            ORDER BY `id_shop` ASC, `id_product` ASC
            LIMIT ' . (int) $limit
        ) ?: [];
    }

    public function countProductShop()
    {
        return (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_shop`');
    }

    public function getProductShops($idProduct)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'product_shop` WHERE `id_product` = ' . (int) $idProduct
        ) ?: [];

        return array_map('intval', array_column($rows, 'id_shop'));
    }

    public function getProductAttributeIds($idProduct, $idShop)
    {
        if (!Combination::isFeatureActive()) {
            return [0];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_product_attribute` FROM `' . _DB_PREFIX_ . 'product_attribute_shop`
            WHERE `id_product` = ' . (int) $idProduct . ' AND `id_shop` = ' . (int) $idShop
        ) ?: [];

        return $rows ? array_map('intval', array_column($rows, 'id_product_attribute')) : [0];
    }

    /**
     * Active currencies of a shop with their display precision.
     */
    public function getShopCurrencies($idShop)
    {
        if (!isset($this->shopCurrencies[$idShop])) {
            $this->shopCurrencies[$idShop] = Db::getInstance()->executeS(
                'SELECT c.`id_currency`, c.`precision` FROM `' . _DB_PREFIX_ . 'currency` c
                INNER JOIN `' . _DB_PREFIX_ . 'currency_shop` cs
                    ON (cs.`id_currency` = c.`id_currency` AND cs.`id_shop` = ' . (int) $idShop . ')
                WHERE c.`active` = 1 AND c.`deleted` = 0'
            ) ?: [];
        }

        return $this->shopCurrencies[$idShop];
    }

    /**
     * Override for the combination, or for the whole product (id_product_attribute = 0).
     */
    public function getOverride($idProduct, $idProductAttribute, $idShop)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::OVERRIDE_TABLE . '`
            WHERE `id_product` = ' . (int) $idProduct . '
                AND `id_shop` = ' . (int) $idShop . '
                AND `id_product_attribute` IN (0, ' . (int) $idProductAttribute . ')
            ORDER BY `id_product_attribute` DESC'
        );

        return $row ?: null;
    }

    public function getOverrides($idShop)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::OVERRIDE_TABLE . '`
            WHERE `id_shop` = ' . (int) $idShop . '
            ORDER BY `id_product` ASC, `id_product_attribute` ASC'
        ) ?: [];
    }

    public function saveOverride($idProduct, $idProductAttribute, $idShop, $priceTaxExcl, $priceTaxIncl)
    {
        return Db::getInstance()->insert(self::OVERRIDE_TABLE, [
            'id_product' => (int) $idProduct,
            'id_product_attribute' => (int) $idProductAttribute,
            'id_shop' => (int) $idShop,
            'price_tax_excl' => (float) $priceTaxExcl,
            'price_tax_incl' => (float) $priceTaxIncl,
            'date_upd' => date('Y-m-d H:i:s'),
        ], false, true, Db::REPLACE);
    }

    public function deleteOverride($idProduct, $idProductAttribute, $idShop)
    {
        return Db::getInstance()->delete(
            self::OVERRIDE_TABLE,
            '`id_product` = ' . (int) $idProduct . '
                AND `id_product_attribute` = ' . (int) $idProductAttribute . '
                AND `id_shop` = ' . (int) $idShop
        );
    }
}
