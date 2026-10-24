<?php
/**
 * Standalone tests of the lowest price logic (no PrestaShop needed):
 * php modules/appinetomnibus/tests/standalone.php
 */
define('_PS_VERSION_', 'test');

require __DIR__ . '/../classes/AppinetOmnibusReferencePriceCalculator.php';

class FakeOmnibusRepository
{
    public $rows = [];
    public $override = null;

    public function getHistory()
    {
        return $this->rows;
    }

    public function getOverride()
    {
        return $this->override;
    }
}

const NOW = '2026-10-24 12:00:00';

function day($offset)
{
    return date('Y-m-d H:i:s', strtotime(NOW) + $offset * 86400);
}

/**
 * Builds contiguous periods: [[startOffsetDays, price, isReduced, regular], ...].
 */
function periods(array $definition)
{
    $rows = [];

    foreach ($definition as $i => $period) {
        list($start, $price, $reduced) = $period;
        $regular = isset($period[3]) ? $period[3] : $price;
        $rows[] = [
            'date_from' => day($start),
            'date_to' => isset($definition[$i + 1]) ? day($definition[$i + 1][0]) : null,
            'price_tax_incl' => $price,
            'price_tax_excl' => round($price / 1.23, 2),
            'regular_tax_incl' => $regular,
            'regular_tax_excl' => round($regular / 1.23, 2),
            'is_reduced' => $reduced,
        ];
    }

    return $rows;
}

function calculate(array $rows, $fallback = 'regular', $trackingStart = -365, $reducedNow = true, $override = null)
{
    $repository = new FakeOmnibusRepository();
    $repository->rows = $rows;
    $repository->override = $override;
    $calculator = new AppinetOmnibusReferencePriceCalculator($repository, 30, $fallback, day($trackingStart), function ($amount) {
        return $amount * 0.25;
    });

    return $calculator->calculate(1, 0, 1, 1, 3, $reducedNow, NOW);
}

$failures = 0;

function check($name, $actual, $expectedPrice, $expectedSource = null)
{
    global $failures;
    $price = $actual === null ? null : $actual['price_tax_incl'];
    $source = $actual === null ? null : $actual['source'];
    $ok = $price === ($expectedPrice === null ? null : (float) $expectedPrice)
        && ($expectedSource === null || $source === $expectedSource);

    if (!$ok) {
        ++$failures;
    }

    printf("[%s] %s (cena: %s, źródło: %s)\n", $ok ? 'OK' : 'FAIL', $name, var_export($price, true), var_export($source, true));
}

check('Zwykła promocja: cena regularna przez 60 dni', calculate(periods([
    [-60, 100, 0], [-5, 80, 1, 100],
])), 100, 'history');

check('Niższa cena 20 dni przed promocją', calculate(periods([
    [-60, 100, 0], [-25, 90, 0], [-15, 100, 0], [-5, 80, 1, 100],
])), 90, 'history');

check('Niższa cena poza oknem 30 dni nie jest brana', calculate(periods([
    [-90, 70, 0], [-40, 100, 0], [-5, 80, 1, 100],
])), 100, 'history');

check('Okno liczone od startu promocji, nie od dziś', calculate(periods([
    [-120, 60, 0], [-80, 100, 0], [-50, 80, 1, 100],
])), 100, 'history');

check('Obniżka stopniowa: cena sprzed pierwszej obniżki', calculate(periods([
    [-60, 100, 0], [-10, 90, 1, 100], [-5, 80, 1, 100],
])), 100, 'history');

check('Poprzednia promocja w oknie obniża cenę referencyjną', calculate(periods([
    [-60, 100, 0], [-20, 75, 1, 100], [-12, 100, 0], [-5, 80, 1, 100],
])), 75, 'history');

check('Brak obniżki teraz: brak informacji', calculate(periods([
    [-60, 100, 0],
]), 'regular', -365, false), null);

check('Promocja jeszcze nie zapisana przez cron: okno do teraz', calculate(periods([
    [-60, 120, 0], [-20, 100, 0],
])), 100, 'history');

check('Nowy produkt wprowadzony od razu w promocji', calculate(periods([
    [-5, 80, 1, 100],
]), 'regular', -100), 80, 'new_product');

check('Promocja sprzed instalacji: fallback cena regularna', calculate(periods([
    [-5, 80, 1, 100],
]), 'regular', -5), 100, 'fallback_regular');

check('Promocja sprzed instalacji: fallback cena aktualna', calculate(periods([
    [-5, 85, 1, 100], [-2, 80, 1, 100],
]), 'current', -5), 80, 'fallback_current');

check('Promocja sprzed instalacji: fallback ukryj', calculate(periods([
    [-5, 80, 1, 100],
]), 'hide', -5), null);

check('Ręczna cena referencyjna ma pierwszeństwo (z przeliczeniem waluty)', calculate(periods([
    [-60, 100, 0], [-5, 80, 1, 100],
]), 'regular', -365, true, ['price_tax_incl' => 400, 'price_tax_excl' => 325.2]), 100, 'override');

$gap = periods([[-60, 100, 0], [-20, 70, 1, 100]]);
$gap[1]['date_to'] = day(-15);
$gap[] = ['date_from' => day(-10), 'date_to' => null, 'price_tax_incl' => 80, 'price_tax_excl' => 65.04, 'regular_tax_incl' => 100, 'regular_tax_excl' => 81.3, 'is_reduced' => 1];
check('Przerwa w sprzedaży przerywa ciąg promocji', calculate($gap), 70, 'history');

$partial = calculate(periods([[-10, 100, 0], [-5, 80, 1, 100]]), 'regular', -10);
check('Historia krótsza niż okno jest oznaczona jako niepełna', $partial, 100, 'history');
if ($partial['complete']) {
    ++$failures;
    echo "[FAIL] complete powinno być false\n";
}

echo $failures ? "\nBłędy: $failures\n" : "\nWszystkie testy przeszły.\n";
exit($failures ? 1 : 0);
