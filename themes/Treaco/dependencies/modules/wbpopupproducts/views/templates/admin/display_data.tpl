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
{if !empty($allData)}
<div class="wbcustom-data wbblink-product-admin">
	<div class="wbcustom-data-inner">
		<table class="wbcustom-data-table">
			<tr>
				<td>{l s='Product' mod='wbpopupproducts'}</td>
				<td>{l s='Status' mod='wbpopupproducts'}</td>
				<td>{l s='Actions' mod='wbpopupproducts'}</td>
			</tr>
			{foreach $allData as $data}
				<tr>
					<td>{$data.id_product|escape:'htmlall':'UTF-8'} : {$data.name|escape:'htmlall':'UTF-8'}</td>
					<td>
						{if $data.status == '1'}
							<span class='right'><i class='material-icons'>&#xe5ca;</i></span>
						{else}
							<span class='wrong'><i class='material-icons'>&#xe14c;</i></span>
						{/if}
					</td>
					<td>
						<form method="post">
							<input type="hidden" name="id" value="{$data.id|escape:'htmlall':'UTF-8'}">
							<button type="submit" class="edit" name="submitActionEdit" value="edit">{l s='Edit' mod='wbpopupproducts'}</button>	
						</form>

						<form method="post">
							<input type="hidden" name="id" value="{$data.id|escape:'htmlall':'UTF-8'}">
							<button type="submit" class="delete" name="submitActionDelete" value="delete">{l s='Delete' mod='wbpopupproducts'}</button>	
						</form>
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>
{/if}
{/strip}