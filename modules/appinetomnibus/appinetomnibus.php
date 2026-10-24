<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/AppinetOmnibusPriceRepository.php';
require_once __DIR__ . '/classes/AppinetOmnibusPriceRecorder.php';
require_once __DIR__ . '/classes/AppinetOmnibusReferencePriceCalculator.php';

class AppinetOmnibus extends Module
{
    const PREFIX = 'APPINET_OMNIBUS_';

    // Above this many products changed in one request (imports, catalog price
    // rules) the prices are recorded by the cron instead of at request end.
    const QUEUE_LIMIT = 200;

    const CRON_TIME_BUDGET = 50;
    const ADMIN_TIME_BUDGET = 20;

    private static $queue = [];
    private static $shutdownRegistered = false;
    private static $referenceCache = [];

    private $settingKeys = [
        'ENABLED',
        'DAYS',
        'GROUPS',
        'DISPLAY_TAX',
        'SHOW_PRODUCT',
        'HOOK_PRODUCT',
        'SHOW_LIST',
        'HOOK_LIST',
        'RECALCULATE_DISCOUNT',
        'REPLACE_REGULAR_PRICE',
        'FALLBACK',
        'RETENTION_DAYS',
        'LABEL',
    ];

    private $stateKeys = [
        'CRON_TOKEN',
        'TRACKING_START',
        'LAST_RUN',
        'LAST_FULL_RUN',
        'LAST_STATUS',
        'FULL_PENDING',
        'FULL_CURSOR',
        'FULL_PROCESSED',
    ];

    private $priceBlockTypes = ['old_price', 'before_price', 'unit_price', 'price', 'after_price', 'weight'];

    public function __construct()
    {
        $this->name = 'appinetomnibus';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'appiNET';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Omnibus - najniższa cena z 30 dni');
        $this->description = $this->l(
            'Zapisuje historię cen i przy każdej obniżce pokazuje najniższą cenę z 30 dni przed jej wprowadzeniem (dyrektywa Omnibus).'
        );

        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        if (!parent::install() || !include __DIR__ . '/sql/install.php') {
            return false;
        }

        $label = [];

        foreach (Language::getLanguages(false) as $language) {
            $label[(int) $language['id_lang']] = 'Najniższa cena z %days% dni przed obniżką: %price%';
        }

        $defaults = [
            'ENABLED' => 1,
            'DAYS' => 30,
            'GROUPS' => implode(',', [
                (int) Configuration::get('PS_UNIDENTIFIED_GROUP'),
                (int) Configuration::get('PS_GUEST_GROUP'),
                (int) Configuration::get('PS_CUSTOMER_GROUP'),
            ]),
            'DISPLAY_TAX' => 'both',
            'SHOW_PRODUCT' => 1,
            'HOOK_PRODUCT' => 'after_price',
            'SHOW_LIST' => 1,
            'HOOK_LIST' => 'weight',
            'RECALCULATE_DISCOUNT' => 1,
            'REPLACE_REGULAR_PRICE' => 0,
            'FALLBACK' => AppinetOmnibusReferencePriceCalculator::FALLBACK_REGULAR,
            'RETENTION_DAYS' => 730,
            'LABEL' => $label,
        ];

        foreach ($defaults as $key => $value) {
            Configuration::updateValue(self::PREFIX . $key, $value);
        }

        // Price recording state is shared by all shops.
        Configuration::updateGlobalValue(self::PREFIX . 'CRON_TOKEN', $this->generateToken());
        Configuration::updateGlobalValue(self::PREFIX . 'FULL_PENDING', 1);
        Configuration::updateGlobalValue(self::PREFIX . 'LAST_STATUS', 'never');

        return $this->registerHook([
            'displayHeader',
            'displayProductPriceBlock',
            'actionGetProductPropertiesAfter',
            'displayAdminProductsExtra',
            'actionObjectProductAddAfter',
            'actionObjectProductUpdateAfter',
            'actionObjectProductDeleteAfter',
            'actionObjectCombinationAddAfter',
            'actionObjectCombinationUpdateAfter',
            'actionObjectCombinationDeleteAfter',
            'actionObjectSpecificPriceAddAfter',
            'actionObjectSpecificPriceUpdateAfter',
            'actionObjectSpecificPriceDeleteAfter',
            'actionObjectCurrencyUpdateAfter',
            'actionObjectGroupUpdateAfter',
            'actionObjectTaxRuleAddAfter',
            'actionObjectTaxRuleUpdateAfter',
            'actionObjectTaxRuleDeleteAfter',
        ]);
    }

