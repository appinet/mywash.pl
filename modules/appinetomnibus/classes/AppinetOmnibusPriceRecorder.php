<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Calculates current product prices for the reference customer (tracked group,
 * default country, quantity 1) and stores them as price periods.
 */
class AppinetOmnibusPriceRecorder
{
    const BATCH_SIZE = 50;

    private $repository;
    private $groupIds;

    public function __construct(AppinetOmnibusPriceRepository $repository, array $groupIds)
    {
        $this->repository = $repository;
        $this->groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
    }

    /**
     * Records prices of the given products in every shop they belong to.
     *
     * @return int number of changed price series
     */
    public function recordProducts(array $productIds, $date = null)
    {
        $date = $date ?: date('Y-m-d H:i:s');
        $changes = 0;

        foreach (array_unique(array_map('intval', $productIds)) as $idProduct) {
            if ($idProduct <= 0) {
                continue;
            }

            foreach ($this->repository->getProductShops($idProduct) as $idShop) {
                $changes += $this->recordProductInShop($idProduct, $idShop, $date);
            }
        }

        return $changes;
    }

    /**
     * Processes product/shop pairs after the cursor until the deadline passes.
     *
     * @param array $cursor ['id_shop' => int, 'id_product' => int]
     * @param float $deadline microtime(true) value
     *
     * @return array ['done' => bool, 'cursor' => array, 'processed' => int, 'changes' => int]
     */
    public function recordChunk(array $cursor, $deadline, $date)
    {
        $processed = 0;
        $changes = 0;

        while (true) {
            $batch = $this->repository->getProductShopBatch(
                (int) $cursor['id_shop'],
                (int) $cursor['id_product'],
                self::BATCH_SIZE
            );

            if (!$batch) {
                return ['done' => true, 'cursor' => $cursor, 'processed' => $processed, 'changes' => $changes];
            }

            foreach ($batch as $row) {
                $changes += $this->recordProductInShop((int) $row['id_product'], (int) $row['id_shop'], $date);
                $cursor = ['id_shop' => (int) $row['id_shop'], 'id_product' => (int) $row['id_product']];
                ++$processed;
            }

            // Core price caches are static and grow with every product.
            SpecificPrice::flushCache();

            if (microtime(true) >= $deadline) {
                return ['done' => false, 'cursor' => $cursor, 'processed' => $processed, 'changes' => $changes];
            }
        }
    }

    public function recordProductInShop($idProduct, $idShop, $date)
    {
        $open = $this->repository->getOpenRows($idProduct, $idShop);
        $idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT', null, null, $idShop);
        $seen = [];
        $changes = 0;

        foreach ($this->repository->getProductAttributeIds($idProduct, $idShop) as $idProductAttribute) {
            foreach ($this->repository->getShopCurrencies($idShop) as $currency) {
                foreach ($this->groupIds as $idGroup) {
                    $prices = $this->calculate(
                        $idProduct,
                        $idProductAttribute,
                        $idShop,
                        (int) $currency['id_currency'],
                        $idGroup,
                        $idCountry,
                        isset($currency['precision']) ? (int) $currency['precision'] : 2
                    );

                    if ($prices === null) {
                        continue;
                    }

                    $key = AppinetOmnibusPriceRepository::key($idProductAttribute, $currency['id_currency'], $idGroup);
                    $seen[$key] = true;
                    $current = isset($open[$key]) ? $open[$key] : null;

                    if ($current && !$this->hasChanged($current, $prices)) {
                        continue;
                    }

                    if ($current) {
                        $this->repository->closePeriod($current['id_appinet_omnibus_price'], $date);
                    }

                    $this->repository->openPeriod($prices + [
                        'id_product' => $idProduct,
                        'id_product_attribute' => $idProductAttribute,
                        'id_shop' => $idShop,
                        'id_currency' => (int) $currency['id_currency'],
                        'id_group' => $idGroup,
                    ], $date);
                    ++$changes;
                }
            }
        }

        // Combinations, currencies or groups that are no longer available.
        foreach ($open as $key => $row) {
            if (!isset($seen[$key])) {
                $this->repository->closePeriod($row['id_appinet_omnibus_price'], $date);
                ++$changes;
            }
        }

        return $changes;
    }

    private function calculate($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $idCountry, $precision)
    {
        $priceTaxIncl = $this->price($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $idCountry, true, true);

        if ($priceTaxIncl === null) {
            return null;
        }

        $prices = [
            'price_tax_incl' => $priceTaxIncl,
            'price_tax_excl' => $this->price($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $idCountry, false, true),
            'regular_tax_incl' => $this->price($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $idCountry, true, false),
            'regular_tax_excl' => $this->price($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $idCountry, false, false),
        ];

        foreach ($prices as $field => $value) {
            $prices[$field] = Tools::ps_round((float) $value, $precision);
        }

        $prices['is_reduced'] = $prices['regular_tax_incl'] > $prices['price_tax_incl'] ? 1 : 0;

        return $prices;
    }

    private function price($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $idCountry, $useTax, $useReduction)
    {
        $specificPrice = null;

        return Product::priceCalculation(
            $idShop,
            $idProduct,
            $idProductAttribute,
            $idCountry,
            0,
            '',
            $idCurrency,
            $idGroup,
            1,
            $useTax,
            6,
            false,
            $useReduction,
            true,
            $specificPrice,
            true
        );
    }

    private function hasChanged(array $current, array $prices)
    {
        foreach (['price_tax_incl', 'price_tax_excl', 'regular_tax_incl', 'regular_tax_excl'] as $field) {
            if (abs((float) $current[$field] - (float) $prices[$field]) > 0.000001) {
                return true;
            }
        }

        return (int) $current['is_reduced'] !== (int) $prices['is_reduced'];
    }
}
