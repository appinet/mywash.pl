<?php
/**
* 2007-2018 PrestaShop
* NOTICE OF LICENSE
* ...
*/

class AdminwbcategoryController extends ModuleAdminController
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
        $this->table = 'wbcategory';
        $this->className = 'wbcategoryclass';
        $this->lang = true;
        $this->deleted = false;

        // Do NOT assign a string to $this->module; keep the Module instance set by parent.
        // $this->module = 'wbblog'; // removed

        $this->explicitSelect = true;
        $this->_defaultOrderBy = 'position';
        $this->allow_export = false;
        $this->_defaultOrderWay = 'DESC';
        $this->bootstrap = true;

        if (Shop::isFeatureActive()) {
            Shop::addTableAssociation($this->table, ['type' => 'shop']);
        }

        parent::__construct();

        $t = $this->context->getTranslator();

        $this->fields_list = [
            'id_wbcategory' => [
                'title' => $t->trans('ID', [], self::TRANS_DOMAIN),
                'width' => 100,
                'type'  => 'text',
            ],
            'name' => [
                'title' => $t->trans('Category Name', [], self::TRANS_DOMAIN),
                'width' => 60,
                'type'  => 'text',
            ],
            'link_rewrite' => [
                'title' => $t->trans('URL Rewrite', [], self::TRANS_DOMAIN),
                'width' => 220,
                'type'  => 'text',
            ],
            'position' => [
                'title'    => $t->trans('Position', [], self::TRANS_DOMAIN),
                'align'    => 'left',
                'position' => 'position',
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

        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'wbcategory_shop sbp
            ON a.id_wbcategory = sbp.id_wbcategory && sbp.id_shop IN ('.implode(',', Shop::getContextListShopID()).')';

        // Keep both selected columns in a single _select:
        $this->_select = 'sbp.id_shop, a.position position';

        $this->_defaultOrderBy = 'a.position';
        $this->_defaultOrderWay = 'DESC';

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->_group = 'GROUP BY a.id_wbcategory';
        }

        $this->_where = ' AND a.category_type = "category" ';
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
        $this->addJqueryPlugin('select2');
    }

    public function renderForm()
    {
        $t = $this->context->getTranslator();

        $id_wbcategory = Tools::getValue('id_wbcategory');
        $category_img_temp = '';

        if (!empty($id_wbcategory)) {
            $wbcategoryclass = new WbCategoryClass($id_wbcategory);
            if (!empty($wbcategoryclass->category_img)) {
                $category_img_temp = '<img src="'.WBBLOG_IMG_URI.$wbcategoryclass->category_img.'" height="110" width="auto"><br>';
            }
        }

        $this->fields_form = [
            'legend' => [
                'title' => $t->trans('Wb Blog Category', [], self::TRANS_DOMAIN),
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'category_type',
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Category Name', [], self::TRANS_DOMAIN),
                    'name'  => 'name',
                    'id'    => 'name', // for copyMeta2friendlyURL compatibility
                    'class' => 'copyMeta2friendlyURL',
                    'desc'  => $t->trans('Enter Your Category Name', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $t->trans('Category Description', [], self::TRANS_DOMAIN),
                    'name'         => 'description',
                    'autoload_rte' => true,
                    'rows'         => 5,
                    'cols'         => 40,
                    'lang'         => true,
                    'desc'         => $t->trans('Please Enter Category Description', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'file',
                    'label' => $t->trans('Category Feature Image', [], self::TRANS_DOMAIN),
                    'name'  => 'category_img',
                    'desc'  => $category_img_temp.$t->trans('Please upload category feature image from your computer.', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'select',
                    'label' => $t->trans('Select Category Group', [], self::TRANS_DOMAIN),
                    'name'  => 'category_group',
                    'options' => [
                        'query' => wbcategoryclass::serializeCategory(),
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Meta Title', [], self::TRANS_DOMAIN),
                    'name'  => 'title',
                    'desc'  => $t->trans('Enter Your Category Meta Title for SEO', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'  => 'textarea',
                    'label' => $t->trans('Meta Description', [], self::TRANS_DOMAIN),
                    'name'  => 'meta_description',
                    'desc'  => $t->trans('Enter Your Category Meta Description for SEO', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'  => 'tags',
                    'label' => $t->trans('Meta Keyword', [], self::TRANS_DOMAIN),
                    'name'  => 'keyword',
                    'desc'  => $t->trans('Enter Your Category Meta Keyword for SEO. Seperate by comma(,)', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('URL Rewrite', [], self::TRANS_DOMAIN),
                    'name'  => 'link_rewrite',
                    'desc'  => $t->trans('Enter Your Category URL for SEO URL', [], self::TRANS_DOMAIN),
                    'lang'  => true,
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

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $t->trans('Shop association:', [], self::TRANS_DOMAIN),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        if (!($wbcategoryclass = $this->loadObject(true))) {
            return;
        }

        if (!empty($wbcategoryclass->category_type)) {
            $this->fields_value['category_type'] = $wbcategoryclass->category_type;
        } else {
            $this->fields_value['category_type'] = 'category';
        }

        $this->tpl_form_vars = [
            'active' => $this->object->active,
            'PS_ALLOW_ACCENTED_CHARS_URL' => (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'),
        ];

        Media::addJsDef([
            'PS_ALLOW_ACCENTED_CHARS_URL' => (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'),
        ]);

        return parent::renderForm();
    }

    public function renderList()
    {
        if (isset($this->_filter) && trim($this->_filter) === '') {
            $this->_filter = $this->original_filter;
        }
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        return parent::renderList();
    }

    public function initToolbar()
    {
        parent::initToolbar();
    }

    public function processPosition()
    {
        if ($this->tabAccess['edit'] !== '1') {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        } elseif (!Validate::isLoadedObject(
            $object = new WbCategoryClass(
                (int) Tools::getValue(
                    $this->identifier,
                    Tools::getValue('id_wbcategory', 1)
                )
            )
        )) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.
                $this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        if (isset($object) && !$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } elseif (isset($object)) {
            $object->regenerateEntireNtree();
            Tools::redirectAdmin(
                self::$currentIndex
                .'&'.$this->table.'Orderby=position'
                .'&'.$this->table.'Orderway=asc'
                .'&conf=5'
                .(($id_wbcategory = (int) Tools::getValue($this->identifier)) ? ('&'.$this->identifier.'='.$id_wbcategory) : '')
                .'&token='.Tools::getAdminTokenLite('Adminwbcategory')
            );
        }
    }

    public function ajaxProcessUpdatePositions()
    {
        $id_wbcategory = (int) Tools::getValue('id');
        $way = (int) Tools::getValue('way');
        $positions = Tools::getValue($this->table);

        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $pos = explode('_', $value);
                if ((isset($pos[1], $pos[2])) && ((int) $pos[2] === $id_wbcategory)) {
                    $position = $key + 1;
                    break;
                }
            }

            $wbcategoryclass = new WbCategoryClass($id_wbcategory);
            if (Validate::isLoadedObject($wbcategoryclass)) {
                if (isset($position) && $wbcategoryclass->updatePosition($way, (int) $position)) {
                    Hook::exec('action'.$this->className.'Update');
                    die(true);
                } else {
                    die('{"hasError": true, "errors": "Can not update wbcategoryclass position"}');
                }
            } else {
                die('{"hasError": true, "errors": "This wbcategoryclass can not be loaded"}');
            }
        }
    }
}