    public function uninstall()
    {
        if (!include __DIR__ . '/sql/uninstall.php') {
            return false;
        }

        foreach (array_merge($this->settingKeys, $this->stateKeys) as $key) {
            Configuration::deleteByName(self::PREFIX . $key);
        }

        return parent::uninstall();
    }

    /*
     * Front office
     */

    public function hookDisplayHeader()
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'module-appinetomnibus',
            'modules/' . $this->name . '/views/css/front.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );
    }

    /**
     * Runs for every product presented on the storefront (listings, product
     * page, combination refresh, quick view, Creative Elements widgets) before
     * the discount fields are built, so the announced reduction can be
     * related to the lowest price instead of the regular price.
     */
    public function hookActionGetProductPropertiesAfter(array $params)
    {
        if (!$this->isEnabled() || !$this->isFrontOffice() || empty($params['product']['id_product'])) {
            return;
        }

        $row = &$params['product'];

        if (empty($row['specific_prices']) || empty($row['reduction']) || (float) $row['reduction'] == 0) {
            return;
        }

        $reference = $this->getReferencePrice(
            (int) $row['id_product'],
            isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : 0
        );

        if ($reference === null) {
            return;
        }

        $currentTaxIncl = (float) $row['price'];
        $currentTaxExcl = (float) $row['price_tax_exc'];
        $reference['has_reduction'] = $reference['price_tax_incl'] - $currentTaxIncl > 0.000001;
        $row['appinet_omnibus'] = $reference;

        if (!Configuration::get(self::PREFIX . 'RECALCULATE_DISCOUNT')) {
            return;
        }

        if (!$reference['has_reduction']) {
            // The price is not lower than the reference price, so no reduction
            // may be announced: drop the strike-through price and the badge.
            $row['reduction'] = 0;
            $row['reduction_without_tax'] = 0;
            $row['price_without_reduction'] = $currentTaxIncl;
            $row['price_without_reduction_without_tax'] = $currentTaxExcl;

            if (is_array($row['specific_prices'])) {
                $row['specific_prices']['reduction'] = 0;
            }

            return;
        }

        $row['reduction'] = $reference['price_tax_incl'] - $currentTaxIncl;
        $row['reduction_without_tax'] = max(0, $reference['price_tax_excl'] - $currentTaxExcl);

        if (is_array($row['specific_prices'])
            && isset($row['specific_prices']['reduction_type'])
            && $row['specific_prices']['reduction_type'] === 'percentage'
        ) {
            // Rounded down so the announced percentage is never exaggerated.
            $row['specific_prices']['reduction'] = floor(
                ($reference['price_tax_incl'] - $currentTaxIncl) / $reference['price_tax_incl'] * 10000
            ) / 10000;
        }

        if (Configuration::get(self::PREFIX . 'REPLACE_REGULAR_PRICE')) {
            $row['price_without_reduction'] = $reference['price_tax_incl'];
            $row['price_without_reduction_without_tax'] = $reference['price_tax_excl'];
        }
    }

    public function hookDisplayProductPriceBlock(array $params)
    {
        if (!$this->isEnabled() || empty($params['product']) || empty($params['type'])) {
            return '';
        }

        $product = $params['product'];
        $placement = $this->isMainProduct($product) ? 'PRODUCT' : 'LIST';

        if (!Configuration::get(self::PREFIX . 'SHOW_' . $placement)
            || $params['type'] !== Configuration::get(self::PREFIX . 'HOOK_' . $placement)
            || empty($product['has_discount'])
        ) {
            return '';
        }

        if (isset($product['appinet_omnibus'])) {
            $reference = $product['appinet_omnibus'];
        } else {
            $reference = $this->getReferencePrice(
                (int) $product['id_product'],
                isset($product['id_product_attribute']) ? (int) $product['id_product_attribute'] : 0
            );
        }

        if (!$reference || (isset($reference['has_reduction']) && !$reference['has_reduction'])) {
            return '';
        }

        $this->smarty->assign('appinet_omnibus', [
            'placement' => strtolower($placement),
            'text' => $this->buildLabel($reference),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/lowest-price.tpl');
    }

    /**
     * Lowest price before the current reduction for the visitor's shop,
     * currency and customer group. Null if the group is not tracked or the
     * price history is not available.
     */
    public function getReferencePrice($idProduct, $idProductAttribute, $isReducedNow = true)
    {
        $idGroup = (int) Group::getCurrent()->id;

        if (!in_array($idGroup, $this->getTrackedGroups(), true)) {
            return null;
        }

        $idShop = (int) $this->context->shop->id;
        $idCurrency = (int) $this->context->currency->id;
        $cacheKey = implode('-', [$idProduct, $idProductAttribute, $idShop, $idCurrency, $idGroup, (int) $isReducedNow]);

        if (!array_key_exists($cacheKey, self::$referenceCache)) {
            self::$referenceCache[$cacheKey] = $this->getCalculator()->calculate(
                $idProduct,
                $idProductAttribute,
                $idShop,
                $idCurrency,
                $idGroup,
                $isReducedNow
            );
        }

        return self::$referenceCache[$cacheKey];
    }

    private function buildLabel(array $reference)
    {
        $locale = $this->context->getCurrentLocale();
        $isoCode = $this->context->currency->iso_code;
        $taxIncl = $locale->formatPrice($reference['price_tax_incl'], $isoCode);
        $taxExcl = $locale->formatPrice($reference['price_tax_excl'], $isoCode);

        switch (Configuration::get(self::PREFIX . 'DISPLAY_TAX')) {
            case 'incl':
                $price = $taxIncl;
                break;
            case 'excl':
                $price = $taxExcl;
                break;
            default:
                $price = sprintf('%s %s (%s %s)', $taxExcl, $this->l('netto'), $taxIncl, $this->l('brutto'));
        }

        $label = Configuration::get(self::PREFIX . 'LABEL', (int) $this->context->language->id)
            ?: $this->l('Najniższa cena z %days% dni przed obniżką: %price%');

        return str_replace(
            ['%days%', '%price%'],
            [
                (int) Configuration::get(self::PREFIX . 'DAYS'),
                '<span class="appinet-omnibus__price">' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</span>',
            ],
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    private function isMainProduct($product)
    {
        $controller = $this->context->controller;

        if (!$controller instanceof ProductController || !method_exists($controller, 'getProduct')) {
            return false;
        }

        $mainProduct = $controller->getProduct();

        return Validate::isLoadedObject($mainProduct) && (int) $mainProduct->id === (int) $product['id_product'];
    }

    private function isFrontOffice()
    {
        return $this->context->controller instanceof FrontController;
    }

    private function isEnabled()
    {
        return (bool) Configuration::get(self::PREFIX . 'ENABLED');
    }

    /*
     * Recording price changes
     */

    public function hookActionObjectProductAddAfter(array $params)
    {
        $this->queueFromObject($params, 'id');
    }

    public function hookActionObjectProductUpdateAfter(array $params)
    {
        $this->queueFromObject($params, 'id');
    }

    public function hookActionObjectProductDeleteAfter(array $params)
    {
        if (!empty($params['object']->id)) {
            $this->getRepository()->closeProduct((int) $params['object']->id, date('Y-m-d H:i:s'));
        }
    }

    public function hookActionObjectCombinationAddAfter(array $params)
    {
        $this->queueFromObject($params, 'id_product');
    }

    public function hookActionObjectCombinationUpdateAfter(array $params)
    {
        $this->queueFromObject($params, 'id_product');
    }

    public function hookActionObjectCombinationDeleteAfter(array $params)
    {
        $this->queueFromObject($params, 'id_product');
    }

    public function hookActionObjectSpecificPriceAddAfter(array $params)
    {
        $this->queueFromObject($params, 'id_product');
    }

    public function hookActionObjectSpecificPriceUpdateAfter(array $params)
    {
        $this->queueFromObject($params, 'id_product');
    }

    public function hookActionObjectSpecificPriceDeleteAfter(array $params)
    {
        $this->queueFromObject($params, 'id_product');
    }

    // Exchange rates, group reductions and tax rules change prices of the
    // whole catalog: the next cron run records everything.

    public function hookActionObjectCurrencyUpdateAfter()
    {
        $this->requestFullRun();
    }

    public function hookActionObjectGroupUpdateAfter()
    {
        $this->requestFullRun();
    }

    public function hookActionObjectTaxRuleAddAfter()
    {
        $this->requestFullRun();
    }

    public function hookActionObjectTaxRuleUpdateAfter()
    {
        $this->requestFullRun();
    }

    public function hookActionObjectTaxRuleDeleteAfter()
    {
        $this->requestFullRun();
    }

    private function queueFromObject(array $params, $property)
    {
        if (empty($params['object']) || !isset($params['object']->{$property})) {
            return;
        }

        $idProduct = (int) $params['object']->{$property};

        // A specific price with id_product = 0 applies to the whole catalog.
        if ($idProduct <= 0) {
            $this->requestFullRun();

            return;
        }

        self::$queue[$idProduct] = $idProduct;

        if (!self::$shutdownRegistered) {
            // Recording at the end of the request sees the final state after
            // all related objects (product, combinations, specific prices) are saved.
            register_shutdown_function([$this, 'processQueue']);
            self::$shutdownRegistered = true;
        }
    }

    public function processQueue()
    {
        $productIds = self::$queue;
        self::$queue = [];

        if (!$productIds) {
            return;
        }

        if (count($productIds) > self::QUEUE_LIMIT) {
            $this->requestFullRun();

            return;
        }

        try {
            SpecificPrice::flushCache();
            $this->getRecorder()->recordProducts($productIds);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog($this->name . ': ' . $e->getMessage(), 3);
            $this->requestFullRun();
        }
    }

    private function requestFullRun()
    {
        Configuration::updateGlobalValue(self::PREFIX . 'FULL_PENDING', 1);
    }

    /**
     * Records prices whose specific prices started or ended since the last
     * run and continues/starts the full snapshot when it is due (once a day,
     * after catalog-wide changes or until the first snapshot is complete).
     */
    public function runCron($forceFull = false, $timeBudget = self::CRON_TIME_BUDGET)
    {
        @set_time_limit($timeBudget + 120);

        $now = date('Y-m-d H:i:s');
        $deadline = microtime(true) + $timeBudget;
        $repository = $this->getRepository();
        $recorder = $this->getRecorder();
        $result = ['changes' => 0, 'full' => 'skipped', 'processed' => 0];

        $lastRun = Configuration::getGlobalValue(self::PREFIX . 'LAST_RUN');

        if ($lastRun) {
            $boundaries = $repository->getSpecificPriceBoundaryProducts($lastRun, $now);

            if ($boundaries['all_products']) {
                $this->requestFullRun();
            }

            $result['changes'] += $recorder->recordProducts($boundaries['ids'], $now);
        }

        $cursor = json_decode((string) Configuration::getGlobalValue(self::PREFIX . 'FULL_CURSOR'), true);
        $lastFullRun = Configuration::getGlobalValue(self::PREFIX . 'LAST_FULL_RUN');
        $fullDue = $forceFull
            || is_array($cursor)
            || Configuration::getGlobalValue(self::PREFIX . 'FULL_PENDING')
            || !$lastFullRun
            || strtotime($lastFullRun) < time() - 86400 + 600;

        if ($fullDue) {
            if (!is_array($cursor)) {
                $cursor = ['id_shop' => 0, 'id_product' => 0];
                Configuration::updateGlobalValue(self::PREFIX . 'FULL_PENDING', 0);
                Configuration::updateGlobalValue(self::PREFIX . 'FULL_PROCESSED', 0);
            }

            $chunk = $recorder->recordChunk($cursor, $deadline, $now);
            $result['changes'] += $chunk['changes'];
            $result['processed'] = $chunk['processed'];

            Configuration::updateGlobalValue(
                self::PREFIX . 'FULL_PROCESSED',
                (int) Configuration::getGlobalValue(self::PREFIX . 'FULL_PROCESSED') + $chunk['processed']
            );

            if ($chunk['done']) {
                $result['full'] = 'done';
                Configuration::updateGlobalValue(self::PREFIX . 'FULL_CURSOR', '');
                Configuration::updateGlobalValue(self::PREFIX . 'LAST_FULL_RUN', $now);

                if (!Configuration::getGlobalValue(self::PREFIX . 'TRACKING_START')) {
                    Configuration::updateGlobalValue(self::PREFIX . 'TRACKING_START', $now);
                }

                $retention = max(60, (int) Configuration::get(self::PREFIX . 'RETENTION_DAYS'));
                $repository->deleteClosedBefore(date('Y-m-d H:i:s', strtotime($now) - $retention * 86400));
            } else {
                $result['full'] = 'in_progress';
                Configuration::updateGlobalValue(self::PREFIX . 'FULL_CURSOR', json_encode($chunk['cursor']));
            }
        }

        Configuration::updateGlobalValue(self::PREFIX . 'LAST_RUN', $now);
        Configuration::updateGlobalValue(
            self::PREFIX . 'LAST_STATUS',
            sprintf('%s: zmian %d, pełny zapis: %s', $now, $result['changes'], $result['full'])
        );

        return $result;
    }

    public function isValidCronToken($token)
    {
        $expected = (string) Configuration::getGlobalValue(self::PREFIX . 'CRON_TOKEN');

        return $expected !== '' && hash_equals($expected, (string) $token);
    }

    public function getCronUrl()
    {
        return $this->context->link->getModuleLink(
            $this->name,
            'cron',
            ['token' => Configuration::getGlobalValue(self::PREFIX . 'CRON_TOKEN')],
            true
        );
    }

    /*
     * Back office
     */

    public function hookDisplayAdminProductsExtra(array $params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : (int) Tools::getValue('id_product');

        if (!$idProduct) {
            return '';
        }

        $idShop = (int) $this->context->shop->id;
        $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $groups = $this->getTrackedGroups();
        $idGroup = $groups ? (int) $groups[0] : 0;
        $combinationNames = $this->getCombinationNames($idProduct);
        $currency = new Currency($idCurrency);
        $locale = $this->context->getCurrentLocale();
        $rows = [];

        foreach ($this->getRepository()->getProductHistory($idProduct, $idShop, $idCurrency, $idGroup, 100) as $row) {
            $row['combination'] = isset($combinationNames[(int) $row['id_product_attribute']])
                ? $combinationNames[(int) $row['id_product_attribute']]
                : '-';
            $row['price_tax_incl_formatted'] = $locale->formatPrice($row['price_tax_incl'], $currency->iso_code);
            $row['price_tax_excl_formatted'] = $locale->formatPrice($row['price_tax_excl'], $currency->iso_code);
            $row['regular_tax_incl_formatted'] = $locale->formatPrice($row['regular_tax_incl'], $currency->iso_code);
            $rows[] = $row;
        }

        $group = new Group($idGroup, (int) $this->context->language->id);

        $this->context->smarty->assign([
            'appinet_omnibus_rows' => $rows,
            'appinet_omnibus_group' => $group->name,
            'appinet_omnibus_currency' => $currency->iso_code,
            'appinet_omnibus_config_url' => $this->getConfigUrl(),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product-history.tpl');
    }

    public function getContent()
    {
        $output = '';

        try {
            if (Tools::isSubmit('submitAppinetOmnibus')) {
                $this->saveSettings();
                $output .= $this->displayConfirmation($this->l('Ustawienia zostały zapisane.'));
            } elseif (Tools::isSubmit('submitAppinetOmnibusRun')) {
                $result = $this->runCron(true, self::ADMIN_TIME_BUDGET);
                $output .= $result['full'] === 'in_progress'
                    ? $this->displayWarning(sprintf(
                        $this->l('Zapisano kolejną partię produktów (%d). Kliknij ponownie, aby kontynuować, albo poczekaj na cron.'),
                        $result['processed']
                    ))
                    : $this->displayConfirmation(sprintf($this->l('Ceny zostały zapisane. Zmian: %d.'), $result['changes']));
            } elseif (Tools::isSubmit('submitAppinetOmnibusToken')) {
                Configuration::updateGlobalValue(self::PREFIX . 'CRON_TOKEN', $this->generateToken());
                $output .= $this->displayConfirmation($this->l('Wygenerowano nowy token crona.'));
            } elseif (Tools::isSubmit('submitAppinetOmnibusOverride')) {
                $this->saveOverride();
                self::$referenceCache = [];
                $output .= $this->displayConfirmation($this->l('Cena referencyjna została zapisana.'));
            } elseif (Tools::isSubmit('deleteAppinetOmnibusOverride')) {
                $this->getRepository()->deleteOverride(
                    (int) Tools::getValue('override_id_product'),
                    (int) Tools::getValue('override_id_product_attribute'),
                    (int) $this->context->shop->id
                );
                $output .= $this->displayConfirmation($this->l('Cena referencyjna została usunięta.'));
            }
        } catch (Exception $e) {
            $output .= $this->displayError($e->getMessage());
        }

        return $output . $this->renderSettingsForm() . $this->renderPanels();
    }

    private function saveSettings()
    {
        $days = (int) Tools::getValue(self::PREFIX . 'DAYS');
        $retention = (int) Tools::getValue(self::PREFIX . 'RETENTION_DAYS');

        if ($days < 1 || $days > 365) {
            throw new Exception($this->l('Liczba dni musi być z zakresu 1-365.'));
        }

        if ($retention < $days * 2) {
            throw new Exception($this->l('Okres przechowywania historii musi być co najmniej dwa razy dłuższy niż liczba dni.'));
        }

        $groups = [];

        foreach (Group::getGroups((int) $this->context->language->id) as $group) {
            if (Tools::getValue(self::PREFIX . 'GROUPS_' . (int) $group['id_group'])) {
                $groups[] = (int) $group['id_group'];
            }
        }

        if (!$groups) {
            throw new Exception($this->l('Wybierz co najmniej jedną grupę klientów.'));
        }

        if (implode(',', $groups) !== implode(',', $this->getTrackedGroups())) {
            $this->requestFullRun();
        }

        $label = [];

        foreach (Language::getLanguages(false) as $language) {
            $label[(int) $language['id_lang']] = (string) Tools::getValue(self::PREFIX . 'LABEL_' . (int) $language['id_lang']);
        }

        $values = [
            'ENABLED' => (int) Tools::getValue(self::PREFIX . 'ENABLED'),
            'DAYS' => $days,
            'GROUPS' => implode(',', $groups),
            'DISPLAY_TAX' => $this->allowed(Tools::getValue(self::PREFIX . 'DISPLAY_TAX'), ['both', 'incl', 'excl'], 'both'),
            'SHOW_PRODUCT' => (int) Tools::getValue(self::PREFIX . 'SHOW_PRODUCT'),
            'HOOK_PRODUCT' => $this->allowed(Tools::getValue(self::PREFIX . 'HOOK_PRODUCT'), $this->priceBlockTypes, 'after_price'),
            'SHOW_LIST' => (int) Tools::getValue(self::PREFIX . 'SHOW_LIST'),
            'HOOK_LIST' => $this->allowed(Tools::getValue(self::PREFIX . 'HOOK_LIST'), $this->priceBlockTypes, 'weight'),
            'RECALCULATE_DISCOUNT' => (int) Tools::getValue(self::PREFIX . 'RECALCULATE_DISCOUNT'),
            'REPLACE_REGULAR_PRICE' => (int) Tools::getValue(self::PREFIX . 'REPLACE_REGULAR_PRICE'),
            'FALLBACK' => $this->allowed(Tools::getValue(self::PREFIX . 'FALLBACK'), [
                AppinetOmnibusReferencePriceCalculator::FALLBACK_REGULAR,
                AppinetOmnibusReferencePriceCalculator::FALLBACK_CURRENT,
                AppinetOmnibusReferencePriceCalculator::FALLBACK_HIDE,
            ], AppinetOmnibusReferencePriceCalculator::FALLBACK_REGULAR),
            'RETENTION_DAYS' => $retention,
            'LABEL' => $label,
        ];

        foreach ($values as $key => $value) {
            Configuration::updateValue(self::PREFIX . $key, $value);
        }
    }

    private function saveOverride()
    {
        $idProduct = (int) Tools::getValue('override_id_product');
        $idProductAttribute = (int) Tools::getValue('override_id_product_attribute');
        $priceTaxIncl = (float) str_replace(',', '.', (string) Tools::getValue('override_price_tax_incl'));
        $product = new Product($idProduct);

        if (!Validate::isLoadedObject($product)) {
            throw new Exception($this->l('Nie znaleziono produktu o podanym ID.'));
        }

        if ($idProductAttribute && !in_array($idProductAttribute, array_map('intval', array_column($product->getAttributesResume((int) $this->context->language->id) ?: [], 'id_product_attribute')), true)) {
            throw new Exception($this->l('Kombinacja nie należy do tego produktu.'));
        }

        if ($priceTaxIncl <= 0) {
            throw new Exception($this->l('Podaj cenę brutto większą od zera.'));
        }

        $address = new Address();
        $address->id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $taxRate = (float) $product->getTaxesRate($address);

        $this->getRepository()->saveOverride(
            $idProduct,
            $idProductAttribute,
            (int) $this->context->shop->id,
            $priceTaxIncl / (1 + $taxRate / 100),
            $priceTaxIncl
        );
    }

    private function renderSettingsForm()
    {
        $idLang = (int) $this->context->language->id;
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitAppinetOmnibus';
        $helper->default_form_language = $idLang;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->languages = $this->context->controller->getLanguages();

        foreach ($this->settingKeys as $key) {
            if ($key !== 'GROUPS' && $key !== 'LABEL') {
                $helper->fields_value[self::PREFIX . $key] = Configuration::get(self::PREFIX . $key);
            }
        }

        foreach (Language::getLanguages(false) as $language) {
            $helper->fields_value[self::PREFIX . 'LABEL'][(int) $language['id_lang']] = Configuration::get(
                self::PREFIX . 'LABEL',
                (int) $language['id_lang']
            );
        }

        $groups = Group::getGroups($idLang);

        foreach ($groups as $group) {
            $helper->fields_value[self::PREFIX . 'GROUPS_' . (int) $group['id_group']] = in_array(
                (int) $group['id_group'],
                $this->getTrackedGroups(),
                true
            );
        }

        $hookOptions = [];

        foreach ($this->priceBlockTypes as $type) {
            $hookOptions[] = ['id' => $type, 'name' => 'displayProductPriceBlock - ' . $type];
        }

        return $helper->generateForm([[
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    $this->switchInput($this->l('Włącz moduł'), 'ENABLED'),
                    [
                        'type' => 'text',
                        'label' => $this->l('Liczba dni'),
                        'name' => self::PREFIX . 'DAYS',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Ustawa wymaga 30 dni przed wprowadzeniem obniżki.'),
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Śledzone grupy klientów'),
                        'name' => self::PREFIX . 'GROUPS',
                        'values' => [
                            'query' => $groups,
                            'id' => 'id_group',
                            'name' => 'name',
                        ],
                        'desc' => $this->l('Grupy konsumentów (zwykle Odwiedzający, Gość, Klient). Klientom z innych grup (np. B2B) informacja nie jest wyświetlana, a rabaty pozostają bez zmian.'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Wyświetlana cena'),
                        'name' => self::PREFIX . 'DISPLAY_TAX',
                        'options' => [
                            'query' => [
                                ['id' => 'both', 'name' => $this->l('Netto i brutto (jak w motywie)')],
                                ['id' => 'incl', 'name' => $this->l('Brutto')],
                                ['id' => 'excl', 'name' => $this->l('Netto')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Treść komunikatu'),
                        'name' => self::PREFIX . 'LABEL',
                        'lang' => true,
                        'desc' => $this->l('Zmienne: %days% - liczba dni, %price% - najniższa cena.'),
                    ],
                    $this->switchInput($this->l('Pokazuj na karcie produktu'), 'SHOW_PRODUCT'),
                    [
                        'type' => 'select',
                        'label' => $this->l('Pozycja na karcie produktu'),
                        'name' => self::PREFIX . 'HOOK_PRODUCT',
                        'options' => ['query' => $hookOptions, 'id' => 'id', 'name' => 'name'],
                    ],
                    $this->switchInput($this->l('Pokazuj na listach produktów'), 'SHOW_LIST'),
                    [
                        'type' => 'select',
                        'label' => $this->l('Pozycja na listach produktów'),
                        'name' => self::PREFIX . 'HOOK_LIST',
                        'options' => ['query' => $hookOptions, 'id' => 'id', 'name' => 'name'],
                        'desc' => $this->l('Widgety Creative Elements Grid / Carousel / Box w stylu "custom" nie wywołują tego hooka - ustaw w nich styl "Product miniature".'),
                    ],
                    $this->switchInput(
                        $this->l('Licz rabat od najniższej ceny'),
                        'RECALCULATE_DISCOUNT',
                        $this->l('Etykiety "-X%" i "Oszczędzasz X" liczone od najniższej ceny z okresu (wyrok TSUE C-330/23). Gdy cena nie jest niższa od najniższej ceny, obniżka nie jest ogłaszana.')
                    ),
                    $this->switchInput(
                        $this->l('Przekreślona cena = najniższa cena'),
                        'REPLACE_REGULAR_PRICE',
                        $this->l('Zamiast ceny regularnej przekreślana jest najniższa cena z okresu.')
                    ),
                    [
                        'type' => 'select',
                        'label' => $this->l('Gdy brak historii'),
                        'name' => self::PREFIX . 'FALLBACK',
                        'options' => [
                            'query' => [
                                ['id' => AppinetOmnibusReferencePriceCalculator::FALLBACK_REGULAR, 'name' => $this->l('Użyj ceny regularnej sprzed promocji')],
                                ['id' => AppinetOmnibusReferencePriceCalculator::FALLBACK_CURRENT, 'name' => $this->l('Użyj aktualnej ceny promocyjnej (najbezpieczniej)')],
                                ['id' => AppinetOmnibusReferencePriceCalculator::FALLBACK_HIDE, 'name' => $this->l('Nie pokazuj informacji')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'desc' => $this->l('Dotyczy promocji rozpoczętych przed zebraniem historii cen. Takie produkty można uzupełnić ręczną ceną referencyjną poniżej.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Przechowuj historię (dni)'),
                        'name' => self::PREFIX . 'RETENTION_DAYS',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Historia jest dowodem dla UOKiK / Inspekcji Handlowej - zalecane min. 365 dni.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Zapisz')],
            ],
        ]]);
    }

    private function renderPanels()
    {
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $repository = $this->getRepository();
        $locale = $this->context->getCurrentLocale();
        $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $overrides = [];

        foreach ($repository->getOverrides($idShop) as $override) {
            $override['name'] = Product::getProductName((int) $override['id_product'], (int) $override['id_product_attribute'] ?: null, $idLang);
            $override['price_tax_incl_formatted'] = $locale->formatPrice($override['price_tax_incl'], $defaultCurrency->iso_code);
            $override['price_tax_excl_formatted'] = $locale->formatPrice($override['price_tax_excl'], $defaultCurrency->iso_code);
            $overrides[] = $override;
        }

        $this->context->smarty->assign([
            'appinet_omnibus' => [
                'form_action' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'cron_url' => $this->getCronUrl(),
                'tracking_start' => Configuration::getGlobalValue(self::PREFIX . 'TRACKING_START'),
                'last_run' => Configuration::getGlobalValue(self::PREFIX . 'LAST_RUN'),
                'last_full_run' => Configuration::getGlobalValue(self::PREFIX . 'LAST_FULL_RUN'),
                'last_status' => Configuration::getGlobalValue(self::PREFIX . 'LAST_STATUS'),
                'full_in_progress' => (bool) Configuration::getGlobalValue(self::PREFIX . 'FULL_CURSOR'),
                'full_pending' => (bool) Configuration::getGlobalValue(self::PREFIX . 'FULL_PENDING'),
                'full_processed' => (int) Configuration::getGlobalValue(self::PREFIX . 'FULL_PROCESSED'),
                'product_shop_count' => $repository->countProductShop(),
                'history_rows' => $repository->countRows(),
                'overrides' => $overrides,
                'currency' => $defaultCurrency->iso_code,
                'incomplete' => $this->getIncompleteHistoryReport(100),
            ],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Reduced products whose lowest price could not be fully determined from
     * the recorded history (default currency, first tracked group).
     */
    private function getIncompleteHistoryReport($limit)
    {
        $groups = $this->getTrackedGroups();

        if (!$groups) {
            return [];
        }

        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $calculator = $this->getCalculator();
        $result = [];

        foreach ($this->getRepository()->getReducedOpenRows($idShop, $idCurrency, (int) $groups[0], 500) as $row) {
            $reference = $calculator->calculate(
                (int) $row['id_product'],
                (int) $row['id_product_attribute'],
                $idShop,
                $idCurrency,
                (int) $groups[0],
                true
            );

            if ($reference && $reference['complete']) {
                continue;
            }

            $result[] = [
                'id_product' => (int) $row['id_product'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
                'name' => Product::getProductName((int) $row['id_product'], (int) $row['id_product_attribute'] ?: null, $idLang),
                'source' => $reference ? $reference['source'] : AppinetOmnibusReferencePriceCalculator::FALLBACK_HIDE,
                'promotion_start' => $reference ? $reference['promotion_start'] : null,
            ];

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    private function getCombinationNames($idProduct)
    {
        $product = new Product($idProduct);
        $names = [0 => $this->l('produkt bez kombinacji')];

        foreach ($product->getAttributesResume((int) $this->context->language->id) ?: [] as $combination) {
            $names[(int) $combination['id_product_attribute']] = $combination['attribute_designation'];
        }

        return $names;
    }

    private function switchInput($label, $key, $desc = null)
    {
        $input = [
            'type' => 'switch',
            'label' => $label,
            'name' => self::PREFIX . $key,
            'is_bool' => true,
            'values' => [
                ['id' => $key . '_on', 'value' => 1, 'label' => $this->l('Tak')],
                ['id' => $key . '_off', 'value' => 0, 'label' => $this->l('Nie')],
            ],
        ];

        if ($desc) {
            $input['desc'] = $desc;
        }

        return $input;
    }

    private function allowed($value, array $allowed, $default)
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function getConfigUrl()
    {
        return $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]);
    }

    /*
     * Services
     */

    public function getTrackedGroups()
    {
        $groups = array_filter(array_map('intval', explode(',', (string) Configuration::get(self::PREFIX . 'GROUPS'))));

        return array_values($groups);
    }

    public function getRepository()
    {
        return new AppinetOmnibusPriceRepository();
    }

    public function getRecorder()
    {
        return new AppinetOmnibusPriceRecorder($this->getRepository(), $this->getTrackedGroups());
    }

    public function getCalculator()
    {
        return new AppinetOmnibusReferencePriceCalculator(
            $this->getRepository(),
            (int) Configuration::get(self::PREFIX . 'DAYS'),
            Configuration::get(self::PREFIX . 'FALLBACK'),
            Configuration::getGlobalValue(self::PREFIX . 'TRACKING_START')
        );
    }

    private function generateToken()
    {
        return bin2hex(random_bytes(24));
    }
}
