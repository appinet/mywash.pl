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

<div class="block-contact col-md-3 col-sm-3 col-lg-3 col-xs-12 links wrapper">

<div class="title clearfix hidden-md-up" data-toggle="collapse" data-target="#footer_contact">
    <span class="c-info h3">{l s='contact info' d='Shop.Theme.Catalog'}</span>
   <span class="float-xs-right">
          <span class="navbar-toggler collapse-icons">
           <i class="fa fa-plus add"></i>
            <i class="fa fa-minus remove"></i>
          </span>
  </span>
  </div>
  <span class="c-info hidden-sm-down">{l s='contact info' d='Shop.Theme.Catalog'}</span>
 
  <ul id="footer_contact" class="fthr collapse">
  <li class="block">
    <div class="icon"><svg width="20px" height="20px"><use xlink:href="#add"></use></svg></div>
    <div class="data ad">{$contact_infos.address.formatted nofilter}</div>
  </li>

  {if $contact_infos.phone}
    <li class="block">
      <div class="icon"><svg width="20px" height="20px"><use xlink:href="#phone"></use></svg></div>
      <div class="data">
        <a href="tel:{$contact_infos.phone}">{$contact_infos.phone}</a>
       </div>
    </li>
  {/if}

  {if $contact_infos.fax}
    <li class="block">
      <div class="icon"><svg width="21px" height="20px"><use xlink:href="#fax"></use></svg></div>
      <div class="data">
             {$contact_infos.fax}
      </div>
    </li>
  {/if}
  {if $contact_infos.email}
    <li class="block">
      <div class="icon"><svg width="22px" height="22px"><use xlink:href="#mail"></use></svg></div>
      <div class="data email ad">
      <a href="mailto:{$contact_infos.email}">{$contact_infos.email}</a>
       </div>
    </li>
  {/if} 
  <li class="block">
      <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/><path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg></div>
      <div class="data">
        {l s='Pn-pt' d='Modules.Contactinfo.Shop'}: 8-16
       </div>

  </li>
  {block name='hook_footerAfter'}
  {hook h='displayFooterAfter'}
{/block}   
</ul>

</div>