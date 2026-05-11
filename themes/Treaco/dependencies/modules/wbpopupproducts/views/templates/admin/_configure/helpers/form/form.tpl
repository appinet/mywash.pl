{**
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
*}
{strip}
{extends file="helpers/form/form.tpl"}
{block name="input"}
    {if $input.type =="custom_search"}
        <div class="col-lg-9">
            <div class="form-group">
                <div class="col-lg-9">
                    <div class="dummyfile input-group">
                        <select class='make-custom-select-box' id="{$input.name|escape:'htmlall':'UTF-8'}" name="{$input.name|escape:'htmlall':'UTF-8'}">
                            <option value="0" {if $fields_value[$input.name] == '0'}selected="selected"{/if}>{l s='Please Select Product.' mod='wbpopupproducts'}</option>
                            {foreach $product_list as $prod}
                                <option value="{$prod.id|escape:'htmlall':'UTF-8'}" {if $fields_value[$input.name] == $prod.id}selected="selected"{/if}>{$prod.id|escape:'htmlall':'UTF-8'} - {$prod.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>
        </div>
    {/if}
	{$smarty.block.parent}
{/block}
{/strip}