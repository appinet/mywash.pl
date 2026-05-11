<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2020 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Adapter\Translator;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Adapter\BestSales\BestSalesProductSearchProvider;

class WbPopupProducts extends Module
{
    public function __construct()
    {
        $this->name = 'wbpopupproducts';
        $this->tab = 'front_office_features';
        $this->version = '1.1.1';
        $this->author = 'Webibazaar';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName  = $this->l('Wb Popup Products');
        $this->description  = $this->l('Show Popup Products Bottom Left Side of Front Side.');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        Configuration::updateValue('WBPOPUPPRODUCTS_FAKE_MODE', 1);
        Configuration::updateValue('WBPOPUPPRODUCTS_UTM_URL_PARAMS', '');
        Configuration::updateValue('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE', ImageType::getFormattedName('cart'));
        Configuration::updateValue('WBPOPUPPRODUCTS_SECOND_IMG_HOVER', 1);
        Configuration::updateValue('WBPOPUPPRODUCTS_DISPLAY_TIME', '3000');
        Configuration::updateValue('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION', '2000');
        Configuration::updateValue('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE', 1);
        Configuration::updateValue('WBPOPUPPRODUCTS_CLOSE_BTN', 1);
        Configuration::updateValue('WBPOPUPPRODUCTS_FONT_SIZE', '14px');
        Configuration::updateValue('WBPOPUPPRODUCTS_BACKGROUND_COLOR', '#ffffff');
        Configuration::updateValue('WBPOPUPPRODUCTS_BORDER_COLOR', '#eeeeee');
        Configuration::updateValue('WBPOPUPPRODUCTS_BORDER_RADIUS', '5px');
        Configuration::updateValue('WBPOPUPPRODUCTS_SHADOW_COLOR', '#e8e8e8');
        Configuration::updateValue('WBPOPUPPRODUCTS_OPACITY', '80');
        Configuration::updateValue('WBPOPUPPRODUCTS_OPACITY_ON_HOVER', '100');
        Configuration::updateValue('WBPOPUPPRODUCTS_DESKTOP_POSITION', 'bottom-left');
        Configuration::updateValue('WBPOPUPPRODUCTS_MOBILE_POSITION', 'bottom-left');

        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'wbpopupproducts` ( 
            `id_wbpopupproducts` INT NOT NULL AUTO_INCREMENT ,
            `id_product` INT NOT NULL ,
            `status` CHAR(2) NOT NULL ,
            PRIMARY KEY (`id_wbpopupproducts`)) ENGINE = InnoDB');

