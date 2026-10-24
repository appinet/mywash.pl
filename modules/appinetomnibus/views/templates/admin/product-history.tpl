<div class="panel appinet-omnibus-history">
  <h3>{l s='Omnibus - historia cen' mod='appinetomnibus'}</h3>
  <p class="help-block">
    {l s='Grupa' mod='appinetomnibus'}: <strong>{$appinet_omnibus_group|escape:'html':'UTF-8'}</strong>,
    {l s='waluta' mod='appinetomnibus'}: <strong>{$appinet_omnibus_currency|escape:'html':'UTF-8'}</strong>.
    {l s='Ręczną cenę referencyjną ustawisz w' mod='appinetomnibus'}
    <a href="{$appinet_omnibus_config_url|escape:'html':'UTF-8'}">{l s='konfiguracji modułu' mod='appinetomnibus'}</a>.
  </p>
  {if $appinet_omnibus_rows}
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Kombinacja' mod='appinetomnibus'}</th>
          <th>{l s='Od' mod='appinetomnibus'}</th>
          <th>{l s='Do' mod='appinetomnibus'}</th>
          <th class="text-right">{l s='Cena brutto' mod='appinetomnibus'}</th>
          <th class="text-right">{l s='Cena netto' mod='appinetomnibus'}</th>
          <th class="text-right">{l s='Cena regularna brutto' mod='appinetomnibus'}</th>
          <th>{l s='Promocja' mod='appinetomnibus'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $appinet_omnibus_rows as $row}
          <tr>
            <td>{$row.combination|escape:'html':'UTF-8'}</td>
            <td>{$row.date_from|escape:'html':'UTF-8'}</td>
            <td>{if $row.date_to}{$row.date_to|escape:'html':'UTF-8'}{else}<span class="badge badge-success">{l s='obecnie' mod='appinetomnibus'}</span>{/if}</td>
            <td class="text-right">{$row.price_tax_incl_formatted|escape:'html':'UTF-8'}</td>
            <td class="text-right">{$row.price_tax_excl_formatted|escape:'html':'UTF-8'}</td>
            <td class="text-right">{$row.regular_tax_incl_formatted|escape:'html':'UTF-8'}</td>
            <td>{if $row.is_reduced}{l s='tak' mod='appinetomnibus'}{else}-{/if}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p>{l s='Brak zapisanej historii cen. Zostanie zapisana przy następnym uruchomieniu crona.' mod='appinetomnibus'}</p>
  {/if}
</div>
