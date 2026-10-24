<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppinetOmnibusCronModuleFrontController extends ModuleFrontController
{
    public $display_header = false;
    public $display_footer = false;

    public function initContent()
    {
        if (!$this->module->isValidCronToken(Tools::getValue('token'))) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        header('Content-Type: text/plain; charset=UTF-8');

        try {
            $result = $this->module->runCron((bool) Tools::getValue('full'));
            echo sprintf('OK changes=%d full=%s processed=%d', $result['changes'], $result['full'], $result['processed']);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog($this->module->name . ' cron: ' . $e->getMessage(), 3);
            header('HTTP/1.1 500 Internal Server Error');
            echo 'ERROR';
        }

        exit;
    }
}
