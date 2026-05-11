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
<style type="text/css">
    .wbpopup-popducts .wbpopup-popducts-item {
        {if $FAKE_MODE =='1'} 
        opacity: {$OPACITY|escape:'htmlall':'UTF-8'};
        {/if}
        background-color: {$BACKGROUND_COLOR|escape:'htmlall':'UTF-8'};
        border: 1px solid {$BORDER_COLOR|escape:'htmlall':'UTF-8'};
        box-shadow: 0px 0px 1px 0px {$SHADOW_COLOR|escape:'htmlall':'UTF-8'};
        border-radius: {$BORDER_RADIUS|escape:'htmlall':'UTF-8'};
    }
    .wbpopup-popducts .wbpopup-popducts-item:hover{
        {if $FAKE_MODE =='1'} 
        opacity: {$OPACITY_ON_HOVER|escape:'htmlall':'UTF-8'};
        {/if}
    }
    .wbpopup-popducts-name a h4{
        font-size:{$FONT_SIZE|escape:'htmlall':'UTF-8'};
    }
</style>
<div class="wbpopup-popducts">
    <div class='wbpopup-popducts-inner'>
        {foreach $arr_data as $data}
            {$product = $data.ProdInfo}
            <div class="wbpopup-popducts-item desktop-{$DESKTOP_POSITION|escape:'htmlall':'UTF-8'} mobile-{$MOBILE_POSITION|escape:'htmlall':'UTF-8'}" data-id-product="{$product.id_product|escape:'htmlall':'UTF-8'}" data-id-product-attribute="{$product.id_product_attribute|escape:'htmlall':'UTF-8'}" itemscope itemtype="http://schema.org/Product">
                <div class="thumbnail-container">
                    <div class="wbpopup-popducts-wrapper">
                        <div class="wbpopup-popducts-image">
                            {block name='product_thumbnail'}
                                {if $product.cover}
                                    <a href="{$data.custom_link|escape:'htmlall':'UTF-8'}" class="thumbnail product-thumbnail">
                                        <img class="" src="{$product.cover.bySize.$PRODUCT_THUMB_SIZE.url|escape:'htmlall':'UTF-8'}" alt="{if !empty($product.cover.legend)}{$product.cover.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}">

                                        {if $SECOND_IMG_HOVER == '1'}
                                            {if isset($product.images['0']) && !isset($product.images['0']['cover'])}
                                                <img class="wbpopup-products-hover-image" src="{$product.images.0.bySize.$PRODUCT_THUMB_SIZE.url|escape:'htmlall':'UTF-8'}" alt="{if !empty($product.cover.legend)}{$product.cover.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}">
                                            {elseif isset($product.images['1']) && !isset($product.images['1']['cover'])}
                                                <img class="wbpopup-products-hover-image" src="{$product.images.1.bySize.$PRODUCT_THUMB_SIZE.url|escape:'htmlall':'UTF-8'}" alt="{if !empty($product.cover.legend)}{$product.cover.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}">
                                            {/if}
                                        {/if}
                                    </a>
                                {else}
                                    <a href="{$data.custom_link|escape:'htmlall':'UTF-8'}" class="thumbnail product-thumbnail">
                                        <img class="" src="{$urls.no_picture_image.bySize.$PRODUCT_THUMB_SIZE.url|escape:'htmlall':'UTF-8'}">
                                    </a>
                                {/if}
                            {/block}

                            {if $product.discount_type === 'percentage'}
                                <span class="wbdiscount-percentage wbdiscount-product">{$product.discount_percentage|escape:'htmlall':'UTF-8'}</span>
                            {elseif $product.discount_type === 'amount'}
                                <span class="wbdiscount-amount wbdiscount-product">{$product.discount_amount_to_display|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                            
                        </div>

                        <div class="wbpopup-popducts-description">
                            {if $CLOSE_BTN}
                                <div class='wbpopup-popducts-close-icon-wrapper'>
                                    <a href="javascript:;" class="wbpopup-popducts-close-icon">x</a>
                                </div>
                            {/if}
                            {block name='product_name'}
                            <div class="wbpopup-popducts-name">
                                <a href="{$data.custom_link|escape:'htmlall':'UTF-8'}"><h4>{$product.name|escape:'htmlall':'UTF-8'}</h4></a>
                            </div>
                            {/block}
                            {block name='product_reviews'}
                              {hook h='displayProductListReviews' product=$product}
                            {/block}
                            {block name='product_price_and_shipping'}
                                {if $product.show_price}
                                    <div class="wbpopup-popducts-price-and-shipping">
                                        <span itemprop="price" class="price">{$product.price|escape:'htmlall':'UTF-8'}</span>
                                        {if $product.has_discount}
                                            {hook h='displayProductPriceBlock' product=$product type="old_price"}
                                            <span class="sr-only">{l s='Regular price' mod='wbpopupproducts'}</span>
                                            <span class="regular-price">{$product.regular_price|escape:'htmlall':'UTF-8'}</span>
                                        {/if}

                                        {hook h='displayProductPriceBlock' product=$product type="before_price"}

                                        <span class="sr-only">{l s='Price' mod='wbpopupproducts'}</span>
                                        

                                        {hook h='displayProductPriceBlock' product=$product type='unit_price'}

                                        {hook h='displayProductPriceBlock' product=$product type='weight'}
                                    </div>
                                {/if}
                            {/block}

                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>
{/strip}