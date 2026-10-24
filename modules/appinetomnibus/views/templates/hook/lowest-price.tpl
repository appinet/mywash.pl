<div class="appinet-omnibus appinet-omnibus--{$appinet_omnibus.placement|escape:'html':'UTF-8'}" data-placement="{$appinet_omnibus.placement|escape:'html':'UTF-8'}" role="note">
  <span class="appinet-omnibus__icon" aria-hidden="true"></span>
  <span class="appinet-omnibus__text">
    {if $appinet_omnibus.label_before}
      <span class="appinet-omnibus__label">{$appinet_omnibus.label_before|escape:'html':'UTF-8'}</span>
    {/if}
    <span class="appinet-omnibus__amounts">
      {foreach $appinet_omnibus.amounts as $amount}
        <span class="appinet-omnibus__amount"><strong>{$amount.price|escape:'html':'UTF-8'}</strong>{if $amount.tax} {$amount.tax|escape:'html':'UTF-8'}{/if}</span>
      {/foreach}
    </span>
    {if $appinet_omnibus.label_after}
      <span class="appinet-omnibus__label">{$appinet_omnibus.label_after|escape:'html':'UTF-8'}</span>
    {/if}
  </span>
</div>
