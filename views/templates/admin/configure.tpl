{*
 * Amazon Marketplace Pro — admin configuration template
 *
 * Settings | Orders | Products | Returns | FBA | Repricing | Fees | Reports | Promotions | Multi-Account | Automation | Logs
 *}

{if $confirm_msg}{$confirm_msg}{/if}

{* ────────────────── Sidebar navigation (marketplaces first) ────────────────── *}
<style>
    #mkpro-sidebar { background: #fff; border: 1px solid #d6d4d4; border-radius: 3px; padding: 0; }
    #mkpro-sidebar .mkpro-nav-header { padding: 10px 14px 4px; font-size: 11px; font-weight: bold;
        text-transform: uppercase; color: #9aa0a6; letter-spacing: .5px; }
    #mkpro-sidebar ul { margin: 0; }
    #mkpro-sidebar li > a { border-radius: 0; border-bottom: 1px solid #f0f0f0; color: #363a41; }
    #mkpro-sidebar a, #mkpro-sidebar a:focus, #mkpro-sidebar a:active { outline: none !important; }
    #mkpro-sidebar li.active > a, #mkpro-sidebar li.active > a:hover {
        background: #25b2a8; color: #fff; }
    #mkpro-sidebar li.mkpro-soon > a { color: #b9bdc3; cursor: not-allowed; }
    #mkpro-sidebar li.mkpro-soon .badge { background: #eee; color: #999; font-size: 9px; }
    /* Accordion (slide animation via jQuery, exact content height) */
    #mkpro-sidebar .mkpro-submenu { background: #fafbfc; display: none; }
    #mkpro-sidebar li.mkpro-group.open > .mkpro-submenu { display: block; }
    #mkpro-sidebar .mkpro-submenu li > a { padding: 6px 10px 6px 30px; font-size: 12px; }
    #mkpro-sidebar li.mkpro-group > a .mkpro-caret { float: right; transition: transform .15s; }
    #mkpro-sidebar li.mkpro-group.open > a .mkpro-caret { transform: rotate(90deg); }
    #mkpro-sidebar li.mkpro-group.mkpro-current > a { font-weight: bold; }
</style>

