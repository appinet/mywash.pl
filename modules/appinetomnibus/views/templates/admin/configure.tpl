<div class="panel">
  <h3><i class="icon-time"></i> {l s='Zapis historii cen (cron)' mod='appinetomnibus'}</h3>

  {if !$appinet_omnibus.tracking_start}
    <div class="alert alert-warning">
      {l s='Pierwszy pełny zapis cen nie został jeszcze zakończony. Dopóki go nie ma, najniższa cena nie może być wyliczona z historii. Uruchom zapis przyciskiem poniżej lub skonfiguruj cron.' mod='appinetomnibus'}
    </div>
  {/if}

  <p>{l s='Ustaw na serwerze wywołanie tego adresu co godzinę (np. 5 * * * *). Cron zapisuje zmiany cen wynikające z dat rozpoczęcia i zakończenia promocji, a raz na dobę robi pełny zapis całego katalogu (importy, integracje).' mod='appinetomnibus'}</p>
  <div class="form-group">
    <input type="text" class="form-control" readonly value="{$appinet_omnibus.cron_url|escape:'html':'UTF-8'}" onclick="this.select();">
    <p class="help-block">curl -s "{$appinet_omnibus.cron_url|escape:'html':'UTF-8'}" &gt; /dev/null</p>
  </div>

  <table class="table">
    <tr><td>{l s='Historia zbierana od' mod='appinetomnibus'}</td><td>{$appinet_omnibus.tracking_start|default:'-'|escape:'html':'UTF-8'}</td></tr>
    <tr><td>{l s='Ostatnie uruchomienie' mod='appinetomnibus'}</td><td>{$appinet_omnibus.last_run|default:'-'|escape:'html':'UTF-8'}</td></tr>
    <tr><td>{l s='Ostatni pełny zapis' mod='appinetomnibus'}</td><td>{$appinet_omnibus.last_full_run|default:'-'|escape:'html':'UTF-8'}</td></tr>
    <tr><td>{l s='Status' mod='appinetomnibus'}</td><td>{$appinet_omnibus.last_status|default:'-'|escape:'html':'UTF-8'}</td></tr>
    <tr>
      <td>{l s='Pełny zapis' mod='appinetomnibus'}</td>
      <td>
        {if $appinet_omnibus.full_in_progress}
          {l s='w trakcie' mod='appinetomnibus'}: {$appinet_omnibus.full_processed|intval} / {$appinet_omnibus.product_shop_count|intval}
        {elseif $appinet_omnibus.full_pending}
          {l s='zaplanowany przy następnym uruchomieniu' mod='appinetomnibus'}
        {else}
          {l s='aktualny' mod='appinetomnibus'}
        {/if}
      </td>
    </tr>
    <tr><td>{l s='Rekordów historii' mod='appinetomnibus'}</td><td>{$appinet_omnibus.history_rows|intval}</td></tr>
  </table>

  <form method="post" action="{$appinet_omnibus.form_action|escape:'html':'UTF-8'}">
    <button type="submit" name="submitAppinetOmnibusRun" class="btn btn-primary">
      <i class="icon-refresh"></i> {l s='Zapisz ceny teraz' mod='appinetomnibus'}
    </button>
    <button type="submit" name="submitAppinetOmnibusToken" class="btn btn-default">
      {l s='Wygeneruj nowy token crona' mod='appinetomnibus'}
    </button>
  </form>
</div>

