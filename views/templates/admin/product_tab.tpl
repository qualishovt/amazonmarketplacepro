{*
 * Amazon panel inside the PrestaShop product page.
 * Every control here overrides the profile / rules / global settings.
 *}

<div class="panel" id="amzpro-product-panel">
    <h3><i class="icon-amazon"></i> {l s='Amazon Marketplace Pro' mod='amazonmarketplacepro'}</h3>

    <input type="hidden" name="amzpro_tab_present" value="1" />
    <input type="hidden" name="amzpro_id_shop" value="{$amzpro_id_shop|intval}" />

    {if $amzpro_multistore}
        <div class="alert alert-warning">
            {if $amzpro_scope_all}
                {l s='These settings apply to:' mod='amazonmarketplacepro'} <strong>{l s='all shops' mod='amazonmarketplacepro'}</strong>.
                {l s='A shop that saved its own settings for this product keeps them.' mod='amazonmarketplacepro'}
            {else}
                {l s='These settings apply to:' mod='amazonmarketplacepro'} <strong>{$amzpro_scope_shop|escape:'htmlall':'UTF-8'}</strong>.
                {if !$amzpro_scope_own}
                    {l s='This shop uses the settings saved for all shops until you save the product here.' mod='amazonmarketplacepro'}
                {/if}
            {/if}
        </div>
    {/if}

    <div class="alert alert-info">
        {l s='These settings apply to this product only and take precedence over the listing profile, the category/manufacturer/supplier rules and the global settings. Leave a field empty to inherit.' mod='amazonmarketplacepro'}
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Sync this product to Amazon' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <select name="amzpro_sync" class="form-control">
                <option value="1"{if $amzpro.sync} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                <option value="0"{if !$amzpro.sync} selected="selected"{/if}>{l s='No — exclude from every export' mod='amazonmarketplacepro'}</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Send price / send quantity' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <label class="checkbox-inline"><input type="checkbox" name="amzpro_sync_price" value="1"{if $amzpro.sync_price} checked="checked"{/if} /> {l s='Price' mod='amazonmarketplacepro'}</label>
            <label class="checkbox-inline"><input type="checkbox" name="amzpro_sync_quantity" value="1"{if $amzpro.sync_quantity} checked="checked"{/if} /> {l s='Quantity' mod='amazonmarketplacepro'}</label>
            <p class="help-block">{l s='Unticking one leaves that value untouched on Amazon (useful when you reprice on Amazon itself).' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Stock forcing' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <select name="amzpro_stock_force" class="form-control">
                <option value=""{if !$amzpro.force_in_stock && !$amzpro.force_out_of_stock} selected="selected"{/if}>{l s='Send the real quantity' mod='amazonmarketplacepro'}</option>
                <option value="in"{if $amzpro.force_in_stock} selected="selected"{/if}>{l s='Always in stock (send 999)' mod='amazonmarketplacepro'}</option>
                <option value="out"{if $amzpro.force_out_of_stock} selected="selected"{/if}>{l s='Always out of stock (send 0)' mod='amazonmarketplacepro'}</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Override price' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="number" step="0.01" min="0" name="amzpro_override_price" value="{if $amzpro.override_price > 0}{$amzpro.override_price|escape:'htmlall':'UTF-8'}{/if}" class="form-control" />
            <p class="help-block">{l s='Sent instead of the shop price (markups and pricing rules are skipped). Empty = use the shop price.' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Override SKU' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="text" name="amzpro_override_sku" value="{$amzpro.override_sku|escape:'htmlall':'UTF-8'}" class="form-control" />
            <p class="help-block">{l s='Use when this product already exists in your Amazon inventory under a different seller SKU. Used for both listing updates and order matching.' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='ASIN' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="text" name="amzpro_asin" value="{$amzpro.asin|escape:'htmlall':'UTF-8'}" class="form-control" />
            <p class="help-block">{l s='Attach the offer to a known Amazon catalogue page instead of letting Amazon match it by barcode.' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Fulfilled by Amazon (FBA)' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <select name="amzpro_is_fba" class="form-control">
                <option value="0"{if !$amzpro.is_fba} selected="selected"{/if}>{l s='No — I ship it (MFN)' mod='amazonmarketplacepro'}</option>
                <option value="1"{if $amzpro.is_fba} selected="selected"{/if}>{l s='Yes — Amazon ships it (AFN)' mod='amazonmarketplacepro'}</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Handling time (days)' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="number" min="0" name="amzpro_lead_time" value="{if $amzpro.lead_time >= 0}{$amzpro.lead_time|escape:'htmlall':'UTF-8'}{/if}" class="form-control" />
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Shipping template' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="text" name="amzpro_shipping_template" value="{$amzpro.shipping_template|escape:'htmlall':'UTF-8'}" class="form-control" />
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Browse node' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="text" name="amzpro_browse_node" value="{$amzpro.browse_node|escape:'htmlall':'UTF-8'}" class="form-control" />
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Brand override' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="text" name="amzpro_brand" value="{$amzpro.brand|escape:'htmlall':'UTF-8'}" class="form-control" />
            <p class="help-block">{l s='Empty = the product manufacturer.' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Condition' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <select name="amzpro_condition_type" class="form-control">
                <option value="">{l s='-- Global default --' mod='amazonmarketplacepro'}</option>
                <option value="new_new"{if $amzpro.condition_type == 'new_new'} selected="selected"{/if}>{l s='New' mod='amazonmarketplacepro'}</option>
                <option value="used_like_new"{if $amzpro.condition_type == 'used_like_new'} selected="selected"{/if}>{l s='Used - Like New' mod='amazonmarketplacepro'}</option>
                <option value="used_very_good"{if $amzpro.condition_type == 'used_very_good'} selected="selected"{/if}>{l s='Used - Very Good' mod='amazonmarketplacepro'}</option>
                <option value="used_good"{if $amzpro.condition_type == 'used_good'} selected="selected"{/if}>{l s='Used - Good' mod='amazonmarketplacepro'}</option>
                <option value="used_acceptable"{if $amzpro.condition_type == 'used_acceptable'} selected="selected"{/if}>{l s='Used - Acceptable' mod='amazonmarketplacepro'}</option>
                <option value="collectible_like_new"{if $amzpro.condition_type == 'collectible_like_new'} selected="selected"{/if}>{l s='Collectible - Like New' mod='amazonmarketplacepro'}</option>
                <option value="refurbished_refurbished"{if $amzpro.condition_type == 'refurbished_refurbished'} selected="selected"{/if}>{l s='Refurbished' mod='amazonmarketplacepro'}</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Condition note' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-6">
            <textarea name="amzpro_condition_note" class="form-control" rows="2" maxlength="2000">{$amzpro.condition_note|escape:'htmlall':'UTF-8'}</textarea>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Key product features' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-6">
            <textarea name="amzpro_bullet_points" class="form-control" rows="5" placeholder="{l s='One bullet point per line (max 5 lines, 500 characters each)' mod='amazonmarketplacepro'}">{$amzpro.bullet_points|escape:'htmlall':'UTF-8'}</textarea>
            <p class="help-block">{l s='Empty = generated from the short description.' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='GPSR contact' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-6">
            <input type="text" name="amzpro_gpsr_contact" value="{$amzpro.gpsr_contact|escape:'htmlall':'UTF-8'}" class="form-control" />
            <p class="help-block">{l s='E-mail or URL of the responsible person. Overrides the manufacturer/supplier contact.' mod='amazonmarketplacepro'}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Gift options' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <label class="checkbox-inline"><input type="checkbox" name="amzpro_gift_option" value="1"{if $amzpro.gift_option} checked="checked"{/if} /> {l s='Offer gift wrap / gift message' mod='amazonmarketplacepro'}</label>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Transparency code' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-4">
            <input type="text" name="amzpro_transparency_code" value="{$amzpro.transparency_code|escape:'htmlall':'UTF-8'}" class="form-control" />
        </div>
    </div>

    {* ── Propagation ── *}
    <hr />
    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Propagate these settings to' mod='amazonmarketplacepro'}</label>
        <div class="col-lg-6">
            <select name="amzpro_propagate_scope" class="form-control" style="max-width:320px;">
                <option value="">{l s='-- This product only --' mod='amazonmarketplacepro'}</option>
                <option value="category">{l s='All products in the same default category' mod='amazonmarketplacepro'}</option>
                <option value="manufacturer">{l s='All products of the same manufacturer' mod='amazonmarketplacepro'}</option>
                <option value="supplier">{l s='All products of the same supplier' mod='amazonmarketplacepro'}</option>
                <option value="all">{l s='All active products in the shop' mod='amazonmarketplacepro'}</option>
            </select>
            <p class="help-block">{l s='Applied when you save the product. Identity fields (SKU, ASIN, price, transparency code) are never propagated.' mod='amazonmarketplacepro'}</p>
            <div style="margin-top:6px;">
                {foreach from=$amzpro_propagatable item=field}
                    <label class="checkbox-inline" style="font-weight:normal;">
                        <input type="checkbox" name="amzpro_propagate_fields[]" value="{$field|escape:'htmlall':'UTF-8'}" checked="checked" /> {$field|escape:'htmlall':'UTF-8'}
                    </label>
                {/foreach}
            </div>
        </div>
    </div>
</div>
