<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Finds the lowest price from N days before a price reduction was introduced
 * (art. 6a of Directive 98/6/EC, art. 4 ust. 2-5 ustawy o informowaniu o cenach).
 */
class AppinetOmnibusReferencePriceCalculator
{
    const FALLBACK_REGULAR = 'regular';
    const FALLBACK_CURRENT = 'current';
    const FALLBACK_HIDE = 'hide';

    const SOURCE_HISTORY = 'history';
    const SOURCE_OVERRIDE = 'override';
    const SOURCE_NEW_PRODUCT = 'new_product';
    const SOURCE_FALLBACK_REGULAR = 'fallback_regular';
    const SOURCE_FALLBACK_CURRENT = 'fallback_current';

    private $repository;
    private $days;
    private $fallback;
    private $trackingStart;
    private $currencyConverter;

    /**
     * @param string|null $trackingStart date of the first completed full price snapshot
     * @param callable|null $currencyConverter fn(float $amount, int $idCurrency): float, used for overrides
     */
    public function __construct($repository, $days, $fallback, $trackingStart, $currencyConverter = null)
    {
        $this->repository = $repository;
        $this->days = max(1, (int) $days);
        $this->fallback = $fallback;
        $this->trackingStart = $trackingStart ? strtotime($trackingStart) : null;
        $this->currencyConverter = $currencyConverter ?: function ($amount, $idCurrency) {
            return Tools::convertPrice($amount, (int) $idCurrency);
        };
    }

    /**
     * @param bool $isReducedNow whether the storefront currently announces a reduction
     *
     * @return array|null ['price_tax_incl', 'price_tax_excl', 'promotion_start', 'source', 'complete']
     */
    public function calculate($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, $isReducedNow, $now = null)
    {
        if (!$isReducedNow) {
            return null;
        }

        $now = $now ?: date('Y-m-d H:i:s');
        $override = $this->repository->getOverride($idProduct, $idProductAttribute, $idShop);

        if ($override) {
            $convert = $this->currencyConverter;

            return [
                'price_tax_incl' => (float) $convert((float) $override['price_tax_incl'], $idCurrency),
                'price_tax_excl' => (float) $convert((float) $override['price_tax_excl'], $idCurrency),
                'promotion_start' => null,
                'source' => self::SOURCE_OVERRIDE,
                'complete' => true,
            ];
        }

        $rows = $this->repository->getHistory($idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup);

        if (!$rows) {
            return null;
        }

        $streakStart = $this->getReductionStreakStart($rows);
        // No recorded reduction yet (e.g. the cron has not run since the
        // promotion started): the reduction is treated as starting now.
        $promotionStart = $streakStart === null ? $now : $rows[$streakStart]['date_from'];
        $promotionStartTs = strtotime($promotionStart);
        $windowStartTs = $promotionStartTs - $this->days * 86400;
        $before = $streakStart === null ? $rows : array_slice($rows, 0, $streakStart);

        $lowest = null;
        $earliestTs = null;

        foreach ($before as $row) {
            $fromTs = strtotime($row['date_from']);
            $toTs = $row['date_to'] === null ? PHP_INT_MAX : strtotime($row['date_to']);

            if ($fromTs >= $promotionStartTs || $toTs <= $windowStartTs) {
                continue;
            }

            if ($lowest === null || (float) $row['price_tax_incl'] < (float) $lowest['price_tax_incl']) {
                $lowest = $row;
            }

            $earliestTs = $earliestTs === null ? $fromTs : min($earliestTs, $fromTs);
        }

        if ($lowest !== null) {
            return $this->result($lowest['price_tax_incl'], $lowest['price_tax_excl'], $promotionStart, self::SOURCE_HISTORY,
                $earliestTs <= $windowStartTs || $this->isNewProduct($rows));
        }

        $streak = array_slice($rows, (int) $streakStart);

        if ($this->isNewProduct($rows)) {
            // Product offered for the first time already at the reduced price:
            // the lowest price since it went on sale is the reduced price itself.
            $row = $this->lowestRow($streak);

            return $this->result($row['price_tax_incl'], $row['price_tax_excl'], $promotionStart, self::SOURCE_NEW_PRODUCT, true);
        }

        // The promotion started before price tracking did, the real history is unknown.
        switch ($this->fallback) {
            case self::FALLBACK_REGULAR:
                return $this->result($streak[0]['regular_tax_incl'], $streak[0]['regular_tax_excl'], $promotionStart, self::SOURCE_FALLBACK_REGULAR, false);
            case self::FALLBACK_CURRENT:
                $row = $this->lowestRow($streak);

                return $this->result($row['price_tax_incl'], $row['price_tax_excl'], $promotionStart, self::SOURCE_FALLBACK_CURRENT, false);
            default:
                return null;
        }
    }

    /**
     * Index of the first row of the current uninterrupted run of reduced
     * prices (handles progressive reductions), or null if the current price
     * is not a recorded reduction.
     */
    private function getReductionStreakStart(array $rows)
    {
        $last = count($rows) - 1;

        if ($rows[$last]['date_to'] !== null || !(int) $rows[$last]['is_reduced']) {
            return null;
        }

        $start = $last;

        while ($start > 0 && (int) $rows[$start - 1]['is_reduced'] && $rows[$start - 1]['date_to'] === $rows[$start]['date_from']) {
            --$start;
        }

        return $start;
    }

    private function isNewProduct(array $rows)
    {
        return $this->trackingStart !== null
            && strtotime($rows[0]['date_from']) > $this->trackingStart + 86400;
    }

    private function lowestRow(array $rows)
    {
        $lowest = $rows[0];

        foreach ($rows as $row) {
            if ((float) $row['price_tax_incl'] < (float) $lowest['price_tax_incl']) {
                $lowest = $row;
            }
        }

        return $lowest;
    }

    private function result($priceTaxIncl, $priceTaxExcl, $promotionStart, $source, $complete)
    {
        return [
            'price_tax_incl' => (float) $priceTaxIncl,
            'price_tax_excl' => (float) $priceTaxExcl,
            'promotion_start' => $promotionStart,
            'source' => $source,
            'complete' => (bool) $complete,
        ];
    }
}