        return parent::install()
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader');
    }

  
    public function uninstall()
    {
        Configuration::deleteByName('WBPOPUPPRODUCTS_FAKE_MODE');
        Configuration::deleteByName('WBPOPUPPRODUCTS_UTM_URL_PARAMS');
        Configuration::deleteByName('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE');
        Configuration::deleteByName('WBPOPUPPRODUCTS_SECOND_IMG_HOVER');
        Configuration::deleteByName('WBPOPUPPRODUCTS_DISPLAY_TIME');
        Configuration::deleteByName('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION');
        Configuration::deleteByName('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE');
        Configuration::deleteByName('WBPOPUPPRODUCTS_CLOSE_BTN');
        Configuration::deleteByName('WBPOPUPPRODUCTS_FONT_SIZE');
        Configuration::deleteByName('WBPOPUPPRODUCTS_BACKGROUND_COLOR');
        Configuration::deleteByName('WBPOPUPPRODUCTS_BORDER_COLOR');
        Configuration::deleteByName('WBPOPUPPRODUCTS_BORDER_RADIUS');
        Configuration::deleteByName('WBPOPUPPRODUCTS_SHADOW_COLOR');
        Configuration::deleteByName('WBPOPUPPRODUCTS_OPACITY');
        Configuration::deleteByName('WBPOPUPPRODUCTS_OPACITY_ON_HOVER');
        Configuration::deleteByName('WBPOPUPPRODUCTS_DESKTOP_POSITION');
        Configuration::deleteByName('WBPOPUPPRODUCTS_MOBILE_POSITION');

        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'wbpopupproducts`');
        return parent::uninstall();
    }

    public function maxId()
    {
        $query = 'SELECT MAX(id_wbpopupproducts) AS max_id  FROM `'._DB_PREFIX_.'wbpopupproducts`';
        $res = Db::getInstance()->executeS($query);
        $max_id = $res['0']['max_id'];
        return $max_id;
    }

    public function getSingleData($id)
    {
        $result = array();

        $result = Db::getInstance()->getRow('SELECT main.id_wbpopupproducts as id,
                main.id_product,
                main.status
            FROM 
                `'._DB_PREFIX_.'wbpopupproducts` main
            WHERE 
                main.id_wbpopupproducts = '.$id);
        return $result;
    }

    public function getFrontSideData()
    {
        $result = Db::getInstance()->executeS('SELECT main.id_wbpopupproducts as id,
                main.id_product,
                main.status
            FROM
                `'._DB_PREFIX_.'wbpopupproducts` main 
            WHERE main.status=1');

        $ans = array();
        $i = 0;
        foreach ($result as $r) {
            $ans[$i] = $r;
            $ans[$i]['ProdInfo'] = $this->getProductInfo($r['id_product']);
            $prodObj = new Product($r['id_product']);
            
            $params = array();
            if (!empty(Configuration::get('WBPOPUPPRODUCTS_UTM_URL_PARAMS'))) {
                $extraParams = explode("&", Configuration::get('WBPOPUPPRODUCTS_UTM_URL_PARAMS'));
                foreach ($extraParams as $p) {
                    $tmp_arr = explode('=', $p);
                    $params[$tmp_arr[0]] = $tmp_arr[1];
                }
            }
            $ans[$i]['custom_link'] = $this->context->link->getProductLink(
                $prodObj,
                null,
                null,
                null,
                null,
                null,
                null,
                false,
                false,
                false,
                $params
            );
            
            $i++;
        }

        return $ans;
    }

    public function showAdminData()
    {
        $path = _MODULE_DIR_.$this->name."/views/img/";
        $this->context->smarty->assign('path', $path);

        $allData = Db::getInstance()->executeS('SELECT main.id_wbpopupproducts as id,
            main.id_product,
            main.status
            FROM `'._DB_PREFIX_.'wbpopupproducts` main');

        $result = array();
        if (!empty($allData)) {
            $i = 0;
            foreach ($allData as $data) {
                $id_lang = $this->context->cookie->id_lang;
                $result[$i] = $data;
                $prodObj = new Product($data['id_product']);
                $result[$i]['name'] = $prodObj->name[$id_lang];
                $result[$i]['link'] = $this->context->link->getProductLink($prodObj);
                $i++;
            }
        }
        $this->context->smarty->assign('allData', $result);
        $output = $this->randerForm()
            .$this->display(__FILE__, 'views/templates/admin/display_data.tpl');

        return $output;
    }
   
    public function insertData($data)
    {
        $query = '';
        if (!empty($data['id'])) {
            $id = $data['id'];
            $query = 'UPDATE 
                    `'._DB_PREFIX_.'wbpopupproducts`     
                SET
                    id_product      = "'.$data['id_product'].'",
                    status          = "'.$data['status'].'"
                WHERE
                    id_wbpopupproducts = '.$id.';';
        } else {
            $id = $this->maxId();
            $id = $id + 1;
            $query = 'INSERT INTO 
                    `'._DB_PREFIX_.'wbpopupproducts`     
                SET 
                    id_wbpopupproducts  = '.$id.',
                    id_product          = "'.$data['id_product'].'",
                    status              = "'.$data['status'].'";';
        }

        if (Db::getInstance()->execute($query) == false) {
            return false;
        }
        return true;
    }

    public function getProductInfoData()
    {
        $return  = array();
        $id_lang = $this->context->cookie->id_lang;

        $return = Db::getInstance()->executeS('SELECT main.id_product as id,
                        lang.name
            FROM
                `'._DB_PREFIX_.'product` main
            INNER JOIN
                `'._DB_PREFIX_.'product_lang` lang
            ON
                lang.id_product = main.id_product
            WHERE
                lang.id_lang = '.$id_lang.'
            AND
                main.active = 1');

        $this->context->smarty->assign('product_list', $return);
    }

    public function getContent()
    {
        $output = $this->postProcess()
            .$this->showAdminData();
        return $output;
    }

    public function postProcess()
    {
        $messages = '';

        if (Tools::getValue('submitActionDelete')) {
            $id = Tools::getValue('id');
            $res = Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'wbpopupproducts` 
                WHERE id_wbpopupproducts='.$id);
            if ($res) {
                $messages .= $this->displayConfirmation($this->l("Record is Deleted."));
            } else {
                $messages .= $this->displayError($this->l("Record is not Deleted."));
            }
        }

        if (Tools::getValue('submitMainFormDetails')) {
            $tmp = Tools::getValue('WBPOPUPPRODUCTS_FAKE_MODE');
            Configuration::updateValue('WBPOPUPPRODUCTS_FAKE_MODE', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_UTM_URL_PARAMS');
            if (strstr($tmp, '=')) {
                Configuration::updateValue('WBPOPUPPRODUCTS_UTM_URL_PARAMS', $tmp);
            } else {
                if (!empty($tmp)) {
                    $messages .= $this->displayError($this->l("Please enter valid URL Params."));
                } else {
                    Configuration::updateValue('WBPOPUPPRODUCTS_UTM_URL_PARAMS', $tmp);
                }
            }

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE');
            Configuration::updateValue('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_SECOND_IMG_HOVER');
            Configuration::updateValue('WBPOPUPPRODUCTS_SECOND_IMG_HOVER', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_DISPLAY_TIME');
            Configuration::updateValue('WBPOPUPPRODUCTS_DISPLAY_TIME', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION');
            Configuration::updateValue('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE');
            Configuration::updateValue('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_CLOSE_BTN');
            Configuration::updateValue('WBPOPUPPRODUCTS_CLOSE_BTN', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_FONT_SIZE');
            Configuration::updateValue('WBPOPUPPRODUCTS_FONT_SIZE', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_BACKGROUND_COLOR');
            Configuration::updateValue('WBPOPUPPRODUCTS_BACKGROUND_COLOR', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_BORDER_COLOR');
            Configuration::updateValue('WBPOPUPPRODUCTS_BORDER_COLOR', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_BORDER_RADIUS');
            Configuration::updateValue('WBPOPUPPRODUCTS_BORDER_RADIUS', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_SHADOW_COLOR');
            Configuration::updateValue('WBPOPUPPRODUCTS_SHADOW_COLOR', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_OPACITY');
            Configuration::updateValue('WBPOPUPPRODUCTS_OPACITY', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_OPACITY_ON_HOVER');
            Configuration::updateValue('WBPOPUPPRODUCTS_OPACITY_ON_HOVER', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_DESKTOP_POSITION');
            Configuration::updateValue('WBPOPUPPRODUCTS_DESKTOP_POSITION', $tmp);

            $tmp = Tools::getValue('WBPOPUPPRODUCTS_MOBILE_POSITION');
            Configuration::updateValue('WBPOPUPPRODUCTS_MOBILE_POSITION', $tmp);

            $messages .= $this->displayConfirmation($this->l("Popup Products Setting Updated."));
        }

        if (Tools::getValue('submitRecordFormDetails')) {
            $insert_data = array();
            $insert_data['id'] = '';
            $data_store = true;

            if (Tools::getValue('id')) {
                $insert_data['id'] = Tools::getValue('id');
                $res = $this->getSingleData($insert_data['id']);
            }

            if ($data_store) {
                $insert_data['id_product'] = Tools::getValue('id_product');
                $insert_data['status'] = Tools::getValue('status');

                $tmp = Tools::getValue('id_product');
                $id_lang = $this->context->cookie->id_lang;
                $product = new Product($tmp, false, $id_lang);

                if (Validate::isLoadedObject($product)) {
                    $insert_data['id_product'] = Tools::getValue('id_product');
                    $this->insertData($insert_data);
                    if (!empty($insert_data['id'])) {
                        $messages .= $this->displayConfirmation($this->l("Record is Updated."));
                    } else {
                        $messages .= $this->displayConfirmation($this->l("Record is Inserted."));
                    }
                } else {
                    $messages .= $this->displayError($this->l("Please Choose valid Product Id."));
                }
            }
        }

        $this->customRemoveCache('wbblinkproduct_display_home.tpl');
        return $messages;
    }

    public function customRemoveCache($id_cache)
    {
        if (Cache::isStored($id_cache)) {
            Cache::clean($id_cache);
        }
    }

    public function randerForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : 0;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
            '&configure='.$this->name.
            '&tab_module='.$this->tab.
            '&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        $form = array();
        $form[] = $this->mainFormDetails();
        $form[] = $this->recordFormDetails();

        return $helper->generateForm($form);
    }

    public function mainFormDetails()
    {
        $input = array();
        $fields_form = array();

        $input[] = array(
                    'type' => 'switch',
                    'label' => $this->l('Fake Mode'),
                    'name' => 'WBPOPUPPRODUCTS_FAKE_MODE',
                    'is_bool' => true,
                    'desc' => $this->l('If its true then it show product with Fake Mode.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                );

        $input[] = array(
                    'type' => 'text',
                    'label' => $this->l('UTM url params'),
                    'name' => 'WBPOPUPPRODUCTS_UTM_URL_PARAMS',
                    'desc' => $this->l('Add Url Params in link. Keep it blank if you don\'t want to Add.'),
                );

        $img_list = ImageType::getImagesTypes();
        $arr = array();
        $i = 0;
        foreach ($img_list as $img) {
            $arr[$i]['id_option'] = $img['name'];
            $arr[$i]['name'] = $img['name'].' ('.$img['width'].'x'.$img['height'].')';
            $i++;
        }
        $input[] = array(
                'type' => 'select',
                'label' => $this->l('Product thumb size'),
                'name' => 'WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE',
                'desc' => $this->l('Select Product Thumb Size.'),
                'options' => array(
                    'query' => $arr,
                    'id' => 'id_option',
                    'name' => 'name',
                ),
            );

        $input[] = array(
                    'type' => 'switch',
                    'label' => $this->l('Display second product image on hover'),
                    'name' => 'WBPOPUPPRODUCTS_SECOND_IMG_HOVER',
                    'is_bool' => true,
                    'desc' => $this->l('If its true then it Second image on hover otherwise not.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                );

        $input[] = array(
                    'type' => 'text',
                    'label' => $this->l('Display time'),
                    'name' => 'WBPOPUPPRODUCTS_DISPLAY_TIME',
                    'desc' => $this->l('Add display time(in Micro Second) of Product.'),
                );

        $input[] = array(
                    'type' => 'text',
                    'label' => $this->l('Delay between notifications'),
                    'name' => 'WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION',
                    'desc' => $this->l('Add Delay time(in Micro Second) between notifications'),
                );

        $input[] = array(
                    'type' => 'switch',
                    'label' => $this->l('Display on mobile devices'),
                    'name' => 'WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE',
                    'is_bool' => true,
                    'desc' => $this->l('If its true then it show in mobile devide otherwise not.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                );

        $input[] = array(
                    'type' => 'switch',
                    'label' => $this->l('Enable close button'),
                    'name' => 'WBPOPUPPRODUCTS_CLOSE_BTN',
                    'is_bool' => true,
                    'desc' => $this->l('If its true then it show Close Button otherwise not.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                );

        $input[] = array(
                'type' => 'select',
                'label' => $this->l('Font Size'),
                'name' => 'WBPOPUPPRODUCTS_FONT_SIZE',
                'desc' => $this->l('Select Font size of Title.'),
                'options' => array(
                    'query' => array(
                        array('id_option' => '11px', 'name' => '11px'),
                        array('id_option' => '12px', 'name' => '12px'),
                        array('id_option' => '13px', 'name' => '13px'),
                        array('id_option' => '14px', 'name' => '14px'),
                        array('id_option' => '15px', 'name' => '15px'),
                        array('id_option' => '16px', 'name' => '16px'),
                        array('id_option' => '17px', 'name' => '17px'),
                        array('id_option' => '18px', 'name' => '18px'),
                        array('id_option' => '19px', 'name' => '19px'),
                        array('id_option' => '20px', 'name' => '20px'),
                    ),
                    'id' => 'id_option',
                    'name' => 'name',
                ),
            );

        $input[] = array(
                    'type' => 'color',
                    'label' => $this->l('Background color'),
                    'name' => 'WBPOPUPPRODUCTS_BACKGROUND_COLOR',
                    'desc' => $this->l('Add Background Color of Popup Products.'),
                );

        $input[] = array(
                    'type' => 'color',
                    'label' => $this->l('Border color'),
                    'name' => 'WBPOPUPPRODUCTS_BORDER_COLOR',
                    'desc' => $this->l('Add Border Color of Popup Products.'),
                );


        $input[] = array(
                'type' => 'select',
                'label' => $this->l('Border radius'),
                'name' => 'WBPOPUPPRODUCTS_BORDER_RADIUS',
                'desc' => $this->l('Add Border Color Radius of Popup Products.'),
                'options' => array(
                    'query' => array(
                        array('id_option' => '1px', 'name' => '1px'),
                        array('id_option' => '2px', 'name' => '2px'),
                        array('id_option' => '3px', 'name' => '3px'),
                        array('id_option' => '4px', 'name' => '4px'),
                        array('id_option' => '5px', 'name' => '5px'),
                        array('id_option' => '6px', 'name' => '6px'),
                        array('id_option' => '7px', 'name' => '7px'),
                        array('id_option' => '8px', 'name' => '8px'),
                        array('id_option' => '9px', 'name' => '9px'),
                        array('id_option' => '10px', 'name' => '10px'),
                        array('id_option' => '11px', 'name' => '11px'),
                        array('id_option' => '12px', 'name' => '12px'),
                        array('id_option' => '13px', 'name' => '13px'),
                        array('id_option' => '14px', 'name' => '14px'),
                        array('id_option' => '15px', 'name' => '15px'),
                        array('id_option' => '16px', 'name' => '16px'),
                        array('id_option' => '17px', 'name' => '17px'),
                        array('id_option' => '18px', 'name' => '18px'),
                        array('id_option' => '19px', 'name' => '19px'),
                        array('id_option' => '20px', 'name' => '20px'),
                    ),
                    'id' => 'id_option',
                    'name' => 'name',
                ),
            );



        $input[] = array(
                    'type' => 'color',
                    'label' => $this->l('Shadow color'),
                    'name' => 'WBPOPUPPRODUCTS_SHADOW_COLOR',
                    'desc' => $this->l('Add Shadow Color of Popup Products.'),
                );

        $input[] = array(
                    'type' => 'text',
                    'label' => $this->l('Opacity'),
                    'name' => 'WBPOPUPPRODUCTS_OPACITY',
                    'desc' => $this->l('Add Opacity of Popup Products(0 To 100).'),
                );

        $input[] = array(
                    'type' => 'text',
                    'label' => $this->l('Opacity on hover'),
                    'name' => 'WBPOPUPPRODUCTS_OPACITY_ON_HOVER',
                    'desc' => $this->l('Add Opacity on Hover of Popup Products(0 To 100).'),
                );

        $input[] = array(
                'type' => 'select',
                'label' => $this->l('Desktop Position'),
                'name' => 'WBPOPUPPRODUCTS_DESKTOP_POSITION',
                'desc' => $this->l('Select Desktop Position of Popup Products.'),
                'options' => array(
                    'query' => array(
                        array('id_option' => 'top-left', 'name' => 'Top Left'),
                        array('id_option' => 'top-right', 'name' => 'Top Right'),
                        array('id_option' => 'bottom-left', 'name' => 'Bottom Left'),
                        array('id_option' => 'bottom-right', 'name' => 'Bottom Right'),
                    ),
                    'id' => 'id_option',
                    'name' => 'name',
                ),
            );


        $input[] = array(
                'type' => 'select',
                'label' => $this->l('Mobile Position'),
                'name' => 'WBPOPUPPRODUCTS_MOBILE_POSITION',
                'desc' => $this->l('Select Mobile Position of Popup Products.'),
                'options' => array(
                    'query' => array(
                        array('id_option' => 'top-left', 'name' => 'Top Left'),
                        array('id_option' => 'top-right', 'name' => 'Top Right'),
                        array('id_option' => 'bottom-left', 'name' => 'Bottom Left'),
                        array('id_option' => 'bottom-right', 'name' => 'Bottom Right'),
                    ),
                    'id' => 'id_option',
                    'name' => 'name',
                ),
            );




        $fields_form['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Main Information'),
            ),
            'input' => $input,
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'submitMainFormDetails',
                'class' => 'btn btn-default pull-right',
            )
        );

        return $fields_form;
    }

    public function recordFormDetails()
    {
        $fields_form = array();
        // Record Form Information
        $input = array();
        if (Tools::getValue('submitActionEdit')) {
            $input[] = array(
                    'type' => 'hidden',
                    'name' => 'id',
                    'label' => $this->l('id'),
                );
        }

        $input[] = array(
                    'type' => 'custom_search',
                    'label' => $this->l('Product Id'),
                    'name' => 'id_product',
                    'desc' => $this->l('Enter Product Id to show Popup Product'),
                );

        $input[] = array(
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'status',
                    'is_bool' => true,
                    'desc' => $this->l('If its true then show in front side otherwise not.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                );


        $fields_form['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Record Information'),
            ),
            'input' => $input,
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'submitRecordFormDetails',
                'class' => 'btn btn-default pull-right',
            )
        );

        return $fields_form;
    }

    public function getConfigFieldsValues()
    {
        $result = array();

        $result['WBPOPUPPRODUCTS_FAKE_MODE'] = Configuration::get('WBPOPUPPRODUCTS_FAKE_MODE');
        $result['WBPOPUPPRODUCTS_UTM_URL_PARAMS'] = Configuration::get('WBPOPUPPRODUCTS_UTM_URL_PARAMS');
        $result['WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE'] = Configuration::get('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE');
        $result['WBPOPUPPRODUCTS_SECOND_IMG_HOVER'] = Configuration::get('WBPOPUPPRODUCTS_SECOND_IMG_HOVER');
        $result['WBPOPUPPRODUCTS_DISPLAY_TIME'] = Configuration::get('WBPOPUPPRODUCTS_DISPLAY_TIME');
        $tmp = Configuration::get('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION');
        $result['WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION'] = $tmp;
        $result['WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE'] = Configuration::get('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE');
        $result['WBPOPUPPRODUCTS_CLOSE_BTN'] = Configuration::get('WBPOPUPPRODUCTS_CLOSE_BTN');
        $result['WBPOPUPPRODUCTS_FONT_SIZE'] = Configuration::get('WBPOPUPPRODUCTS_FONT_SIZE');
        $result['WBPOPUPPRODUCTS_BACKGROUND_COLOR'] = Configuration::get('WBPOPUPPRODUCTS_BACKGROUND_COLOR');
        $result['WBPOPUPPRODUCTS_BORDER_COLOR'] = Configuration::get('WBPOPUPPRODUCTS_BORDER_COLOR');
        $result['WBPOPUPPRODUCTS_BORDER_RADIUS'] = Configuration::get('WBPOPUPPRODUCTS_BORDER_RADIUS');
        $result['WBPOPUPPRODUCTS_SHADOW_COLOR'] = Configuration::get('WBPOPUPPRODUCTS_SHADOW_COLOR');
        $result['WBPOPUPPRODUCTS_OPACITY'] = Configuration::get('WBPOPUPPRODUCTS_OPACITY');
        $result['WBPOPUPPRODUCTS_OPACITY_ON_HOVER'] = Configuration::get('WBPOPUPPRODUCTS_OPACITY_ON_HOVER');
        $result['WBPOPUPPRODUCTS_DESKTOP_POSITION'] = Configuration::get('WBPOPUPPRODUCTS_DESKTOP_POSITION');
        $result['WBPOPUPPRODUCTS_MOBILE_POSITION'] = Configuration::get('WBPOPUPPRODUCTS_MOBILE_POSITION');

        $result['id_product'] = '0';
        $result['status'] = 1;
        
        $this->getProductInfoData();
        if (Tools::getValue('submitActionEdit')) {
            $id = Tools::getValue('id');
            $data = $this->getSingleData($id);
            $result['id'] = $data['id'];
            $result['id_product'] = $data['id_product'];
            $result['status'] = $data['status'];
        }

        return $result;
    }

    public function hookdisplayBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path.'views/js/back.js');
        $this->context->controller->addJS($this->_path.'views/js/select2.full.min.js');
        $this->context->controller->addCSS($this->_path.'views/css/back.css');
        $this->context->controller->addCSS($this->_path.'views/css/select2.min.css');
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->addJS(_PS_JS_DIR_.'jquery/jquery-1.11.0.min.js');
        $this->context->controller->addJS(_PS_JS_DIR_.'jquery/jquery-migrate-1.2.1.min.js');

        $this->context->controller->addJqueryPlugin('fancybox');

        $this->context->controller->addJS($this->_path.'views/js/front.js');
        $this->context->controller->addCSS($this->_path.'views/css/front.css');

        $popup_close_link = $this->context->link->getModuleLink('wbpopupproducts', 'default');
        Media::addJsDef(array('popup_close_link' => $popup_close_link));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_FAKE_MODE');
        Media::addJsDef(array('WBPOPUPPRODUCTS_FAKE_MODE' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_UTM_URL_PARAMS');
        Media::addJsDef(array('WBPOPUPPRODUCTS_UTM_URL_PARAMS' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE');
        Media::addJsDef(array('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_SECOND_IMG_HOVER');
        Media::addJsDef(array('WBPOPUPPRODUCTS_SECOND_IMG_HOVER' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_DISPLAY_TIME');
        Media::addJsDef(array('WBPOPUPPRODUCTS_DISPLAY_TIME' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION');
        Media::addJsDef(array('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE');
        Media::addJsDef(array('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_CLOSE_BTN');
        Media::addJsDef(array('WBPOPUPPRODUCTS_CLOSE_BTN' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_FONT_SIZE');
        Media::addJsDef(array('WBPOPUPPRODUCTS_FONT_SIZE' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_BACKGROUND_COLOR');
        Media::addJsDef(array('WBPOPUPPRODUCTS_BACKGROUND_COLOR' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_BORDER_COLOR');
        Media::addJsDef(array('WBPOPUPPRODUCTS_BORDER_COLOR' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_BORDER_RADIUS');
        Media::addJsDef(array('WBPOPUPPRODUCTS_BORDER_RADIUS' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_SHADOW_COLOR');
        Media::addJsDef(array('WBPOPUPPRODUCTS_SHADOW_COLOR' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_OPACITY');
        Media::addJsDef(array('WBPOPUPPRODUCTS_OPACITY' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_OPACITY_ON_HOVER');
        Media::addJsDef(array('WBPOPUPPRODUCTS_OPACITY_ON_HOVER' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_DESKTOP_POSITION');
        Media::addJsDef(array('WBPOPUPPRODUCTS_DESKTOP_POSITION' => $tmp));

        $tmp = Configuration::get('WBPOPUPPRODUCTS_MOBILE_POSITION');
        Media::addJsDef(array('WBPOPUPPRODUCTS_MOBILE_POSITION' => $tmp));
    }

    public function getProductInfo($id_prod)
    {
        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $product = array();

        $presentationSettings->showPrices = 1;

        $product[] = $presenter->present(
            $presentationSettings,
            $assembler->assembleProduct(array('id_product' => $id_prod)),
            $this->context->language
        );

        return $product[0];
    }

    public function hookdisplayBeforeBodyClosingTag()
    {
        if (!Cache::isStored('wbblinkproduct_display_home.tpl')) {
            $output = '';
            if ($this->context->cookie->wbpopupproducts != 1) {
                $arr_data = $this->getFrontSideData();
                $this->context->smarty->assign('arr_data', $arr_data);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_FAKE_MODE');
                $this->context->smarty->assign('FAKE_MODE', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_UTM_URL_PARAMS');
                $this->context->smarty->assign('UTM_URL_PARAMS', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_PRODUCT_THUMB_SIZE');
                $this->context->smarty->assign('PRODUCT_THUMB_SIZE', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_SECOND_IMG_HOVER');
                $this->context->smarty->assign('SECOND_IMG_HOVER', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_DISPLAY_TIME');
                $this->context->smarty->assign('DISPLAY_TIME', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_DELAY_TIME_BETWEEN_NOTIFICATION');
                $this->context->smarty->assign('DELAY_TIME_BETWEEN_NOTIFICATION', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_DISPLAY_ON_MOBILE');
                $this->context->smarty->assign('DISPLAY_ON_MOBILE', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_CLOSE_BTN');
                $this->context->smarty->assign('CLOSE_BTN', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_FONT_SIZE');
                $this->context->smarty->assign('FONT_SIZE', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_BACKGROUND_COLOR');
                $this->context->smarty->assign('BACKGROUND_COLOR', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_BORDER_COLOR');
                $this->context->smarty->assign('BORDER_COLOR', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_BORDER_RADIUS');
                $this->context->smarty->assign('BORDER_RADIUS', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_SHADOW_COLOR');
                $this->context->smarty->assign('SHADOW_COLOR', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_OPACITY') / 100;
                $this->context->smarty->assign('OPACITY', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_OPACITY_ON_HOVER')/100;
                $this->context->smarty->assign('OPACITY_ON_HOVER', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_DESKTOP_POSITION');
                $this->context->smarty->assign('DESKTOP_POSITION', $tmp);

                $tmp = Configuration::get('WBPOPUPPRODUCTS_MOBILE_POSITION');
                $this->context->smarty->assign('MOBILE_POSITION', $tmp);


                $output = $this->display(__FILE__, 'views/templates/hook/display_home.tpl');
            }
            Cache::store('wbblinkproduct_display_home.tpl', $output);
        }
        return Cache::retrieve('wbblinkproduct_display_home.tpl');
    }
}
