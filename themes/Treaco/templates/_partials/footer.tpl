{**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}



  <div class="foot-sp">
  <div class="footnews container">
    <div class="emailp">
        {block name='hook_footer_before'}
          {hook h='displayFooterBefore'}
        {/block}
      </div>
  </div>
  </div>

<div class="footer-container">

<div class="container">
  <div class="middle-footer">
    <div class="smmail"></div>
        <div class="row">
          {block name='hook_footer'}
            {hook h='displayFooter'}
          {/block}
        </div>
  </div>
  <div class="text-xs-center">
    {block name='hook_displayTopColumn'}
      {hook h='displayTopColumn'}
    {/block}
  </div>
  <div class="mobile-center">
  <a href="#" style="display: inline-block; margin-right: 20px; margin-top: 10px;"><img style="height: 25px;" src="/img/raben-white.png" /></a>
  <a href="#" style="display: inline-block; margin-right: 10px; margin-top: 10px;"><img style="height: 45px;" src="/img/dpd-white.png" /></a>
  <a href="#" style="display: inline-block; margin-right: 10px; margin-top: 10px;"><img style="height: 80px;" src="/img/inpost-white.png" /></a>
 <a href="#" target="_blank" title="Obsługujemy płatności internetowe przez system płatności online Tpay" style="display: inline-block; margin-right: 15px; margin-top: 10px;"><img style="height: 60px;" src="https://tpay.com/img/banners/tpay-partner-160x75.svg" style="border:0" alt="Logo Tpay" title="Logo Tpay"/></a>
</div>
</div> 
        
      <div class="foot-copy">
        <div class="container">
          <div class="row">
            <div class="col-sm-6 col-xs-12"> 
              {block name='copyright_link'}
                <a class="_blank" href="#" style="cursor: auto;" onclick="event.preventDefault();">
                  {l s='%copyright% %year% - Ecommerce software by %prestashop%' sprintf=['%prestashop%' => 'PrestaShop™', '%year%' => 'Y'|date, '%copyright%' => '©'] d='Shop.Theme.Global'}
                </a>
              {/block}
            </div>
            <div class="col-sm-6 col-xs-12 text-xs-right">
              {block name='hook_footerDown'}
                {hook h='displayFooterDown'}
              {/block}
            </div>
          </div> 
      </div>
    </div>
  <a href="" id="scroll" title="Scroll to Top" style="display: none;"><i class="fa fa-angle-up"></i></a>
</div>