<div class="panel">
  <h3><i class="icon-pencil"></i> {l s='Ręczne ceny referencyjne' mod='appinetomnibus'}</h3>
  <p>{l s='Najniższa cena wpisana ręcznie ma pierwszeństwo przed historią. Użyj jej dla promocji rozpoczętych przed instalacją modułu. Cena w walucie domyślnej, przeliczana dla innych walut.' mod='appinetomnibus'}</p>

  <form method="post" action="{$appinet_omnibus.form_action|escape:'html':'UTF-8'}" class="form-inline">
    <div class="form-group">
      <label>{l s='ID produktu' mod='appinetomnibus'}</label>
      <input type="number" min="1" name="override_id_product" class="form-control" required>
    </div>
    <div class="form-group">
      <label>{l s='ID kombinacji (0 = wszystkie)' mod='appinetomnibus'}</label>
      <input type="number" min="0" name="override_id_product_attribute" value="0" class="form-control">
    </div>
    <div class="form-group">
      <label>{l s='Najniższa cena brutto' mod='appinetomnibus'} ({$appinet_omnibus.currency|escape:'html':'UTF-8'})</label>
      <input type="text" name="override_price_tax_incl" class="form-control" required>
    </div>
    <button type="submit" name="submitAppinetOmnibusOverride" class="btn btn-primary">{l s='Zapisz' mod='appinetomnibus'}</button>
  </form>

  {if $appinet_omnibus.overrides}
    <table class="table" style="margin-top: 15px;">
      <thead>
        <tr>
          <th>{l s='Produkt' mod='appinetomnibus'}</th>
          <th>{l s='ID kombinacji' mod='appinetomnibus'}</th>
          <th class="text-right">{l s='Brutto' mod='appinetomnibus'}</th>
          <th class="text-right">{l s='Netto' mod='appinetomnibus'}</th>
          <th>{l s='Zmieniono' mod='appinetomnibus'}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {foreach $appinet_omnibus.overrides as $override}
          <tr>
            <td>#{$override.id_product|intval} {$override.name|escape:'html':'UTF-8'}</td>
            <td>{$override.id_product_attribute|intval}</td>
            <td class="text-right">{$override.price_tax_incl_formatted|escape:'html':'UTF-8'}</td>
            <td class="text-right">{$override.price_tax_excl_formatted|escape:'html':'UTF-8'}</td>
            <td>{$override.date_upd|escape:'html':'UTF-8'}</td>
            <td class="text-right">
              <form method="post" action="{$appinet_omnibus.form_action|escape:'html':'UTF-8'}">
                <input type="hidden" name="override_id_product" value="{$override.id_product|intval}">
                <input type="hidden" name="override_id_product_attribute" value="{$override.id_product_attribute|intval}">
                <button type="submit" name="deleteAppinetOmnibusOverride" class="btn btn-default btn-sm">
                  <i class="icon-trash"></i> {l s='Usuń' mod='appinetomnibus'}
                </button>
              </form>
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {/if}
</div>

<div class="panel">
  <h3><i class="icon-warning"></i> {l s='Promocje bez pełnej historii' mod='appinetomnibus'}</h3>
  <p>{l s='Produkty w promocji, dla których historia nie obejmuje pełnego okresu przed obniżką. Dla nich użyto ustawienia "Gdy brak historii" - sprawdź je lub dodaj ręczną cenę referencyjną.' mod='appinetomnibus'}</p>
  {if $appinet_omnibus.incomplete}
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Produkt' mod='appinetomnibus'}</th>
          <th>{l s='ID kombinacji' mod='appinetomnibus'}</th>
          <th>{l s='Początek promocji' mod='appinetomnibus'}</th>
          <th>{l s='Użyta wartość' mod='appinetomnibus'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $appinet_omnibus.incomplete as $item}
          <tr>
            <td>#{$item.id_product|intval} {$item.name|escape:'html':'UTF-8'}</td>
            <td>{$item.id_product_attribute|intval}</td>
            <td>{$item.promotion_start|default:'-'|escape:'html':'UTF-8'}</td>
            <td>
              {if $item.source == 'fallback_regular'}{l s='cena regularna' mod='appinetomnibus'}
              {elseif $item.source == 'fallback_current'}{l s='aktualna cena promocyjna' mod='appinetomnibus'}
              {elseif $item.source == 'history'}{l s='niepełna historia' mod='appinetomnibus'}
              {else}{l s='informacja ukryta' mod='appinetomnibus'}{/if}
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p>{l s='Brak - wszystkie promocje mają pełną historię cen.' mod='appinetomnibus'}</p>
  {/if}
</div>
