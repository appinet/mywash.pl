<?php
/**
* 2007-2018 PrestaShop
* NOTICE OF LICENSE
* ...
*/

class AdminwbimagetypeController extends ModuleAdminController
{
    private const TRANS_DOMAIN = 'Modules.Wbblog.Admin';

    /**
     * Backward-compat shim: some helpers still call $this->l().
     */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        $translated = $this->context->getTranslator()->trans($string, [], self::TRANS_DOMAIN);
        if ($htmlentities) {
            $translated = htmlentities($translated, ENT_COMPAT, 'UTF-8');
        }
        if ($addslashes) {
            $translated = addslashes($translated);
        }
        return $translated;
    }

    public function __construct()
    {
        $this->table = 'wb_image_type';
        $this->className = 'wbimagetypeclass';
        $this->deleted = false;

        // Do NOT assign a string to $this->module; ModuleAdminController sets a Module instance.
        // $this->module = 'wbblog'; // <- removed

        $this->allow_export = false;
        $this->_defaultOrderWay = 'DESC';
        $this->bootstrap = true;

        parent::__construct();

        $t = $this->context->getTranslator();

        $this->fields_list = [
            'id_wb_image_type' => [
                'title' => $t->trans('ID', [], self::TRANS_DOMAIN),
                'width' => 100,
                'type'  => 'text',
            ],
            'name' => [
                'title' => $t->trans('Name', [], self::TRANS_DOMAIN),
                'width' => 60,
                'type'  => 'text',
            ],
            'width' => [
                'title' => $t->trans('Width', [], self::TRANS_DOMAIN),
                'width' => 220,
                'type'  => 'text',
            ],
            'height' => [
                'title' => $t->trans('Height', [], self::TRANS_DOMAIN),
                'width' => 100,
                'type'  => 'text',
            ],
            'active' => [
                'title'   => $t->trans('Status', [], self::TRANS_DOMAIN),
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $t->trans('Delete selected', [], self::TRANS_DOMAIN),
                'icon'    => 'icon-trash',
                'confirm' => $t->trans('Delete selected items?', [], self::TRANS_DOMAIN),
            ],
        ];
    }

    public function init()
    {
        parent::init();
        $id_shop = (int) Context::getContext()->shop->id;

        // Filter rows to current shop; your original code set this into _select by mistake.
        $this->_where = ' AND a.id_shop = '.$id_shop;
    }

    public function renderForm()
    {
        $t = $this->context->getTranslator();

        $this->fields_form = [
            'legend' => [
                'title' => $t->trans('wb Blog Image Type', [], self::TRANS_DOMAIN),
            ],
            'input'  => [
                [
                    'type'  => 'text',
                    'label' => $t->trans('Name', [], self::TRANS_DOMAIN),
                    'name'  => 'name',
                    'desc'  => $t->trans('Enter Your Image Type Name', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Width', [], self::TRANS_DOMAIN),
                    'name'  => 'width',
                    'desc'  => $t->trans('Enter Your Width', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Height', [], self::TRANS_DOMAIN),
                    'name'  => 'height',
                    'desc'  => $t->trans('Enter Your Height', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $t->trans('Status', [], self::TRANS_DOMAIN),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active',
                            'value' => 1,
                            'label' => $t->trans('Enabled', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'active',
                            'value' => 0,
                            'label' => $t->trans('Disabled', [], self::TRANS_DOMAIN),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $t->trans('Save', [], self::TRANS_DOMAIN),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        if (!($wbimagetypeclass = $this->loadObject(true))) {
            return;
        }
        unset($wbimagetypeclass);

        return parent::renderForm();
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        return parent::renderList();
    }
}