<div class="row">
<div class="col-lg-2 col-md-3">
    <div id="mkpro-sidebar">
        <ul class="nav nav-pills nav-stacked" id="mkpro-tabs">
            <li class="active"><a href="#tab-settings" data-toggle="tab"><i class="icon-cogs"></i> {l s='Settings' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-orders" data-toggle="tab"><i class="icon-shopping-cart"></i> {l s='Orders' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-pending" data-toggle="tab"><i class="icon-pause"></i> {l s='Pending Orders' mod='amazonmarketplacepro'}{if $pending_orders} <span class="badge">{$pending_orders|count}</span>{/if}</a></li>
            <li><a href="#tab-products" data-toggle="tab"><i class="icon-th-list"></i> {l s='Products' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-markup" data-toggle="tab"><i class="icon-sliders"></i> {l s='Markup & Rules' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-prodrules" data-toggle="tab"><i class="icon-check-square-o"></i> {l s='Product Rules' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-queue" data-toggle="tab"><i class="icon-list-ol"></i> {l s='Queue' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-orphans" data-toggle="tab"><i class="icon-unlink"></i> {l s='Orphans' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-returns" data-toggle="tab"><i class="icon-undo"></i> {l s='Returns' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-fba" data-toggle="tab"><i class="icon-truck"></i> {l s='FBA' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-repricing" data-toggle="tab"><i class="icon-usd"></i> {l s='Repricing' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-fees" data-toggle="tab"><i class="icon-money"></i> {l s='Fees' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-reports" data-toggle="tab"><i class="icon-bar-chart"></i> {l s='Reports' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-promotions" data-toggle="tab"><i class="icon-tag"></i> {l s='Promotions' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-multimp" data-toggle="tab"><i class="icon-globe"></i> {l s='Multi-Account' mod='amazonmarketplacepro'}</a></li>
        </ul>
        <div class="mkpro-nav-header">{l s='General' mod='amazonmarketplacepro'}</div>
        <ul class="nav nav-pills nav-stacked">
            <li><a href="#tab-cron" data-toggle="tab"><i class="icon-clock-o"></i> {l s='Automation' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#tab-logs" data-toggle="tab"><i class="icon-file-text-o"></i> {l s='Logs' mod='amazonmarketplacepro'}</a></li>
        </ul>
    </div>
</div>
<div class="col-lg-10 col-md-9">
<div class="tab-content" id="mkpro-tab-content">

{* ═══════════════════════ SETTINGS TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-settings">

    <form method="post" class="form-horizontal" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">

    {* ── Amazon Connection ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-amazon"></i> {l s='Amazon Connection' mod='amazonmarketplacepro'}</div>

        {if $mkpro_oauth_error}
            <div class="alert alert-danger">{l s='Amazon connection failed:' mod='amazonmarketplacepro'} {$mkpro_oauth_error|escape:'htmlall':'UTF-8'}</div>
        {/if}

        {if $mkpro_dev_mode && $mkpro_environment == 'sandbox'}
            <div class="alert alert-warning">
                <i class="icon-warning"></i> <strong>{l s='Sandbox mode.' mod='amazonmarketplacepro'}</strong>
                {l s='Calls go to the SP-API sandbox, which returns fixed sample data. Nothing here touches a live seller account.' mod='amazonmarketplacepro'}
            </div>
        {/if}

        {if $mkpro_connected && $mkpro_auth_mode != 'manual'}
            <div class="alert alert-success">
                <i class="icon-check"></i> {l s='Connected to Amazon' mod='amazonmarketplacepro'}
                {if $mkpro_dev_mode} ({if $mkpro_environment == 'sandbox'}{l s='sandbox app' mod='amazonmarketplacepro'}{else}{l s='production app' mod='amazonmarketplacepro'}{/if}){/if}
                {if $mkpro_selling_partner_id} — {l s='Seller' mod='amazonmarketplacepro'} <strong>{$mkpro_selling_partner_id|escape:'htmlall':'UTF-8'}</strong>{/if}
            </div>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-6">
                    <button type="submit" name="mkproDisconnectAmazon" class="btn btn-default"
                            onclick="return confirm('{l s='Disconnect this shop from Amazon?' mod='amazonmarketplacepro' js=1}');">
                        <i class="icon-unlink"></i> {l s='Disconnect' mod='amazonmarketplacepro'}
                    </button>
                    {if $mkpro_dev_mode && $mkpro_other_env_connected}
                        <p class="help-block">{l s='Disconnects the active environment only — the other one stays connected.' mod='amazonmarketplacepro'}</p>
                    {/if}
                </div>
            </div>
        {elseif $mkpro_manual_connected}
            <div class="alert alert-success">
                <i class="icon-check"></i> {l s='Connected with manual SP-API credentials' mod='amazonmarketplacepro'}
                {if $mkpro_dev_mode} ({if $mkpro_environment == 'sandbox'}{l s='sandbox app' mod='amazonmarketplacepro'}{else}{l s='production app' mod='amazonmarketplacepro'}{/if}){/if}
                {if $mkpro_seller_id} — {l s='Seller' mod='amazonmarketplacepro'} <strong>{$mkpro_seller_id|escape:'htmlall':'UTF-8'}</strong>{/if}
                <br><small>{l s='A refresh token is stored for this environment. The "Connect to Amazon" button is only for the OAuth flow and is not used in manual mode — use "Test Amazon Connection" below to verify the link.' mod='amazonmarketplacepro'}</small>
            </div>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-6">
                    <button type="submit" name="mkproDisconnectAmazon" class="btn btn-default"
                            onclick="return confirm('{l s='Clear the stored token for this environment?' mod='amazonmarketplacepro' js=1}');">
                        <i class="icon-unlink"></i> {l s='Disconnect' mod='amazonmarketplacepro'}
                    </button>
                </div>
            </div>
        {else}
            {if $mkpro_dev_mode && $mkpro_other_env_connected}
                <div class="alert alert-info">
                    <i class="icon-info-circle"></i>
                    {l s='Not connected in this environment yet. The other environment is still connected — switching back restores it without re-authorizing.' mod='amazonmarketplacepro'}
                </div>
            {/if}
            {if $mkpro_auth_mode == 'manual'}
                <div class="alert alert-info">
                    <i class="icon-info-circle"></i>
                    {l s='Manual credentials mode: fill in the Manual SP-API Credentials panel below (client ID, secret, refresh token from self-authorization) and click Save Settings. The OAuth "Connect to Amazon" button is not used in this mode.' mod='amazonmarketplacepro'}
                </div>
            {else}
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-6">
                    <button type="submit" name="mkproConnectAmazon" class="btn btn-primary btn-lg">
                        <i class="icon-amazon"></i> {l s='Connect to Amazon' mod='amazonmarketplacepro'}
                    </button>
                    <p class="help-block">{l s='Select your marketplace below first, then click Connect. You will be sent to Amazon Seller Central to approve the connection — no credentials to copy.' mod='amazonmarketplacepro'}</p>
                </div>
            </div>
            {/if}
        {/if}

        {if $mkpro_dev_mode}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Environment' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_environment" class="form-control">
                    <option value="sandbox"{if $mkpro_environment == 'sandbox'} selected="selected"{/if}>{l s='Sandbox app — canned data, test seller' mod='amazonmarketplacepro'}</option>
                    <option value="production"{if $mkpro_environment == 'production'} selected="selected"{/if}>{l s='Production app — live data, real seller' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">
                    {l s='Switches the whole stack at once: which registered app asks for consent, which SP-API host is called, and which stored token is used. The two connections are kept separately, so switching back does not require re-authorizing.' mod='amazonmarketplacepro'}
                    <br>{l s='Active app id:' mod='amazonmarketplacepro'} <code>{$mkpro_lwa_app_id|escape:'htmlall':'UTF-8'}</code>
                </p>
            </div>
        </div>
        {/if}

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Authentication mode' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_auth_mode" class="form-control">
                    <option value="connect"{if $mkpro_auth_mode != 'manual'} selected="selected"{/if}>{l s='Connect with Amazon (recommended)' mod='amazonmarketplacepro'}</option>
                    <option value="manual"{if $mkpro_auth_mode == 'manual'} selected="selected"{/if}>{l s='Manual SP-API credentials (advanced)' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        {if $mkpro_dev_mode}
        <div class="form-group" id="mkpro-beta-group"{if $mkpro_environment == 'sandbox'} style="display:none;"{/if}>
            <label class="control-label col-lg-3">{l s='Beta authorization (draft app)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_oauth_beta" class="form-control">
                    <option value="1"{if $mkpro_oauth_beta} selected="selected"{/if}>{l s='Enabled (app not published yet)' mod='amazonmarketplacepro'}</option>
                    <option value="0"{if !$mkpro_oauth_beta} selected="selected"{/if}>{l s='Disabled (app is published)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">
                    {l s='Adds version=beta to the Seller Central consent page, which unpublished apps require. It affects the "Connect to Amazon" button only — it has no effect on API calls, and none at all when the token was obtained by self-authorization instead of the consent flow. Applies to the production app only: the sandbox app is never published, so sandbox connects always send version=beta.' mod='amazonmarketplacepro'}
                </p>
            </div>
        </div>
        {/if}
    </div>

    {* ── Amazon SP-API Credentials (manual mode) ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-key"></i> {l s='Manual SP-API Credentials (advanced — not needed with Connect)' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='LWA Client ID' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_client_id" value="{$mkpro_client_id|escape:'htmlall':'UTF-8'}" class="form-control" autocomplete="off" placeholder="amzn1.application-oa2-client...." />
                <p class="help-block">{l s='From Seller Central > Apps & Services > Develop Apps ("View credentials"). It starts with amzn1.application-oa2-client — it is NOT an e-mail address.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        {* Secrets are write-only: never echoed back into the page (autofill
           managers corrupt/blank prefilled password fields), and an empty
           field on save keeps the stored value. *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='LWA Client Secret' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="password" name="mkpro_client_secret" value="" class="form-control" autocomplete="new-password"
                       placeholder="{if $mkpro_client_secret}{l s='Stored — leave empty to keep the current secret' mod='amazonmarketplacepro'}{else}{l s='amzn1.oa2-cs.v1...' mod='amazonmarketplacepro'}{/if}" />
                {if $mkpro_client_secret}<p class="help-block">{l s='A client secret is stored. Enter a new value only to replace it.' mod='amazonmarketplacepro'}</p>{/if}
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='LWA Refresh Token' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="password" name="mkpro_refresh_token" value="" class="form-control" autocomplete="new-password"
                       placeholder="{if $mkpro_refresh_token}{l s='Stored — leave empty to keep the current token' mod='amazonmarketplacepro'}{else}Atzr|...{/if}" />
                <p class="help-block">
                    {l s='Generated when you authorize your app in Seller Central.' mod='amazonmarketplacepro'}
                    {if $mkpro_refresh_token} {l s='A token is stored for this environment; enter a new one only to replace it (use Disconnect to clear it).' mod='amazonmarketplacepro'}{/if}
                </p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Seller ID (Merchant Token)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_seller_id" value="{$mkpro_seller_id|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Required for product sync. Found in Seller Central > Account Info' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
    </div>

    {* ── Marketplace (environment now lives with the connection controls) ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-globe"></i> {l s='Marketplace' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Amazon Marketplace' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_marketplace_id" class="form-control">
                    {foreach from=$marketplaces key=mp_id item=mp_label}
                        <option value="{$mp_id|escape:'htmlall':'UTF-8'}"{if $mkpro_marketplace_id == $mp_id} selected="selected"{/if}>{$mp_label|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        {if $mkpro_dev_mode}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Mock Mode (dev)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_use_mock" class="form-control">
                    <option value="1"{if $mkpro_use_mock} selected="selected"{/if}>{l s='Enabled (sample data for products)' mod='amazonmarketplacepro'}</option>
                    <option value="0"{if !$mkpro_use_mock} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        {/if}
    </div>

    {* ── Listing Defaults ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-tags"></i> {l s='Listing Defaults' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Item condition' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_condition_type" class="form-control">
                    <option value="new_new"{if $mkpro_condition_type == 'new_new' || !$mkpro_condition_type} selected="selected"{/if}>{l s='New' mod='amazonmarketplacepro'}</option>
                    <option value="used_like_new"{if $mkpro_condition_type == 'used_like_new'} selected="selected"{/if}>{l s='Used - Like New' mod='amazonmarketplacepro'}</option>
                    <option value="used_very_good"{if $mkpro_condition_type == 'used_very_good'} selected="selected"{/if}>{l s='Used - Very Good' mod='amazonmarketplacepro'}</option>
                    <option value="used_good"{if $mkpro_condition_type == 'used_good'} selected="selected"{/if}>{l s='Used - Good' mod='amazonmarketplacepro'}</option>
                    <option value="used_acceptable"{if $mkpro_condition_type == 'used_acceptable'} selected="selected"{/if}>{l s='Used - Acceptable' mod='amazonmarketplacepro'}</option>
                    <option value="refurbished_refurbished"{if $mkpro_condition_type == 'refurbished_refurbished'} selected="selected"{/if}>{l s='Refurbished' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Condition sent with every listing (applies to all pushed products).' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Stock buffer (security margin)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="number" min="0" name="mkpro_stock_buffer" value="{$mkpro_stock_buffer|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Units kept back from Amazon. Example: with buffer 2 and 10 in stock, Amazon sees 8. Prevents overselling when several channels share stock.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Listing language' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_listing_lang" class="form-control">
                    <option value=""{if !$mkpro_listing_lang} selected="selected"{/if}>{l s='Shop default language' mod='amazonmarketplacepro'}</option>
                    <option value="auto"{if $mkpro_listing_lang == 'auto'} selected="selected"{/if}>{l s='Auto: match the marketplace (DE gets German, FR gets French, ...)' mod='amazonmarketplacepro'}</option>
                    {foreach from=$ps_languages item=lang}
                        <option value="{$lang.id_lang|escape:'htmlall':'UTF-8'}"{if $mkpro_listing_lang == $lang.id_lang} selected="selected"{/if}>{$lang.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">{l s='Language used for titles, descriptions and bullet points pushed to Amazon. Auto mode needs the matching language installed in your shop; it falls back to the default otherwise.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='When out of stock' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_delete_when_oos" class="form-control">
                    <option value="0"{if !$mkpro_delete_when_oos} selected="selected"{/if}>{l s='Publish quantity 0 (listing stays, shows unavailable)' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_delete_when_oos} selected="selected"{/if}>{l s='Delete the listing from Amazon' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Shipping template name' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_shipping_template" value="{$mkpro_shipping_template|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="{l s='e.g. Migrated Template or Free Economy' mod='amazonmarketplacepro'}" />
                <p class="help-block">{l s='Optional. The exact name of a shipping template from Seller Central > Shipping Settings. When set, every pushed offer is assigned to it (merchant_shipping_group). Leave empty to keep Amazon\'s default.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Amazon Business (B2B) price' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="input-group" style="max-width:220px;">
                    <input type="number" min="0" max="99" step="0.1" name="mkpro_b2b_discount" value="{$mkpro_b2b_discount|escape:'htmlall':'UTF-8'}" class="form-control" />
                    <span class="input-group-addon">% {l s='off' mod='amazonmarketplacepro'}</span>
                </div>
                <p class="help-block">{l s='Optional. 0 disables it. When set, every offer also carries a business price for Amazon Business buyers, discounted by this percentage from your normal price.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
    </div>

    {* ── SKU & Export Filters ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-filter"></i> {l s='SKU & Export Filters' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='SKU source' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_sku_source" class="form-control">
                    <option value="reference"{if $mkpro_sku_source == 'reference' || !$mkpro_sku_source} selected="selected"{/if}>{l s='Reference' mod='amazonmarketplacepro'}</option>
                    <option value="ean13"{if $mkpro_sku_source == 'ean13'} selected="selected"{/if}>{l s='EAN code' mod='amazonmarketplacepro'}</option>
                    <option value="supplier_reference"{if $mkpro_sku_source == 'supplier_reference'} selected="selected"{/if}>{l s='Supplier reference' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Which PrestaShop field becomes the Amazon SKU. Products with an empty source field are not exported. Changing this on a live catalog creates NEW listings under the new SKUs.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='SKU prefix' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_sku_prefix" value="{$mkpro_sku_prefix|escape:'htmlall':'UTF-8'}" class="form-control" style="max-width:220px;" />
                <p class="help-block">{l s='Added in front of the SKU, separated by a dash (e.g. prefix AMZ makes AMZ-REF123).' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Price range (min / max)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="row">
                    <div class="col-xs-4"><input type="number" min="0" step="0.01" name="mkpro_price_min" value="{$mkpro_price_min|escape:'htmlall':'UTF-8'}" class="form-control" /></div>
                    <div class="col-xs-4"><input type="number" min="0" step="0.01" name="mkpro_price_max" value="{$mkpro_price_max|escape:'htmlall':'UTF-8'}" class="form-control" /></div>
                </div>
                <p class="help-block">{l s='Only export products whose final price (after markup) falls inside this range. 0 disables a bound.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Quantity min.' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="number" min="0" name="mkpro_qty_min" value="{$mkpro_qty_min|escape:'htmlall':'UTF-8'}" class="form-control" style="max-width:120px;" />
                <p class="help-block">{l s='Only export products with at least this much stock. 0 disables the filter.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Use specific prices' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_use_specific_prices" class="form-control">
                    <option value="0"{if !$mkpro_use_specific_prices} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_use_specific_prices} selected="selected"{/if}>{l s='Enabled' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Customer group for prices' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_specific_price_group" class="form-control">
                    <option value="0">{l s='-- Default --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$customer_groups item=grp}
                        <option value="{$grp.id_group|escape:'htmlall':'UTF-8'}"{if $mkpro_specific_price_group == $grp.id_group} selected="selected"{/if}>{$grp.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">{l s='Specific prices of this group are applied to exported prices (before markup).' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Report e-mail' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_report_email" value="{$mkpro_report_email|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Cron runs (order import, stock sync) send a short report to this address. Empty = no reports.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
    </div>

    {* ── Sync Options ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-exchange"></i> {l s='Sync Options' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Listings sync mode' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_sync_mode" class="form-control">
                    <option value="normal"{if $mkpro_sync_mode == 'normal' || !$mkpro_sync_mode} selected="selected"{/if}>{l s='Normal (full listing data)' mod='amazonmarketplacepro'}</option>
                    <option value="price"{if $mkpro_sync_mode == 'price'} selected="selected"{/if}>{l s='Price only' mod='amazonmarketplacepro'}</option>
                    <option value="quantity"{if $mkpro_sync_mode == 'quantity'} selected="selected"{/if}>{l s='Quantity only' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Price/quantity modes send small partial updates instead of the full listing — much faster for large catalogs.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Force all quantities to zero' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_force_zero_qty" class="form-control">
                    <option value="0"{if !$mkpro_force_zero_qty} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_force_zero_qty} selected="selected"{/if}>{l s='Enabled — publish 0 stock for everything' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Emergency switch (holidays, stock freeze): every pushed offer carries quantity 0. Listings stay online but show unavailable.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Export only products with ASIN' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_only_with_asin" class="form-control">
                    <option value="0"{if !$mkpro_only_with_asin} selected="selected"{/if}>{l s='No' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_only_with_asin} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Limit export to N lines' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_export_limit" class="form-control">
                    <option value="0"{if !$mkpro_export_limit} selected="selected"{/if}>{l s='No limit' mod='amazonmarketplacepro'}</option>
                    {foreach from=[100, 200, 300, 400, 500, 1000, 1500] item=lim}
                        <option value="{$lim}"{if $mkpro_export_limit == $lim} selected="selected"{/if}>{$lim}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Treat PrestaShop EAN-13 field as' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_ean_as" class="form-control">
                    <option value="EAN"{if $mkpro_ean_as != 'UPC'} selected="selected"{/if}>EAN</option>
                    <option value="UPC"{if $mkpro_ean_as == 'UPC'} selected="selected"{/if}>UPC</option>
                </select>
                <p class="help-block">{l s='Choose UPC if you store 12-digit US barcodes in the EAN field.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Export only products updated in the last' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_delta_hours" class="form-control">
                    <option value="0"{if !$mkpro_delta_hours} selected="selected"{/if}>{l s='Ignore (export everything)' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_delta_hours == 1} selected="selected"{/if}>{l s='1 hour' mod='amazonmarketplacepro'}</option>
                    <option value="3"{if $mkpro_delta_hours == 3} selected="selected"{/if}>{l s='3 hours' mod='amazonmarketplacepro'}</option>
                    <option value="6"{if $mkpro_delta_hours == 6} selected="selected"{/if}>{l s='6 hours' mod='amazonmarketplacepro'}</option>
                    <option value="24"{if $mkpro_delta_hours == 24} selected="selected"{/if}>{l s='24 hours' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Strongly recommended for large catalogs: only recently changed products (or products queued by rule changes — see the Queue tab) are exported.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Delete queue entries after' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_queue_ttl_days" class="form-control">
                    <option value="1"{if $mkpro_queue_ttl_days == 1} selected="selected"{/if}>{l s='1 day' mod='amazonmarketplacepro'}</option>
                    <option value="7"{if $mkpro_queue_ttl_days == 7 || !$mkpro_queue_ttl_days} selected="selected"{/if}>{l s='7 days' mod='amazonmarketplacepro'}</option>
                    <option value="15"{if $mkpro_queue_ttl_days == 15} selected="selected"{/if}>{l s='15 days' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Condition note (Used)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_cond_note_used" value="{$mkpro_cond_note_used|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Sent as the condition note when the item condition is any "Used" grade.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Condition note (Refurbished)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_cond_note_refurb" value="{$mkpro_cond_note_refurb|escape:'htmlall':'UTF-8'}" class="form-control" />
            </div>
        </div>
    </div>

    {* ── Shipping Templates by range ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-truck"></i> {l s='Shipping Templates by Price/Weight Range' mod='amazonmarketplacepro'}</div>
        <p class="help-block">{l s='Assign different Seller Central shipping templates depending on the product price or weight. When disabled (or no range matches), the single template name from Listing Defaults is used.' mod='amazonmarketplacepro'}</p>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Use range-based templates' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_ship_tpl_enabled" class="form-control">
                    <option value="0"{if !$mkpro_ship_tpl_enabled} selected="selected"{/if}>{l s='No' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_ship_tpl_enabled} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Ranges based on' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_ship_tpl_basis" class="form-control">
                    <option value="price"{if $mkpro_ship_tpl_basis != 'weight'} selected="selected"{/if}>{l s='Price' mod='amazonmarketplacepro'}</option>
                    <option value="weight"{if $mkpro_ship_tpl_basis == 'weight'} selected="selected"{/if}>{l s='Weight' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <table class="table" id="shiptpl-table" style="max-width:700px;">
            <thead><tr>
                <th>{l s='Basis' mod='amazonmarketplacepro'}</th>
                <th>{l s='Min (incl.)' mod='amazonmarketplacepro'}</th>
                <th>{l s='Max (excl., 0 = open)' mod='amazonmarketplacepro'}</th>
                <th>{l s='Template name' mod='amazonmarketplacepro'}</th><th></th>
            </tr></thead>
            <tbody>
                {if $shipping_templates}
                    {foreach from=$shipping_templates item=tpl}
                    <tr>
                        <td>{$tpl.basis|escape:'htmlall':'UTF-8'}</td>
                        <td>{$tpl.min_value|escape:'htmlall':'UTF-8'}</td>
                        <td>{$tpl.max_value|escape:'htmlall':'UTF-8'}</td>
                        <td>{$tpl.template_name|escape:'htmlall':'UTF-8'}</td>
                        <td><button type="button" class="btn btn-xs btn-danger shiptpl-delete" data-id="{$tpl.id_amazonmarketplacepro_shipping_template|escape:'htmlall':'UTF-8'}"><i class="icon-trash"></i></button></td>
                    </tr>
                    {/foreach}
                {else}
                    <tr id="shiptpl-empty"><td colspan="5" class="text-center text-muted">{l s='No template ranges yet.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
        <div class="form-inline">
            <select id="shiptpl-basis" class="form-control">
                <option value="price">{l s='Price' mod='amazonmarketplacepro'}</option>
                <option value="weight">{l s='Weight' mod='amazonmarketplacepro'}</option>
            </select>
            <input type="number" step="0.01" min="0" id="shiptpl-min" class="form-control" placeholder="{l s='Min' mod='amazonmarketplacepro'}" style="width:100px;" />
            <input type="number" step="0.01" min="0" id="shiptpl-max" class="form-control" placeholder="{l s='Max' mod='amazonmarketplacepro'}" style="width:100px;" />
            <input type="text" id="shiptpl-name" class="form-control" placeholder="{l s='Template name (from Seller Central)' mod='amazonmarketplacepro'}" style="width:280px;" />
            <button type="button" id="shiptpl-add" class="btn btn-success"><i class="icon-plus"></i> {l s='Add range' mod='amazonmarketplacepro'}</button>
        </div>
        <div id="shiptpl-result" style="display:none; margin-top:10px;"></div>
    </div>

    {* ── Carrier Mapping ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-truck"></i> {l s='Carrier Mapping (shipment confirmations)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Which Amazon carrier code to report when confirming shipments for orders shipped with each PrestaShop carrier. Unmapped carriers fall back to name-based detection.' mod='amazonmarketplacepro'}</p>
        <table class="table" style="max-width:600px;">
            <thead><tr><th>{l s='PrestaShop carrier' mod='amazonmarketplacepro'}</th><th>{l s='Amazon carrier code' mod='amazonmarketplacepro'}</th></tr></thead>
            <tbody>
                {foreach from=$carriers item=carrier}
                <tr>
                    <td>{$carrier.name|escape:'htmlall':'UTF-8'}</td>
                    <td>
                        <select name="mkpro_carrier_map[{$carrier.id_carrier|escape:'htmlall':'UTF-8'}]" class="form-control">
                            <option value="">{l s='-- Auto (by name) --' mod='amazonmarketplacepro'}</option>
                            {foreach from=$amazon_carrier_codes item=code}
                                <option value="{$code|escape:'htmlall':'UTF-8'}"{if isset($mkpro_carrier_map[$carrier.id_carrier]) && $mkpro_carrier_map[$carrier.id_carrier] == $code} selected="selected"{/if}>{$code|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    {* ── Order Import Settings ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-shopping-cart"></i> {l s='Order Import Settings' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Taxes on imported orders' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_tax_mode" class="form-control">
                    <option value="amazon"{if $mkpro_tax_mode != 'ps_rules'} selected="selected"{/if}>{l s='Use the tax amounts Amazon reports (default)' mod='amazonmarketplacepro'}</option>
                    <option value="ps_rules"{if $mkpro_tax_mode == 'ps_rules'} selected="selected"{/if}>{l s='Apply my PrestaShop tax rules (Amazon prices treated as tax-inclusive)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Choose the second option if imported orders show 0% VAT: EU orders often arrive with VAT-inclusive prices and no tax breakdown. Shipping is taxed at the highest goods rate on the order.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='VCS invoice upload' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_vcs_enabled" class="form-control">
                    <option value="0"{if !$mkpro_vcs_enabled} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_vcs_enabled} selected="selected"{/if}>{l s='Enabled (requires VAT Calculation Service enrollment)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Uploads each order\'s PrestaShop invoice PDF to Amazon (UPLOAD_VAT_INVOICE feed) via the upload_invoices cron. Only for sellers enrolled in Amazon\'s VAT Calculation Service with EU VAT registration.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Automatic review requests' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_auto_review_request" class="form-control">
                    <option value="0"{if !$mkpro_auto_review_request} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_auto_review_request} selected="selected"{/if}>{l s='Enabled (via request_reviews cron)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Sends Amazon\'s standard review solicitation for each delivered order (once per order, in Amazon\'s allowed window).' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Download orders in the last' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="input-group" style="max-width:260px;">
                    <input type="number" min="1" name="mkpro_order_lookback_value" value="{$mkpro_order_lookback_value|escape:'htmlall':'UTF-8'}" class="form-control" />
                    <span class="input-group-btn" style="width:120px;">
                        <select name="mkpro_order_lookback_unit" class="form-control">
                            <option value="days"{if $mkpro_order_lookback_unit != 'hours'} selected="selected"{/if}>{l s='days' mod='amazonmarketplacepro'}</option>
                            <option value="hours"{if $mkpro_order_lookback_unit == 'hours'} selected="selected"{/if}>{l s='hours' mod='amazonmarketplacepro'}</option>
                        </select>
                    </span>
                </div>
                <p class="help-block">{l s='Lookback window for order imports (manual and cron).' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Import FBA orders' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_import_fba_orders" class="form-control">
                    <option value="1"{if $mkpro_import_fba_orders} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                    <option value="0"{if !$mkpro_import_fba_orders} selected="selected"{/if}>{l s='No (skip orders fulfilled by Amazon)' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='FBA orders state' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_fba_order_state" class="form-control">
                    <option value="0">{l s='-- Same as default order state --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$order_states item=state}
                        <option value="{$state.id_order_state|escape:'htmlall':'UTF-8'}"{if $mkpro_fba_order_state == $state.id_order_state} selected="selected"{/if}>{$state.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">{l s='PS state given to imported FBA orders (e.g. Shipped — Amazon already handles delivery).' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Shipped orders state' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_order_state_shipped" class="form-control">
                    <option value="0">{l s='-- Same as default order state --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$order_states item=state}
                        <option value="{$state.id_order_state|escape:'htmlall':'UTF-8'}"{if $mkpro_order_state_shipped == $state.id_order_state} selected="selected"{/if}>{$state.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">{l s='PS state for orders Amazon already reports as Shipped at import time.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Prioritize ASIN when matching products' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_prioritize_asin" class="form-control">
                    <option value="0"{if !$mkpro_prioritize_asin} selected="selected"{/if}>{l s='No (match by SKU first)' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_prioritize_asin} selected="selected"{/if}>{l s='Yes (match by ASIN first)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='ASIN matching uses the SKU-ASIN mapping built by product syncs and "Match ASINs by EAN".' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Use an anonymized e-mail for customers' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_fake_email" class="form-control">
                    <option value="0"{if !$mkpro_fake_email} selected="selected"{/if}>{l s='No (keep the Amazon relay address)' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_fake_email} selected="selected"{/if}>{l s='Yes (order-id@marketplace.amazon)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Amazon relay addresses expire after a while; a synthetic address avoids bounced shop e-mails. Buyer messaging via Amazon still works.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Customer group for Amazon buyers' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_customer_group" class="form-control">
                    <option value="0">{l s='-- Shop default --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$customer_groups item=grp}
                        <option value="{$grp.id_group|escape:'htmlall':'UTF-8'}"{if $mkpro_customer_group == $grp.id_group} selected="selected"{/if}>{$grp.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Orders without enough stock' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_skip_no_stock" class="form-control">
                    <option value="0"{if !$mkpro_skip_no_stock} selected="selected"{/if}>{l s='Create the PS order anyway' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_skip_no_stock} selected="selected"{/if}>{l s='Park in Pending Orders for manual review' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='When enabled, staged orders whose products lack stock (and cannot be ordered out of stock) wait in the Pending Orders tab instead of becoming PS orders.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Default Carrier' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_default_carrier" class="form-control">
                    <option value="0">{l s='-- None --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$carriers item=carrier}
                        <option value="{$carrier.id_carrier|escape:'htmlall':'UTF-8'}"{if $mkpro_default_carrier == $carrier.id_carrier} selected="selected"{/if}>{$carrier.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Default Order State' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_default_order_state" class="form-control">
                    {foreach from=$order_states item=state}
                        <option value="{$state.id_order_state|escape:'htmlall':'UTF-8'}"{if $mkpro_default_order_state == $state.id_order_state} selected="selected"{/if}>{$state.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>

    {* ── Real-time Hooks ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-bolt"></i> {l s='Real-time Sync (Hooks)' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Auto-push stock/price on product save' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_sync_stock_hook" class="form-control">
                    <option value="0"{if !$mkpro_sync_stock_hook} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_sync_stock_hook} selected="selected"{/if}>{l s='Enabled (production only)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Automatically push updated price and stock to Amazon when a product is saved.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Auto-confirm shipment on status change' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_sync_order_hook" class="form-control">
                    <option value="0"{if !$mkpro_sync_order_hook} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_sync_order_hook} selected="selected"{/if}>{l s='Enabled (production only)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Send shipment confirmation with tracking to Amazon when order is marked Shipped.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Auto-cancel on Amazon when PS order cancelled' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_sync_cancel_hook" class="form-control">
                    <option value="0"{if !$mkpro_sync_cancel_hook} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_sync_cancel_hook} selected="selected"{/if}>{l s='Enabled (production only)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Notify Amazon when an Amazon-imported order is cancelled in PrestaShop.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
    </div>

    {* ── Connection Test ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-plug"></i> {l s='Connection Test' mod='amazonmarketplacepro'}</div>
        <p>{l s='Test your Amazon SP-API credentials. Save settings first if you just changed them.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="test-amazon-connection" class="btn btn-primary">
            <i class="icon-refresh"></i> {l s='Test Amazon Connection' mod='amazonmarketplacepro'}
        </button>
        <pre id="amazon-connection-result" style="display:none; margin-top:15px; padding:12px; white-space:pre-wrap; word-break:break-word;"></pre>
    </div>

    <div class="panel">
        <div class="panel-footer">
            <button type="submit" name="submitMkproSettings" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save Settings' mod='amazonmarketplacepro'}
            </button>
        </div>
    </div>

    </form>
</div>

{* ═══════════════════════ ORDERS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-orders">

    <div class="panel">
        <div class="panel-heading"><i class="icon-cloud-download"></i> {l s='Import Amazon Orders' mod='amazonmarketplacepro'}</div>
        <p>{l s='Fetch orders from Amazon and stage them. Now captures shipping costs, tax, buyer address (via RDT), and FBA channel.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="import-amazon-orders" class="btn btn-primary">
            <i class="icon-download"></i> {l s='Fetch & Stage Amazon Orders' mod='amazonmarketplacepro'}
        </button>
        <button type="button" id="create-ps-orders" class="btn btn-success" style="margin-left:10px;">
            <i class="icon-check"></i> {l s='Create PrestaShop Orders' mod='amazonmarketplacepro'}
        </button>
        <p class="help-block" style="margin-top:8px;">
            {l s='Orders now include: shipping costs, tax, real buyer address, and FBA/MFN channel detection.' mod='amazonmarketplacepro'}
        </p>
        <div id="amazon-orders-summary" style="display:none; margin-top:15px;"></div>
        <div id="amazon-orders-result" style="display:none; margin-top:10px;"></div>
    </div>

    {* ── Buyer Messaging ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-envelope"></i> {l s='Buyer Messaging' mod='amazonmarketplacepro'}</div>
        <p>{l s='Send an Amazon-approved, order-related message to the buyer of an imported order. Amazon limits which message types are allowed per order; buyer replies arrive in Seller Central.' mod='amazonmarketplacepro'}</p>
        <div class="row" style="margin-bottom:10px;">
            <div class="col-lg-4">
                <input type="text" id="msg-order-id" class="form-control" placeholder="{l s='Amazon Order ID (e.g. 123-1234567-1234567)' mod='amazonmarketplacepro'}" />
            </div>
            <div class="col-lg-3">
                <button type="button" id="msg-load-actions" class="btn btn-default">
                    <i class="icon-refresh"></i> {l s='Load allowed message types' mod='amazonmarketplacepro'}
                </button>
            </div>
        </div>
        <div class="row" style="margin-bottom:10px;">
            <div class="col-lg-4">
                <select id="msg-action" class="form-control" disabled="disabled">
                    <option value="">{l s='-- Load message types first --' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="row" style="margin-bottom:10px;">
            <div class="col-lg-7">
                <textarea id="msg-text" class="form-control" rows="3" placeholder="{l s='Message text' mod='amazonmarketplacepro'}"></textarea>
            </div>
        </div>
        <button type="button" id="msg-send" class="btn btn-primary" disabled="disabled">
            <i class="icon-envelope"></i> {l s='Send message to buyer' mod='amazonmarketplacepro'}
        </button>
        <button type="button" id="msg-request-review" class="btn btn-default" style="margin-left:10px;">
            <i class="icon-star"></i> {l s='Request a review' mod='amazonmarketplacepro'}
        </button>
        <p class="help-block" style="margin-top:8px;">
            {l s='"Request a review" sends Amazon\'s standard, Amazon-templated review solicitation (allowed once per order, 5-30 days after delivery). Enable automatic review requests in Settings and schedule the request_reviews cron for hands-free operation.' mod='amazonmarketplacepro'}
        </p>
        <div id="msg-result" style="display:none; margin-top:10px;"></div>
    </div>

</div>

{* ═══════════════════════ PRODUCTS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-products">

    <div class="panel">
        <div class="panel-heading"><i class="icon-refresh"></i> {l s='Product Sync (PrestaShop &harr; Amazon)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Reconcile products by SKU. Now syncs full data: description, bullet points, brand, images, EAN, and categories.' mod='amazonmarketplacepro'}</p>
        <div class="btn-group" style="margin-bottom:15px;">
            <button type="button" id="sync-products-ps" class="btn btn-primary">
                <i class="icon-cloud-upload"></i> {l s='Sync PS to Amazon' mod='amazonmarketplacepro'}
            </button>
            <button type="button" id="sync-products-amazon" class="btn btn-default">
                <i class="icon-cloud-download"></i> {l s='Sync Amazon to PS' mod='amazonmarketplacepro'}
            </button>
            <button type="button" id="push-products" class="btn btn-warning">
                <i class="icon-upload"></i> {l s='Push to Amazon' mod='amazonmarketplacepro'}
            </button>
            <button type="button" id="match-catalog" class="btn btn-default">
                <i class="icon-magic"></i> {l s='Match ASINs by EAN' mod='amazonmarketplacepro'}
            </button>
        </div>
        <div id="match-catalog-result" style="display:none; margin-bottom:10px;"></div>

        <div class="row" style="margin-bottom:10px;">
            <div class="col-lg-3">
                <select id="import-catalog-category" class="form-control">
                    <option value="0">{l s='-- Import into Home category --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$ps_categories item=cat}
                        <option value="{$cat.id_category|escape:'htmlall':'UTF-8'}">{$cat.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-lg-5">
                <button type="button" id="import-catalog" class="btn btn-default">
                    <i class="icon-download"></i> {l s='Create PS products from Amazon-only listings' mod='amazonmarketplacepro'}
                </button>
            </div>
        </div>
        <p class="help-block">{l s='Products found on Amazon but missing in PrestaShop are created as INACTIVE products (title, description, brand, price, stock, images) for review. Prices are imported without a tax group — assign one before activating.' mod='amazonmarketplacepro'}</p>
        <div id="import-catalog-result" style="display:none; margin-bottom:10px;"></div>
    </div>

    {* ── Bulk Feeds ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-cloud-upload"></i> {l s='Bulk Push (Feeds API)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Send ALL pending listing changes to Amazon in one feed document instead of one call per SKU — the right tool for large catalogs. Amazon processes feeds asynchronously: submit, then check status until done.' mod='amazonmarketplacepro'}</p>
        <div class="btn-group" style="margin-bottom:10px;">
            <button type="button" id="feed-submit" class="btn btn-warning">
                <i class="icon-cloud-upload"></i> {l s='Submit bulk feed' mod='amazonmarketplacepro'}
            </button>
            <button type="button" id="feed-poll" class="btn btn-default">
                <i class="icon-refresh"></i> {l s='Check feed status' mod='amazonmarketplacepro'}
            </button>
        </div>
        <div id="feed-result" style="display:none; margin-bottom:10px;"></div>
        <div id="feed-table"></div>
        <div id="amazon-products-summary" style="display:none;"></div>
        <div id="amazon-products-result" style="display:none; margin-top:10px;"></div>
        <div id="amazon-push-result" style="display:none; margin-top:10px;"></div>
    </div>

    {* ── Category Mapping ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-sitemap"></i> {l s='Category Mapping (PS Category &rarr; Amazon Product Type)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Map PrestaShop categories to Amazon product types. Products with a mapped category will push full listing data (title, description, bullets, brand, images) instead of offer-only.' mod='amazonmarketplacepro'}</p>

        <div class="row" style="margin-bottom:15px;">
            <div class="col-lg-3">
                <select id="catmap-category" class="form-control">
                    <option value="">{l s='-- Select PS Category --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$ps_categories item=cat}
                        <option value="{$cat.id_category|escape:'htmlall':'UTF-8'}">{$cat.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-lg-3">
                <input type="text" id="catmap-product-type" class="form-control" placeholder="{l s='Amazon Product Type (e.g. SHIRT)' mod='amazonmarketplacepro'}" />
            </div>
            <div class="col-lg-2">
                <input type="text" id="catmap-browse-node" class="form-control" placeholder="{l s='Browse Node (optional)' mod='amazonmarketplacepro'}" />
            </div>
            <div class="col-lg-2">
                <button type="button" id="catmap-save" class="btn btn-success"><i class="icon-plus"></i> {l s='Add Mapping' mod='amazonmarketplacepro'}</button>
            </div>
        </div>
        <div class="row" style="margin-bottom:15px;">
            <div class="col-lg-8">
                <textarea id="catmap-attributes" class="form-control" rows="3" placeholder='{l s='Extra attributes (optional JSON), e.g. {"target_gender": "male", "material": "feature:Material", "country_of_origin": "CN"}' mod='amazonmarketplacepro'}'></textarea>
                <p class="help-block">
                    {l s='Product-type specific attributes sent with every full listing in this category. Use "feature:Name" to pull a PrestaShop product feature value. GPSR/compliance fields go here too. Advanced: a JSON array value is passed to Amazon unchanged.' mod='amazonmarketplacepro'}
                </p>
            </div>
        </div>
        <div id="catmap-result" style="display:none;"></div>

        <table class="table" id="catmap-table">
            <thead>
                <tr><th>{l s='PS Category' mod='amazonmarketplacepro'}</th><th>{l s='Amazon Product Type' mod='amazonmarketplacepro'}</th><th>{l s='Browse Node' mod='amazonmarketplacepro'}</th><th>{l s='Actions' mod='amazonmarketplacepro'}</th></tr>
            </thead>
            <tbody>
                {if $category_mappings}
                    {foreach from=$category_mappings item=map}
                        <tr data-id="{$map.id_amazonmarketplacepro_category_map|escape:'htmlall':'UTF-8'}">
                            <td>{$map.category_name|escape:'htmlall':'UTF-8'}</td>
                            <td>{$map.amazon_product_type|escape:'htmlall':'UTF-8'}</td>
                            <td>{$map.amazon_browse_node|escape:'htmlall':'UTF-8'}</td>
                            <td><button type="button" class="btn btn-xs btn-danger catmap-delete" data-id="{$map.id_amazonmarketplacepro_category_map|escape:'htmlall':'UTF-8'}"><i class="icon-trash"></i></button></td>
                        </tr>
                    {/foreach}
                {else}
                    <tr id="catmap-empty"><td colspan="4" class="text-center">{l s='No category mappings configured yet.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Amazon Product List (read-only)' mod='amazonmarketplacepro'}</div>
        <button type="button" id="list-amazon-products" class="btn btn-info">
            <i class="icon-list"></i> {l s='List Amazon Products' mod='amazonmarketplacepro'}
        </button>
        <div id="amazon-list-messages" style="display:none; margin-top:15px;"></div>
        <div id="amazon-list-result" style="display:none; margin-top:10px;"></div>
    </div>

</div>

{* ═══════════════════════ RETURNS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-returns">

    <div class="panel">
        <div class="panel-heading"><i class="icon-undo"></i> {l s='Amazon Returns & Cancellations' mod='amazonmarketplacepro'}</div>
        <p>{l s='Import returns and cancellations from Amazon. Process them to create PS credit slips or cancel PS orders.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="import-returns" class="btn btn-primary">
            <i class="icon-download"></i> {l s='Import Returns from Amazon' mod='amazonmarketplacepro'}
        </button>
        <button type="button" id="process-returns" class="btn btn-success" style="margin-left:10px;">
            <i class="icon-check"></i> {l s='Process Pending Returns' mod='amazonmarketplacepro'}
        </button>
        <div id="returns-summary" style="display:none; margin-top:15px;"></div>
        <div id="returns-result" style="display:none; margin-top:10px;"></div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Return History' mod='amazonmarketplacepro'}</div>
        {if $returns && count($returns) > 0}
            <table class="table" id="returns-table">
                <thead>
                    <tr>
                        <th>{l s='Amazon Order' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Type' mod='amazonmarketplacepro'}</th>
                        <th>{l s='SKU' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Title' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Qty' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Refund' mod='amazonmarketplacepro'}</th>
                        <th>{l s='PS Order' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Credit Slip' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Status' mod='amazonmarketplacepro'}</th>
                        <th>{l s='Date' mod='amazonmarketplacepro'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$returns item=ret}
                        <tr class="{if $ret.return_status == 'imported'}warning{elseif $ret.return_status == 'refunded'}success{/if}">
                            <td>{$ret.amazon_order_id|escape:'htmlall':'UTF-8'}</td>
                            <td>
                                {if $ret.status == 'Canceled'}
                                    <span class="badge badge-danger">{l s='Cancelled' mod='amazonmarketplacepro'}</span>
                                {elseif $ret.status == 'Returned'}
                                    <span class="badge badge-warning">{l s='Returned' mod='amazonmarketplacepro'}</span>
                                {else}
                                    <span class="badge badge-default">{$ret.status|escape:'htmlall':'UTF-8'}</span>
                                {/if}
                            </td>
                            <td>{$ret.seller_sku|escape:'htmlall':'UTF-8'}</td>
                            <td>{$ret.title|truncate:40:'...':true|escape:'htmlall':'UTF-8'}</td>
                            <td>{$ret.quantity|escape:'htmlall':'UTF-8'}</td>
                            <td>{$ret.refund_amount|escape:'htmlall':'UTF-8'} {$ret.currency|escape:'htmlall':'UTF-8'}</td>
                            <td>
                                {if $ret.id_order > 0}
                                    <span class="badge badge-info">#{$ret.id_order|escape:'htmlall':'UTF-8'}</span>
                                {else}
                                    <span class="badge badge-default">-</span>
                                {/if}
                            </td>
                            <td>
                                {if $ret.id_order_slip > 0}
                                    <span class="badge badge-success">#{$ret.id_order_slip|escape:'htmlall':'UTF-8'}</span>
                                {else}
                                    <span class="badge badge-default">-</span>
                                {/if}
                            </td>
                            <td>
                                {if $ret.return_status == 'imported'}
                                    <span class="badge badge-warning">{l s='Pending' mod='amazonmarketplacepro'}</span>
                                {elseif $ret.return_status == 'processed'}
                                    <span class="badge badge-info">{l s='Processed' mod='amazonmarketplacepro'}</span>
                                {elseif $ret.return_status == 'refunded'}
                                    <span class="badge badge-success">{l s='Refunded' mod='amazonmarketplacepro'}</span>
                                {else}
                                    <span class="badge badge-default">{$ret.return_status|escape:'htmlall':'UTF-8'}</span>
                                {/if}
                            </td>
                            <td style="white-space:nowrap;">{$ret.date_add|escape:'htmlall':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-info">{l s='No returns imported yet.' mod='amazonmarketplacepro'}</div>
        {/if}
    </div>

</div>

{* ═══════════════════════ FBA TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-fba">

    <div class="panel">
        <div class="panel-heading"><i class="icon-truck"></i> {l s='FBA Inventory' mod='amazonmarketplacepro'}</div>
        <p>{l s='Sync Fulfillment by Amazon (FBA) inventory levels. FBA stock can optionally sync to PrestaShop quantities.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="btn-sync-fba" class="btn btn-primary"><i class="icon-refresh"></i> {l s='Sync FBA Inventory' mod='amazonmarketplacepro'}</button>
        <button type="button" id="btn-fba-stock-ps" class="btn btn-default"><i class="icon-download"></i> {l s='Update PS Stock from FBA' mod='amazonmarketplacepro'}</button>
        <div id="fba-result" style="margin-top:15px;"></div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='FBA Inventory Levels' mod='amazonmarketplacepro'}</div>
        <div class="table-responsive">
            <table class="table" id="fba-table">
                <thead><tr>
                    <th>{l s='SKU' mod='amazonmarketplacepro'}</th><th>{l s='ASIN' mod='amazonmarketplacepro'}</th>
                    <th>{l s='FN SKU' mod='amazonmarketplacepro'}</th><th>{l s='Product' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Fulfillable' mod='amazonmarketplacepro'}</th><th>{l s='Reserved' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Inbound' mod='amazonmarketplacepro'}</th><th>{l s='Unfulfillable' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Total' mod='amazonmarketplacepro'}</th><th>{l s='Last Synced' mod='amazonmarketplacepro'}</th>
                </tr></thead>
                <tbody>
                {if $fba_inventory}
                    {foreach from=$fba_inventory item=inv}
                    <tr>
                        <td><code>{$inv.seller_sku|escape:'htmlall':'UTF-8'}</code></td>
                        <td>{$inv.asin|escape:'htmlall':'UTF-8'}</td>
                        <td><small>{$inv.fn_sku|escape:'htmlall':'UTF-8'}</small></td>
                        <td>{$inv.product_name|truncate:40|escape:'htmlall':'UTF-8'}</td>
                        <td><strong>{$inv.fulfillable_qty|escape:'htmlall':'UTF-8'}</strong></td>
                        <td>{$inv.reserved_qty|escape:'htmlall':'UTF-8'}</td>
                        <td>{$inv.inbound_shipped_qty|escape:'htmlall':'UTF-8'}</td>
                        <td>{$inv.unfulfillable_qty|escape:'htmlall':'UTF-8'}</td>
                        <td>{$inv.total_qty|escape:'htmlall':'UTF-8'}</td>
                        <td><small>{$inv.last_synced|escape:'htmlall':'UTF-8'}</small></td>
                    </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="10" class="text-center text-muted">{l s='No FBA inventory data yet. Click "Sync FBA Inventory" to fetch.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-paper-plane"></i> {l s='Multi-Channel Fulfillment (MCF)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Send a PrestaShop order to Amazon for fulfillment from FBA inventory.' mod='amazonmarketplacepro'}</p>
        <div class="form-inline">
            <input type="number" id="mcf-order-id" class="form-control" placeholder="{l s='PS Order ID' mod='amazonmarketplacepro'}" style="width:150px" />
            <button type="button" id="btn-create-mcf" class="btn btn-warning"><i class="icon-truck"></i> {l s='Create MCF Order' mod='amazonmarketplacepro'}</button>
        </div>
        <div id="mcf-result" style="margin-top:10px;"></div>
    </div>
</div>

{* ═══════════════════════ REPRICING TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-repricing">

    <div class="panel">
        <div class="panel-heading"><i class="icon-usd"></i> {l s='Competitive Pricing & Repricing' mod='amazonmarketplacepro'}</div>
        <p>{l s='Fetch Buy Box and lowest prices from Amazon, then apply pricing rules to stay competitive.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="btn-fetch-pricing" class="btn btn-primary"><i class="icon-refresh"></i> {l s='Fetch Competitive Pricing' mod='amazonmarketplacepro'}</button>
        <button type="button" id="btn-apply-rules" class="btn btn-default"><i class="icon-cog"></i> {l s='Apply Pricing Rules' mod='amazonmarketplacepro'}</button>
        <button type="button" id="btn-push-prices" class="btn btn-success"><i class="icon-arrow-up"></i> {l s='Push Suggested Prices' mod='amazonmarketplacepro'}</button>
        <div id="pricing-result" style="margin-top:15px;"></div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-plus"></i> {l s='Pricing Rules' mod='amazonmarketplacepro'}</div>
        <div class="form-inline" style="margin-bottom:15px;">
            <input type="text" id="rule-name" class="form-control" placeholder="{l s='Rule Name' mod='amazonmarketplacepro'}" />
            <select id="rule-type" class="form-control">
                <option value="match_lowest">{l s='Match Lowest' mod='amazonmarketplacepro'}</option>
                <option value="beat_lowest">{l s='Beat Lowest' mod='amazonmarketplacepro'}</option>
                <option value="match_buybox">{l s='Match Buy Box' mod='amazonmarketplacepro'}</option>
                <option value="beat_buybox">{l s='Beat Buy Box' mod='amazonmarketplacepro'}</option>
                <option value="fixed_margin">{l s='Fixed Margin' mod='amazonmarketplacepro'}</option>
            </select>
            <input type="number" step="0.01" id="price-adjustment" class="form-control" placeholder="{l s='Adjustment' mod='amazonmarketplacepro'}" style="width:100px" />
            <select id="adjustment-type" class="form-control">
                <option value="percentage">%</option>
                <option value="fixed">{l s='Fixed' mod='amazonmarketplacepro'}</option>
            </select>
            <input type="number" step="0.01" id="min-price" class="form-control" placeholder="{l s='Min Price' mod='amazonmarketplacepro'}" style="width:100px" />
            <input type="number" step="0.01" id="max-price" class="form-control" placeholder="{l s='Max Price' mod='amazonmarketplacepro'}" style="width:100px" />
            <button type="button" id="btn-save-rule" class="btn btn-primary"><i class="icon-save"></i> {l s='Save Rule' mod='amazonmarketplacepro'}</button>
        </div>
        <table class="table" id="rules-table">
            <thead><tr><th>{l s='Name' mod='amazonmarketplacepro'}</th><th>{l s='Type' mod='amazonmarketplacepro'}</th><th>{l s='Adjustment' mod='amazonmarketplacepro'}</th><th>{l s='Min/Max' mod='amazonmarketplacepro'}</th><th>{l s='Active' mod='amazonmarketplacepro'}</th><th></th></tr></thead>
            <tbody>
            {if $pricing_rules}
                {foreach from=$pricing_rules item=rule}
                <tr>
                    <td>{$rule.name|escape:'htmlall':'UTF-8'}</td>
                    <td><span class="label label-info">{$rule.rule_type|escape:'htmlall':'UTF-8'}</span></td>
                    <td>{$rule.price_adjustment|escape:'htmlall':'UTF-8'} {if $rule.adjustment_type == 'percentage'}%{else}{l s='fixed' mod='amazonmarketplacepro'}{/if}</td>
                    <td>{$rule.min_price|escape:'htmlall':'UTF-8'} / {$rule.max_price|escape:'htmlall':'UTF-8'}</td>
                    <td>{if $rule.active}<span class="label label-success">{l s='Yes' mod='amazonmarketplacepro'}</span>{else}<span class="label label-default">{l s='No' mod='amazonmarketplacepro'}</span>{/if}</td>
                    <td><button type="button" class="btn btn-xs btn-danger btn-delete-rule" data-id="{$rule.id_amazonmarketplacepro_pricing_rule|escape:'htmlall':'UTF-8'}"><i class="icon-trash"></i></button></td>
                </tr>
                {/foreach}
            {else}
                <tr><td colspan="6" class="text-muted text-center">{l s='No pricing rules configured.' mod='amazonmarketplacepro'}</td></tr>
            {/if}
            </tbody>
        </table>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-trophy"></i> {l s='Buy Box Status' mod='amazonmarketplacepro'}</div>
        <div class="table-responsive">
            <table class="table" id="buybox-table">
                <thead><tr>
                    <th>{l s='SKU' mod='amazonmarketplacepro'}</th><th>{l s='Product' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Our Price' mod='amazonmarketplacepro'}</th><th>{l s='Buy Box' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Lowest' mod='amazonmarketplacepro'}</th><th>{l s='Winner?' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Offers' mod='amazonmarketplacepro'}</th><th>{l s='Suggested' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Last Repriced' mod='amazonmarketplacepro'}</th>
                </tr></thead>
                <tbody>
                {if $competitive_prices}
                    {foreach from=$competitive_prices item=cp}
                    <tr>
                        <td><code>{$cp.seller_sku|escape:'htmlall':'UTF-8'}</code></td>
                        <td>{$cp.ps_name|truncate:30|escape:'htmlall':'UTF-8'}</td>
                        <td>{$cp.our_price|escape:'htmlall':'UTF-8'}</td>
                        <td>{$cp.buybox_landed|escape:'htmlall':'UTF-8'}</td>
                        <td>{$cp.lowest_landed|escape:'htmlall':'UTF-8'}</td>
                        <td>{if $cp.is_buybox_winner}<span class="label label-success">{l s='YES' mod='amazonmarketplacepro'}</span>{else}<span class="label label-danger">{l s='NO' mod='amazonmarketplacepro'}</span>{/if}</td>
                        <td>{$cp.number_of_offers|escape:'htmlall':'UTF-8'}</td>
                        <td>{if $cp.suggested_price > 0}<strong>{$cp.suggested_price|escape:'htmlall':'UTF-8'}</strong>{else}-{/if}</td>
                        <td><small>{$cp.last_repriced|escape:'htmlall':'UTF-8'}</small></td>
                    </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="9" class="text-muted text-center">{l s='No competitive pricing data yet. Click "Fetch Competitive Pricing".' mod='amazonmarketplacepro'}</td></tr>
                {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>

{* ═══════════════════════ FEES TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-fees">

    <div class="panel">
        <div class="panel-heading"><i class="icon-money"></i> {l s='Amazon Fees & Commissions' mod='amazonmarketplacepro'}</div>
        <p>{l s='Track Amazon fees (referral fees, FBA fees, commissions) per order via the Finances API.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="btn-fetch-fees" class="btn btn-primary"><i class="icon-refresh"></i> {l s='Fetch Order Fees' mod='amazonmarketplacepro'}</button>
        <div id="fees-result" style="margin-top:15px;"></div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Fee Summary by Type' mod='amazonmarketplacepro'}</div>
        <table class="table" id="fees-table">
            <thead><tr><th>{l s='Fee Type' mod='amazonmarketplacepro'}</th><th>{l s='Total Amount' mod='amazonmarketplacepro'}</th><th>{l s='Count' mod='amazonmarketplacepro'}</th><th>{l s='Currency' mod='amazonmarketplacepro'}</th></tr></thead>
            <tbody>
            {if $fee_summary}
                {foreach from=$fee_summary item=fee}
                <tr>
                    <td>{$fee.fee_type|escape:'htmlall':'UTF-8'}</td>
                    <td><strong>{$fee.total_amount|string_format:"%.2f"|escape:'htmlall':'UTF-8'}</strong></td>
                    <td>{$fee.count|escape:'htmlall':'UTF-8'}</td>
                    <td>{$fee.currency|escape:'htmlall':'UTF-8'}</td>
                </tr>
                {/foreach}
            {else}
                <tr><td colspan="4" class="text-muted text-center">{l s='No fee data yet. Click "Fetch Order Fees" to pull from Amazon Finances API.' mod='amazonmarketplacepro'}</td></tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ REPORTS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-reports">

    <div class="panel">
        <div class="panel-heading"><i class="icon-bar-chart"></i> {l s='Amazon Reports' mod='amazonmarketplacepro'}</div>
        <p>{l s='Request and download Amazon reports: merchant listings, settlement, FBA inventory.' mod='amazonmarketplacepro'}</p>
        <div class="btn-group">
            <button type="button" id="btn-report-listings" class="btn btn-primary" data-type="GET_MERCHANT_LISTINGS_ALL_DATA"><i class="icon-th-list"></i> {l s='Merchant Listings' mod='amazonmarketplacepro'}</button>
            <button type="button" id="btn-report-settlement" class="btn btn-default" data-type="GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE"><i class="icon-money"></i> {l s='Settlement Report' mod='amazonmarketplacepro'}</button>
            <button type="button" id="btn-report-fba-inv" class="btn btn-default" data-type="GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA"><i class="icon-truck"></i> {l s='FBA Inventory Report' mod='amazonmarketplacepro'}</button>
        </div>
        <button type="button" id="btn-poll-reports" class="btn btn-warning" style="margin-left:10px;"><i class="icon-refresh"></i> {l s='Poll Pending Reports' mod='amazonmarketplacepro'}</button>
        <div id="reports-result" style="margin-top:15px;"></div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Report History' mod='amazonmarketplacepro'}</div>
        <table class="table" id="reports-table">
            <thead><tr><th>{l s='Report ID' mod='amazonmarketplacepro'}</th><th>{l s='Type' mod='amazonmarketplacepro'}</th><th>{l s='Status' mod='amazonmarketplacepro'}</th><th>{l s='Rows' mod='amazonmarketplacepro'}</th><th>{l s='Requested' mod='amazonmarketplacepro'}</th></tr></thead>
            <tbody>
            {if $reports}
                {foreach from=$reports item=rpt}
                <tr>
                    <td><small><code>{$rpt.report_id|truncate:20|escape:'htmlall':'UTF-8'}</code></small></td>
                    <td><span class="label label-info">{$rpt.report_type|escape:'htmlall':'UTF-8'}</span></td>
                    <td>{if $rpt.status == 'DONE'}<span class="label label-success">{$rpt.status|escape:'htmlall':'UTF-8'}</span>{elseif $rpt.status == 'FATAL'}<span class="label label-danger">{$rpt.status|escape:'htmlall':'UTF-8'}</span>{else}<span class="label label-warning">{$rpt.status|escape:'htmlall':'UTF-8'}</span>{/if}</td>
                    <td>{$rpt.row_count|escape:'htmlall':'UTF-8'}</td>
                    <td><small>{$rpt.date_add|escape:'htmlall':'UTF-8'}</small></td>
                </tr>
                {/foreach}
            {else}
                <tr><td colspan="5" class="text-muted text-center">{l s='No reports requested yet.' mod='amazonmarketplacepro'}</td></tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ PROMOTIONS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-promotions">

    <div class="panel">
        <div class="panel-heading"><i class="icon-tag"></i> {l s='Promotions & Coupons Sync' mod='amazonmarketplacepro'}</div>
        <p>{l s='Import Amazon order promotions and export PS cart rules for tracking.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="btn-import-promos" class="btn btn-primary"><i class="icon-download"></i> {l s='Import from Orders' mod='amazonmarketplacepro'}</button>
        <button type="button" id="btn-export-promos" class="btn btn-default"><i class="icon-upload"></i> {l s='Export PS Cart Rules' mod='amazonmarketplacepro'}</button>
        <button type="button" id="btn-create-cart-rules" class="btn btn-success"><i class="icon-plus"></i> {l s='Create PS Cart Rules' mod='amazonmarketplacepro'}</button>
        <div id="promos-result" style="margin-top:15px;"></div>
    </div>

    {if $promotion_stats}
    <div class="panel">
        <div class="panel-heading"><i class="icon-signal"></i> {l s='Promotion Stats' mod='amazonmarketplacepro'}</div>
        <div class="row">
            <div class="col-md-3"><div class="well text-center"><strong>{$promotion_stats.total|escape:'htmlall':'UTF-8'}</strong><br>{l s='Total' mod='amazonmarketplacepro'}</div></div>
            <div class="col-md-3"><div class="well text-center"><strong>{$promotion_stats.from_amazon|escape:'htmlall':'UTF-8'}</strong><br>{l s='From Amazon' mod='amazonmarketplacepro'}</div></div>
            <div class="col-md-3"><div class="well text-center"><strong>{$promotion_stats.from_ps|escape:'htmlall':'UTF-8'}</strong><br>{l s='From PS' mod='amazonmarketplacepro'}</div></div>
            <div class="col-md-3"><div class="well text-center"><strong>{$promotion_stats.with_cart_rule|escape:'htmlall':'UTF-8'}</strong><br>{l s='With Cart Rule' mod='amazonmarketplacepro'}</div></div>
        </div>
    </div>
    {/if}

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Promotion History' mod='amazonmarketplacepro'}</div>
        <table class="table">
            <thead><tr><th>{l s='Promo ID' mod='amazonmarketplacepro'}</th><th>{l s='Type' mod='amazonmarketplacepro'}</th><th>{l s='SKU' mod='amazonmarketplacepro'}</th><th>{l s='Discount' mod='amazonmarketplacepro'}</th><th>{l s='Direction' mod='amazonmarketplacepro'}</th><th>{l s='Status' mod='amazonmarketplacepro'}</th><th>{l s='Date' mod='amazonmarketplacepro'}</th></tr></thead>
            <tbody>
            {if $promotions}
                {foreach from=$promotions item=promo}
                <tr>
                    <td><small><code>{$promo.amazon_promotion_id|truncate:25|escape:'htmlall':'UTF-8'}</code></small></td>
                    <td>{$promo.promotion_type|escape:'htmlall':'UTF-8'}</td>
                    <td>{$promo.seller_sku|escape:'htmlall':'UTF-8'}</td>
                    <td>{$promo.discount_value|string_format:"%.2f"|escape:'htmlall':'UTF-8'} {if $promo.discount_type == 'percentage'}%{/if}</td>
                    <td>{if $promo.sync_direction == 'amazon_to_ps'}<span class="label label-primary">AMZ&rarr;PS</span>{else}<span class="label label-info">PS&rarr;AMZ</span>{/if}</td>
                    <td><span class="label label-default">{$promo.status|escape:'htmlall':'UTF-8'}</span></td>
                    <td><small>{$promo.date_add|escape:'htmlall':'UTF-8'}</small></td>
                </tr>
                {/foreach}
            {else}
                <tr><td colspan="7" class="text-muted text-center">{l s='No promotions synced yet.' mod='amazonmarketplacepro'}</td></tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ MULTI-MARKETPLACE TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-multimp">

    <div class="panel">
        <div class="panel-heading"><i class="icon-globe"></i> {l s='Multi-Marketplace Configuration' mod='amazonmarketplacepro'}</div>
        <p>{l s='Configure multiple Amazon marketplaces. Each can use its own or the primary credentials. Enable per-marketplace order/product/stock sync.' mod='amazonmarketplacepro'}</p>
        <div class="form-inline" style="margin-bottom:15px;">
            <select id="mp-marketplace-id" class="form-control">
                <option value="">{l s='-- Select Marketplace --' mod='amazonmarketplacepro'}</option>
                {foreach from=$marketplaces key=mp_id item=mp_label}
                    <option value="{$mp_id|escape:'htmlall':'UTF-8'}">{$mp_label|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
            <input type="text" id="mp-seller-id" class="form-control" placeholder="{l s='Seller ID (optional)' mod='amazonmarketplacepro'}" />
            <label><input type="checkbox" id="mp-sync-orders" checked /> {l s='Orders' mod='amazonmarketplacepro'}</label>
            <label><input type="checkbox" id="mp-sync-products" checked /> {l s='Products' mod='amazonmarketplacepro'}</label>
            <label><input type="checkbox" id="mp-sync-stock" checked /> {l s='Stock' mod='amazonmarketplacepro'}</label>
            <button type="button" id="btn-save-mp" class="btn btn-primary"><i class="icon-save"></i> {l s='Add Marketplace' mod='amazonmarketplacepro'}</button>
        </div>
        <div id="multimp-result" style="margin-top:10px;"></div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Active Marketplaces' mod='amazonmarketplacepro'}</div>
        <table class="table" id="mp-table">
            <thead><tr><th>{l s='Marketplace' mod='amazonmarketplacepro'}</th><th>{l s='Seller ID' mod='amazonmarketplacepro'}</th><th>{l s='Orders' mod='amazonmarketplacepro'}</th><th>{l s='Products' mod='amazonmarketplacepro'}</th><th>{l s='Stock' mod='amazonmarketplacepro'}</th><th>{l s='Active' mod='amazonmarketplacepro'}</th><th></th></tr></thead>
            <tbody>
            {if $marketplace_configs}
                {foreach from=$marketplace_configs item=mp}
                <tr>
                    <td><strong>{$mp.marketplace_name|escape:'htmlall':'UTF-8'}</strong><br><small class="text-muted">{$mp.marketplace_id|escape:'htmlall':'UTF-8'}</small></td>
                    <td>{$mp.seller_id|escape:'htmlall':'UTF-8'}</td>
                    <td>{if $mp.sync_orders}<span class="label label-success">{l s='Yes' mod='amazonmarketplacepro'}</span>{else}<span class="label label-default">{l s='No' mod='amazonmarketplacepro'}</span>{/if}</td>
                    <td>{if $mp.sync_products}<span class="label label-success">{l s='Yes' mod='amazonmarketplacepro'}</span>{else}<span class="label label-default">{l s='No' mod='amazonmarketplacepro'}</span>{/if}</td>
                    <td>{if $mp.sync_stock}<span class="label label-success">{l s='Yes' mod='amazonmarketplacepro'}</span>{else}<span class="label label-default">{l s='No' mod='amazonmarketplacepro'}</span>{/if}</td>
                    <td>{if $mp.active}<span class="label label-success">{l s='Yes' mod='amazonmarketplacepro'}</span>{else}<span class="label label-danger">{l s='No' mod='amazonmarketplacepro'}</span>{/if}</td>
                    <td><button type="button" class="btn btn-xs btn-danger btn-delete-mp" data-id="{$mp.marketplace_id|escape:'htmlall':'UTF-8'}"><i class="icon-trash"></i></button></td>
                </tr>
                {/foreach}
            {else}
                <tr><td colspan="7" class="text-muted text-center">{l s='No additional marketplaces configured. The primary marketplace from Settings tab is always active.' mod='amazonmarketplacepro'}</td></tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ PENDING ORDERS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-pending">

    <div class="panel">
        <div class="panel-heading"><i class="icon-pause"></i> {l s='Pending Orders (insufficient stock)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Amazon orders that could not become PrestaShop orders because a product lacks stock. Restock the product and re-run "Create PrestaShop Orders", force-create the order regardless, or remove it.' mod='amazonmarketplacepro'}</p>
        {if !$mkpro_skip_no_stock}
            <div class="alert alert-info">{l s='Stock-checking is currently off. Enable "Orders without enough stock → Park in Pending Orders" in Settings > Order Import Settings.' mod='amazonmarketplacepro'}</div>
        {/if}
        <div id="pending-result" style="display:none; margin-bottom:10px;"></div>
        <table class="table" id="pending-table">
            <thead><tr>
                <th>{l s='Amazon Order' mod='amazonmarketplacepro'}</th>
                <th>{l s='Order date' mod='amazonmarketplacepro'}</th>
                <th>{l s='Items' mod='amazonmarketplacepro'}</th>
                <th>{l s='Total' mod='amazonmarketplacepro'}</th>
                <th>{l s='Parked since' mod='amazonmarketplacepro'}</th>
                <th>{l s='Actions' mod='amazonmarketplacepro'}</th>
            </tr></thead>
            <tbody>
                {if $pending_orders}
                    {foreach from=$pending_orders item=po}
                    <tr data-id="{$po.id_amazonmarketplacepro_order|escape:'htmlall':'UTF-8'}">
                        <td>{$po.amazon_order_id|escape:'htmlall':'UTF-8'}</td>
                        <td>{$po.purchase_date|escape:'htmlall':'UTF-8'}</td>
                        <td><small>{$po.item_list|escape:'htmlall':'UTF-8'}</small></td>
                        <td>{$po.order_total|escape:'htmlall':'UTF-8'} {$po.currency|escape:'htmlall':'UTF-8'}</td>
                        <td><small>{$po.date_upd|escape:'htmlall':'UTF-8'}</small></td>
                        <td>
                            <button type="button" class="btn btn-xs btn-success pending-create" data-id="{$po.id_amazonmarketplacepro_order|escape:'htmlall':'UTF-8'}"><i class="icon-check"></i> {l s='Create anyway' mod='amazonmarketplacepro'}</button>
                            <button type="button" class="btn btn-xs btn-danger pending-delete" data-id="{$po.id_amazonmarketplacepro_order|escape:'htmlall':'UTF-8'}"><i class="icon-trash"></i></button>
                        </td>
                    </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="6" class="text-center text-muted">{l s='No pending orders.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ MARKUP & RULES TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-markup">

    <div class="panel">
        <div class="panel-heading"><i class="icon-sliders"></i> {l s='Price Markup, Handling Delay & GPSR' mod='amazonmarketplacepro'}</div>
        <p class="help-block">
            {l s='Markups are fixed values ("5.99") or percentages ("10%"), applied to the exported price. Rules cascade per product: category, then manufacturer, then supplier — the first source with a value wins; the defaults below apply when none matches.' mod='amazonmarketplacepro'}
        </p>
        <form method="post" class="form-horizontal" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">
            <input type="hidden" name="mkpro_markup_sources_present" value="1" />
            <input type="hidden" name="mkpro_delay_sources_present" value="1" />
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Default price markup' mod='amazonmarketplacepro'}</label>
                <div class="col-lg-6">
                    <input type="text" name="mkpro_default_markup" value="{$mkpro_default_markup|escape:'htmlall':'UTF-8'}" class="form-control" style="max-width:150px;" placeholder="{l s='e.g. 10% or 2.50' mod='amazonmarketplacepro'}" />
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Get price markups from' mod='amazonmarketplacepro'}</label>
                <div class="col-lg-6">
                    <label class="checkbox-inline"><input type="checkbox" name="mkpro_markup_sources[]" value="category"{if in_array('category', $mkpro_markup_sources)} checked="checked"{/if} /> {l s='Categories' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" name="mkpro_markup_sources[]" value="manufacturer"{if in_array('manufacturer', $mkpro_markup_sources)} checked="checked"{/if} /> {l s='Manufacturers' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" name="mkpro_markup_sources[]" value="supplier"{if in_array('supplier', $mkpro_markup_sources)} checked="checked"{/if} /> {l s='Suppliers' mod='amazonmarketplacepro'}</label>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Default shipping delay (days)' mod='amazonmarketplacepro'}</label>
                <div class="col-lg-6">
                    <input type="number" min="0" name="mkpro_default_delay" value="{$mkpro_default_delay|escape:'htmlall':'UTF-8'}" class="form-control" style="max-width:120px;" />
                    <p class="help-block">{l s='Handling time sent with every offer. 0 = do not send.' mod='amazonmarketplacepro'}</p>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Get shipping delay from' mod='amazonmarketplacepro'}</label>
                <div class="col-lg-6">
                    <label class="checkbox-inline"><input type="checkbox" name="mkpro_delay_sources[]" value="category"{if in_array('category', $mkpro_delay_sources)} checked="checked"{/if} /> {l s='Categories' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" name="mkpro_delay_sources[]" value="manufacturer"{if in_array('manufacturer', $mkpro_delay_sources)} checked="checked"{/if} /> {l s='Manufacturers' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" name="mkpro_delay_sources[]" value="supplier"{if in_array('supplier', $mkpro_delay_sources)} checked="checked"{/if} /> {l s='Suppliers' mod='amazonmarketplacepro'}</label>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='GPSR: give priority to' mod='amazonmarketplacepro'}</label>
                <div class="col-lg-6">
                    <select name="mkpro_gpsr_priority" class="form-control" style="max-width:250px;">
                        <option value="manufacturer"{if $mkpro_gpsr_priority != 'supplier'} selected="selected"{/if}>{l s='Manufacturers' mod='amazonmarketplacepro'}</option>
                        <option value="supplier"{if $mkpro_gpsr_priority == 'supplier'} selected="selected"{/if}>{l s='Suppliers' mod='amazonmarketplacepro'}</option>
                    </select>
                    <p class="help-block">{l s='GPSR responsible-person contact (e-mail or URL) sent with full listings. A per-product contact (Product Rules tab) always wins; below that, this priority decides between manufacturer and supplier contacts. The contact must already be registered in Seller Central > Account Health > Product Policy Compliance.' mod='amazonmarketplacepro'}</p>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" name="submitMkproSettings" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save Defaults' mod='amazonmarketplacepro'}
                </button>
            </div>
        </form>
    </div>

    {* Per-entity rule tables *}
    {foreach from=['category' => $entity_categories, 'manufacturer' => $entity_manufacturers, 'supplier' => $entity_suppliers] key=etype item=erows}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-{if $etype == 'category'}sitemap{elseif $etype == 'manufacturer'}building{else}truck{/if}"></i>
            {if $etype == 'category'}{l s='Category Rules' mod='amazonmarketplacepro'}
            {elseif $etype == 'manufacturer'}{l s='Manufacturer Rules' mod='amazonmarketplacepro'}
            {else}{l s='Supplier Rules' mod='amazonmarketplacepro'}{/if}
        </div>
        <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
            <table class="table entity-table" data-type="{$etype}">
                <thead><tr>
                    <th>ID</th>
                    <th>{l s='Name' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Price markup' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Shipping delay (days)' mod='amazonmarketplacepro'}</th>
                    <th>{l s='GPSR e-mail or link' mod='amazonmarketplacepro'}</th>
                    {if $etype == 'manufacturer'}<th>{l s='Country of origin' mod='amazonmarketplacepro'}</th>{/if}
                    <th>{l s='Sync' mod='amazonmarketplacepro'}</th>
                </tr></thead>
                <tbody>
                    {if $erows}
                        {foreach from=$erows item=er}
                        <tr data-id="{$er.id_entity|escape:'htmlall':'UTF-8'}">
                            <td>{$er.id_entity|escape:'htmlall':'UTF-8'}</td>
                            <td>{$er.name|escape:'htmlall':'UTF-8'}</td>
                            <td><input type="text" class="form-control input-sm ent-markup" value="{$er.price_markup|escape:'htmlall':'UTF-8'}" placeholder="10% / 2.50" style="width:90px;" /></td>
                            <td><input type="number" min="0" class="form-control input-sm ent-delay" value="{$er.shipping_delay|escape:'htmlall':'UTF-8'}" style="width:80px;" /></td>
                            <td><input type="text" class="form-control input-sm ent-gpsr" value="{$er.gpsr_contact|escape:'htmlall':'UTF-8'}" style="min-width:180px;" /></td>
                            {if $etype == 'manufacturer'}<td><input type="text" maxlength="2" class="form-control input-sm ent-coo" value="{$er.country_of_origin|escape:'htmlall':'UTF-8'}" placeholder="CN" style="width:60px;" /></td>{/if}
                            <td>
                                <select class="form-control input-sm ent-sync" style="width:70px;">
                                    <option value="1"{if $er.sync} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                                    <option value="0"{if !$er.sync} selected="selected"{/if}>{l s='No' mod='amazonmarketplacepro'}</option>
                                </select>
                            </td>
                        </tr>
                        {/foreach}
                    {else}
                        <tr><td colspan="7" class="text-center text-muted">{l s='Nothing found.' mod='amazonmarketplacepro'}</td></tr>
                    {/if}
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">
            <button type="button" class="btn btn-primary entity-save" data-type="{$etype}"><i class="icon-save"></i> {l s='Save' mod='amazonmarketplacepro'}</button>
            <span class="entity-result" style="margin-left:10px;"></span>
        </div>
        <p class="help-block" style="margin-top:8px;">{l s='Saving queues the affected products so the next delta sync resends them.' mod='amazonmarketplacepro'}</p>
    </div>
    {/foreach}
</div>

{* ═══════════════════════ PRODUCT RULES TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-prodrules">

    <div class="panel">
        <div class="panel-heading"><i class="icon-check-square-o"></i> {l s='Per-Product Rules' mod='amazonmarketplacepro'} <small>({l s='first 500 products' mod='amazonmarketplacepro'})</small></div>
        <p class="help-block">{l s='Switch individual products out of the Amazon sync, or give a product its own GPSR contact (overrides manufacturer/supplier contacts).' mod='amazonmarketplacepro'}</p>
        <input type="text" id="prodrules-filter" class="form-control" placeholder="{l s='Filter by name or reference...' mod='amazonmarketplacepro'}" style="max-width:320px; margin-bottom:10px;" />
        <div class="table-responsive" style="max-height:480px; overflow-y:auto;">
            <table class="table" id="prodrules-table">
                <thead><tr>
                    <th>ID</th>
                    <th>{l s='Reference' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Name' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Sync' mod='amazonmarketplacepro'}</th>
                    <th>{l s='GPSR e-mail or link' mod='amazonmarketplacepro'}</th>
                </tr></thead>
                <tbody>
                    {if $product_rules}
                        {foreach from=$product_rules item=pr}
                        <tr data-id="{$pr.id_product|escape:'htmlall':'UTF-8'}">
                            <td>{$pr.id_product|escape:'htmlall':'UTF-8'}</td>
                            <td><code>{$pr.reference|escape:'htmlall':'UTF-8'}</code></td>
                            <td class="pr-name">{$pr.name|escape:'htmlall':'UTF-8'}</td>
                            <td>
                                <select class="form-control input-sm pr-sync" style="width:70px;">
                                    <option value="1"{if $pr.sync} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                                    <option value="0"{if !$pr.sync} selected="selected"{/if}>{l s='No' mod='amazonmarketplacepro'}</option>
                                </select>
                            </td>
                            <td><input type="text" class="form-control input-sm pr-gpsr" value="{$pr.gpsr_contact|escape:'htmlall':'UTF-8'}" style="min-width:200px;" /></td>
                        </tr>
                        {/foreach}
                    {else}
                        <tr><td colspan="5" class="text-center text-muted">{l s='No active products found.' mod='amazonmarketplacepro'}</td></tr>
                    {/if}
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">
            <button type="button" id="prodrules-save" class="btn btn-primary"><i class="icon-save"></i> {l s='Save' mod='amazonmarketplacepro'}</button>
            <span id="prodrules-result" style="margin-left:10px;"></span>
        </div>
    </div>
</div>

{* ═══════════════════════ QUEUE TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-queue">

    <div class="panel">
        <div class="panel-heading"><i class="icon-list-ol"></i> {l s='Change Queue' mod='amazonmarketplacepro'}</div>
        <p class="help-block">
            {l s='Products modified in PrestaShop or touched by rule changes (markup, delay, GPSR, sync switches). With "Export only products updated in the last N hours" enabled in Settings > Sync Options, active entries here are included in the next sync even if the product itself was not modified recently. After a successful sync, sent entries are set inactive; entries older than the configured retention are purged automatically.' mod='amazonmarketplacepro'}
        </p>
        <div class="form-inline" style="margin-bottom:10px;">
            <select id="queue-op" class="form-control">
                <option value="">{l s='-- Select an action --' mod='amazonmarketplacepro'}</option>
                <option value="enable">{l s='Enable all' mod='amazonmarketplacepro'}</option>
                <option value="disable">{l s='Disable all' mod='amazonmarketplacepro'}</option>
                <option value="purge">{l s='Purge expired entries' mod='amazonmarketplacepro'}</option>
                <option value="clear">{l s='Remove all' mod='amazonmarketplacepro'}</option>
            </select>
            <button type="button" id="queue-run" class="btn btn-primary">{l s='Perform action' mod='amazonmarketplacepro'}</button>
            <span id="queue-result" style="margin-left:10px;"></span>
        </div>
        <table class="table" id="queue-table">
            <thead><tr>
                <th>{l s='Product ID' mod='amazonmarketplacepro'}</th>
                <th>{l s='Reference' mod='amazonmarketplacepro'}</th>
                <th>{l s='Name' mod='amazonmarketplacepro'}</th>
                <th>{l s='Reason' mod='amazonmarketplacepro'}</th>
                <th>{l s='Active' mod='amazonmarketplacepro'}</th>
                <th>{l s='Added' mod='amazonmarketplacepro'}</th>
                <th>{l s='Updated' mod='amazonmarketplacepro'}</th>
            </tr></thead>
            <tbody>
                {if $queue_rows}
                    {foreach from=$queue_rows item=q}
                    <tr>
                        <td>{$q.id_product|escape:'htmlall':'UTF-8'}</td>
                        <td><code>{$q.reference|escape:'htmlall':'UTF-8'}</code></td>
                        <td>{$q.name|escape:'htmlall':'UTF-8'}</td>
                        <td>{$q.reason|escape:'htmlall':'UTF-8'}</td>
                        <td>{if $q.active}<span class="badge badge-success">{l s='Yes' mod='amazonmarketplacepro'}</span>{else}<span class="badge badge-default">{l s='No' mod='amazonmarketplacepro'}</span>{/if}</td>
                        <td><small>{$q.date_add|escape:'htmlall':'UTF-8'}</small></td>
                        <td><small>{$q.date_upd|escape:'htmlall':'UTF-8'}</small></td>
                    </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="7" class="text-center text-muted">{l s='Queue is empty.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ ORPHANS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-orphans">

    <div class="panel">
        <div class="panel-heading"><i class="icon-unlink"></i> {l s='Orphaned Listings' mod='amazonmarketplacepro'}</div>
        <p class="help-block">
            {l s='Amazon listings that no longer map to a sellable PrestaShop product — the product was deleted, deactivated, or its SKU changed. Run "Sync Amazon to PS" (Products tab) first so this list reflects your live inventory, then fix the products or delete the listings on Amazon.' mod='amazonmarketplacepro'}
        </p>
        <button type="button" id="orphans-refresh" class="btn btn-primary"><i class="icon-refresh"></i> {l s='Get orphaned products' mod='amazonmarketplacepro'}</button>
        <div id="orphans-result" style="display:none; margin-top:10px;"></div>
        <table class="table" id="orphans-table" style="margin-top:10px;">
            <thead><tr>
                <th>{l s='SKU' mod='amazonmarketplacepro'}</th>
                <th>{l s='ASIN' mod='amazonmarketplacepro'}</th>
                <th>{l s='Amazon title' mod='amazonmarketplacepro'}</th>
                <th>{l s='PS Product' mod='amazonmarketplacepro'}</th>
                <th>{l s='Reason' mod='amazonmarketplacepro'}</th>
            </tr></thead>
            <tbody>
                {if $orphan_rows}
                    {foreach from=$orphan_rows item=orp}
                    <tr>
                        <td><code>{$orp.seller_sku|escape:'htmlall':'UTF-8'}</code></td>
                        <td>{$orp.amazon_asin|escape:'htmlall':'UTF-8'}</td>
                        <td>{$orp.amazon_title|truncate:60:'...':true|escape:'htmlall':'UTF-8'}</td>
                        <td>{if $orp.id_product > 0}#{$orp.id_product|escape:'htmlall':'UTF-8'}{else}-{/if}</td>
                        <td>{$orp.reason|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="5" class="text-center text-muted">{l s='No orphaned listings found. Run an Amazon-side sync to refresh.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>

{* ═══════════════════════ CRON TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-cron">

    <div class="panel">
        <div class="panel-heading"><i class="icon-clock-o"></i> {l s='Cron URLs for Automation' mod='amazonmarketplacepro'}</div>
        <p>{l s='Add these URLs to your server\'s crontab (or use a webcron service) to automate Amazon sync.' mod='amazonmarketplacepro'}</p>

        <table class="table">
            <thead>
                <tr><th>{l s='Action' mod='amazonmarketplacepro'}</th><th>{l s='URL' mod='amazonmarketplacepro'}</th><th>{l s='Schedule' mod='amazonmarketplacepro'}</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{l s='Import Orders' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_import_orders_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 15 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Create PS Orders' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_create_orders_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 15 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Sync Stock' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_sync_stock_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 30 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Full Product Sync' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_sync_products_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Once daily' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Import Returns' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_import_returns_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 1 hour' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Process Returns' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_process_returns_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 1 hour' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='FBA Inventory Sync' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_sync_fba_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 1 hour' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Repricing Cycle' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_reprice_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 1 hour' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Fetch Fees' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_fetch_fees_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 6 hours' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Poll Reports' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_poll_reports_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 30 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Sync Promotions' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_sync_promotions_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Once daily' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Request Reviews' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_request_reviews_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Once daily' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Bulk Feed Cycle (large catalogs)' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_process_feeds_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 30 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='VCS Invoice Upload' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_upload_invoices_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 1 hour' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Import Orders' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_import_orders_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 15 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Sync Stock' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_sync_stock_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 30 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Create PS Orders' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_create_orders_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 15 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Publish Offers' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_publish_offers_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 6 hours' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Import Returns' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_import_returns_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every hour' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Import Fees' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_import_fees_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Daily' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Repricing' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_reprice_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 6 hours' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Catalog Matching' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_match_catalog_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Weekly' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Publish on all sites' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_multi_publish_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 6 hours' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='eBay: Import orders from all sites' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_ebay_multi_import_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 15 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Multi-MP Order Import' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_multi_import_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Every 15 min' mod='amazonmarketplacepro'}</td>
                </tr>
                <tr>
                    <td><strong>{l s='Multi-MP Product Sync' mod='amazonmarketplacepro'}</strong></td>
                    <td><code style="font-size:11px; word-break:break-all;">{$cron_multi_sync_url|escape:'htmlall':'UTF-8'}</code></td>
                    <td>{l s='Once daily' mod='amazonmarketplacepro'}</td>
                </tr>
            </tbody>
        </table>

        <div class="alert alert-info">
            <strong>{l s='Example crontab entry' mod='amazonmarketplacepro'}:</strong><br/>
            <code>*/15 * * * * curl -s "{$cron_import_orders_url|escape:'htmlall':'UTF-8'}" > /dev/null 2>&1</code>
        </div>

        <p>
            <strong>{l s='Your cron token' mod='amazonmarketplacepro'}:</strong>
            <code>{$mkpro_cron_token|escape:'htmlall':'UTF-8'}</code>
        </p>
    </div>

</div>

{* ═══════════════════════ LOGS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-logs">

    <div class="panel">
        <div class="panel-heading"><i class="icon-file-text-o"></i> {l s='Activity Log' mod='amazonmarketplacepro'} ({l s='last 50 entries' mod='amazonmarketplacepro'})</div>
        {if $log_entries && count($log_entries) > 0}
            <table class="table">
                <thead>
                    <tr><th>{l s='Date' mod='amazonmarketplacepro'}</th><th>{l s='Level' mod='amazonmarketplacepro'}</th><th>{l s='Source' mod='amazonmarketplacepro'}</th><th>{l s='Message' mod='amazonmarketplacepro'}</th></tr>
                </thead>
                <tbody>
                    {foreach from=$log_entries item=entry}
                        <tr class="{if $entry.level == 'error'}danger{elseif $entry.level == 'warning'}warning{/if}">
                            <td style="white-space:nowrap;">{$entry.date_add|escape:'htmlall':'UTF-8'}</td>
                            <td><span class="badge {if $entry.level == 'error'}badge-danger{elseif $entry.level == 'warning'}badge-warning{else}badge-info{/if}">{$entry.level|escape:'htmlall':'UTF-8'}</span></td>
                            <td>{$entry.source|escape:'htmlall':'UTF-8'}</td>
                            <td>{$entry.message|escape:'htmlall':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-info">{l s='No log entries yet.' mod='amazonmarketplacepro'}</div>
        {/if}
    </div>

</div>

</div>{* end tab-content *}
</div>{* end content column *}
</div>{* end row *}

{* ═══════════════════════ JAVASCRIPT ═══════════════════════ *}
<script type="text/javascript">
(function () {
    /* ── Helper: HTML-escape ── */
    function esc(v) {
        if (v === null || typeof v === 'undefined') return '';
        return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Helper: AJAX POST returning parsed JSON ── */
    /* ── Sidebar accordion: one open marketplace group, single active item.
       Runs on Bootstrap's shown event (after the pane switch) — reacting
       earlier would make Bootstrap think the tab is already active. ── */
    if (window.jQuery) {
        jQuery(document).on('shown.bs.tab shown', '#mkpro-sidebar a[data-toggle="tab"]', function () {
            var $a = jQuery(this);
            jQuery('#mkpro-sidebar li').removeClass('active');

            var $group = $a.closest('li.mkpro-group');
            var $open = jQuery('#mkpro-sidebar li.mkpro-group.open');

            if ($group.length) {
                if (!$group.hasClass('open')) {
                    // PS-menu style: the open group slides shut, this one slides open
                    $open.removeClass('open mkpro-current').children('.mkpro-submenu').stop(true, true).slideUp(200);
                    $group.addClass('open mkpro-current').children('.mkpro-submenu').stop(true, true).hide().slideDown(200);
                }
                if ($a.parent('li').closest('.mkpro-submenu').length) {
                    $a.parent('li').addClass('active');
                } else {
                    // Group header clicked: highlight its first submenu entry
                    $group.find('.mkpro-submenu li').first().addClass('active');
                }
            } else {
                // General section item (Automation / Logs): collapse groups
                $open.removeClass('open mkpro-current').children('.mkpro-submenu').stop(true, true).slideUp(200);
                $a.parent('li').addClass('active');
            }
        });
    }

    function ajaxPost(url, onDone, extraData) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var data;
            try { data = JSON.parse(xhr.responseText); }
            catch (e) { data = null; }
            onDone(data, xhr);
        };
        if (extraData) {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(extraData);
        } else {
            xhr.send();
        }
    }

    /* ── Helper: Direction badge ── */
    function dirBadge(dir) {
        var cls = { ps_only:'badge-warning', amazon_only:'badge-info', conflict:'badge-danger', in_sync:'badge-success' };
        var lbl = { ps_only:'PS only', amazon_only:'Amazon only', conflict:'Conflict', in_sync:'In sync' };
        return '<span class="badge '+(cls[dir]||'badge-default')+'">'+ esc(lbl[dir]||dir) +'</span>';
    }

    /* ── Helper: Status badge ── */
    function statusBadge(s) {
        var c = 'badge-default';
        if (s === 'ACCEPTED') c = 'badge-success';
        else if (s === 'SKIPPED') c = 'badge-info';
        else c = 'badge-danger';
        return '<span class="badge '+c+'">'+ esc(s) +'</span>';
    }

    /* ──────── CONNECTION TEST ──────── */
    (function () {
        var btn = document.getElementById('test-amazon-connection');
        var out = document.getElementById('amazon-connection-result');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.className = '';
            out.textContent = 'Contacting Amazon...';

            ajaxPost('{$ajax_test_amazon_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.className='alert alert-danger'; out.textContent='Unexpected response'; return; }
                var text = (data.lines || []).join('\n');
                if (data.success) {
                    out.className = 'alert alert-success';
                } else {
                    out.className = 'alert alert-danger';
                    if (data.error) {
                        text += '\nFAILED: ' + data.error;
                        if (data.hint) text += '\nHint: ' + data.hint;
                    }
                }
                out.textContent = text;
            });
        });
    })();

    /* ──────── ORDER IMPORT ──────── */
    (function () {
        var btnImport = document.getElementById('import-amazon-orders');
        var btnCreate = document.getElementById('create-ps-orders');
        var summary = document.getElementById('amazon-orders-summary');
        var out = document.getElementById('amazon-orders-result');
        if (!out) return;

        function renderOrders(orders) {
            if (!orders || !orders.length) return '<div class="alert alert-info">No orders staged yet.</div>';
            var h = '<table class="table"><thead><tr>'
                +'<th>Amazon Order ID</th><th>Date</th><th>Status</th>'
                +'<th>Total</th><th>Shipping</th><th>Tax</th><th>Channel</th>'
                +'<th>Items</th><th>PS Order</th><th>Import</th>'
                +'</tr></thead><tbody>';
            for (var i=0; i<orders.length; i++) {
                var o = orders[i];
                var um = parseInt(o.items_unmatched,10)||0;
                var badge = um > 0
                    ? '<span class="badge badge-warning">'+esc(o.items_matched)+'/'+esc(o.items_unmatched)+'</span>'
                    : '<span class="badge badge-success">'+esc(o.items_matched)+'/0</span>';
                var psOrder = parseInt(o.id_order,10) > 0
                    ? '<span class="badge badge-success">#'+esc(o.id_order)+'</span>'
                    : '<span class="badge badge-default">-</span>';
                var impBadge = o.import_status === 'created'
                    ? '<span class="badge badge-success">Created</span>'
                    : (o.import_status === 'cancelled'
                        ? '<span class="badge badge-danger">Cancelled</span>'
                        : '<span class="badge badge-info">Staged</span>');
                var channel = (o.fulfillment_channel === 'AFN')
                    ? '<span class="badge badge-info">FBA</span>'
                    : '<span class="badge badge-default">MFN</span>';
                if (parseInt(o.is_prime, 10) === 1) {
                    channel += ' <span class="badge badge-warning">Prime</span>';
                }
                h += '<tr>'
                    +'<td>'+esc(o.amazon_order_id)+'</td>'
                    +'<td>'+esc(o.purchase_date)+'</td>'
                    +'<td>'+esc(o.order_status)+'</td>'
                    +'<td>'+esc(o.order_total)+' '+esc(o.currency)+'</td>'
                    +'<td>'+esc(o.shipping_total||'0')+'</td>'
                    +'<td>'+esc(o.order_tax||'0')+'</td>'
                    +'<td>'+channel+'</td>'
                    +'<td>'+badge+'</td>'
                    +'<td>'+psOrder+'</td>'
                    +'<td>'+impBadge+'</td>'
                    +'</tr>';
            }
            return h + '</tbody></table>';
        }

        if (btnImport) {
            btnImport.addEventListener('click', function () {
                btnImport.disabled = true;
                if (btnCreate) btnCreate.disabled = true;
                summary.style.display = 'none';
                out.style.display = 'block';
                out.innerHTML = '<div class="alert alert-info">Fetching orders from Amazon...</div>';

                ajaxPost('{$ajax_import_orders_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnImport.disabled = false;
                    if (btnCreate) btnCreate.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }

                    if (data.summary) {
                        var s = data.summary;
                        summary.style.display = 'block';
                        summary.className = 'alert alert-success';
                        summary.innerHTML = 'Fetched '+esc(s.fetched)+' &middot; imported '+esc(s.imported_new)+' new &middot; '
                            +esc(s.already)+' already staged &middot; items matched '+esc(s.items_matched)+' / unmatched '+esc(s.items_unmatched);
                    }
                    if (data.error) {
                        summary.style.display = 'block';
                        summary.className = 'alert alert-danger';
                        summary.innerHTML = 'Error: '+esc(data.error);
                    }
                    out.innerHTML = renderOrders(data.orders);
                });
            });
        }

        if (btnCreate) {
            btnCreate.addEventListener('click', function () {
                btnCreate.disabled = true;
                if (btnImport) btnImport.disabled = true;
                summary.style.display = 'none';
                out.style.display = 'block';
                out.innerHTML = '<div class="alert alert-info">Creating PrestaShop orders from staged Amazon orders...</div>';

                ajaxPost('{$ajax_create_ps_orders_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnCreate.disabled = false;
                    if (btnImport) btnImport.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }

                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'
                            + esc(s.total)+' pending &middot; '+esc(s.created)+' created &middot; '
                            + esc(s.skipped)+' skipped &middot; '+esc(s.failed)+' failed'
                            + '</div>';
                    }
                    if (data.notices && data.notices.length) {
                        for (var n=0; n<data.notices.length; n++) {
                            html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                        }
                    }
                    if (data.summary && data.summary.errors && data.summary.errors.length) {
                        for (var e=0; e<data.summary.errors.length; e++) {
                            html += '<div class="alert alert-danger">'+esc(data.summary.errors[e])+'</div>';
                        }
                    }
                    out.innerHTML = html || '<div class="alert alert-info">No pending orders to create.</div>';
                });
            });
        }
    })();

    /* ──────── BUYER MESSAGING ──────── */
    (function () {
        var btnLoad = document.getElementById('msg-load-actions');
        var btnSend = document.getElementById('msg-send');
        var selAction = document.getElementById('msg-action');
        var inpOrder = document.getElementById('msg-order-id');
        var txtMsg = document.getElementById('msg-text');
        var out = document.getElementById('msg-result');
        if (!btnLoad || !btnSend) return;

        function show(cls, msg) {
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-'+cls+'">'+esc(msg)+'</div>';
        }

        btnLoad.addEventListener('click', function () {
            var orderId = inpOrder.value.replace(/^\s+|\s+$/g, '');
            if (!orderId) { show('warning', 'Enter an Amazon order id first.'); return; }
            btnLoad.disabled = true;
            show('info', 'Asking Amazon which message types are allowed...');

            ajaxPost('{$ajax_get_messaging_actions_url|escape:'javascript':'UTF-8'}', function (data) {
                btnLoad.disabled = false;
                if (!data) { show('danger', 'Unexpected response'); return; }
                if (data.error) { show('danger', data.error); return; }

                selAction.innerHTML = '';
                if (!data.actions || !data.actions.length) {
                    selAction.disabled = true;
                    btnSend.disabled = true;
                    show('warning', 'Amazon allows no messages for this order right now.');
                    return;
                }
                for (var i = 0; i < data.actions.length; i++) {
                    var opt = document.createElement('option');
                    opt.value = data.actions[i];
                    opt.textContent = data.actions[i];
                    selAction.appendChild(opt);
                }
                selAction.disabled = false;
                btnSend.disabled = false;
                show('success', (data.actions.length)+' message type(s) allowed.'+(data.notice ? ' '+data.notice : ''));
            }, 'amazon_order_id='+encodeURIComponent(orderId));
        });

        var btnReview = document.getElementById('msg-request-review');
        if (btnReview) {
            btnReview.addEventListener('click', function () {
                var orderId = inpOrder.value.replace(/^\s+|\s+$/g, '');
                if (!orderId) { show('warning', 'Enter an Amazon order id first.'); return; }
                btnReview.disabled = true;
                show('info', 'Requesting a review from the buyer...');

                ajaxPost('{$ajax_request_review_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnReview.disabled = false;
                    if (!data) { show('danger', 'Unexpected response'); return; }
                    if (data.error) { show('danger', data.error); return; }
                    show('success', 'Review request sent.'+(data.notice ? ' '+data.notice : ''));
                }, 'amazon_order_id='+encodeURIComponent(orderId));
            });
        }

        btnSend.addEventListener('click', function () {
            var orderId = inpOrder.value.replace(/^\s+|\s+$/g, '');
            var action = selAction.value;
            if (!orderId || !action) { show('warning', 'Load message types and pick one first.'); return; }
            btnSend.disabled = true;
            show('info', 'Sending message...');

            ajaxPost('{$ajax_send_buyer_message_url|escape:'javascript':'UTF-8'}', function (data) {
                btnSend.disabled = false;
                if (!data) { show('danger', 'Unexpected response'); return; }
                if (data.error) { show('danger', data.error); return; }
                show('success', 'Message sent to the buyer.'+(data.notice ? ' '+data.notice : ''));
                txtMsg.value = '';
            }, 'amazon_order_id='+encodeURIComponent(orderId)
                +'&action_name='+encodeURIComponent(action)
                +'&message_text='+encodeURIComponent(txtMsg.value));
        });
    })();

    /* ──────── CATALOG (ASIN) MATCHING ──────── */
    (function () {
        var btn = document.getElementById('match-catalog');
        var out = document.getElementById('match-catalog-result');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Searching the Amazon catalog by EAN...</div>';

            ajaxPost('{$ajax_match_catalog_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML = '<div class="alert alert-danger">Unexpected response</div>'; return; }
                if (data.error) { out.innerHTML = '<div class="alert alert-danger">'+esc(data.error)+'</div>'; return; }
                var s = data.summary || {};
                out.innerHTML = '<div class="alert alert-success">'
                    + esc(s.candidates||0)+' checked &middot; '+esc(s.matched||0)+' ASIN(s) matched &middot; '
                    + esc(s.not_found||0)+' not in Amazon catalog'
                    + (data.notice ? ' &middot; '+esc(data.notice) : '')
                    + '</div>';
            });
        });
    })();

    /* ──────── CATALOG IMPORT (AMAZON -> PS) ──────── */
    (function () {
        var btn = document.getElementById('import-catalog');
        var out = document.getElementById('import-catalog-result');
        var sel = document.getElementById('import-catalog-category');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Creating PrestaShop products from Amazon listings...</div>';

            ajaxPost('{$ajax_import_catalog_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML = '<div class="alert alert-danger">Unexpected response</div>'; return; }
                if (data.error) { out.innerHTML = '<div class="alert alert-danger">'+esc(data.error)+'</div>'; return; }
                var s = data.summary || {};
                var html = '<div class="alert alert-success">'
                    + esc(s.candidates||0)+' Amazon-only listing(s) &middot; '+esc(s.created||0)+' product(s) created (inactive) &middot; '
                    + esc(s.skipped||0)+' linked to existing &middot; '+esc(s.failed||0)+' failed'
                    + ((s.images_failed|0) > 0 ? ' &middot; '+esc(s.images_failed)+' image(s) failed' : '')
                    + '</div>';
                if (data.notices && data.notices.length) {
                    for (var n=0; n<data.notices.length; n++) {
                        html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                    }
                }
                out.innerHTML = html;
            }, 'id_category='+encodeURIComponent(sel ? sel.value : 0));
        });
    })();

    /* ──────── BULK FEEDS ──────── */
    (function () {
        var btnSubmit = document.getElementById('feed-submit');
        var btnPoll = document.getElementById('feed-poll');
        var out = document.getElementById('feed-result');
        var tableWrap = document.getElementById('feed-table');
        if (!btnSubmit || !btnPoll) return;

        function renderFeeds(feeds) {
            if (!feeds || !feeds.length) { tableWrap.innerHTML = ''; return; }
            var h = '<table class="table"><thead><tr><th>Feed</th><th>Status</th><th>Messages</th><th>Accepted</th><th>Errors</th><th>Warnings</th><th>Updated</th></tr></thead><tbody>';
            for (var i = 0; i < feeds.length; i++) {
                var f = feeds[i];
                var cls = f.processing_status === 'DONE' ? 'badge-success'
                    : (f.processing_status === 'FATAL' || f.processing_status === 'CANCELLED' ? 'badge-danger' : 'badge-info');
                h += '<tr><td>'+esc(f.feed_id)+'</td>'
                    +'<td><span class="badge '+cls+'">'+esc(f.processing_status)+'</span></td>'
                    +'<td>'+esc(f.messages_count)+'</td><td>'+esc(f.accepted)+'</td>'
                    +'<td>'+esc(f.errors)+'</td><td>'+esc(f.warnings)+'</td>'
                    +'<td>'+esc(f.date_upd)+'</td></tr>';
            }
            tableWrap.innerHTML = h + '</tbody></table>';
        }

        function show(cls, msg) {
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-'+cls+'">'+msg+'</div>';
        }

        btnSubmit.addEventListener('click', function () {
            btnSubmit.disabled = true;
            show('info', 'Building and submitting the feed...');
            ajaxPost('{$ajax_submit_feed_url|escape:'javascript':'UTF-8'}', function (data) {
                btnSubmit.disabled = false;
                if (!data) { show('danger', 'Unexpected response'); return; }
                if (data.error) {
                    var msg = esc(data.error);
                    if (data.skipped && data.skipped.length) msg += ' &middot; '+esc(data.skipped.length)+' SKU(s) skipped';
                    show('danger', msg); return;
                }
                var msg = 'Feed '+esc(data.feed_id)+' submitted with '+esc(data.messages)+' message(s).';
                if (data.skipped && data.skipped.length) msg += ' '+esc(data.skipped.length)+' SKU(s) skipped (no category mapping).';
                if (data.notice) msg += ' '+esc(data.notice);
                show('success', msg);
                renderFeeds(data.feeds);
            });
        });

        btnPoll.addEventListener('click', function () {
            btnPoll.disabled = true;
            show('info', 'Checking feed status with Amazon...');
            ajaxPost('{$ajax_poll_feeds_url|escape:'javascript':'UTF-8'}', function (data) {
                btnPoll.disabled = false;
                if (!data) { show('danger', 'Unexpected response'); return; }
                if (data.error) { show('danger', esc(data.error)); return; }
                var s = data.summary || {};
                show('success', esc(s.checked||0)+' feed(s) checked &middot; '+esc(s.done||0)+' completed &middot; '
                    +esc(s.still_processing||0)+' still processing &middot; '+esc(s.failed||0)+' failed');
                renderFeeds(data.feeds);
            });
        });
    })();

    /* ──────── PRODUCT SYNC ──────── */
    (function () {
        var btnPs = document.getElementById('sync-products-ps');
        var btnAz = document.getElementById('sync-products-amazon');
        var sumBox = document.getElementById('amazon-products-summary');
        var out = document.getElementById('amazon-products-result');
        if (!out) return;

        var urls = {
            ps: '{$ajax_sync_products_ps_url|escape:'javascript':'UTF-8'}',
            amazon: '{$ajax_sync_products_amazon_url|escape:'javascript':'UTF-8'}'
        };

        function renderProducts(products) {
            if (!products || !products.length) return '<div class="alert alert-info">No products with a reference (SKU) found.</div>';
            var h = '<table class="table"><thead><tr>'
                +'<th>SKU</th><th>PrestaShop</th><th>Brand/Mfr</th><th>EAN</th>'
                +'<th>Amazon</th><th>Brand</th><th>Type</th><th>Direction</th>'
                +'</tr></thead><tbody>';
            for (var i=0; i<products.length; i++) {
                var p = products[i];
                var ps = parseInt(p.ps_exists,10) ? esc(p.ps_name)+' &middot; '+esc(p.ps_price)+' &middot; qty '+esc(p.ps_quantity) : '<em>&mdash;</em>';
                var az = parseInt(p.amazon_exists,10) ? esc(p.amazon_title)+' &middot; '+esc(p.amazon_price)+' &middot; qty '+esc(p.amazon_quantity) : '<em>&mdash;</em>';
                h += '<tr>'
                    +'<td>'+esc(p.seller_sku)+'</td>'
                    +'<td>'+ps+'</td>'
                    +'<td>'+esc(p.ps_manufacturer||'')+'</td>'
                    +'<td>'+esc(p.ps_ean13||'')+'</td>'
                    +'<td>'+az+'</td>'
                    +'<td>'+esc(p.amazon_brand||'')+'</td>'
                    +'<td>'+esc(p.amazon_product_type||'')+'</td>'
                    +'<td>'+dirBadge(p.sync_direction)+'</td>'
                    +'</tr>';
            }
            return h + '</tbody></table>';
        }

        function setBusy(b) {
            if (btnPs) btnPs.disabled = b;
            if (btnAz) btnAz.disabled = b;
        }

        function run(dir) {
            setBusy(true);
            sumBox.style.display = 'none';
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">'+(dir==='amazon'?'Pulling from Amazon...':'Scanning PrestaShop...')+'</div>';

            ajaxPost(urls[dir], function (data) {
                setBusy(false);
                if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                var html = '';
                if (data.summary) {
                    var s = data.summary;
                    html += '<div class="alert alert-success">'
                        +esc(s.total)+' products &middot; '+esc(s.ps_only)+' PS only &middot; '
                        +esc(s.amazon_only)+' Amazon only &middot; '+esc(s.conflict)+' conflicts &middot; '
                        +esc(s.in_sync)+' in sync</div>';
                }
                if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                if (html) { sumBox.style.display='block'; sumBox.className=''; sumBox.innerHTML=html; }
                out.innerHTML = renderProducts(data.products);
            });
        }

        if (btnPs) btnPs.addEventListener('click', function(){ run('ps'); });
        if (btnAz) btnAz.addEventListener('click', function(){ run('amazon'); });
    })();

    /* ──────── PUSH TO AMAZON ──────── */
    (function () {
        var btn = document.getElementById('push-products');
        var out = document.getElementById('amazon-push-result');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Submitting products to Amazon...</div>';

            ajaxPost('{$ajax_push_products_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                var html = '';
                if (data.summary) {
                    var s = data.summary;
                    html += '<div class="alert alert-success">'+esc(s.candidates)+' candidates &middot; '+esc(s.pushed)+' accepted &middot; '+esc(s.failed)+' failed</div>';
                }
                if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                if (data.summary && data.summary.results && data.summary.results.length) {
                    html += '<table class="table"><thead><tr><th>SKU</th><th>Status</th><th>Issues</th></tr></thead><tbody>';
                    for (var i=0; i<data.summary.results.length; i++) {
                        var r = data.summary.results[i];
                        html += '<tr><td>'+esc(r.sku)+'</td><td>'+statusBadge(r.status)+'</td><td>'+esc(r.issues)+'</td></tr>';
                    }
                    html += '</tbody></table>';
                }
                out.innerHTML = html || '<div class="alert alert-info">No products eligible to push.</div>';
            });
        });
    })();

    /* ──────── LIST AMAZON PRODUCTS ──────── */
    (function () {
        var btn = document.getElementById('list-amazon-products');
        var msg = document.getElementById('amazon-list-messages');
        var out = document.getElementById('amazon-list-result');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            msg.style.display = 'none';
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Fetching products from Amazon...</div>';

            ajaxPost('{$ajax_list_amazon_products_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                var html = '';
                if (data.products && data.products.length) html += '<div class="alert alert-success">'+data.products.length+' product(s) found.</div>';
                if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                if (html) { msg.style.display='block'; msg.className=''; msg.innerHTML=html; }

                if (data.products && data.products.length) {
                    var t = '<table class="table"><thead><tr><th>SKU</th><th>ASIN</th><th>Title</th><th>Price</th><th>Qty</th><th>Brand</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                    for (var i=0; i<data.products.length; i++) {
                        var p = data.products[i];
                        t += '<tr><td>'+esc(p.seller_sku)+'</td><td>'+esc(p.asin)+'</td><td>'+esc(p.title)+'</td><td>'+esc(p.price)+'</td><td>'+esc(p.quantity)+'</td><td>'+esc(p.brand||'')+'</td><td>'+esc(p.product_type||'')+'</td><td>'+esc(p.status)+'</td></tr>';
                    }
                    t += '</tbody></table>';
                    out.innerHTML = t;
                } else {
                    out.innerHTML = '<div class="alert alert-info">No products to display.</div>';
                }
            });
        });
    })();

    /* ──────── CATEGORY MAPPING ──────── */
    (function () {
        var btnSave = document.getElementById('catmap-save');
        var result = document.getElementById('catmap-result');
        if (!btnSave) return;

        btnSave.addEventListener('click', function () {
            var cat = document.getElementById('catmap-category').value;
            var pt = document.getElementById('catmap-product-type').value.trim();
            var bn = document.getElementById('catmap-browse-node').value.trim();
            var attrsEl = document.getElementById('catmap-attributes');
            var attrs = attrsEl ? attrsEl.value.trim() : '';

            if (!cat || !pt) {
                result.style.display = 'block';
                result.className = 'alert alert-danger';
                result.textContent = 'Please select a category and enter an Amazon Product Type.';
                return;
            }
            if (attrs) {
                try { JSON.parse(attrs); } catch (err) {
                    result.style.display = 'block';
                    result.className = 'alert alert-danger';
                    result.textContent = 'Extra attributes must be valid JSON: ' + err.message;
                    return;
                }
            }

            btnSave.disabled = true;
            var params = 'id_category='+encodeURIComponent(cat)+'&amazon_product_type='+encodeURIComponent(pt)+'&amazon_browse_node='+encodeURIComponent(bn)+'&attributes_json='+encodeURIComponent(attrs);

            ajaxPost('{$ajax_save_category_map_url|escape:'javascript':'UTF-8'}', function (data) {
                btnSave.disabled = false;
                if (!data || !data.success) {
                    result.style.display = 'block';
                    result.className = 'alert alert-danger';
                    result.textContent = (data && data.error) ? data.error : 'Failed to save mapping.';
                    return;
                }
                result.style.display = 'block';
                result.className = 'alert alert-success';
                result.textContent = 'Mapping saved.';
                if (data.mappings) renderCatmapTable(data.mappings);
            }, params);
        });

        // Delete mapping
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.catmap-delete');
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            if (!id) return;

            ajaxPost('{$ajax_delete_category_map_url|escape:'javascript':'UTF-8'}', function (data) {
                if (data && data.success && data.mappings) renderCatmapTable(data.mappings);
            }, 'id_category_map='+encodeURIComponent(id));
        });

        function renderCatmapTable(mappings) {
            var tbody = document.querySelector('#catmap-table tbody');
            if (!tbody) return;
            if (!mappings.length) {
                tbody.innerHTML = '<tr id="catmap-empty"><td colspan="4" class="text-center">No category mappings configured yet.</td></tr>';
                return;
            }
            var h = '';
            for (var i=0; i<mappings.length; i++) {
                var m = mappings[i];
                h += '<tr data-id="'+esc(m.id_amazonmarketplacepro_category_map)+'">'
                    +'<td>'+esc(m.category_name)+'</td>'
                    +'<td>'+esc(m.amazon_product_type)+'</td>'
                    +'<td>'+esc(m.amazon_browse_node)+'</td>'
                    +'<td><button type="button" class="btn btn-xs btn-danger catmap-delete" data-id="'+esc(m.id_amazonmarketplacepro_category_map)+'"><i class="icon-trash"></i></button></td>'
                    +'</tr>';
            }
            tbody.innerHTML = h;
        }
    })();

    /* ──────── RETURNS ──────── */
    (function () {
        var btnImport = document.getElementById('import-returns');
        var btnProcess = document.getElementById('process-returns');
        var summary = document.getElementById('returns-summary');
        var out = document.getElementById('returns-result');
        if (!out) return;

        if (btnImport) {
            btnImport.addEventListener('click', function () {
                btnImport.disabled = true;
                if (btnProcess) btnProcess.disabled = true;
                summary.style.display = 'none';
                out.style.display = 'block';
                out.innerHTML = '<div class="alert alert-info">Importing returns from Amazon...</div>';

                ajaxPost('{$ajax_import_returns_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnImport.disabled = false;
                    if (btnProcess) btnProcess.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }

                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'
                            +'Checked '+esc(s.checked)+' &middot; '+esc(s.new_returns)+' new returns &middot; '
                            +esc(s.new_cancellations)+' new cancellations &middot; '+esc(s.already)+' already imported'
                            +'</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';

                    if (html) { summary.style.display='block'; summary.className=''; summary.innerHTML=html; }
                    out.innerHTML = html ? '' : '<div class="alert alert-info">No new returns found.</div>';
                });
            });
        }

        if (btnProcess) {
            btnProcess.addEventListener('click', function () {
                btnProcess.disabled = true;
                if (btnImport) btnImport.disabled = true;
                summary.style.display = 'none';
                out.style.display = 'block';
                out.innerHTML = '<div class="alert alert-info">Processing pending returns...</div>';

                ajaxPost('{$ajax_process_returns_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnProcess.disabled = false;
                    if (btnImport) btnImport.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }

                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'
                            +esc(s.total)+' pending &middot; '+esc(s.processed)+' processed &middot; '
                            +esc(s.skipped)+' skipped &middot; '+esc(s.failed)+' failed'
                            +'</div>';
                    }
                    if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">No pending returns to process.</div>';
                });
            });
        }
    })();

    /* ──────── FBA ──────── */
    (function () {
        var btnSync = document.getElementById('btn-sync-fba');
        var btnStock = document.getElementById('btn-fba-stock-ps');
        var btnMcf = document.getElementById('btn-create-mcf');
        var fbaResult = document.getElementById('fba-result');
        var mcfResult = document.getElementById('mcf-result');

        if (btnSync) {
            btnSync.addEventListener('click', function () {
                btnSync.disabled = true;
                fbaResult.style.display = 'block';
                fbaResult.innerHTML = '<div class="alert alert-info">Syncing FBA inventory from Amazon...</div>';

                ajaxPost('{$ajax_sync_fba_inventory_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnSync.disabled = false;
                    if (!data) { fbaResult.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">Fetched '+esc(s.fetched)+' SKUs &middot; '+esc(s.new_skus)+' new &middot; '+esc(s.updated)+' updated &middot; '+esc(s.ps_matched)+' PS matched</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                    fbaResult.innerHTML = html || '<div class="alert alert-info">No FBA inventory data returned.</div>';
                });
            });
        }

        if (btnStock) {
            btnStock.addEventListener('click', function () {
                btnStock.disabled = true;
                fbaResult.style.display = 'block';
                fbaResult.innerHTML = '<div class="alert alert-info">Updating PrestaShop stock from FBA quantities...</div>';

                ajaxPost('{$ajax_sync_fba_stock_ps_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnStock.disabled = false;
                    if (!data) { fbaResult.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.total)+' FBA SKUs &middot; '+esc(s.updated)+' PS stock updated &middot; '+esc(s.skipped)+' skipped</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    fbaResult.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }

        if (btnMcf) {
            btnMcf.addEventListener('click', function () {
                var orderId = document.getElementById('mcf-order-id').value;
                if (!orderId) { mcfResult.innerHTML='<div class="alert alert-danger">Please enter a PS Order ID.</div>'; return; }
                btnMcf.disabled = true;
                mcfResult.innerHTML = '<div class="alert alert-info">Creating MCF fulfillment order...</div>';

                ajaxPost('{$ajax_create_mcf_order_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnMcf.disabled = false;
                    if (!data) { mcfResult.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    if (data.success) {
                        mcfResult.innerHTML = '<div class="alert alert-success">MCF order created! Seller Fulfillment Order ID: '+esc(data.seller_fulfillment_order_id)+'</div>';
                    } else {
                        mcfResult.innerHTML = '<div class="alert alert-danger">'+(data.error ? esc(data.error) : 'Failed to create MCF order.')+'</div>';
                    }
                }, 'id_order='+encodeURIComponent(orderId));
            });
        }
    })();

    /* ──────── REPRICING ──────── */
    (function () {
        var btnFetch = document.getElementById('btn-fetch-pricing');
        var btnApply = document.getElementById('btn-apply-rules');
        var btnPush = document.getElementById('btn-push-prices');
        var btnSave = document.getElementById('btn-save-rule');
        var out = document.getElementById('pricing-result');

        function setBusy(b) {
            if (btnFetch) btnFetch.disabled = b;
            if (btnApply) btnApply.disabled = b;
            if (btnPush) btnPush.disabled = b;
        }

        if (btnFetch) {
            btnFetch.addEventListener('click', function () {
                setBusy(true);
                out.innerHTML = '<div class="alert alert-info">Fetching competitive pricing from Amazon...</div>';

                ajaxPost('{$ajax_fetch_pricing_url|escape:'javascript':'UTF-8'}', function (data) {
                    setBusy(false);
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.total)+' ASINs processed &middot; '+esc(s.buybox_wins)+' Buy Box wins &middot; '+esc(s.updated)+' updated</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }

        if (btnApply) {
            btnApply.addEventListener('click', function () {
                setBusy(true);
                out.innerHTML = '<div class="alert alert-info">Applying pricing rules...</div>';

                ajaxPost('{$ajax_apply_pricing_rules_url|escape:'javascript':'UTF-8'}', function (data) {
                    setBusy(false);
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.evaluated)+' products evaluated &middot; '+esc(s.repriced)+' repriced &middot; '+esc(s.capped)+' capped at min/max</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }

        if (btnPush) {
            btnPush.addEventListener('click', function () {
                setBusy(true);
                out.innerHTML = '<div class="alert alert-info">Pushing suggested prices to Amazon...</div>';

                ajaxPost('{$ajax_push_prices_url|escape:'javascript':'UTF-8'}', function (data) {
                    setBusy(false);
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.candidates)+' candidates &middot; '+esc(s.pushed)+' pushed &middot; '+esc(s.failed)+' failed</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }

        if (btnSave) {
            btnSave.addEventListener('click', function () {
                var name = document.getElementById('rule-name').value.trim();
                var type = document.getElementById('rule-type').value;
                var adj = document.getElementById('price-adjustment').value;
                var adjType = document.getElementById('adjustment-type').value;
                var minP = document.getElementById('min-price').value;
                var maxP = document.getElementById('max-price').value;

                if (!name) { out.innerHTML='<div class="alert alert-danger">Please enter a rule name.</div>'; return; }

                btnSave.disabled = true;
                var params = 'name='+encodeURIComponent(name)+'&rule_type='+encodeURIComponent(type)
                    +'&price_adjustment='+encodeURIComponent(adj)+'&adjustment_type='+encodeURIComponent(adjType)
                    +'&min_price='+encodeURIComponent(minP)+'&max_price='+encodeURIComponent(maxP);

                ajaxPost('{$ajax_save_pricing_rule_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnSave.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    if (data.success) {
                        out.innerHTML = '<div class="alert alert-success">Pricing rule saved. Refresh the page to see it in the table.</div>';
                        document.getElementById('rule-name').value = '';
                        document.getElementById('price-adjustment').value = '';
                        document.getElementById('min-price').value = '';
                        document.getElementById('max-price').value = '';
                    } else {
                        out.innerHTML = '<div class="alert alert-danger">'+(data.error ? esc(data.error) : 'Failed to save rule.')+'</div>';
                    }
                }, params);
            });
        }

        // Delete pricing rule
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-delete-rule');
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            if (!id) return;

            ajaxPost('{$ajax_delete_pricing_rule_url|escape:'javascript':'UTF-8'}', function (data) {
                if (data && data.success) {
                    var row = btn.closest('tr');
                    if (row) row.remove();
                }
            }, 'id_pricing_rule='+encodeURIComponent(id));
        });
    })();

    /* ──────── FEES ──────── */
    (function () {
        var btn = document.getElementById('btn-fetch-fees');
        var out = document.getElementById('fees-result');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.innerHTML = '<div class="alert alert-info">Fetching order fees from Amazon Finances API...</div>';

            ajaxPost('{$ajax_fetch_fees_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                var html = '';
                if (data.summary) {
                    var s = data.summary;
                    html += '<div class="alert alert-success">'+esc(s.orders_checked)+' orders checked &middot; '+esc(s.fees_recorded)+' fee entries recorded &middot; '+esc(s.orders_updated)+' orders updated</div>';
                }
                if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
            });
        });
    })();

    /* ──────── REPORTS ──────── */
    (function () {
        var btnListings = document.getElementById('btn-report-listings');
        var btnSettlement = document.getElementById('btn-report-settlement');
        var btnFbaInv = document.getElementById('btn-report-fba-inv');
        var btnPoll = document.getElementById('btn-poll-reports');
        var out = document.getElementById('reports-result');
        if (!out) return;

        function requestReport(reportType, btn) {
            btn.disabled = true;
            out.innerHTML = '<div class="alert alert-info">Requesting '+esc(reportType)+' report...</div>';

            ajaxPost('{$ajax_request_report_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                if (data.success) {
                    out.innerHTML = '<div class="alert alert-success">Report requested! ID: '+esc(data.report_id)+'. Use "Poll Pending Reports" to check status.</div>';
                } else {
                    out.innerHTML = '<div class="alert alert-danger">'+(data.error ? esc(data.error) : 'Failed to request report.')+'</div>';
                }
            }, 'report_type='+encodeURIComponent(reportType));
        }

        if (btnListings) btnListings.addEventListener('click', function () { requestReport(this.getAttribute('data-type'), this); });
        if (btnSettlement) btnSettlement.addEventListener('click', function () { requestReport(this.getAttribute('data-type'), this); });
        if (btnFbaInv) btnFbaInv.addEventListener('click', function () { requestReport(this.getAttribute('data-type'), this); });

        if (btnPoll) {
            btnPoll.addEventListener('click', function () {
                btnPoll.disabled = true;
                out.innerHTML = '<div class="alert alert-info">Polling pending reports...</div>';

                ajaxPost('{$ajax_poll_reports_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnPoll.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.checked)+' reports checked &middot; '+esc(s.completed)+' completed &middot; '+esc(s.still_pending)+' still pending</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }
    })();

    /* ──────── PROMOTIONS ──────── */
    (function () {
        var btnImport = document.getElementById('btn-import-promos');
        var btnExport = document.getElementById('btn-export-promos');
        var btnCartRules = document.getElementById('btn-create-cart-rules');
        var out = document.getElementById('promos-result');
        if (!out) return;

        if (btnImport) {
            btnImport.addEventListener('click', function () {
                btnImport.disabled = true;
                out.innerHTML = '<div class="alert alert-info">Importing promotions from Amazon orders...</div>';

                ajaxPost('{$ajax_import_promotions_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnImport.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.orders_scanned)+' orders scanned &middot; '+esc(s.promotions_found)+' promotions found &middot; '+esc(s.promotions_new)+' new &middot; '+esc(s.promotions_updated)+' updated</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }

        if (btnExport) {
            btnExport.addEventListener('click', function () {
                btnExport.disabled = true;
                out.innerHTML = '<div class="alert alert-info">Exporting PS cart rules as promotions...</div>';

                ajaxPost('{$ajax_export_promotions_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnExport.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.scanned)+' cart rules scanned &middot; '+esc(s.exported)+' exported</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    if (data.notices) for (var n=0; n<data.notices.length; n++) html += '<div class="alert alert-warning">'+esc(data.notices[n])+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }

        if (btnCartRules) {
            btnCartRules.addEventListener('click', function () {
                btnCartRules.disabled = true;
                out.innerHTML = '<div class="alert alert-info">Creating PrestaShop cart rules from promotions...</div>';

                ajaxPost('{$ajax_create_promo_cart_rules_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnCartRules.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    var html = '';
                    if (data.summary) {
                        var s = data.summary;
                        html += '<div class="alert alert-success">'+esc(s.total)+' promotions &middot; '+esc(s.created)+' cart rules created &middot; '+esc(s.errors)+' errors</div>';
                    }
                    if (data.error) html += '<div class="alert alert-danger">'+esc(data.error)+'</div>';
                    out.innerHTML = html || '<div class="alert alert-info">Done.</div>';
                });
            });
        }
    })();

    /* ──────── MULTI-MARKETPLACE ──────── */
    (function () {
        var btnSave = document.getElementById('btn-save-mp');
        var out = document.getElementById('multimp-result');
        if (!out) return;

        if (btnSave) {
            btnSave.addEventListener('click', function () {
                var mpId = document.getElementById('mp-marketplace-id').value;
                if (!mpId) { out.innerHTML='<div class="alert alert-danger">Please select a marketplace.</div>'; return; }

                var sellerId = document.getElementById('mp-seller-id').value.trim();
                var syncOrders = document.getElementById('mp-sync-orders').checked ? 1 : 0;
                var syncProducts = document.getElementById('mp-sync-products').checked ? 1 : 0;
                var syncStock = document.getElementById('mp-sync-stock').checked ? 1 : 0;

                btnSave.disabled = true;
                var params = 'marketplace_id='+encodeURIComponent(mpId)
                    +'&seller_id='+encodeURIComponent(sellerId)
                    +'&sync_orders='+syncOrders
                    +'&sync_products='+syncProducts
                    +'&sync_stock='+syncStock;

                ajaxPost('{$ajax_save_marketplace_url|escape:'javascript':'UTF-8'}', function (data) {
                    btnSave.disabled = false;
                    if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                    if (data.success) {
                        out.innerHTML = '<div class="alert alert-success">Marketplace saved. Refresh the page to see it in the table.</div>';
                    } else {
                        out.innerHTML = '<div class="alert alert-danger">'+(data.error ? esc(data.error) : 'Failed to save marketplace.')+'</div>';
                    }
                }, params);
            });
        }

        // Delete marketplace
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-delete-mp');
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            if (!id) return;

            ajaxPost('{$ajax_delete_marketplace_url|escape:'javascript':'UTF-8'}', function (data) {
                if (data && data.success) {
                    var row = btn.closest('tr');
                    if (row) row.remove();
                }
            }, 'marketplace_id='+encodeURIComponent(id));
        });
    })();

    /* ──────── ENTITY RULES (categories / manufacturers / suppliers) ──────── */
    (function () {
        var buttons = document.querySelectorAll('.entity-save');
        for (var b = 0; b < buttons.length; b++) {
            buttons[b].addEventListener('click', function () {
                var btn = this;
                var type = btn.getAttribute('data-type');
                var table = document.querySelector('.entity-table[data-type="' + type + '"]');
                var resultEl = btn.parentNode.querySelector('.entity-result');
                if (!table) return;

                var rows = [];
                var trs = table.querySelectorAll('tbody tr[data-id]');
                for (var i = 0; i < trs.length; i++) {
                    var tr = trs[i];
                    var coo = tr.querySelector('.ent-coo');
                    rows.push({
                        id: tr.getAttribute('data-id'),
                        markup: tr.querySelector('.ent-markup').value,
                        delay: tr.querySelector('.ent-delay').value,
                        gpsr: tr.querySelector('.ent-gpsr').value,
                        coo: coo ? coo.value : '',
                        sync: tr.querySelector('.ent-sync').value
                    });
                }

                btn.disabled = true;
                resultEl.textContent = 'Saving...';
                ajaxPost('{$ajax_save_entity_settings_url|escape:'javascript':'UTF-8'}', function (data) {
                    btn.disabled = false;
                    if (!data || !data.success) {
                        resultEl.innerHTML = '<span class="text-danger">' + esc(data && data.error ? data.error : 'Save failed') + '</span>';
                        return;
                    }
                    resultEl.innerHTML = '<span class="text-success">' + esc(data.saved) + ' rule(s) saved, ' + esc(data.queued) + ' product(s) queued.</span>';
                }, 'entity_type=' + encodeURIComponent(type) + '&rows=' + encodeURIComponent(JSON.stringify(rows)));
            });
        }
    })();

    /* ──────── PRODUCT RULES ──────── */
    (function () {
        var btn = document.getElementById('prodrules-save');
        var result = document.getElementById('prodrules-result');
        var filter = document.getElementById('prodrules-filter');
        if (!btn) return;

        if (filter) {
            filter.addEventListener('input', function () {
                var needle = filter.value.toLowerCase();
                var trs = document.querySelectorAll('#prodrules-table tbody tr[data-id]');
                for (var i = 0; i < trs.length; i++) {
                    var txt = trs[i].textContent.toLowerCase();
                    trs[i].style.display = (needle === '' || txt.indexOf(needle) !== -1) ? '' : 'none';
                }
            });
        }

        btn.addEventListener('click', function () {
            var rows = [];
            var trs = document.querySelectorAll('#prodrules-table tbody tr[data-id]');
            for (var i = 0; i < trs.length; i++) {
                rows.push({
                    id: trs[i].getAttribute('data-id'),
                    sync: trs[i].querySelector('.pr-sync').value,
                    gpsr: trs[i].querySelector('.pr-gpsr').value
                });
            }
            btn.disabled = true;
            result.textContent = 'Saving...';
            ajaxPost('{$ajax_save_product_rules_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) {
                    result.innerHTML = '<span class="text-danger">' + esc(data && data.error ? data.error : 'Save failed') + '</span>';
                    return;
                }
                result.innerHTML = '<span class="text-success">' + esc(data.saved) + ' product rule(s) saved.</span>';
            }, 'rows=' + encodeURIComponent(JSON.stringify(rows)));
        });
    })();

    /* ──────── QUEUE ──────── */
    (function () {
        var btn = document.getElementById('queue-run');
        var sel = document.getElementById('queue-op');
        var result = document.getElementById('queue-result');
        if (!btn || !sel) return;

        btn.addEventListener('click', function () {
            var op = sel.value;
            if (!op) { result.innerHTML = '<span class="text-danger">Select an action first.</span>'; return; }
            if (op === 'clear' && !confirm('Remove ALL queue entries?')) return;

            btn.disabled = true;
            result.textContent = 'Working...';
            ajaxPost('{$ajax_queue_action_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) {
                    result.innerHTML = '<span class="text-danger">' + esc(data && data.error ? data.error : 'Action failed') + '</span>';
                    return;
                }
                var tbody = document.querySelector('#queue-table tbody');
                var rows = data.queue || [];
                var h = '';
                for (var i = 0; i < rows.length; i++) {
                    var q = rows[i];
                    h += '<tr><td>' + esc(q.id_product) + '</td><td><code>' + esc(q.reference || '') + '</code></td>'
                        + '<td>' + esc(q.name || '') + '</td><td>' + esc(q.reason) + '</td>'
                        + '<td>' + (parseInt(q.active, 10) ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-default">No</span>') + '</td>'
                        + '<td><small>' + esc(q.date_add) + '</small></td><td><small>' + esc(q.date_upd) + '</small></td></tr>';
                }
                tbody.innerHTML = h || '<tr><td colspan="7" class="text-center text-muted">Queue is empty.</td></tr>';
                result.innerHTML = '<span class="text-success">Done.</span>';
            }, 'queue_op=' + encodeURIComponent(op));
        });
    })();

    /* ──────── ORPHANS ──────── */
    (function () {
        var btn = document.getElementById('orphans-refresh');
        var out = document.getElementById('orphans-result');
        if (!btn) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Looking for orphaned listings...</div>';

            ajaxPost('{$ajax_refresh_orphans_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) {
                    out.innerHTML = '<div class="alert alert-danger">' + esc(data && data.error ? data.error : 'Refresh failed') + '</div>';
                    return;
                }
                var rows = data.orphans || [];
                out.innerHTML = '<div class="alert alert-' + (rows.length ? 'warning' : 'success') + '">'
                    + esc(rows.length) + ' orphaned listing(s).' + (data.notice ? ' ' + esc(data.notice) : '') + '</div>';
                var tbody = document.querySelector('#orphans-table tbody');
                var h = '';
                for (var i = 0; i < rows.length; i++) {
                    var o = rows[i];
                    h += '<tr><td><code>' + esc(o.seller_sku) + '</code></td><td>' + esc(o.amazon_asin) + '</td>'
                        + '<td>' + esc((o.amazon_title || '').substring(0, 60)) + '</td>'
                        + '<td>' + (parseInt(o.id_product, 10) > 0 ? '#' + esc(o.id_product) : '-') + '</td>'
                        + '<td>' + esc(o.reason) + '</td></tr>';
                }
                tbody.innerHTML = h || '<tr><td colspan="5" class="text-center text-muted">No orphaned listings found.</td></tr>';
            });
        });
    })();

    /* ──────── PENDING ORDERS ──────── */
    (function () {
        var out = document.getElementById('pending-result');
        if (!out) return;

        function act(op, id, row) {
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Working...</div>';
            ajaxPost('{$ajax_pending_order_action_url|escape:'javascript':'UTF-8'}', function (data) {
                if (!data || !data.success) {
                    out.innerHTML = '<div class="alert alert-danger">' + esc(data && data.error ? data.error : 'Action failed') + '</div>';
                    return;
                }
                out.innerHTML = '<div class="alert alert-success">' + esc(data.message || 'Done.') + '</div>';
                if (row) row.remove();
                var tbody = document.querySelector('#pending-table tbody');
                if (tbody && !tbody.querySelector('tr[data-id]')) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No pending orders.</td></tr>';
                }
            }, 'pending_op=' + encodeURIComponent(op) + '&id_staged=' + encodeURIComponent(id));
        }

        document.addEventListener('click', function (e) {
            var create = e.target.closest('.pending-create');
            if (create) {
                if (!confirm('Create this PS order even though stock is insufficient?')) return;
                act('create', create.getAttribute('data-id'), create.closest('tr'));
                return;
            }
            var del = e.target.closest('.pending-delete');
            if (del) {
                if (!confirm('Remove this pending order? It will not become a PS order.')) return;
                act('delete', del.getAttribute('data-id'), del.closest('tr'));
            }
        });
    })();

    /* ──────── SHIPPING TEMPLATE RANGES ──────── */
    (function () {
        var btn = document.getElementById('shiptpl-add');
        var out = document.getElementById('shiptpl-result');
        if (!btn) return;

        function renderRows(templates) {
            var tbody = document.querySelector('#shiptpl-table tbody');
            var h = '';
            for (var i = 0; i < templates.length; i++) {
                var t = templates[i];
                h += '<tr><td>' + esc(t.basis) + '</td><td>' + esc(t.min_value) + '</td><td>' + esc(t.max_value) + '</td>'
                    + '<td>' + esc(t.template_name) + '</td>'
                    + '<td><button type="button" class="btn btn-xs btn-danger shiptpl-delete" data-id="' + esc(t.id_amazonmarketplacepro_shipping_template) + '"><i class="icon-trash"></i></button></td></tr>';
            }
            tbody.innerHTML = h || '<tr id="shiptpl-empty"><td colspan="5" class="text-center text-muted">No template ranges yet.</td></tr>';
        }

        function show(cls, msg) {
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-' + cls + '">' + esc(msg) + '</div>';
        }

        btn.addEventListener('click', function () {
            var name = document.getElementById('shiptpl-name').value.trim();
            if (!name) { show('danger', 'Enter the template name exactly as in Seller Central.'); return; }
            btn.disabled = true;
            ajaxPost('{$ajax_save_shipping_template_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) { show('danger', (data && data.error) ? data.error : 'Save failed'); return; }
                renderRows(data.templates || []);
                show('success', 'Range saved.');
                document.getElementById('shiptpl-name').value = '';
            }, 'basis=' + encodeURIComponent(document.getElementById('shiptpl-basis').value)
                + '&min_value=' + encodeURIComponent(document.getElementById('shiptpl-min').value || '0')
                + '&max_value=' + encodeURIComponent(document.getElementById('shiptpl-max').value || '0')
                + '&template_name=' + encodeURIComponent(name));
        });

        document.addEventListener('click', function (e) {
            var del = e.target.closest('.shiptpl-delete');
            if (!del) return;
            ajaxPost('{$ajax_delete_shipping_template_url|escape:'javascript':'UTF-8'}', function (data) {
                if (data && data.success) renderRows(data.templates || []);
            }, 'id_template=' + encodeURIComponent(del.getAttribute('data-id')));
        });
    })();

    /* ──────── Beta authorization visibility (production app only) ──────── */
    (function () {
        var envSel = document.querySelector('select[name="mkpro_environment"]');
        var betaGroup = document.getElementById('mkpro-beta-group');
        if (!envSel || !betaGroup) return;

        function toggle() {
            // Sandbox connects always send version=beta, so the choice only
            // exists for the production app.
            betaGroup.style.display = (envSel.value === 'sandbox') ? 'none' : '';
        }
        envSel.addEventListener('change', toggle);
        toggle();
    })();

    /* ──────── Remember active tab ──────── */
    (function () {
        var hash = window.location.hash;
        if (hash) {
            var tab = document.querySelector('#mkpro-tabs a[href="'+hash+'"]');
            if (tab) tab.click();
        }
        var tabs = document.querySelectorAll('#mkpro-tabs a[data-toggle="tab"]');
        for (var i=0; i<tabs.length; i++) {
            tabs[i].addEventListener('click', function (e) {
                window.location.hash = this.getAttribute('href');
            });
        }
    })();
})();
</script>
