<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
* ...
*/

class AdminWbblocksController extends ModuleAdminController
{
    /** Use a stable translation domain for your module's BO strings */
    private const TRANS_DOMAIN = 'Modules.Wbblocks.Admin';

    protected $id_banner;

    /**
     * Backward-compatibility shim for legacy calls that expect $this->l().
     * Uses the Symfony translator under the proper domain.
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
        $this->table = 'wbstaticblock';
        $this->className = 'Staticblock';
        $this->identifier = 'id_wbstaticblock';
        $this->lang = true;
        $this->bootstrap = true;
        $this->deleted = false;
        $this->colorOnBackground = false;

        Shop::addTableAssociation($this->table, ['type' => 'shop']);
        $this->context = Context::getContext();

        parent::__construct();

        $t = $this->context->getTranslator();

        $this->bulk_actions = [
            'delete' => [
                'text'    => $t->trans('Delete selected', [], self::TRANS_DOMAIN),
                'confirm' => $t->trans('Delete selected items?', [], self::TRANS_DOMAIN),
            ],
        ];
    }

    public function renderList()
    {
        $t = $this->context->getTranslator();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->bulk_actions = [
            'delete' => [
                'text'    => $t->trans('Delete selected', [], self::TRANS_DOMAIN),
                'confirm' => $t->trans('Delete selected items?', [], self::TRANS_DOMAIN),
            ],
        ];

        $this->fields_list = [
            'id_wbstaticblock' => [
                'title' => $t->trans('ID', [], self::TRANS_DOMAIN),
                'align' => 'center',
                'width' => 25,
                'lang'  => false,
            ],
            'title' => [
                'title' => $t->trans('Title', [], self::TRANS_DOMAIN),
                'width' => 90,
                'lang'  => false,
            ],
            'identify' => [
                'title' => $t->trans('Identify', [], self::TRANS_DOMAIN),
                'width' => '100',
                'lang'  => false,
            ],
            'hook_position' => [
                'title' => $t->trans('Hook Position', [], self::TRANS_DOMAIN),
                'width' => '300',
                'lang'  => false,
            ],
            'wborder' => [
                'title' => $t->trans('Order', [], self::TRANS_DOMAIN),
                'width' => '30',
                'lang'  => false,
            ],
        ];

        $lists = parent::renderList();
        parent::initToolbar();
        return $lists;
    }

    public function renderForm()
    {
        $t = $this->context->getTranslator();

        $mod = new wbstaticblocks();
        $listModules = $mod->getListModuleInstalled();

        $listHookPosition = [
            ['hook_position' => 'displayTop'],
            ['hook_position' => 'displayTopColumn'],
            ['hook_position' => 'rightColumn'],
            ['hook_position' => 'displayLeftColumn'],
            ['hook_position' => 'displayHeader'],
            ['hook_position' => 'displayFooter'],
            ['hook_position' => 'displayHome'],
            ['hook_position' => 'displayLeftColumnProduct'],
            ['hook_position' => 'displayFooterDown'],
            ['hook_position' => 'displayFooterLeft'],
            ['hook_position' => 'displayFooterAfter'],
            ['hook_position' => 'displayFooterBefore'],
            ['hook_position' => 'displayNav1'],
            ['hook_position' => 'displayNavFullWidth'],
            ['hook_position' => 'displayHomeBlock'],
        ];

        $this->fields_form = [
            'tinymce' => true,
            'legend' => [
                'title' => $t->trans('Slideshow', [], self::TRANS_DOMAIN),
                'image' => '../img/admin/cog.gif',
            ],
            'input' => [
                [
                    'type'  => 'text',
                    'label' => $t->trans('Title:', [], self::TRANS_DOMAIN),
                    'name'  => 'title',
                    'size'  => 40,
                    'lang'  => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Identify:', [], self::TRANS_DOMAIN),
                    'name'  => 'identify',
                    'size'  => 40,
                    'require' => false,
                ],
                [
                    'type'  => 'switch',
                    'label' => $t->trans('Show/Hide title', [], self::TRANS_DOMAIN),
                    'name'  => 'active',
                    'desc'  => $t->trans('Show/Hide title.', [], self::TRANS_DOMAIN),
                    'values' => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $t->trans('Enabled', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $t->trans('Disabled', [], self::TRANS_DOMAIN),
                        ],
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $t->trans('Hook Position:', [], self::TRANS_DOMAIN),
                    'name'     => 'hook_position',
                    'required' => true,
                    'options'  => [
                        'query' => $listHookPosition,
                        'id'    => 'hook_position',
                        'name'  => 'hook_position',
                    ],
                    'desc' => $t->trans('Choose the type of the Hooks', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'switch',
                    'label' => $t->trans('Show/hide Hook', [], self::TRANS_DOMAIN),
                    'name'  => 'showhook',
                    'desc'  => $t->trans('Show/hide Hook', [], self::TRANS_DOMAIN),
                    'values' => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $t->trans('Enabled', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $t->trans('Disabled', [], self::TRANS_DOMAIN),
                        ],
                    ],
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $t->trans('Description', [], self::TRANS_DOMAIN),
                    'name'         => 'description',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'required'     => true,
                    'class'        => 'rte',
                    'rows'         => 5,
                    'cols'         => 40,
                    'hint'         => $t->trans('Invalid characters:', [], self::TRANS_DOMAIN) . ' <>;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Order:', [], self::TRANS_DOMAIN),
                    'name'  => 'wborder',
                    'size'  => 40,
                    'require' => false,
                ],
                [
                    'type'  => 'switch',
                    'label' => $t->trans('Insert Module?', [], self::TRANS_DOMAIN),
                    'name'  => 'insert_module',
                    'desc'  => $t->trans('Insert Module?', [], self::TRANS_DOMAIN),
                    'values' => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $t->trans('Enabled', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $t->trans('Disabled', [], self::TRANS_DOMAIN),
                        ],
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $t->trans('Modules:', [], self::TRANS_DOMAIN),
                    'name'     => 'name_module',
                    'required' => true,
                    'options'  => [
                        'query' => $listModules,
                        'id'    => 'name',
                        'name'  => 'name',
                    ],
                    'desc' => $t->trans('Choose the type of the Module', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'     => 'select',
                    'label'    => $t->trans('Hook-Modules:', [], self::TRANS_DOMAIN),
                    'name'     => 'hook_module',
                    'required' => true,
                    'options'  => [
                        'query' => $listHookPosition,
                        'id'    => 'hook_position',
                        'name'  => 'hook_position',
                    ],
                    'desc' => $t->trans('Choose the type of the Hooks', [], self::TRANS_DOMAIN),
                ],
            ],
            'submit' => [
                'title' => $t->trans('Save', [], self::TRANS_DOMAIN),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $t->trans('Shop association:', [], self::TRANS_DOMAIN),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        if (!($obj = $this->loadObject(true))) {
            unset($obj);
            return;
        }

        return parent::renderForm();
    }
}
