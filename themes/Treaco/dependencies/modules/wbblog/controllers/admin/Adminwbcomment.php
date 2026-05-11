<?php
/**
* 2007-2018 PrestaShop
* NOTICE OF LICENSE
* ...
*/

class AdminwbcommentController extends ModuleAdminController
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
        $this->table = 'wb_comments';
        $this->className = 'wbcommentclass';
        $this->deleted = false;

        // Do NOT assign a string to $this->module; keep Module instance from parent.
        // $this->module = 'wbblog'; // removed

        $this->allow_export = false;
        $this->_defaultOrderWay = 'DESC';
        $this->bootstrap = true;

        parent::__construct();

        $t = $this->context->getTranslator();

        $this->fields_list = [
            'id_wb_comments' => [
                'title' => $t->trans('ID', [], self::TRANS_DOMAIN),
                'width' => 100,
                'type'  => 'text',
            ],
            'id_post' => [
                'title' => $t->trans('Post ID', [], self::TRANS_DOMAIN),
                'width' => 100,
                'type'  => 'text',
            ],
            'name' => [
                'title' => $t->trans('Name', [], self::TRANS_DOMAIN),
                'width' => 60,
                'type'  => 'text',
            ],
            'subject' => [
                'title' => $t->trans('Subject', [], self::TRANS_DOMAIN),
                'width' => 220,
                'type'  => 'text',
            ],
            'content' => [
                'title' => $t->trans('Comment', [], self::TRANS_DOMAIN),
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

    public function renderList()
    {
        // Add row actions if you want inline moderation:
        // $this->addRowAction('edit');
        // $this->addRowAction('delete');
        return parent::renderList();
    }
}
