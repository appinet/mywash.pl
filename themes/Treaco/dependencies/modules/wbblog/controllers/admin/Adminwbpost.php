<?php
/**
* 2007-2018 PrestaShop
* NOTICE OF LICENSE
* ...
*/

class AdminwbpostController extends ModuleAdminController
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
        $this->table = 'wbposts';
        $this->className = 'wbPostsclass';
        $this->lang = true;
        $this->deleted = false;

        // NOTE: ModuleAdminController normally assigns $this->module (Module instance).
        // Avoid overriding it with a string; keep your module name implicit for translations.
        // $this->module = 'wbblog'; // <-- removed on purpose

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
            'id_wbposts' => [
                'title' => $t->trans('Id', [], self::TRANS_DOMAIN),
                'width' => 100,
                'type'  => 'text',
            ],
            'post_title' => [
                'title' => $t->trans('Post Title', [], self::TRANS_DOMAIN),
                'width' => 60,
                'type'  => 'text',
            ],
            'post_excerpt' => [
                'title' => $t->trans('Excerpt', [], self::TRANS_DOMAIN),
                'width' => 220,
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
                'width'   => 60,
                'align'   => 'center',
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

        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'wbposts_shop sbp 
        ON a.id_wbposts = sbp.id_wbposts && sbp.id_shop IN ('.implode(',', Shop::getContextListShopID()).')';

        // If you intended both columns, keep them in a single _select:
        $this->_select = 'sbp.id_shop, a.position position';

        $this->_defaultOrderBy = 'a.position';
        $this->_defaultOrderWay = 'DESC';
        $this->_where = ' AND a.post_type = "post" ';

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->_group = 'GROUP BY a.id_wbposts';
        }
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

        $id_wbposts   = Tools::getValue('id_wbposts');
        $audio_temp   = '';
        $video_temp   = '';
        $gallery_temp = '';
        $gallery_temp_str = '';
        $post_img_temp = '';

        if ($id_wbposts) {
            $wbpostsclass = new wbPostsClass($id_wbposts);
            if (!empty($wbpostsclass->audio)) {
                $audio_temp = @explode(',', $wbpostsclass->audio);
            }
            if (!empty($wbpostsclass->video)) {
                $video_temp = @explode(',', $wbpostsclass->video);
            }
            if (!empty($wbpostsclass->gallery)) {
                $gallery_temp = @explode(',', $wbpostsclass->gallery);
                $gallery_temp_str = $wbpostsclass->gallery;
            }
            if (!empty($wbpostsclass->post_img)) {
                $post_img_temp = '<img src="'.WBBLOG_IMG_URI.$wbpostsclass->post_img.'" height="110" width="auto"><br>';
            }
        }

        $this->fields_form = [
            'legend' => [
                'title' => $t->trans('Add New Post', [], self::TRANS_DOMAIN),
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'post_type',
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Post Title', [], self::TRANS_DOMAIN),
                    'name'  => 'post_title',
                    'id'    => 'name', // for copyMeta2friendlyURL compatibility
                    'class' => 'copyMeta2friendlyURL',
                    'desc'  => $t->trans('Enter Your Blog Post Title', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'     => 'radio',
                    'label'    => $t->trans('Post Format', [], self::TRANS_DOMAIN),
                    'name'     => 'post_format',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'standrad',
                            'value' => 'standrad',
                            'label' => $t->trans('Standrad', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'gallery',
                            'value' => 'gallery',
                            'label' => $t->trans('Gallery', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'video',
                            'value' => 'video',
                            'label' => $t->trans('Video', [], self::TRANS_DOMAIN),
                        ],
                        [
                            'id'    => 'audio',
                            'value' => 'audio',
                            'label' => $t->trans('Audio', [], self::TRANS_DOMAIN),
                        ],
                    ],
                ],
                [
                    'type'  => 'textarea',
                    'label' => $t->trans('Post Excerpt', [], self::TRANS_DOMAIN),
                    'name'  => 'post_excerpt',
                    'desc'  => $t->trans('Enter Your Blog Post Excerpt', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $t->trans('Post Content', [], self::TRANS_DOMAIN),
                    'name'         => 'post_content',
                    'desc'         => $t->trans('Enter Your Blog Post Content', [], self::TRANS_DOMAIN),
                    'lang'         => true,
                    'autoload_rte' => true,
                ],
                [
                    'type'  => 'file',
                    'label' => $t->trans('Post Feature Image', [], self::TRANS_DOMAIN),
                    'name'  => 'post_img',
                    'desc'  => $post_img_temp.$t->trans('Please Upload Feature Image From Your Computer.', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'select',
                    'label' => $t->trans('Select Default Category', [], self::TRANS_DOMAIN),
                    'name'  => 'category_def',
                    'options' => [
                        'query' => wbcategoryclass::serializeCategory(),
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'  => 'select',
                    'label' => $t->trans('Select Related Products', [], self::TRANS_DOMAIN),
                    'name'  => 'related_products',
                    'options' => [
                        'query' => self::getallproducts(),
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('Meta Title', [], self::TRANS_DOMAIN),
                    'name'  => 'meta_title',
                    'desc'  => $t->trans('Enter Your Post Meta Title for SEO', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'  => 'tags',
                    'label' => $t->trans('Meta Tag', [], self::TRANS_DOMAIN),
                    'name'  => 'meta_tag',
                    'desc'  => $t->trans('Enter Your Post Meta Tag. Seperate by comma(,)', [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'textarea',
                    'label' => $t->trans('Meta Description', [], self::TRANS_DOMAIN),
                    'name'  => 'meta_description',
                    'desc'  => $t->trans('Enter Your Post Meta Description for SEO', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'  => 'tags',
                    'label' => $t->trans('Meta Keyword', [], self::TRANS_DOMAIN),
                    'name'  => 'meta_keyword',
                    'desc'  => $t->trans('Enter Your Post Meta Keyword for SEO. Seperate by comma(,)', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'  => 'text',
                    'label' => $t->trans('URL Rewrite', [], self::TRANS_DOMAIN),
                    'name'  => 'link_rewrite',
                    'desc'  => $t->trans('Enter Your Post Url for SEO', [], self::TRANS_DOMAIN),
                    'lang'  => true,
                ],
                [
                    'type'     => 'gallery',
                    'label'    => $t->trans('Gallery', [], self::TRANS_DOMAIN),
                    'name'     => 'gallery_temp',
                    'defaults' => $gallery_temp,
                    'defaults_str' => $gallery_temp_str,
                    'url'      => WBBLOG_IMG_URI,
                    'desc'     => $t->trans("Please give Image url for Gallery post.
                        seperate by comma(,). You can add Any Kind of Image URL.", [], self::TRANS_DOMAIN),
                ],
                [
                    'type'     => 'text_multiple',
                    'label'    => $t->trans('Video', [], self::TRANS_DOMAIN),
                    'name'     => 'video_temp',
                    'defaults' => $video_temp,
                    'desc'     => $t->trans("Please give video irame url for video post.
                        seperate by comma(,). You can add youtube or vimeo video url.", [], self::TRANS_DOMAIN),
                ],
                [
                    'type'     => 'text_multiple',
                    'label'    => $t->trans('Audio', [], self::TRANS_DOMAIN),
                    'name'     => 'audio_temp',
                    'defaults' => $audio_temp,
                    'desc'     => $t->trans("Please give Audio url for Audio post.
                        seperate by comma(,). You can add any kind of anudio sourch.", [], self::TRANS_DOMAIN),
                ],
                [
                    'type'  => 'select',
                    'label' => $t->trans('Comment Status', [], self::TRANS_DOMAIN),
                    'name'  => 'comment_status',
                    'options' => [
                        'query' => [
                            ['id' => 'open',    'name' => $t->trans('Open', [], self::TRANS_DOMAIN)],
                            ['id' => 'close',   'name' => $t->trans('Closed', [], self::TRANS_DOMAIN)],
                            ['id' => 'disable', 'name' => $t->trans('Disabled', [], self::TRANS_DOMAIN)],
                        ],
                        'id'   => 'id',
                        'name' => 'name',
                    ],
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

        if (!($wbpostsclass = $this->loadObject(true))) {
            return;
        }

        $this->setdefaultvalue($wbpostsclass);

        $this->fields_form['submit'] = [
            'title' => $t->trans('Save', [], self::TRANS_DOMAIN),
            'class' => 'btn btn-default pull-right',
        ];

        $this->tpl_form_vars = [
            'active' => $this->object->active,
            'PS_ALLOW_ACCENTED_CHARS_URL' => (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL'),
        ];

        Media::addJsDef(['PS_ALLOW_ACCENTED_CHARS_URL' => (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')]);

        return parent::renderForm();
    }

    public function setdefaultvalue($obj)
    {
        $this->fields_value['post_type']   = !empty($obj->post_type)   ? $obj->post_type   : 'post';
        $this->fields_value['post_format'] = !empty($obj->post_format) ? $obj->post_format : 'standrad';
        $this->fields_value['active']      = isset($obj->active)       ? $obj->active      : 1;

        if (!empty($obj->id)) {
            $this->fields_value['meta_tag'] = wbPostsClass::getPostTags($obj->id);
        } else {
            $this->fields_value['meta_tag'] = '';
        }
    }

    public function renderList()
    {
        if (isset($this->_filter) && trim($this->_filter) == '') {
            $this->_filter = $this->original_filter;
        }
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        return parent::renderList();
    }

    public static function getallproducts()
    {
        $rslt = [];
        $rslt[0]['id'] = 0;
        $rslt[0]['name'] = 'Select Products';

        $id_lang = (int) Context::getContext()->language->id;

        $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                  ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                WHERE pl.`id_lang` = '.(int) $id_lang.'
                ORDER BY pl.`name`';

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($products) {
            $i = 1;
            foreach ($products as $r) {
                $rslt[$i]['id'] = (int) $r['id_product'];
                $rslt[$i]['name'] = $r['name'];
                $i++;
            }
        }
        return $rslt;
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
            $object = new wbPostsClass(
                (int) Tools::getValue(
                    $this->identifier,
                    Tools::getValue('id_wbposts', 1)
                )
            )
        )) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.
                $this->table.'</b> '.Tools::displayError('(cannot load object)');
        }

        if (!$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
            $this->errors[] = Tools::displayError('Failed to update the position.');
        } else {
            $object->regenerateEntireNtree();
            Tools::redirectAdmin(self::$currentIndex.'&'.$this->table.'Orderby=position&'.$this->table.'Orderway=asc&conf=5'
                .(($id_wbposts = (int) Tools::getValue($this->identifier)) ? ('&'.$this->identifier.'='.$id_wbposts) : '')
                .'&token='.Tools::getAdminTokenLite('Adminwbcategory'));
        }
    }

    public function ajaxProcessUpdatePositions()
    {
        $id_wbposts = (int) Tools::getValue('id');
        $way        = (int) Tools::getValue('way');
        $positions  = Tools::getValue($this->table);

        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $pos = explode('_', $value);
                if ((isset($pos[1], $pos[2])) && ((int) $pos[2] === $id_wbposts)) {
                    $position = $key + 1;
                    break;
                }
            }
        }

        $wbpostsclass = new wbPostsClass($id_wbposts);
        if (Validate::isLoadedObject($wbpostsclass)) {
            if (isset($position) && $wbpostsclass->updatePosition($way, (int) $position)) {
                Hook::exec('action'.$this->className.'Update');
                die(true);
            } else {
                die('{"hasError" : true, "errors" : "Can not update wbpostsclass position"}');
            }
        } else {
            die('{"hasError" : true, "errors" : "This wbpostsclass can not be loaded"}');
        }
    }
}
