{**
 * Amazon Marketplace Pro - the Amazon box on the order page.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *}
<div id="amzpro-order-box" class="{if $amzpro_ps17}card mt-2{else}panel{/if}">
    <div class="{if $amzpro_ps17}card-header{else}panel-heading{/if}">
        <img src="{$amzpro_logo|escape:'htmlall':'UTF-8'}" alt="" style="height:18px;vertical-align:middle;margin-right:6px;" />
        {l s='Amazon order' mod='amazonmarketplacepro'}
        {if $amzpro_order.is_business}<span class="badge badge-info label label-info">Amazon Business</span>{/if}
        {if $amzpro_order.is_prime}<span class="badge badge-warning label label-warning">Prime</span>{/if}
    </div>
    <div class="{if $amzpro_ps17}card-body{else}panel-body{/if}">
        <div class="row">
            <div class="col-md-7 col-lg-7">
                <table class="table table-condensed" style="margin-bottom:10px;">
                    <tr><th style="width:45%;">{l s='Amazon order number' mod='amazonmarketplacepro'}</th>
                        <td><strong>{$amzpro_order.amazon_order_id|escape:'htmlall':'UTF-8'}</strong></td></tr>
                    <tr><th>{l s='Sales channel' mod='amazonmarketplacepro'}</th>
                        <td>{$amzpro_order.sales_channel|escape:'htmlall':'UTF-8'}{if $amzpro_order.fulfillment_channel == 'AFN'} (FBA){/if}</td></tr>
                    {if $amzpro_order.ship_service_level}
                    <tr><th>{l s='Shipping service (Amazon)' mod='amazonmarketplacepro'}</th>
                        <td>{$amzpro_order.ship_service_level|escape:'htmlall':'UTF-8'}</td></tr>
                    {/if}
                    {if $amzpro_order.earliest_ship}
                    <tr><th>{l s='Earliest ship date' mod='amazonmarketplacepro'}</th>
                        <td>{$amzpro_order.earliest_ship|escape:'htmlall':'UTF-8'}</td></tr>
                    {/if}
                    {if $amzpro_order.latest_ship}
                    <tr><th>{l s='Latest ship date' mod='amazonmarketplacepro'}</th>
                        <td><strong>{$amzpro_order.latest_ship|escape:'htmlall':'UTF-8'}</strong></td></tr>
                    {/if}
                    {if $amzpro_order.latest_delivery}
                    <tr><th>{l s='Latest delivery date' mod='amazonmarketplacepro'}</th>
                        <td>{$amzpro_order.latest_delivery|escape:'htmlall':'UTF-8'}</td></tr>
                    {/if}
                    <tr><th>{l s='Status on Amazon' mod='amazonmarketplacepro'}</th>
                        <td>{$amzpro_order.order_status|escape:'htmlall':'UTF-8'}</td></tr>
                    {if $amzpro_order.refunded_total > 0}
                    <tr><th>{l s='Refunded on Amazon' mod='amazonmarketplacepro'}</th>
                        <td>{$amzpro_order.refunded_total|string_format:'%.2f'|escape:'htmlall':'UTF-8'} {$amzpro_order.currency|escape:'htmlall':'UTF-8'}</td></tr>
                    {/if}
                </table>
            </div>
            <div class="col-md-5 col-lg-5 text-right">
                <a href="{$amzpro_sc_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-primary">
                    {l s='Open in Seller Central' mod='amazonmarketplacepro'}
                </a>
                {if $amzpro_order.fulfillment_channel != 'AFN' && $amzpro_order.items}
                    <button type="button" class="btn btn-default btn-outline-secondary" id="amzpro-refund-toggle" style="margin-left:6px;">
                        {l s='Refund on Amazon' mod='amazonmarketplacepro'}
                    </button>
                {/if}
            </div>
        </div>

        {if $amzpro_order.fulfillment_channel != 'AFN' && $amzpro_order.items}
        <form id="amzpro-refund-form" style="display:none;margin-top:10px;" data-url="{$amzpro_refund_url|escape:'htmlall':'UTF-8'}" data-order="{$amzpro_id_order|intval}"
              data-confirm="{l s='Refund %s to the buyer on Amazon? This cannot be undone.' mod='amazonmarketplacepro'}"
              data-done="{l s='Refund sent to Amazon (feed %s). Amazon confirms it within a few minutes.' mod='amazonmarketplacepro'}">
            <table class="table">
                <thead><tr>
                    <th>{l s='Item' mod='amazonmarketplacepro'}</th>
                    <th class="text-right">{l s='Paid' mod='amazonmarketplacepro'}</th>
                    <th class="text-right">{l s='Already refunded' mod='amazonmarketplacepro'}</th>
                    <th style="width:160px;">{l s='Amount to refund' mod='amazonmarketplacepro'}</th>
                </tr></thead>
                <tbody>
                {foreach from=$amzpro_order.items item=it}
                    <tr>
                        <td>{$it.title|escape:'htmlall':'UTF-8'}<br/><small class="text-muted">SKU {$it.sku|escape:'htmlall':'UTF-8'} &middot; {$it.quantity|intval} &times;</small></td>
                        <td class="text-right">{$it.paid|string_format:'%.2f'|escape:'htmlall':'UTF-8'}</td>
                        <td class="text-right">{$it.refunded|string_format:'%.2f'|escape:'htmlall':'UTF-8'}</td>
                        <td><input type="text" class="form-control amzpro-amount" name="items[{$it.code|escape:'htmlall':'UTF-8'}]" data-max="{$it.left|string_format:'%.2f'|escape:'htmlall':'UTF-8'}" placeholder="max {$it.left|string_format:'%.2f'|escape:'htmlall':'UTF-8'}" /></td>
                    </tr>
                {/foreach}
                    <tr>
                        <td>{l s='Shipping' mod='amazonmarketplacepro'}</td>
                        <td class="text-right">{$amzpro_order.shipping_paid|string_format:'%.2f'|escape:'htmlall':'UTF-8'}</td>
                        <td class="text-right">{($amzpro_order.shipping_paid - $amzpro_order.shipping_left)|string_format:'%.2f'|escape:'htmlall':'UTF-8'}</td>
                        <td><input type="text" class="form-control amzpro-amount" name="shipping" data-max="{$amzpro_order.shipping_left|string_format:'%.2f'|escape:'htmlall':'UTF-8'}" placeholder="max {$amzpro_order.shipping_left|string_format:'%.2f'|escape:'htmlall':'UTF-8'}" /></td>
                    </tr>
                    <tr>
                        <td colspan="3">{l s='Additional refund' mod='amazonmarketplacepro'}</td>
                        <td><input type="text" class="form-control amzpro-amount" name="extra" placeholder="0.00" /></td>
                    </tr>
                </tbody>
            </table>
            <div class="row">
                <div class="col-md-6">
                    <label>{l s='Reason for the refund' mod='amazonmarketplacepro'}</label>
                    <select name="reason" class="form-control">
                        <option value="">{l s='-- Choose a reason --' mod='amazonmarketplacepro'}</option>
                        <option value="CouldNotShip">{l s='Could not ship' mod='amazonmarketplacepro'}</option>
                        <option value="CustomerReturn">{l s='Customer return' mod='amazonmarketplacepro'}</option>
                        <option value="GeneralAdjustment">{l s='General adjustment' mod='amazonmarketplacepro'}</option>
                        <option value="RefusedDelivery">{l s='Delivery refused' mod='amazonmarketplacepro'}</option>
                        <option value="CustomerCancel">{l s='Buyer cancelled' mod='amazonmarketplacepro'}</option>
                        <option value="DifferentItem">{l s='Different item' mod='amazonmarketplacepro'}</option>
                        <option value="CarrierCreditDecision">{l s='Carrier credit decision' mod='amazonmarketplacepro'}</option>
                        <option value="Undeliverable">{l s='Undeliverable to address' mod='amazonmarketplacepro'}</option>
                        <option value="CustomerAddressIncorrect">{l s='Incorrect delivery address' mod='amazonmarketplacepro'}</option>
                        <option value="NoInventory">{l s='Out of stock' mod='amazonmarketplacepro'}</option>
                        <option value="PriceError">{l s='Pricing error' mod='amazonmarketplacepro'}</option>
                        <option value="Other">{l s='Other' mod='amazonmarketplacepro'}</option>
                    </select>
                </div>
                <div class="col-md-6 text-right" style="padding-top:24px;">
                    <button type="button" class="btn btn-default btn-outline-secondary" id="amzpro-refund-all">{l s='Fill in everything' mod='amazonmarketplacepro'}</button>
                    <button type="submit" class="btn btn-primary" style="margin-left:6px;">{l s='Send refund to Amazon' mod='amazonmarketplacepro'}</button>
                </div>
            </div>
            <p class="help-block text-muted" style="margin-top:8px;">
                {l s='Amounts include VAT, as on Amazon\'s refund form. Amazon refunds the buyer and confirms it within a few minutes. This does not change the PrestaShop order or create a credit slip.' mod='amazonmarketplacepro'}
            </p>
            <div id="amzpro-refund-result" style="display:none;"></div>
        </form>
        <script type="text/javascript">
        {literal}
        (function () {
            var form = document.getElementById('amzpro-refund-form');
            var toggle = document.getElementById('amzpro-refund-toggle');
            if (!form || !toggle) { return; }
            toggle.addEventListener('click', function () {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            });
            document.getElementById('amzpro-refund-all').addEventListener('click', function () {
                var inputs = form.querySelectorAll('.amzpro-amount[data-max]');
                for (var i = 0; i < inputs.length; i++) { inputs[i].value = inputs[i].getAttribute('data-max'); }
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var total = 0, inputs = form.querySelectorAll('.amzpro-amount');
                for (var i = 0; i < inputs.length; i++) {
                    var v = parseFloat((inputs[i].value || '0').replace(',', '.'));
                    if (!isNaN(v)) { total += v; }
                }
                var msg = form.getAttribute('data-confirm').replace('%s', total.toFixed(2));
                if (!window.confirm(msg)) { return; }
                var out = document.getElementById('amzpro-refund-result');
                var data = new FormData(form);
                data.append('id_order', form.getAttribute('data-order'));
                var btns = form.querySelectorAll('button');
                for (var b = 0; b < btns.length; b++) { btns[b].disabled = true; }
                fetch(form.getAttribute('data-url'), {method: 'POST', body: data, credentials: 'same-origin'})
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        out.style.display = 'block';
                        out.className = 'alert ' + (res.success ? 'alert-success' : 'alert-danger');
                        out.textContent = res.success
                            ? form.getAttribute('data-done').replace('%s', res.feed_id)
                            : res.error;
                        if (!res.success) { for (var b = 0; b < btns.length; b++) { btns[b].disabled = false; } }
                    })
                    .catch(function (err) {
                        out.style.display = 'block';
                        out.className = 'alert alert-danger';
                        out.textContent = String(err);
                        for (var b = 0; b < btns.length; b++) { btns[b].disabled = false; }
                    });
            });
        })();
        {/literal}
        </script>
        {/if}
    </div>
</div>
