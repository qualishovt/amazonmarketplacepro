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
    /* ── mkpro-nav-crossversion ─────────────────────────────────────────
       The module has to look right on three different back offices, and
       none of them agree about navigation:

         PrestaShop 1.6      Bootstrap 3
         PrestaShop 1.7 - 9  Bootstrap 4
         Basic Edition       repaints the whole admin monochrome

       So the module draws its own navigation instead of inheriting
       whichever look happens to be loaded. Four things make this work, and
       every one of them is easy to break by accident.

       1. The active class lands in a different place per framework.
          Bootstrap 3 keeps it on the <li>, Bootstrap 4 can move it onto
          the <a>. Every rule below matches BOTH "li.active > a" and
          "li > a.active", so the highlight survives either behaviour.

       2. PrestaShop 9's theme.css underlines every link inside
          #content.bootstrap, exempting a short list of classes. That
          selector carries an id, so out-specifying it from a module is not
          worth the arms race - text-decoration is forced instead, and only
          on our own navigation.

       3. Weight. theme.css has ".bootstrap .nav-tabs > li.active > a",
          which is three classes; a rule with two loses to it silently. The
          selectors therefore carry .nav-tabs as well as .mkpro-section-tabs.

       4. Hover must not move anything. theme.css styles
          ".bootstrap .nav-tabs > li > a:hover" - and a pseudo-class counts
          as a class, so that hover selector outranks a base rule that has
          one fewer. It sets "border-width: 0 0 3px", so on hover the side
          borders collapsed and the tab jumped sideways. The fix is not to
          out-specify one property but to state the whole geometry -
          padding, margin, border widths, font-weight - identically for
          idle, hover, focus and active, and let the colour rules below
          change nothing but colour.

       Selectors are scoped to #mkpro-sidebar and ul.nav-tabs.mkpro-section-tabs
       because the Module Manager renders .nav-tabs of its own on the same
       page, and those must be left alone. */

    #mkpro-sidebar a,
    ul.nav-tabs.mkpro-section-tabs a { text-decoration: none !important; }

    /* Bootstrap 4 puts the active class on the anchor. */
    #mkpro-sidebar li > a.active,
    #mkpro-sidebar li > a.active:hover,
    #mkpro-sidebar li > a.active:focus { background: #25b2a8; color: #fff; }

    ul.nav-tabs.mkpro-section-tabs { display: block; margin: 0 0 15px; padding: 0;
        list-style: none; border-bottom: 1px solid #d6d4d4; }
    ul.nav-tabs.mkpro-section-tabs > li { display: inline-block; float: none; margin: 0; }

    /* Geometry, identical in every state - see note 4 above. Nothing here
       may differ between the selectors, or the tabs will shift on hover. */
    ul.nav-tabs.mkpro-section-tabs > li > a,
    ul.nav-tabs.mkpro-section-tabs > li > a:hover,
    ul.nav-tabs.mkpro-section-tabs > li > a:focus,
    ul.nav-tabs.mkpro-section-tabs > li.active > a,
    ul.nav-tabs.mkpro-section-tabs > li.active > a:hover,
    ul.nav-tabs.mkpro-section-tabs > li.active > a:focus,
    ul.nav-tabs.mkpro-section-tabs > li > a.active,
    ul.nav-tabs.mkpro-section-tabs > li > a.active:hover,
    ul.nav-tabs.mkpro-section-tabs > li > a.active:focus {
        display: inline-block; padding: 10px 16px; margin: 0 2px -1px 0;
        line-height: 1.4; font-weight: 400; background: transparent;
        border: 0; border-bottom: 3px solid transparent; border-radius: 0;
        box-shadow: none; outline: none; }

    /* Colour, and nothing but colour. */
    ul.nav-tabs.mkpro-section-tabs > li > a { color: #6c868e; }
    ul.nav-tabs.mkpro-section-tabs > li > a:hover,
    ul.nav-tabs.mkpro-section-tabs > li > a:focus {
        color: #363a41; border-bottom-color: #d6d4d4; }
    ul.nav-tabs.mkpro-section-tabs > li.active > a,
    ul.nav-tabs.mkpro-section-tabs > li.active > a:hover,
    ul.nav-tabs.mkpro-section-tabs > li.active > a:focus,
    ul.nav-tabs.mkpro-section-tabs > li > a.active,
    ul.nav-tabs.mkpro-section-tabs > li > a.active:hover,
    ul.nav-tabs.mkpro-section-tabs > li > a.active:focus {
        color: #25b2a8; border-bottom-color: #25b2a8; }
    ul.nav-tabs.mkpro-section-tabs > li > a .badge { margin-left: 5px; }
</style>

<div class="row">
<div class="col-lg-2 col-md-3">
    <div id="mkpro-sidebar">
        <ul class="nav nav-pills nav-stacked" id="mkpro-tabs">
            <li class="active"><a href="#grp-settings" data-toggle="tab"><i class="icon-cogs"></i> {l s='Settings' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#grp-catalog" data-toggle="tab"><i class="icon-th-list"></i> {l s='Catalog' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#grp-orders" data-toggle="tab"><i class="icon-shopping-cart"></i> {l s='Orders' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#grp-fulfilment" data-toggle="tab"><i class="icon-truck"></i> {l s='Fulfilment' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#grp-money" data-toggle="tab"><i class="icon-money"></i> {l s='Money' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#grp-insights" data-toggle="tab"><i class="icon-bar-chart"></i> {l s='Insights' mod='amazonmarketplacepro'}</a></li>
            <li><a href="#grp-system" data-toggle="tab"><i class="icon-wrench"></i> {l s='System' mod='amazonmarketplacepro'}</a></li>
        </ul>
    </div>
</div>
<div class="col-lg-10 col-md-9">
<div class="tab-content" id="mkpro-tab-content">

{* ═══════════════════════ SETTINGS TAB ═══════════════════════ *}

{* ════════════════════ SETUP ════════════════════ *}
<div class="tab-pane active" id="grp-settings">

    <form method="post" class="form-horizontal" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">

    {* Sub-tabs. One form still wraps every pane, so Save writes all of
       them at once regardless of which is on screen - hidden inputs are
       still submitted. Splitting the form per tab would mean a merchant
       could lose edits by switching tab before saving. *}
    <ul class="nav nav-tabs mkpro-section-tabs" id="mkpro-settings-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#set-connection" data-toggle="tab"><i class="icon-plug"></i> {l s='Connection' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#set-listings" data-toggle="tab"><i class="icon-tags"></i> {l s='Listings' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#set-sync" data-toggle="tab"><i class="icon-exchange"></i> {l s='Sync' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#set-shipping" data-toggle="tab"><i class="icon-truck"></i> {l s='Shipping' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#set-orders" data-toggle="tab"><i class="icon-shopping-cart"></i> {l s='Orders' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#set-messaging" data-toggle="tab"><i class="icon-inbox"></i> {l s='Messaging' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

    <div class="tab-pane active" id="set-connection">
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

        {if $mkpro_dev_mode}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Authentication mode' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_auth_mode" class="form-control">
                    <option value="connect"{if $mkpro_auth_mode != 'manual'} selected="selected"{/if}>{l s='Connect with Amazon (recommended)' mod='amazonmarketplacepro'}</option>
                    <option value="manual"{if $mkpro_auth_mode == 'manual'} selected="selected"{/if}>{l s='Manual SP-API credentials (advanced)' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        {/if}
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
    {* ── Amazon SP-API Credentials (manual mode) ──
       Hidden from merchants: with Connect they never need these, and a
       field for a secret invites someone to paste one. Still shown to an
       install already on manual mode, which would otherwise lose access to
       its own working configuration. *}
    {if $mkpro_dev_mode || $mkpro_auth_mode == 'manual'}
    <div class="panel">
        <div class="panel-heading"><i class="icon-key"></i> {l s='Manual SP-API Credentials (advanced — not needed with Connect)' mod='amazonmarketplacepro'}</div>
        <div class="alert alert-warning">
            <i class="icon-warning"></i>
            <strong>{l s='All three values below must come from the SAME registered app.' mod='amazonmarketplacepro'}</strong>
            {l s='A refresh token is bound to the app that issued it. Pairing a token from one app with another app\'s client ID and secret still yields an access token, but every SP-API call then fails with 403 Unauthorized. When you switch app — sandbox to production, for instance — replace the client ID and secret too, not only the token.' mod='amazonmarketplacepro'}
        </div>
        {if $mkpro_dev_mode}
            <p class="help-block">
                {l s='Active environment:' mod='amazonmarketplacepro'}
                <strong>{if $mkpro_environment == 'sandbox'}{l s='Sandbox app' mod='amazonmarketplacepro'}{else}{l s='Production app' mod='amazonmarketplacepro'}{/if}</strong>
                — {l s='the credentials here must belong to that app.' mod='amazonmarketplacepro'}
                {l s='Active app id:' mod='amazonmarketplacepro'} <code>{$mkpro_lwa_app_id|escape:'htmlall':'UTF-8'}</code>
            </p>
        {/if}
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
    {/if}
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
    {* ── Connection Test ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-plug"></i> {l s='Connection Test' mod='amazonmarketplacepro'}</div>
        <p>{l s='Checks the connection made with Connect to Amazon: requests an access token and reads recent orders. Run it after connecting, or whenever a sync reports an authorisation error.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="test-amazon-connection" class="btn btn-primary">
            <i class="icon-refresh"></i> {l s='Test Amazon Connection' mod='amazonmarketplacepro'}
        </button>
        <pre id="amazon-connection-result" style="display:none; margin-top:15px; padding:12px; white-space:pre-wrap; word-break:break-word;"></pre>
    </div>
    </div>

    <div class="tab-pane" id="set-listings">
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
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='B2B quantity discounts from group' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_business_group" class="form-control">
                    <option value="0">{l s='-- No quantity ladder --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$customer_groups item=grp}
                        <option value="{$grp.id_group|escape:'htmlall':'UTF-8'}"{if $mkpro_business_group == $grp.id_group} selected="selected"{/if}>{$grp.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">
                    {l s='Pick the PrestaShop customer group you use for trade customers. Its specific prices with a minimum quantity above 1 become Amazon Business volume tiers — so "buy 10, save 5%" is expressed once, in PrestaShop, and exported. Amazon accepts up to five tiers.' mod='amazonmarketplacepro'}
                </p>
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
    </div>

    <div class="tab-pane" id="set-sync">
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
            <label class="control-label col-lg-3">{l s='Send images' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_send_images" class="form-control">
                    <option value="1"{if $mkpro_send_images != '0'} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                    <option value="0"{if $mkpro_send_images == '0'} selected="selected"{/if}>{l s='No — keep the images already on Amazon' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Amazon fetches every image URL you send, which slows large pushes. Many merchants send images on the first publish, then switch this off.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Send full product data' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_extended_data" class="form-control">
                    <option value="1"{if $mkpro_extended_data != '0'} selected="selected"{/if}>{l s='Yes — title, description, bullets, brand, images' mod='amazonmarketplacepro'}</option>
                    <option value="0"{if $mkpro_extended_data == '0'} selected="selected"{/if}>{l s='No — offer only (price and stock)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Offer-only never rewrites the Amazon listing content, which is what you want once your listings are established or when you sell on someone else\'s catalogue page.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Send the entire catalogue' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_full_catalog" class="form-control">
                    <option value="0"{if !$mkpro_full_catalog} selected="selected"{/if}>{l s='No — respect the delta window below' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_full_catalog} selected="selected"{/if}>{l s='Yes — ignore the delta window and export everything' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='For a first publish, or after changing a setting that affects every listing. Switch it back off afterwards — Amazon limits how much you may send.' mod='amazonmarketplacepro'}</p>
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
            <label class="control-label col-lg-3">{l s='Price rounding' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_rounding" class="form-control">
                    <option value="cents"{if $mkpro_rounding != 'smart' && $mkpro_rounding != 'integer'} selected="selected"{/if}>{l s='Nearest cent (default)' mod='amazonmarketplacepro'}</option>
                    <option value="smart"{if $mkpro_rounding == 'smart'} selected="selected"{/if}>{l s='Smart — round up to .99 (15.93 becomes 15.99)' mod='amazonmarketplacepro'}</option>
                    <option value="integer"{if $mkpro_rounding == 'integer'} selected="selected"{/if}>{l s='Whole number (15.99 becomes 16)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Applied after markups and specific prices, to every exported price including sale and list prices.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Send sale prices' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_send_sale_price" class="form-control">
                    <option value="0"{if !$mkpro_send_sale_price} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_send_sale_price} selected="selected"{/if}>{l s='Enabled — export dated specific prices as Amazon sales' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='A PrestaShop specific price with a start AND an end date becomes a sale price with the same window. Amazon shows the discount only inside it, so upcoming promotions can be pushed in advance. Undated specific prices are handled by the "Use specific prices" setting instead.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Send list price (strikethrough)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_send_list_price" class="form-control">
                    <option value="0"{if !$mkpro_send_list_price} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_send_list_price} selected="selected"{/if}>{l s='Enabled' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Sends the pre-discount shop price as the crossed-out list price, when it is higher than the exported price.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Preorder' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_preorder" class="form-control">
                    <option value="0"{if !$mkpro_preorder} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_preorder} selected="selected"{/if}>{l s='Enabled — send the availability date as Amazon restock date' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Products whose PrestaShop availability date is in the future are listed with that restock date, so Amazon can show preorder messaging.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Condition mapping' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <table class="table" style="max-width:520px; margin-bottom:6px;">
                    <thead><tr><th>{l s='PrestaShop condition' mod='amazonmarketplacepro'}</th><th>{l s='Amazon condition' mod='amazonmarketplacepro'}</th></tr></thead>
                    <tbody>
                        {foreach from=['new', 'used', 'refurbished'] item=psCond}
                        <tr>
                            <td style="vertical-align:middle;">{$psCond|escape:'htmlall':'UTF-8'}</td>
                            <td>
                                <select name="mkpro_cond_map_{$psCond}" class="form-control input-sm">
                                    {foreach from=$amazon_conditions key=azCode item=azLabel}
                                        <option value="{$azCode|escape:'htmlall':'UTF-8'}"{if $mkpro_condition_map[$psCond] == $azCode} selected="selected"{/if}>{$azLabel|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
                <p class="help-block">{l s='PrestaShop only has three conditions; this maps them onto Amazon\'s richer set. A per-product condition on the product\'s Amazon tab overrides this.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Listing title format' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_title_format" class="form-control">
                    <option value="name"{if $mkpro_title_format != 'brand_name_attrs'} selected="selected"{/if}>{l s='Product name as-is' mod='amazonmarketplacepro'}</option>
                    <option value="brand_name_attrs"{if $mkpro_title_format == 'brand_name_attrs'} selected="selected"{/if}>{l s='Brand - Product name - Attributes (Amazon guideline)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Keep names as-is if your titles are already optimised for Amazon. The brand is skipped when the name already starts with it.' mod='amazonmarketplacepro'}</p>
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
    </div>

    <div class="tab-pane" id="set-shipping">
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
    </div>

    <div class="tab-pane" id="set-orders">
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
            <label class="control-label col-lg-3">{l s='Match order lines by' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_order_match" class="form-control">
                    <option value="reference"{if $mkpro_order_match != 'id'} selected="selected"{/if}>{l s='Reference / Override SKU (recommended)' mod='amazonmarketplacepro'}</option>
                    <option value="id"{if $mkpro_order_match == 'id'} selected="selected"{/if}>{l s='PrestaShop IDs (Amazon SKU looks like 123 or 123_45)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='The default tries the per-product Override SKU first, then the reference, then EAN. Choose the ID mode only if your Amazon SKUs were built from PrestaShop product and combination ids.' mod='amazonmarketplacepro'}</p>
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
            <label class="control-label col-lg-3">{l s='Delete buyer data after' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="input-group" style="max-width:220px;">
                    <input type="number" min="1" max="365" name="mkpro_pii_retention_days" value="{$mkpro_pii_retention_days|escape:'htmlall':'UTF-8'}" class="form-control"{if !$mkpro_pii_purge} disabled="disabled"{/if} />
                    <span class="input-group-addon">{l s='days' mod='amazonmarketplacepro'}</span>
                </div>
                <p class="help-block">
                    {l s='Amazon\'s Data Protection Policy requires buyer data to be deleted within 30 days of delivery. Once an order is finished and older than this, the module clears the buyer name, e-mail, street, city, region, postcode, phone and the stored Amazon payload from its own order table. Order totals, tax, fees and the destination country stay, so your reporting and VAT records are unaffected.' mod='amazonmarketplacepro'}
                </p>
                <p class="help-block">
                    <strong>{$pii_due_count|intval}</strong> {l s='order(s) waiting to be cleared;' mod='amazonmarketplacepro'}
                    <strong>{$pii_purged_count|intval}</strong> {l s='already cleared.' mod='amazonmarketplacepro'}
                    {if $pii_due_count > 0}
                        <button type="submit" name="submitMkproSettings" value="purge_pii" class="btn btn-default btn-xs">{l s='Clear them now' mod='amazonmarketplacepro'}</button>
                    {/if}
                </p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Buyer data deletion' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_pii_purge" class="form-control">
                    <option value="1"{if $mkpro_pii_purge} selected="selected"{/if}>{l s='On (required for Amazon compliance)' mod='amazonmarketplacepro'}</option>
                    <option value="0"{if !$mkpro_pii_purge} selected="selected"{/if}>{l s='Off' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Turning this off means keeping Amazon buyer data longer than Amazon permits. Only do so if you have a legal obligation that requires it, and can show what it is.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Also anonymise the PrestaShop customer and address' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_pii_purge_ps" class="form-control">
                    <option value="0"{if !$mkpro_pii_purge_ps} selected="selected"{/if}>{l s='No (recommended)' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_pii_purge_ps} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">
                    {l s='Off by default on purpose. PrestaShop renders invoices from the stored address, so anonymising it changes documents you may be legally required to keep intact — and an invoice you must keep is exactly the exception Amazon\'s policy allows. Only turn this on after taking your own advice. When on, a customer is anonymised only if every order they have is an Amazon order that has already been cleared; city, postcode and country are kept so the VAT treatment stays justifiable.' mod='amazonmarketplacepro'}
                </p>
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
            <label class="control-label col-lg-3">{l s='Remote Cart (reserve stock for unpaid orders)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_remote_cart" class="form-control">
                    <option value="0"{if !$mkpro_remote_cart} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_remote_cart} selected="selected"{/if}>{l s='Enabled' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">
                    {l s='Amazon exposes a checkout in progress as a "Pending" order. With this on, the module takes those units out of PrestaShop stock immediately so no other channel can sell them, and gives them back if the order never becomes payable. Requires importing Pending orders — the module does that automatically once this is enabled — and the remote_cart cron to settle holds.' mod='amazonmarketplacepro'}
                </p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Release a hold after' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="input-group" style="max-width:200px;">
                    <input type="number" min="1" max="72" name="mkpro_remote_cart_ttl" value="{$mkpro_remote_cart_ttl|escape:'htmlall':'UTF-8'}" class="form-control" />
                    <span class="input-group-addon">{l s='hours' mod='amazonmarketplacepro'}</span>
                </div>
                <p class="help-block">{l s='Grace period before an order still stuck in Pending has its stock returned. 4 hours is a safe default — Amazon can take a while to move an order to Unshipped.' mod='amazonmarketplacepro'}</p>
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
    {* ── Incoming carrier mapping & status routing ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-random"></i> {l s='Imported Order Routing' mod='amazonmarketplacepro'}</div>

        <p class="help-block">{l s='Amazon tells you the delivery speed it promised the buyer. Map each one onto a PrestaShop carrier so imported orders carry the right shipping method; unmapped speeds use the default carrier above.' mod='amazonmarketplacepro'}</p>
        <table class="table" style="max-width:600px;">
            <thead><tr><th>{l s='Amazon shipping speed' mod='amazonmarketplacepro'}</th><th>{l s='PrestaShop carrier' mod='amazonmarketplacepro'}</th></tr></thead>
            <tbody>
                {foreach from=$amazon_ship_levels item=level}
                <tr>
                    <td>{$level|escape:'htmlall':'UTF-8'}</td>
                    <td>
                        <select name="mkpro_carrier_map_in[{$level|escape:'htmlall':'UTF-8'}]" class="form-control">
                            <option value="0">{l s='-- Default carrier --' mod='amazonmarketplacepro'}</option>
                            {foreach from=$carriers item=carrier}
                                <option value="{$carrier.id_carrier|escape:'htmlall':'UTF-8'}"{if isset($mkpro_carrier_map_in[$level]) && $mkpro_carrier_map_in[$level] == $carrier.id_carrier} selected="selected"{/if}>{$carrier.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        <div class="panel-heading" style="margin-top:15px;"><i class="icon-sitemap"></i> {l s='Advanced status rules' mod='amazonmarketplacepro'}</div>
        <p class="help-block">{l s='Give particular kinds of order their own PrestaShop status — for example Prime orders in a bright status your warehouse cannot miss, or FBA orders straight to Shipped. Rules are evaluated top to bottom and the first match wins; "Any" ignores that flag. A rule with no status is skipped.' mod='amazonmarketplacepro'}</p>
        <table class="table" style="max-width:800px;">
            <thead><tr>
                <th>{l s='Prime' mod='amazonmarketplacepro'}</th>
                <th>{l s='FBA' mod='amazonmarketplacepro'}</th>
                <th>{l s='Amazon Business' mod='amazonmarketplacepro'}</th>
                <th>{l s='PrestaShop status' mod='amazonmarketplacepro'}</th>
            </tr></thead>
            <tbody>
                {section name=ruleRow start=0 loop=4}
                {assign var=i value=$smarty.section.ruleRow.index}
                <tr>
                    {foreach from=['prime', 'fba', 'business'] item=flag}
                    <td>
                        <select name="mkpro_rule_{$flag}[{$i}]" class="form-control input-sm">
                            <option value="-1"{if !isset($mkpro_status_rules[$i]) || $mkpro_status_rules[$i][$flag] == -1} selected="selected"{/if}>{l s='Any' mod='amazonmarketplacepro'}</option>
                            <option value="1"{if isset($mkpro_status_rules[$i]) && $mkpro_status_rules[$i][$flag] == 1} selected="selected"{/if}>{l s='Yes' mod='amazonmarketplacepro'}</option>
                            <option value="0"{if isset($mkpro_status_rules[$i]) && $mkpro_status_rules[$i][$flag] === 0} selected="selected"{/if}>{l s='No' mod='amazonmarketplacepro'}</option>
                        </select>
                    </td>
                    {/foreach}
                    <td>
                        <select name="mkpro_rule_state[{$i}]" class="form-control input-sm">
                            <option value="0">{l s='-- No rule --' mod='amazonmarketplacepro'}</option>
                            {foreach from=$order_states item=state}
                                <option value="{$state.id_order_state|escape:'htmlall':'UTF-8'}"{if isset($mkpro_status_rules[$i]) && $mkpro_status_rules[$i].state == $state.id_order_state} selected="selected"{/if}>{$state.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                {/section}
            </tbody>
        </table>

        <div class="panel-heading" style="margin-top:15px;"><i class="icon-envelope"></i> {l s='Invoice by e-mail' mod='amazonmarketplacepro'}</div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='E-mail the invoice to the buyer' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_invoice_email" class="form-control">
                    <option value="0"{if !$mkpro_invoice_email} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_invoice_email} selected="selected"{/if}>{l s='Enabled' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Sends the PrestaShop invoice PDF to the buyer when the order reaches the status below. Skipped when the buyer e-mail is anonymised.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Send it on status' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_invoice_email_state" class="form-control">
                    <option value="0">{l s='-- Never --' mod='amazonmarketplacepro'}</option>
                    {foreach from=$order_states item=state}
                        <option value="{$state.id_order_state|escape:'htmlall':'UTF-8'}"{if $mkpro_invoice_email_state == $state.id_order_state} selected="selected"{/if}>{$state.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Extra attachment' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_invoice_attachment" value="{$mkpro_invoice_attachment|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="terms.pdf" />
                <p class="help-block">{l s='Optional PDF filename to attach alongside the invoice (terms, returns policy). Upload it into the module\'s docs/ folder.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
    </div>
    </div>

    <div class="tab-pane" id="set-messaging">
    {* ── Inbound buyer messages ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-inbox"></i> {l s='Buyer Replies (inbound)' mod='amazonmarketplacepro'}</div>
        <p class="help-block">
            {l s='Amazon has no API for reading what a buyer writes back — replies are delivered by e-mail to your seller address. Point the module at that mailbox and it files any message quoting an Amazon order into PrestaShop\'s Customer Service, against the right order.' mod='amazonmarketplacepro'}
        </p>
        {if !$mkpro_imap_available}
            <div class="alert alert-warning">
                <i class="icon-warning"></i>
                {l s='The PHP IMAP extension is not installed on this server, so this feature cannot run. Ask your host to enable ext-imap; the settings below will be saved in the meantime.' mod='amazonmarketplacepro'}
            </div>
        {/if}
        <div class="alert alert-info">
            <i class="icon-info-circle"></i>
            {l s='This stores a mailbox password in your shop database. Use a dedicated mailbox or an app password rather than your main account credentials, and give it read access only.' mod='amazonmarketplacepro'}
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Read buyer replies' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select name="mkpro_imap_enabled" class="form-control">
                    <option value="0"{if !$mkpro_imap_enabled} selected="selected"{/if}>{l s='Disabled' mod='amazonmarketplacepro'}</option>
                    <option value="1"{if $mkpro_imap_enabled} selected="selected"{/if}>{l s='Enabled' mod='amazonmarketplacepro'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='IMAP host / port' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="row">
                    <div class="col-xs-7"><input type="text" name="mkpro_imap_host" value="{$mkpro_imap_host|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="imap.example.com" /></div>
                    <div class="col-xs-3"><input type="number" name="mkpro_imap_port" value="{$mkpro_imap_port|escape:'htmlall':'UTF-8'}" class="form-control" /></div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Mailbox user' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" name="mkpro_imap_user" value="{$mkpro_imap_user|escape:'htmlall':'UTF-8'}" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Mailbox password' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="password" name="mkpro_imap_password" value="" class="form-control" autocomplete="new-password"
                       placeholder="{if $mkpro_imap_password_set}{l s='Stored — leave empty to keep it' mod='amazonmarketplacepro'}{else}{l s='Mailbox or app password' mod='amazonmarketplacepro'}{/if}" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Folder / SSL' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="row">
                    <div class="col-xs-5"><input type="text" name="mkpro_imap_folder" value="{$mkpro_imap_folder|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="INBOX" /></div>
                    <div class="col-xs-5">
                        <select name="mkpro_imap_ssl" class="form-control">
                            <option value="1"{if $mkpro_imap_ssl} selected="selected"{/if}>{l s='Use SSL' mod='amazonmarketplacepro'}</option>
                            <option value="0"{if !$mkpro_imap_ssl} selected="selected"{/if}>{l s='No SSL' mod='amazonmarketplacepro'}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-offset-3 col-lg-6">
                <button type="button" id="inbox-fetch" class="btn btn-primary"><i class="icon-download"></i> {l s='Fetch buyer replies now' mod='amazonmarketplacepro'}</button>
                <p class="help-block">{l s='Save the settings first. Only unread messages are read, and a message is marked read only once it is safely in Customer Service. Schedule the fetch_messages cron for hands-free operation.' mod='amazonmarketplacepro'}</p>
                <div id="inbox-result" style="display:none; margin-top:10px;"></div>
            </div>
        </div>
    </div>
    </div>

    </div>{* /tab-content *}

    <div class="panel">
        <div class="panel-footer">
            <button type="submit" name="submitMkproSettings" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save Settings' mod='amazonmarketplacepro'}
            </button>
        </div>
    </div>

    </form>
</div>

{* ════════════════════ SYSTEM ════════════════════ *}
<div class="tab-pane" id="grp-system">

    <ul class="nav nav-tabs mkpro-section-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#tab-cron" data-toggle="tab"><i class="icon-clock-o"></i> {l s='Automation' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-tools" data-toggle="tab"><i class="icon-wrench"></i> {l s='Tools' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-logs" data-toggle="tab"><i class="icon-file-text-o"></i> {l s='Logs' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

{* ═══════════════════════ CRON TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-cron">

    <div class="panel">
        <div class="panel-heading"><i class="icon-clock-o"></i> {l s='How automation runs' mod='amazonmarketplacepro'}</div>

        {if $schedule_notice}
            <div class="alert alert-info">{$schedule_notice|escape:'htmlall':'UTF-8'}</div>
        {/if}

        <p>
            {l s='Something has to call the shop on a timer, because a shop only runs code when a request arrives. Choose who does the calling.' mod='amazonmarketplacepro'}
        </p>

        <form method="post" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Run the schedule' mod='amazonmarketplacepro'}</label>
                <div class="col-lg-9">
                    {* form-control is display:block, so without this row the button drops under the select *}
                    <div style="display:flex;align-items:center;">
                        <select name="mode" class="form-control fixed-width-xxl">
                            <option value="cron"{if $schedule_mode != 'relay'} selected="selected"{/if}>{l s='My own server cron (recommended)' mod='amazonmarketplacepro'}</option>
                            <option value="relay"{if $schedule_mode == 'relay'} selected="selected"{/if}>{l s='The IntelliPresta scheduler' mod='amazonmarketplacepro'}</option>
                        </select>
                        <button type="submit" name="mkproScheduleMode" class="btn btn-default" style="margin-left:8px;white-space:nowrap;">
                            <i class="icon-save"></i> {l s='Save' mod='amazonmarketplacepro'}
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-lg-6">
                <div class="alert {if $schedule_mode != 'relay'}alert-success{else}alert-info{/if}">
                    <strong>{l s='My own server cron' mod='amazonmarketplacepro'}</strong><br/>
                    {l s='Add this one line to your hosting control panel or crontab. Nothing sits between your shop and its schedule, which is why this is the recommendation.' mod='amazonmarketplacepro'}
                    <br/><br/>
                    <code style="font-size:11px; word-break:break-all;">*/5 * * * * curl -s "{$cron_run_due_url|escape:'htmlall':'UTF-8'}" &gt; /dev/null 2&gt;&amp;1</code>
                    <br/><br/>
                    <span class="help-block" style="margin-bottom:0;">
                        {l s='Each call runs only what the table below says is due, so a five minute cron does not mean a five minute sync.' mod='amazonmarketplacepro'}
                    </span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="alert {if $schedule_mode == 'relay'}alert-success{else}alert-info{/if}">
                    <strong>{l s='The IntelliPresta scheduler' mod='amazonmarketplacepro'}</strong><br/>
                    {l s='For hosting with no crontab. Our server calls your shop every five minutes so your intervals are kept exactly, with nothing for you to set up.' mod='amazonmarketplacepro'}
                    <br/><br/>
                    <span class="help-block">
                        {l s='Two things to know before choosing it. Your shop address and its cron token are stored on our server, because that token is what lets the call start a run. And if our server is unreachable, your schedule pauses until it returns.' mod='amazonmarketplacepro'}
                    </span>

                    {if $schedule_relay.registered}
                        <p>
                            <span class="badge" style="background:#51954B;">{l s='Registered' mod='amazonmarketplacepro'}</span>
                            {if $schedule_relay.since}<small class="text-muted">{l s='since' mod='amazonmarketplacepro'} {$schedule_relay.since|escape:'htmlall':'UTF-8'}</small>{/if}
                        </p>
                        <form method="post">
                            <button type="submit" name="mkproRelayUnregister" class="btn btn-default btn-sm">
                                <i class="icon-times"></i> {l s='Stop using the scheduler' mod='amazonmarketplacepro'}
                            </button>
                        </form>
                    {else}
                        {if $schedule_relay.last_error}
                            <div class="alert alert-warning" style="margin-bottom:8px;">
                                {$schedule_relay.last_error|escape:'htmlall':'UTF-8'}
                            </div>
                        {/if}
                        <form method="post">
                            <button type="submit" name="mkproRelayRegister" class="btn btn-primary btn-sm">
                                <i class="icon-cloud-upload"></i> {l s='Register this shop' mod='amazonmarketplacepro'}
                            </button>
                        </form>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading"><i class="icon-list"></i> {l s='Scheduled tasks' mod='amazonmarketplacepro'}</div>

        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Task' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Every' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Last run' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Result' mod='amazonmarketplacepro'}</th>
                    <th>{l s='Next run' mod='amazonmarketplacepro'}</th>
                    <th style="width:230px;">{l s='Actions' mod='amazonmarketplacepro'}</th>
                </tr>
            </thead>
            <tbody>
            {foreach from=$schedule_tasks item=t}
                <tr{if !$t.active} class="text-muted"{/if}>
                    <td>
                        <strong>
                            {if isset($schedule_catalogue[$t.task_key])}
                                {$schedule_catalogue[$t.task_key][0]|escape:'htmlall':'UTF-8'}
                            {else}
                                {$t.task_key|escape:'htmlall':'UTF-8'}
                            {/if}
                        </strong>
                        <br/><small class="text-muted">{$t.task_key|escape:'htmlall':'UTF-8'}</small>
                    </td>
                    <td>
                        <form method="post" class="form-inline">
                            <input type="hidden" name="id_task" value="{$t.id_task|intval}" />
                            <input type="hidden" name="task_key" value="{$t.task_key|escape:'htmlall':'UTF-8'}" />
                            <input type="hidden" name="active" value="{$t.active|intval}" />
                            <input type="number" min="1" name="interval_minutes" value="{$t.interval_minutes|intval}" class="form-control" style="width:100px; display:inline-block;" />
                            <span class="text-muted">{l s='min' mod='amazonmarketplacepro'}</span>
                            <button type="submit" name="mkproScheduleSave" class="btn btn-default btn-sm" title="{l s='Save interval' mod='amazonmarketplacepro'}">
                                <i class="icon-save"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        {if $t.last_run_at}
                            {$t.last_run_at|escape:'htmlall':'UTF-8'}
                            {if $t.last_duration_ms}<br/><small class="text-muted">{$t.last_duration_ms|intval} ms</small>{/if}
                        {else}
                            <span class="text-muted">{l s='never' mod='amazonmarketplacepro'}</span>
                        {/if}
                    </td>
                    <td>
                        {if $t.last_status == 'success'}
                            <span class="badge" style="background:#51954B;">{l s='OK' mod='amazonmarketplacepro'}</span>
                        {elseif $t.last_status == 'error'}
                            <span class="badge" style="background:#E05252;">{l s='Failed' mod='amazonmarketplacepro'}</span>
                        {else}
                            <span class="text-muted">&mdash;</span>
                        {/if}
                        {if $t.last_message}
                            <br/><small class="text-muted" style="word-break:break-all;">{$t.last_message|truncate:90:'…'|escape:'htmlall':'UTF-8'}</small>
                        {/if}
                        {if $t.fail_count}
                            <br/><small class="text-muted">{l s='failures:' mod='amazonmarketplacepro'} {$t.fail_count|intval}</small>
                        {/if}
                    </td>
                    <td>
                        {if $t.active}{$t.next_run_at|escape:'htmlall':'UTF-8'}
                        {else}<span class="text-muted">{l s='off' mod='amazonmarketplacepro'}</span>{/if}
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_task" value="{$t.id_task|intval}" />
                            <button type="submit" name="mkproScheduleToggle" class="btn btn-sm {if $t.active}btn-success{else}btn-default{/if}">
                                {if $t.active}<i class="icon-check"></i> {l s='On' mod='amazonmarketplacepro'}
                                {else}<i class="icon-times"></i> {l s='Off' mod='amazonmarketplacepro'}{/if}
                            </button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_task" value="{$t.id_task|intval}" />
                            <button type="submit" name="mkproScheduleRunNow" class="btn btn-default btn-sm" title="{l s='Run this task now' mod='amazonmarketplacepro'}">
                                <i class="icon-play"></i> {l s='Run now' mod='amazonmarketplacepro'}
                            </button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('{l s='Remove this task from the schedule?' mod='amazonmarketplacepro' js=1}');">
                            <input type="hidden" name="id_task" value="{$t.id_task|intval}" />
                            <button type="submit" name="mkproScheduleDelete" class="btn btn-default btn-sm" title="{l s='Remove' mod='amazonmarketplacepro'}">
                                <i class="icon-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            {foreachelse}
                <tr><td colspan="6" class="text-muted">{l s='No tasks scheduled yet. Add one below.' mod='amazonmarketplacepro'}</td></tr>
            {/foreach}
            </tbody>
        </table>

        <form method="post" class="form-inline" style="margin-top:10px;">
            <select name="task_key" class="form-control">
                {foreach from=$schedule_catalogue key=k item=meta}
                    <option value="{$k|escape:'htmlall':'UTF-8'}">{$meta[0]|escape:'htmlall':'UTF-8'} &mdash; {$meta[2]|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
            <input type="number" min="1" name="interval_minutes" value="60" class="form-control" style="width:100px;" />
            <span class="text-muted">{l s='minutes' mod='amazonmarketplacepro'}</span>
            <label class="checkbox-inline"><input type="checkbox" name="active" value="1" checked="checked" /> {l s='on' mod='amazonmarketplacepro'}</label>
            <button type="submit" name="mkproScheduleSave" class="btn btn-primary">
                <i class="icon-plus"></i> {l s='Add task' mod='amazonmarketplacepro'}
            </button>
        </form>

        <p style="margin-top:14px;">
            <strong>{l s='Your cron token' mod='amazonmarketplacepro'}:</strong>
            <code>{$mkpro_cron_token|escape:'htmlall':'UTF-8'}</code>
        </p>
    </div>

</div>

{* ═══════════════════════ TOOLS TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-tools">

    {* ── Catalogue audit ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-stethoscope"></i> {l s='Catalogue Check' mod='amazonmarketplacepro'}</div>
        <p>{l s='Amazon matches everything on the reference and the barcode. Products missing either, or sharing a reference with another product, cannot be listed or matched to incoming orders.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="audit-run" class="btn btn-primary"><i class="icon-search"></i> {l s='Check my catalogue' mod='amazonmarketplacepro'}</button>
        <div id="audit-result" style="display:none; margin-top:12px;"></div>
    </div>

    {* ── Reference / barcode CSV ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-table"></i> {l s='Reference & Barcode Editor (CSV)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Export every product and combination, fix the references and barcodes in a spreadsheet, then upload the file back. Only reference, EAN, UPC and supplier reference are written; the key and name columns are read-only.' mod='amazonmarketplacepro'}</p>
        <div class="alert alert-warning">
            <i class="icon-warning"></i>
            {l s='This edits your PrestaShop catalogue directly and cannot be undone — back up your database first. Nothing is sent to Amazon. Do it before you publish, or you will create duplicate SKUs on Amazon.' mod='amazonmarketplacepro'}
        </div>
        <p>
            <a href="{$export_references_url|escape:'htmlall':'UTF-8'}" class="btn btn-default">
                <i class="icon-download"></i> {l s='Export CSV' mod='amazonmarketplacepro'}
            </a>
        </p>
        <div class="form-inline" style="margin-top:10px;">
            <input type="file" id="reference-file" accept=".csv,text/csv" class="form-control" />
            <button type="button" id="reference-import" class="btn btn-warning">
                <i class="icon-upload"></i> {l s='Import CSV' mod='amazonmarketplacepro'}
            </button>
        </div>
        <p class="help-block">{l s='Semicolon-separated, UTF-8. Barcodes are exported with a leading apostrophe so spreadsheets keep them as text; it is removed on import.' mod='amazonmarketplacepro'}</p>
        <div id="reference-result" style="display:none; margin-top:10px;"></div>
    </div>

    {* ── Listing deletion ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-trash"></i> {l s='Delete Listings from Amazon' mod='amazonmarketplacepro'}</div>
        <p>{l s='Removes offers from Amazon for products you no longer sell — deleted, disabled, or switched off for Amazon on the product page. Review the list first: deletion removes the offer and its history on Amazon, which is not reversible from here.' mod='amazonmarketplacepro'}</p>
        <p class="help-block">{l s='If you only want to stop selling temporarily, publishing quantity 0 (Listing Defaults) keeps the listing and its reviews alive.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="deletions-list" class="btn btn-primary"><i class="icon-search"></i> {l s='Find listings to delete' mod='amazonmarketplacepro'}</button>
        <button type="button" id="deletions-send" class="btn btn-danger" style="margin-left:10px;" disabled="disabled">
            <i class="icon-trash"></i> {l s='Delete selected from Amazon' mod='amazonmarketplacepro'}
        </button>
        <div id="deletions-result" style="display:none; margin-top:10px;"></div>
        <table class="table" id="deletions-table" style="display:none; margin-top:10px;">
            <thead><tr>
                <th style="width:30px;"><input type="checkbox" id="deletions-all" /></th>
                <th>{l s='SKU' mod='amazonmarketplacepro'}</th>
                <th>{l s='ASIN' mod='amazonmarketplacepro'}</th>
                <th>{l s='Product' mod='amazonmarketplacepro'}</th>
                <th>{l s='Why' mod='amazonmarketplacepro'}</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>

    {* ── Feed payloads ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-file-code-o"></i> {l s='Submitted Feed Payloads' mod='amazonmarketplacepro'}</div>
        <p>{l s='The exact JSON sent to Amazon for each bulk feed is kept here. Amazon support (and ours) will ask for it whenever a listing is rejected for a reason the report does not explain.' mod='amazonmarketplacepro'}</p>
        <div id="feed-payload-hint" class="alert alert-info">
            {l s='Submit a bulk feed from the Products tab, then come back — each feed will appear with a download link. Feeds sent before this version have no stored payload.' mod='amazonmarketplacepro'}
        </div>
        <div class="form-inline">
            <input type="text" id="feed-payload-id" class="form-control" placeholder="{l s='Feed ID' mod='amazonmarketplacepro'}" style="width:260px;" />
            <a href="#" id="feed-payload-download" class="btn btn-default"><i class="icon-download"></i> {l s='Download this feed\'s JSON' mod='amazonmarketplacepro'}</a>
        </div>
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
    </div>{* /section tab-content *}
</div>

{* ════════════════════ CATALOG ════════════════════ *}
<div class="tab-pane" id="grp-catalog">

    <ul class="nav nav-tabs mkpro-section-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#tab-products" data-toggle="tab"><i class="icon-th-list"></i> {l s='Products' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-profiles" data-toggle="tab"><i class="icon-magic"></i> {l s='Profiles' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-markup" data-toggle="tab"><i class="icon-sliders"></i> {l s='Markup & Rules' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-prodrules" data-toggle="tab"><i class="icon-check-square-o"></i> {l s='Product Rules' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-queue" data-toggle="tab"><i class="icon-list-ol"></i> {l s='Queue' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-orphans" data-toggle="tab"><i class="icon-unlink"></i> {l s='Orphans' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

{* ═══════════════════════ PRODUCTS TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-products">

    {* ── PrestaShop → Amazon ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-cloud-upload"></i> {l s='PrestaShop → Amazon' mod='amazonmarketplacepro'}</div>
        <p>{l s='List your products on Amazon and keep price and stock current. The first step only reads and compares; nothing reaches Amazon until you push.' mod='amazonmarketplacepro'}</p>

        <div class="mkpro-step" style="display:flex;align-items:flex-start;margin-bottom:16px;">
            <span style="display:inline-block;flex:none;min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:13px;background:#25b2a8;color:#fff;font-weight:700;margin-top:5px;">1</span>
            <div style="margin-left:12px;flex:1;">
                <button type="button" id="sync-products-ps" class="btn btn-primary">
                    <i class="icon-refresh"></i> {l s='Sync PS to Amazon' mod='amazonmarketplacepro'}
                </button>
                <p class="help-block" style="margin:6px 0 0;">{l s='Reads your PrestaShop catalogue and marks, per SKU, what differs from Amazon: description, bullet points, brand, images, EAN and categories. The comparison appears below.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>

        <div class="mkpro-step" style="display:flex;align-items:flex-start;margin-bottom:16px;">
            <span style="display:inline-block;flex:none;min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:13px;background:#25b2a8;color:#fff;font-weight:700;margin-top:5px;">2</span>
            <div style="margin-left:12px;flex:1;">
                <button type="button" id="match-catalog" class="btn btn-default">
                    <i class="icon-magic"></i> {l s='Match ASINs by EAN' mod='amazonmarketplacepro'}
                </button>
                <p class="help-block" style="margin:6px 0 0;">{l s='Optional. For products Amazon already sells, finds the existing page by EAN so you add an offer to it instead of creating a duplicate listing.' mod='amazonmarketplacepro'}</p>
                <div id="match-catalog-result" style="display:none; margin-top:8px;"></div>
            </div>
        </div>

        <div class="mkpro-step" style="display:flex;align-items:flex-start;margin-bottom:16px;">
            <span style="display:inline-block;flex:none;min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:13px;background:#25b2a8;color:#fff;font-weight:700;margin-top:5px;">3</span>
            <div style="margin-left:12px;flex:1;">
                <button type="button" id="send-pending" class="btn btn-warning">
                    <i class="icon-upload"></i> {l s='Push to Amazon' mod='amazonmarketplacepro'}
                </button>
                <p class="help-block" style="margin:6px 0 0;">{l s='Sends what the comparison marks as pending. Up to 25 SKUs go one by one and are answered at once; more go as a single feed, which you follow under Feed status.' mod='amazonmarketplacepro'}</p>
                <div id="amazon-push-result" style="display:none; margin-top:8px;"></div>
            </div>
        </div>

        <div id="amazon-products-summary-ps" style="display:none;"></div>
        <div id="amazon-products-result-ps" style="display:none; margin-top:10px;"></div>
    </div>

    {* ── Amazon → PrestaShop ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-cloud-download"></i> {l s='Amazon → PrestaShop' mod='amazonmarketplacepro'}</div>
        <p>{l s='Bring Amazon listings into PrestaShop. The first step only reads and compares; choose in the second what to write, after looking at the table.' mod='amazonmarketplacepro'}</p>

        <div class="mkpro-step" style="display:flex;align-items:flex-start;margin-bottom:16px;">
            <span style="display:inline-block;flex:none;min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:13px;background:#25b2a8;color:#fff;font-weight:700;margin-top:5px;">1</span>
            <div style="margin-left:12px;flex:1;">
                <button type="button" id="sync-products-amazon" class="btn btn-primary">
                    <i class="icon-cloud-download"></i> {l s='Sync Amazon to PS' mod='amazonmarketplacepro'}
                </button>
                <p class="help-block" style="margin:6px 0 0;">{l s='Reads your Amazon listings and marks, per SKU, what PrestaShop lacks or holds differently. The comparison appears below. Nothing is written to PrestaShop yet.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>

        <div id="amazon-products-summary-amazon" style="display:none;"></div>
        <div id="amazon-products-result-amazon" style="display:none; margin-bottom:16px;"></div>

        <div class="mkpro-step" style="display:flex;align-items:flex-start;margin-bottom:16px;">
            <span style="display:inline-block;flex:none;min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:13px;background:#25b2a8;color:#fff;font-weight:700;margin-top:5px;">2</span>
            <div style="margin-left:12px;flex:1;">
                <p style="margin:5px 0 8px;"><strong>{l s='Then write one of two things:' mod='amazonmarketplacepro'}</strong></p>

                <div class="row" style="margin-bottom:6px;">
                    <div class="col-lg-3">
                        <select id="import-catalog-category" class="form-control">
                            <option value="0">{l s='-- Import into Home category --' mod='amazonmarketplacepro'}</option>
                            {foreach from=$ps_categories item=cat}
                                <option value="{$cat.id_category|escape:'htmlall':'UTF-8'}">{$cat.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-lg-9">
                        <button type="button" id="import-catalog" class="btn btn-default">
                            <i class="icon-download"></i> {l s='Create PS products from Amazon-only listings' mod='amazonmarketplacepro'}
                        </button>
                    </div>
                </div>
                <p class="help-block" style="margin:6px 0 0;">{l s='Products found on Amazon but missing in PrestaShop are created as INACTIVE products (title, description, brand, price, stock, images) in the chosen category, for review. Prices are imported without a tax group — assign one before activating.' mod='amazonmarketplacepro'}</p>
                <div id="import-catalog-result" style="display:none; margin:8px 0;"></div>

                <hr style="margin:14px 0;" />

                <div style="margin-bottom:8px;">
                    <label class="checkbox-inline"><input type="checkbox" class="amz-update-op" value="content" /> {l s='Title, description & brand' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" class="amz-update-op" value="price" /> {l s='Price' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" class="amz-update-op" value="quantity" /> {l s='Stock' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" class="amz-update-op" value="features" /> {l s='Bullet points as features' mod='amazonmarketplacepro'}</label>
                    <label class="checkbox-inline"><input type="checkbox" class="amz-update-op" value="hide" /> {l s='Deactivate what Amazon no longer carries' mod='amazonmarketplacepro'}</label>
                </div>
                <button type="button" id="amz-update-run" class="btn btn-warning">
                    <i class="icon-cloud-download"></i> {l s='Update from Amazon' mod='amazonmarketplacepro'}
                </button>
                <p class="help-block" style="margin:6px 0 0;">{l s='For products that exist in both catalogues, overwrites the ticked fields in PrestaShop with Amazon\'s values. Each is independent — tick only what you want overwritten. This cannot be undone, so back up first. Prices arrive tax-inclusive and are converted using each product\'s tax rule.' mod='amazonmarketplacepro'}</p>
                <div id="amz-update-result" style="display:none; margin-top:8px;"></div>
            </div>
        </div>
    </div>
    {* ── Bulk Feeds ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-cloud-upload"></i> {l s='Feed status' mod='amazonmarketplacepro'}</div>
        <p>{l s='A send of more than 25 SKUs goes to Amazon as one feed document, which Amazon processes in the background rather than answering at once. Check here whether it has finished and what it rejected. Smaller pushes are answered at once under the Push button.' mod='amazonmarketplacepro'}</p>
        <div class="btn-group" style="margin-bottom:10px;">
            <button type="button" id="feed-poll" class="btn btn-default">
                <i class="icon-refresh"></i> {l s='Check feed status' mod='amazonmarketplacepro'}
            </button>
        </div>
        <div id="feed-result" style="display:none; margin-bottom:10px;"></div>
        <div id="feed-table"></div>
    </div>

    {* ── Category Mapping ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-sitemap"></i> {l s='Category Mapping (PS Category → Amazon Product Type)' mod='amazonmarketplacepro'}</div>
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

{* ═══════════════════════ PROFILES TAB ═══════════════════════ *}
<div class="tab-pane" id="tab-profiles">

    <div class="panel">
        <div class="panel-heading"><i class="icon-magic"></i> {l s='Listing Profiles' mod='amazonmarketplacepro'}</div>
        <p class="help-block">
            {l s='A profile pins one Amazon product type (SHIRT, FASHION_RING, ...) and answers, once, every attribute that product type requires. Amazon publishes those requirements as a schema; the module downloads it and builds the form below, so you fill in real fields instead of hand-writing JSON. Bind the profile to PrestaShop categories and every product in them is listed with the right shape.' mod='amazonmarketplacepro'}
        </p>
        <p class="help-block">
            <strong>{l s='Each category belongs to at most one profile.' mod='amazonmarketplacepro'}</strong>
            {l s='Profiles take precedence over the simpler Category Mapping on the Products tab, which stays available as a fallback.' mod='amazonmarketplacepro'}
        </p>

        <table class="table" id="profiles-table">
            <thead><tr>
                <th>{l s='Name' mod='amazonmarketplacepro'}</th>
                <th>{l s='Amazon product type' mod='amazonmarketplacepro'}</th>
                <th>{l s='Variations' mod='amazonmarketplacepro'}</th>
                <th>{l s='Categories' mod='amazonmarketplacepro'}</th>
                <th>{l s='Browse nodes' mod='amazonmarketplacepro'}</th>
                <th></th>
            </tr></thead>
            <tbody>
                {if $profiles}
                    {foreach from=$profiles item=prof}
                    <tr data-id="{$prof.id_amazonmarketplacepro_profile|escape:'htmlall':'UTF-8'}">
                        <td><strong>{$prof.name|escape:'htmlall':'UTF-8'}</strong></td>
                        <td><code>{$prof.product_type|escape:'htmlall':'UTF-8'}</code></td>
                        <td>{if $prof.is_variation}<span class="label label-info">{$prof.variation_attributes|escape:'htmlall':'UTF-8'}</span>{else}<span class="text-muted">&mdash;</span>{/if}</td>
                        <td>{$prof.category_count|escape:'htmlall':'UTF-8'}</td>
                        <td><small>{$prof.browse_nodes|escape:'htmlall':'UTF-8'}</small></td>
                        <td style="white-space:nowrap;">
                            <button type="button" class="btn btn-xs btn-default profile-edit" data-id="{$prof.id_amazonmarketplacepro_profile|escape:'htmlall':'UTF-8'}"><i class="icon-pencil"></i> {l s='Edit' mod='amazonmarketplacepro'}</button>
                            <button type="button" class="btn btn-xs btn-danger profile-delete" data-id="{$prof.id_amazonmarketplacepro_profile|escape:'htmlall':'UTF-8'}"><i class="icon-trash"></i></button>
                        </td>
                    </tr>
                    {/foreach}
                {else}
                    <tr id="profiles-empty"><td colspan="6" class="text-center text-muted">{l s='No profiles yet. Create one below to list products Amazon does not already carry.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
        <button type="button" id="profile-new" class="btn btn-success"><i class="icon-plus"></i> {l s='New profile' mod='amazonmarketplacepro'}</button>
    </div>

    {* ── Profile editor ── *}
    <div class="panel" id="profile-editor" style="display:none;">
        <div class="panel-heading"><i class="icon-pencil"></i> <span id="profile-editor-title">{l s='New profile' mod='amazonmarketplacepro'}</span></div>
        <input type="hidden" id="profile-id" value="0" />

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Profile name' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" id="profile-name" class="form-control" placeholder="{l s='e.g. Skirts - Women' mod='amazonmarketplacepro'}" />
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Amazon product type' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <div class="input-group" style="max-width:460px;">
                    <input type="text" id="pt-search" class="form-control" placeholder="{l s='Search Amazon product types (e.g. shirt, ring, toy)' mod='amazonmarketplacepro'}" />
                    <span class="input-group-btn">
                        <button type="button" id="pt-search-btn" class="btn btn-default"><i class="icon-search"></i> {l s='Search' mod='amazonmarketplacepro'}</button>
                    </span>
                </div>
                <div style="margin-top:8px;">
                    <select id="pt-results" class="form-control" style="max-width:460px;" size="1">
                        <option value="">{l s='-- Search, then pick a product type --' mod='amazonmarketplacepro'}</option>
                    </select>
                </div>
                <div style="margin-top:8px;">
                    <button type="button" id="pt-load" class="btn btn-primary"><i class="icon-download"></i> {l s='Load required fields' mod='amazonmarketplacepro'}</button>
                    <button type="button" id="pt-refresh" class="btn btn-default"><i class="icon-refresh"></i> {l s='Refresh from Amazon' mod='amazonmarketplacepro'}</button>
                    <span id="pt-status" style="margin-left:10px;"></span>
                </div>
                <p class="help-block">{l s='Schemas are cached for 30 days per marketplace. Amazon decides required fields dynamically, so a few may only surface as errors on the first feed — add them from the optional list below when that happens.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>

        {* Attribute form, rendered from the schema *}
        <div id="profile-attributes-wrap" style="display:none;">
            <div class="panel-heading" style="margin-top:10px;"><i class="icon-asterisk"></i> {l s='Required attributes' mod='amazonmarketplacepro'}</div>
            <div id="attrs-required"></div>

            <div class="panel-heading" style="margin-top:10px;"><i class="icon-plus-square-o"></i> {l s='Optional attributes' mod='amazonmarketplacepro'}</div>
            <div class="form-group">
                <div class="col-lg-9 col-lg-offset-3">
                    <input type="text" id="attrs-filter" class="form-control" placeholder="{l s='Filter optional attributes...' mod='amazonmarketplacepro'}" style="max-width:360px;" />
                </div>
            </div>
            <div id="attrs-optional" style="max-height:360px; overflow-y:auto;"></div>
        </div>

        <div class="panel-heading" style="margin-top:10px;"><i class="icon-cogs"></i> {l s='Profile settings' mod='amazonmarketplacepro'}</div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Products have variations' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select id="profile-is-variation" class="form-control" style="max-width:200px;">
                    <option value="0">{l s='No (simple products)' mod='amazonmarketplacepro'}</option>
                    <option value="1">{l s='Yes (parent + child listings)' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='PrestaShop combinations become Amazon variants. The base product reference is the parent SKU; each combination reference is a child SKU and needs its own barcode.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group" id="profile-variation-attrs-group" style="display:none;">
            <label class="control-label col-lg-3">{l s='Variation attributes' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" id="profile-variation-attributes" class="form-control" style="max-width:360px;" placeholder="{l s='e.g. Size,Color' mod='amazonmarketplacepro'}" />
                <p class="help-block">
                    {l s='PrestaShop attribute groups that form the variation axis, comma-separated.' mod='amazonmarketplacepro'}
                    {if $attribute_groups}<br>{l s='Available in your shop:' mod='amazonmarketplacepro'}
                        {foreach from=$attribute_groups item=ag name=agl}<code>{$ag.name|escape:'htmlall':'UTF-8'}</code>{if !$smarty.foreach.agl.last}, {/if}{/foreach}
                    {/if}
                </p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Recommended browse nodes' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" id="profile-browse-nodes" class="form-control" style="max-width:360px;" placeholder="2494728031" />
                <p class="help-block">{l s='Amazon category IDs, comma- or semicolon-separated. Required for Canada, Europe and Japan. Node IDs differ per marketplace.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Handling time (days)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="number" min="0" id="profile-latency" class="form-control" style="max-width:120px;" />
                <p class="help-block">{l s='Overrides the global default for products in this profile. Leave empty to inherit.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Shipping template' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" id="profile-shipping-template" class="form-control" style="max-width:360px;" />
                <p class="help-block">{l s='Seller Central template name for this profile. Overrides the global template and the price/weight ranges.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='GTIN exemption' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <select id="profile-gtin-exemption" class="form-control" style="max-width:280px;">
                    <option value="0">{l s='No — send EAN/UPC as usual' mod='amazonmarketplacepro'}</option>
                    <option value="1">{l s='Yes — Amazon granted a barcode exemption' mod='amazonmarketplacepro'}</option>
                </select>
                <p class="help-block">{l s='Only enable when Amazon approved a GTIN exemption for this brand and product type (Seller Central > Catalog > View Selling Applications). The listing is then sent without a barcode.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='PrestaShop categories' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <input type="text" id="profile-cat-filter" class="form-control" placeholder="{l s='Filter categories...' mod='amazonmarketplacepro'}" style="max-width:300px; margin-bottom:6px;" />
                <div id="profile-categories" style="max-height:240px; overflow-y:auto; border:1px solid #ddd; padding:8px; border-radius:3px;">
                    {foreach from=$ps_categories item=cat}
                        <label class="profile-cat-row" style="display:block; font-weight:normal;">
                            <input type="checkbox" class="profile-cat" value="{$cat.id_category|escape:'htmlall':'UTF-8'}" />
                            {$cat.name|escape:'htmlall':'UTF-8'} <small class="text-muted">#{$cat.id_category|escape:'htmlall':'UTF-8'}</small>
                        </label>
                    {/foreach}
                </div>
                <p class="help-block">{l s='Ticking a category here detaches it from any other profile.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Extra attributes (raw JSON)' mod='amazonmarketplacepro'}</label>
            <div class="col-lg-6">
                <textarea id="profile-raw-json" class="form-control" rows="3" placeholder='&#123;"my_attribute": [&#123;"value": "x"}]}'></textarea>
                <p class="help-block">{l s='Escape hatch for anything the form above cannot express. Passed to Amazon unchanged.' mod='amazonmarketplacepro'}</p>
            </div>
        </div>

        <div class="panel-footer">
            <button type="button" id="profile-save" class="btn btn-primary"><i class="process-icon-save"></i> {l s='Save profile' mod='amazonmarketplacepro'}</button>
            <button type="button" id="profile-cancel" class="btn btn-default">{l s='Cancel' mod='amazonmarketplacepro'}</button>
            <span id="profile-save-result" style="margin-left:10px;"></span>
        </div>
    </div>
</div>

{* ═══════════════════════ (Remote Cart panel lives in the Pending tab) ═══════════════════════ *}

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
    </div>{* /section tab-content *}
</div>

{* ════════════════════ ORDERS ════════════════════ *}
<div class="tab-pane" id="grp-orders">

    <ul class="nav nav-tabs mkpro-section-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#tab-orders" data-toggle="tab"><i class="icon-shopping-cart"></i> {l s='Orders' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-pending" data-toggle="tab"><i class="icon-pause"></i> {l s='Pending Orders' mod='amazonmarketplacepro'}{if $pending_orders} <span class="badge">{$pending_orders|count}</span>{/if}</a></li>
        <li><a href="#tab-returns" data-toggle="tab"><i class="icon-undo"></i> {l s='Returns' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

{* ═══════════════════════ ORDERS TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-orders">

    <div class="panel">
        <div class="panel-heading"><i class="icon-cloud-download"></i> {l s='Import Amazon Orders' mod='amazonmarketplacepro'}</div>
        <p>{l s='Fetch orders from Amazon and stage them for review, with shipping costs, tax, the buyer address (via RDT) and the FBA or MFN channel.' mod='amazonmarketplacepro'}</p>
        <button type="button" id="import-amazon-orders" class="btn btn-primary">
            <i class="icon-download"></i> {l s='Fetch & Stage Amazon Orders' mod='amazonmarketplacepro'}
        </button>
        <button type="button" id="create-ps-orders" class="btn btn-success" style="margin-left:10px;">
            <i class="icon-check"></i> {l s='Create PrestaShop Orders' mod='amazonmarketplacepro'}
        </button>
        <p class="help-block" style="margin-top:8px;">
            {l s='Staged orders become PrestaShop orders with your own carriers, statuses and invoices.' mod='amazonmarketplacepro'}
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

    {* ── Remote Cart reservations ── *}
    <div class="panel">
        <div class="panel-heading"><i class="icon-lock"></i> {l s='Reserved Stock (Remote Cart)' mod='amazonmarketplacepro'}</div>
        <p>{l s='Units held for Amazon checkouts that are not payable yet. They have already been taken out of PrestaShop stock, so no other channel can sell them. Each hold is either handed over when the order becomes payable, or returned once it expires.' mod='amazonmarketplacepro'}</p>
        {if !$mkpro_remote_cart}
            <div class="alert alert-info">{l s='Remote Cart is off. Enable it in Settings > Order Import Settings to start reserving stock, and schedule the remote_cart cron (Automation tab).' mod='amazonmarketplacepro'}</div>
        {/if}
        <table class="table">
            <thead><tr>
                <th>{l s='Amazon Order' mod='amazonmarketplacepro'}</th>
                <th>{l s='SKU' mod='amazonmarketplacepro'}</th>
                <th>{l s='Product' mod='amazonmarketplacepro'}</th>
                <th>{l s='Qty held' mod='amazonmarketplacepro'}</th>
                <th>{l s='Amazon status' mod='amazonmarketplacepro'}</th>
                <th>{l s='Held since' mod='amazonmarketplacepro'}</th>
            </tr></thead>
            <tbody>
                {if $reservations}
                    {foreach from=$reservations item=res}
                    <tr>
                        <td>{$res.amazon_order_id|escape:'htmlall':'UTF-8'}</td>
                        <td><code>{$res.seller_sku|escape:'htmlall':'UTF-8'}</code></td>
                        <td>{$res.product_name|escape:'htmlall':'UTF-8'}</td>
                        <td><span class="badge badge-warning">{$res.quantity|escape:'htmlall':'UTF-8'}</span></td>
                        <td><span class="badge badge-default">{if $res.order_status}{$res.order_status|escape:'htmlall':'UTF-8'}{else}In Cart{/if}</span></td>
                        <td><small>{$res.date_add|escape:'htmlall':'UTF-8'}</small></td>
                    </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="6" class="text-center text-muted">{l s='No stock is currently reserved.' mod='amazonmarketplacepro'}</td></tr>
                {/if}
            </tbody>
        </table>
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
    </div>{* /section tab-content *}
</div>

{* ════════════════════ FULFILMENT ════════════════════ *}
<div class="tab-pane" id="grp-fulfilment">

    <ul class="nav nav-tabs mkpro-section-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#tab-fba" data-toggle="tab"><i class="icon-truck"></i> {l s='FBA' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-multimp" data-toggle="tab"><i class="icon-globe"></i> {l s='Multi-Account' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

{* ═══════════════════════ FBA TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-fba">

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
    </div>{* /section tab-content *}
</div>

{* ════════════════════ MONEY ════════════════════ *}
<div class="tab-pane" id="grp-money">

    <ul class="nav nav-tabs mkpro-section-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#tab-repricing" data-toggle="tab"><i class="icon-usd"></i> {l s='Repricing' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-fees" data-toggle="tab"><i class="icon-money"></i> {l s='Fees' mod='amazonmarketplacepro'}</a></li>
        <li><a href="#tab-promotions" data-toggle="tab"><i class="icon-tag"></i> {l s='Promotions' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

{* ═══════════════════════ REPRICING TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-repricing">

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
    </div>{* /section tab-content *}
</div>

{* ════════════════════ INSIGHTS ════════════════════ *}
<div class="tab-pane" id="grp-insights">

    <ul class="nav nav-tabs mkpro-section-tabs" style="margin-bottom:15px;">
        <li class="active"><a href="#tab-reports" data-toggle="tab"><i class="icon-bar-chart"></i> {l s='Reports' mod='amazonmarketplacepro'}</a></li>
    </ul>

    <div class="tab-content">

{* ═══════════════════════ REPORTS TAB ═══════════════════════ *}
<div class="tab-pane active" id="tab-reports">

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
    </div>{* /section tab-content *}
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
        var btnPoll = document.getElementById('feed-poll');
        var out = document.getElementById('feed-result');
        var tableWrap = document.getElementById('feed-table');
        if (!btnPoll) return;

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
        window.mkproRenderFeeds = renderFeeds;

        function show(cls, msg) {
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-'+cls+'">'+msg+'</div>';
        }

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
        // Each direction shows its comparison inside its own panel.
        var boxes = {
            ps: { sum: document.getElementById('amazon-products-summary-ps'), out: document.getElementById('amazon-products-result-ps') },
            amazon: { sum: document.getElementById('amazon-products-summary-amazon'), out: document.getElementById('amazon-products-result-amazon') }
        };
        if (!boxes.ps.out || !boxes.amazon.out) return;

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
            var sumBox = boxes[dir].sum, out = boxes[dir].out;
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

    /* ──────── SEND PENDING CHANGES ────────
       One button. The server decides between SKU-by-SKU calls and a feed
       by how many SKUs are pending, and says which it chose. */
    (function () {
        var btn = document.getElementById('send-pending');
        var out = document.getElementById('amazon-push-result');
        if (!btn || !out) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Sending pending changes to Amazon...</div>';

            ajaxPost('{$ajax_send_pending_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data) { out.innerHTML='<div class="alert alert-danger">Unexpected response</div>'; return; }
                var html = '';

                if (data.method === 'feed') {
                    if (data.error) {
                        html += '<div class="alert alert-danger">'+esc(data.error)
                              + (data.skipped && data.skipped.length ? ' &middot; '+esc(data.skipped.length)+' SKU(s) skipped' : '')+'</div>';
                    } else {
                        html += '<div class="alert alert-success">'+esc(data.pending)+' SKUs pending, so they went as one feed. Feed '+esc(data.feed_id)
                              + ' submitted with '+esc(data.messages)+' message(s). Amazon processes it in the background: use Check feed status below.'
                              + (data.skipped && data.skipped.length ? ' '+esc(data.skipped.length)+' SKU(s) skipped (no category mapping).' : '')
                              + (data.notice ? ' '+esc(data.notice) : '')+'</div>';
                        if (window.mkproRenderFeeds) window.mkproRenderFeeds(data.feeds);
                    }
                } else if (data.summary) {
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
                out.innerHTML = html || '<div class="alert alert-info">Nothing to send.</div>';
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

    /* ──────── LISTING PROFILES (Amazon product type schemas) ──────── */
    (function () {
        var editor = document.getElementById('profile-editor');
        if (!editor) return;

        var psFields = {$profile_ps_fields|@json_encode nofilter};
        var schemaAttributes = [];   // loaded schema
        var pendingValues = {};      // stored profile values while the schema loads

        function el(id) { return document.getElementById(id); }
        function status(msg, cls) {
            el('pt-status').innerHTML = msg ? '<span class="text-' + (cls || 'muted') + '">' + esc(msg) + '</span>' : '';
        }

        /* Build the source selector + value control for one attribute. */
        function attrRow(attr) {
            var name = attr.name;
            var enumKeys = [];
            for (var k in attr.enum) { if (attr.enum.hasOwnProperty(k)) enumKeys.push(k); }

            var h = '<div class="form-group amz-attr" data-name="' + esc(name) + '" data-label="'
                + esc((attr.title + ' ' + name).toLowerCase()) + '">'
                + '<label class="control-label col-lg-3">' + esc(attr.title)
                + (attr.required ? ' <span class="text-danger">*</span>' : '')
                + '<br><small class="text-muted"><code>' + esc(name) + '</code></small></label>'
                + '<div class="col-lg-6">'
                + '<div class="row"><div class="col-xs-4">'
                + '<select class="form-control input-sm attr-src">'
                + '<option value="">' + 'Not set' + '</option>'
                + (enumKeys.length ? '<option value="allowed">Amazon value</option>' : '')
                + '<option value="ps">PrestaShop field</option>'
                + '<option value="fixed">Fixed text</option>'
                + '</select></div>'
                + '<div class="col-xs-8">';

            // Amazon allowed values
            h += '<select class="form-control input-sm attr-allowed" style="display:none;">';
            h += '<option value="">-- pick a value --</option>';
            for (var i = 0; i < enumKeys.length; i++) {
                h += '<option value="' + esc(enumKeys[i]) + '">' + esc(attr.enum[enumKeys[i]]) + '</option>';
            }
            h += '</select>';

            // PrestaShop field
            h += '<select class="form-control input-sm attr-ps" style="display:none;">';
            h += '<option value="">-- pick a field --</option>';
            for (var f in psFields) {
                if (psFields.hasOwnProperty(f)) h += '<option value="' + esc(f) + '">' + esc(psFields[f]) + '</option>';
            }
            h += '<option value="__feature">Product feature (type the name)</option>';
            h += '<option value="__attribute">Combination attribute (type the group)</option>';
            h += '</select>';
            h += '<input type="text" class="form-control input-sm attr-ps-extra" style="display:none; margin-top:4px;" placeholder="Feature / attribute group name" />';

            // Fixed literal
            h += '<input type="text" class="form-control input-sm attr-fixed" style="display:none;" />';

            h += '</div></div>';
            if (attr.description) {
                h += '<p class="help-block" style="margin-bottom:0;"><small>' + esc(attr.description) + '</small></p>';
            }
            h += '</div></div>';

            return h;
        }

        function wireRow(row) {
            var src = row.querySelector('.attr-src');
            function sync() {
                row.querySelector('.attr-allowed').style.display = (src.value === 'allowed') ? '' : 'none';
                row.querySelector('.attr-ps').style.display = (src.value === 'ps') ? '' : 'none';
                row.querySelector('.attr-fixed').style.display = (src.value === 'fixed') ? '' : 'none';
                var psSel = row.querySelector('.attr-ps');
                var extra = row.querySelector('.attr-ps-extra');
                extra.style.display = (src.value === 'ps' && (psSel.value === '__feature' || psSel.value === '__attribute')) ? '' : 'none';
            }
            src.addEventListener('change', sync);
            row.querySelector('.attr-ps').addEventListener('change', sync);
            sync();
        }

        function renderSchema(attributes) {
            schemaAttributes = attributes || [];
            var req = '', opt = '';
            for (var i = 0; i < schemaAttributes.length; i++) {
                var a = schemaAttributes[i];
                if (a.required) req += attrRow(a); else opt += attrRow(a);
            }
            el('attrs-required').innerHTML = req || '<p class="help-block col-lg-offset-3">Amazon lists no strictly required extra attributes for this product type.</p>';
            el('attrs-optional').innerHTML = opt || '<p class="help-block col-lg-offset-3">No optional attributes.</p>';
            el('profile-attributes-wrap').style.display = 'block';

            var rows = editor.querySelectorAll('.amz-attr');
            for (var r = 0; r < rows.length; r++) wireRow(rows[r]);

            applyPendingValues();
        }

        /* Re-apply a saved profile's values once its schema is on screen. */
        function applyPendingValues() {
            for (var name in pendingValues) {
                if (!pendingValues.hasOwnProperty(name)) continue;
                var spec = pendingValues[name];
                var row = editor.querySelector('.amz-attr[data-name="' + name.replace(/"/g, '') + '"]');
                if (!row) continue;
                row.querySelector('.attr-src').value = spec.src || '';
                if (spec.src === 'allowed') {
                    row.querySelector('.attr-allowed').value = spec.value || '';
                } else if (spec.src === 'fixed') {
                    row.querySelector('.attr-fixed').value = spec.value || '';
                } else if (spec.src === 'ps') {
                    var v = spec.value || '';
                    var psSel = row.querySelector('.attr-ps');
                    if (v.indexOf('feature:') === 0) {
                        psSel.value = '__feature';
                        row.querySelector('.attr-ps-extra').value = v.substring(8);
                    } else if (v.indexOf('attribute:') === 0) {
                        psSel.value = '__attribute';
                        row.querySelector('.attr-ps-extra').value = v.substring(10);
                    } else {
                        psSel.value = v;
                    }
                }
                wireRow(row);
            }
            pendingValues = {};
        }

        function collectAttributes() {
            var out = {};
            var rows = editor.querySelectorAll('.amz-attr');
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var src = row.querySelector('.attr-src').value;
                if (!src) continue;
                var value = '';
                if (src === 'allowed') {
                    value = row.querySelector('.attr-allowed').value;
                } else if (src === 'fixed') {
                    value = row.querySelector('.attr-fixed').value;
                } else if (src === 'ps') {
                    value = row.querySelector('.attr-ps').value;
                    if (value === '__feature') value = 'feature:' + row.querySelector('.attr-ps-extra').value;
                    else if (value === '__attribute') value = 'attribute:' + row.querySelector('.attr-ps-extra').value;
                }
                if (!value) continue;
                out[row.getAttribute('data-name')] = { src: src, value: value };
            }
            return out;
        }

        /* ── product type search ── */
        el('pt-search-btn').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            status('Asking Amazon for matching product types...');
            ajaxPost('{$ajax_search_product_types_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) { status((data && data.error) ? data.error : 'Search failed', 'danger'); return; }
                var sel = el('pt-results');
                sel.innerHTML = '<option value="">-- ' + data.product_types.length + ' result(s) --</option>';
                for (var i = 0; i < data.product_types.length; i++) {
                    var pt = data.product_types[i];
                    sel.innerHTML += '<option value="' + esc(pt.name) + '">' + esc(pt.displayName) + ' (' + esc(pt.name) + ')</option>';
                }
                sel.size = Math.min(10, Math.max(2, data.product_types.length + 1));
                status(data.product_types.length + ' product type(s) found — pick one and load its fields.', 'success');
            }, 'keywords=' + encodeURIComponent(el('pt-search').value));
        });

        function loadSchema(refresh) {
            var pt = el('pt-results').value;
            if (!pt) { status('Pick a product type first.', 'danger'); return; }
            status('Downloading the attribute schema from Amazon...');
            ajaxPost('{$ajax_load_pt_schema_url|escape:'javascript':'UTF-8'}', function (data) {
                if (!data || !data.success) { status((data && data.error) ? data.error : 'Load failed', 'danger'); return; }
                renderSchema(data.attributes);
                status(esc(data.display_name) + ': ' + data.required_count + ' required, '
                    + (data.attributes.length - data.required_count) + ' optional attribute(s).', 'success');
            }, 'product_type=' + encodeURIComponent(pt) + (refresh ? '&refresh=1' : ''));
        }
        el('pt-load').addEventListener('click', function () { loadSchema(false); });
        el('pt-refresh').addEventListener('click', function () { loadSchema(true); });

        /* ── optional attribute filter ── */
        el('attrs-filter').addEventListener('input', function () {
            var needle = this.value.toLowerCase();
            var rows = el('attrs-optional').querySelectorAll('.amz-attr');
            for (var i = 0; i < rows.length; i++) {
                rows[i].style.display = (!needle || rows[i].getAttribute('data-label').indexOf(needle) !== -1) ? '' : 'none';
            }
        });
        el('profile-cat-filter').addEventListener('input', function () {
            var needle = this.value.toLowerCase();
            var rows = document.querySelectorAll('#profile-categories .profile-cat-row');
            for (var i = 0; i < rows.length; i++) {
                rows[i].style.display = (!needle || rows[i].textContent.toLowerCase().indexOf(needle) !== -1) ? '' : 'none';
            }
        });
        el('profile-is-variation').addEventListener('change', function () {
            el('profile-variation-attrs-group').style.display = (this.value === '1') ? '' : 'none';
        });

        /* ── open / reset the editor ── */
        function resetEditor() {
            el('profile-id').value = '0';
            el('profile-name').value = '';
            el('pt-search').value = '';
            el('pt-results').innerHTML = '<option value="">-- Search, then pick a product type --</option>';
            el('pt-results').size = 1;
            el('profile-browse-nodes').value = '';
            el('profile-latency').value = '';
            el('profile-shipping-template').value = '';
            el('profile-gtin-exemption').value = '0';
            el('profile-is-variation').value = '0';
            el('profile-variation-attributes').value = '';
            el('profile-variation-attrs-group').style.display = 'none';
            el('profile-raw-json').value = '';
            el('profile-attributes-wrap').style.display = 'none';
            el('attrs-required').innerHTML = '';
            el('attrs-optional').innerHTML = '';
            el('profile-save-result').innerHTML = '';
            status('');
            var boxes = document.querySelectorAll('.profile-cat');
            for (var i = 0; i < boxes.length; i++) boxes[i].checked = false;
            pendingValues = {};
        }

        el('profile-new').addEventListener('click', function () {
            resetEditor();
            el('profile-editor-title').textContent = 'New profile';
            editor.style.display = 'block';
            editor.scrollIntoView({ behavior: 'smooth' });
        });
        el('profile-cancel').addEventListener('click', function () {
            editor.style.display = 'none';
        });

        /* ── edit an existing profile ── */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.profile-edit');
            if (!btn) return;
            ajaxPost('{$ajax_get_profile_url|escape:'javascript':'UTF-8'}', function (data) {
                if (!data || !data.success) { alert((data && data.error) ? data.error : 'Could not load the profile.'); return; }
                var p = data.profile;
                resetEditor();
                el('profile-editor-title').textContent = 'Edit profile: ' + p.name;
                el('profile-id').value = p.id_amazonmarketplacepro_profile;
                el('profile-name').value = p.name;
                el('pt-results').innerHTML = '<option value="' + esc(p.product_type) + '" selected>' + esc(p.product_type) + '</option>';
                el('profile-browse-nodes').value = p.browse_nodes || '';
                el('profile-latency').value = (parseInt(p.latency, 10) >= 0) ? p.latency : '';
                el('profile-shipping-template').value = p.shipping_template || '';
                el('profile-gtin-exemption').value = parseInt(p.gtin_exemption, 10) ? '1' : '0';
                el('profile-is-variation').value = parseInt(p.is_variation, 10) ? '1' : '0';
                el('profile-variation-attributes').value = p.variation_attributes || '';
                el('profile-variation-attrs-group').style.display = parseInt(p.is_variation, 10) ? '' : 'none';
                el('profile-raw-json').value = p.raw_attributes_json || '';

                var cats = p.categories || [];
                var boxes = document.querySelectorAll('.profile-cat');
                for (var i = 0; i < boxes.length; i++) {
                    boxes[i].checked = (cats.indexOf(parseInt(boxes[i].value, 10)) !== -1);
                }

                pendingValues = p.attributes || {};
                editor.style.display = 'block';
                editor.scrollIntoView({ behavior: 'smooth' });
                // Pull the schema so the stored values have rows to land in.
                loadSchema(false);
            }, 'id_profile=' + encodeURIComponent(btn.getAttribute('data-id')));
        });

        /* ── delete ── */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.profile-delete');
            if (!btn) return;
            if (!confirm('Delete this profile? Its categories fall back to the Category Mapping.')) return;
            ajaxPost('{$ajax_delete_profile_url|escape:'javascript':'UTF-8'}', function (data) {
                if (data && data.success) {
                    var row = btn.closest('tr');
                    if (row) row.remove();
                }
            }, 'id_profile=' + encodeURIComponent(btn.getAttribute('data-id')));
        });

        /* ── save ── */
        el('profile-save').addEventListener('click', function () {
            var btn = this;
            var out = el('profile-save-result');
            var cats = [];
            var boxes = document.querySelectorAll('.profile-cat');
            for (var i = 0; i < boxes.length; i++) { if (boxes[i].checked) cats.push(boxes[i].value); }

            btn.disabled = true;
            out.innerHTML = 'Saving...';
            var params = 'id_profile=' + encodeURIComponent(el('profile-id').value)
                + '&name=' + encodeURIComponent(el('profile-name').value)
                + '&product_type=' + encodeURIComponent(el('pt-results').value)
                + '&browse_nodes=' + encodeURIComponent(el('profile-browse-nodes').value)
                + '&is_variation=' + encodeURIComponent(el('profile-is-variation').value)
                + '&variation_attributes=' + encodeURIComponent(el('profile-variation-attributes').value)
                + '&latency=' + encodeURIComponent(el('profile-latency').value)
                + '&shipping_template=' + encodeURIComponent(el('profile-shipping-template').value)
                + '&gtin_exemption=' + encodeURIComponent(el('profile-gtin-exemption').value)
                + '&raw_attributes_json=' + encodeURIComponent(el('profile-raw-json').value)
                + '&attributes=' + encodeURIComponent(JSON.stringify(collectAttributes()))
                + '&categories=' + encodeURIComponent(JSON.stringify(cats));

            ajaxPost('{$ajax_save_profile_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) {
                    out.innerHTML = '<span class="text-danger">' + esc((data && data.error) ? data.error : 'Save failed') + '</span>';
                    return;
                }
                out.innerHTML = '<span class="text-success">Profile saved. ' + esc(data.queued) + ' product(s) queued for the next sync. Reload the page to refresh the list.</span>';
                el('profile-id').value = data.id_profile;
            }, params);
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

    /* ──────── UPDATE EXISTING PRODUCTS FROM AMAZON ──────── */
    (function () {
        var btn = document.getElementById('amz-update-run');
        var out = document.getElementById('amz-update-result');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var ops = [];
            var boxes = document.querySelectorAll('.amz-update-op');
            for (var i = 0; i < boxes.length; i++) { if (boxes[i].checked) ops.push(boxes[i].value); }
            if (!ops.length) {
                out.style.display = 'block';
                out.innerHTML = '<div class="alert alert-danger">Tick at least one thing to update.</div>';
                return;
            }
            if (!confirm('This overwrites PrestaShop data for products that exist on both sides. Continue?')) return;

            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Updating from Amazon...</div>';
            ajaxPost('{$ajax_update_from_amazon_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) {
                    out.innerHTML = '<div class="alert alert-danger">' + esc((data && data.error) ? data.error : 'Update failed') + '</div>';
                    return;
                }
                var s = data.summary;
                var h = '<div class="alert alert-success">'
                    + esc(s.candidates) + ' matched product(s) &middot; '
                    + esc(s.content) + ' content &middot; ' + esc(s.price) + ' price &middot; '
                    + esc(s.quantity) + ' stock &middot; ' + esc(s.features) + ' feature(s) &middot; '
                    + esc(s.hidden) + ' deactivated &middot; ' + esc(s.failed) + ' failed</div>';
                if (data.notices) {
                    for (var n = 0; n < data.notices.length; n++) {
                        h += '<div class="alert alert-warning">' + esc(data.notices[n]) + '</div>';
                    }
                }
                out.innerHTML = h;
            }, 'operations=' + encodeURIComponent(JSON.stringify(ops)));
        });
    })();

    /* ──────── INBOUND BUYER MESSAGES ──────── */
    (function () {
        var btn = document.getElementById('inbox-fetch');
        var out = document.getElementById('inbox-result');
        if (!btn) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Connecting to the mailbox...</div>';
            ajaxPost('{$ajax_fetch_buyer_messages_url|escape:'javascript':'UTF-8'}', function (data) {
                btn.disabled = false;
                if (!data || !data.success) {
                    out.innerHTML = '<div class="alert alert-danger">' + esc((data && data.error) ? data.error : 'Fetch failed') + '</div>';
                    return;
                }
                var s = data.summary;
                var h = '<div class="alert alert-success">' + esc(s.scanned) + ' unread message(s) scanned &middot; '
                    + esc(s.matched) + ' quoting an Amazon order &middot; ' + esc(s.filed)
                    + ' filed into Customer Service &middot; ' + esc(s.skipped) + ' left alone</div>';
                if (data.notices) {
                    for (var n = 0; n < data.notices.length; n++) {
                        h += '<div class="alert alert-warning">' + esc(data.notices[n]) + '</div>';
                    }
                }
                out.innerHTML = h;
            });
        });
    })();

    /* ──────── TOOLS: catalogue audit, CSV, deletions, feed payloads ──────── */
    (function () {
        var auditBtn = document.getElementById('audit-run');
        if (!auditBtn) return;

        /* Catalogue check */
        auditBtn.addEventListener('click', function () {
            var out = document.getElementById('audit-result');
            auditBtn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Checking...</div>';
            ajaxPost('{$ajax_audit_catalogue_url|escape:'javascript':'UTF-8'}', function (data) {
                auditBtn.disabled = false;
                if (!data || !data.success) { out.innerHTML = '<div class="alert alert-danger">Check failed</div>'; return; }
                var a = data.audit;
                var problems = a.no_reference + a.duplicate_references + a.combinations_no_reference;
                var h = '<table class="table" style="max-width:560px;"><tbody>'
                    + '<tr><td>Active products with no reference</td><td>' + badge(a.no_reference) + '</td></tr>'
                    + '<tr><td>Duplicated references</td><td>' + badge(a.duplicate_references) + '</td></tr>'
                    + '<tr><td>Combinations with no reference</td><td>' + badge(a.combinations_no_reference) + '</td></tr>'
                    + '<tr><td>Active products with no EAN and no UPC</td><td>' + badge(a.no_barcode) + '</td></tr>'
                    + '</tbody></table>';
                h += problems === 0
                    ? '<div class="alert alert-success">Your catalogue is ready to sync.</div>'
                    : '<div class="alert alert-warning">Fix these with the CSV editor below before publishing — Amazon cannot match products without a unique reference.</div>';
                out.innerHTML = h;
            });
        });

        function badge(n) {
            n = parseInt(n, 10) || 0;
            return '<span class="badge badge-' + (n > 0 ? 'warning' : 'success') + '">' + n + '</span>';
        }

        /* CSV import (multipart, so not via ajaxPost) */
        var importBtn = document.getElementById('reference-import');
        importBtn.addEventListener('click', function () {
            var input = document.getElementById('reference-file');
            var out = document.getElementById('reference-result');
            if (!input.files || !input.files.length) {
                out.style.display = 'block';
                out.innerHTML = '<div class="alert alert-danger">Choose a CSV file first.</div>';
                return;
            }
            if (!confirm('This rewrites references and barcodes in your PrestaShop catalogue. Continue?')) return;

            importBtn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Importing...</div>';

            var fd = new FormData();
            fd.append('reference_file', input.files[0]);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '{$ajax_import_references_url|escape:'javascript':'UTF-8'}', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                importBtn.disabled = false;
                var data = null;
                try { data = JSON.parse(xhr.responseText); } catch (e) { data = null; }
                if (!data || !data.success) {
                    out.innerHTML = '<div class="alert alert-danger">' + esc((data && data.error) ? data.error : 'Import failed') + '</div>';
                    return;
                }
                var s = data.summary;
                var h = '<div class="alert alert-success">' + esc(s.updated) + ' row(s) updated &middot; '
                    + esc(s.skipped) + ' skipped</div>';
                if (s.errors && s.errors.length) {
                    for (var i = 0; i < s.errors.length && i < 25; i++) {
                        h += '<div class="alert alert-warning">' + esc(s.errors[i]) + '</div>';
                    }
                    if (s.errors.length > 25) {
                        h += '<div class="alert alert-warning">...and ' + esc(s.errors.length - 25) + ' more.</div>';
                    }
                }
                out.innerHTML = h;
            };
            xhr.send(fd);
        });

        /* Deletion pipeline: list, then send */
        var listBtn = document.getElementById('deletions-list');
        var sendBtn = document.getElementById('deletions-send');
        var table = document.getElementById('deletions-table');
        var out = document.getElementById('deletions-result');

        listBtn.addEventListener('click', function () {
            listBtn.disabled = true;
            out.style.display = 'block';
            out.innerHTML = '<div class="alert alert-info">Looking for listings whose product is gone or excluded...</div>';
            ajaxPost('{$ajax_list_deletions_url|escape:'javascript':'UTF-8'}', function (data) {
                listBtn.disabled = false;
                if (!data || !data.success) { out.innerHTML = '<div class="alert alert-danger">Lookup failed</div>'; return; }
                var rows = data.candidates || [];
                var tbody = table.querySelector('tbody');
                var h = '';
                for (var i = 0; i < rows.length; i++) {
                    var c = rows[i];
                    h += '<tr><td><input type="checkbox" class="deletion-pick" value="' + esc(c.seller_sku) + '" /></td>'
                        + '<td><code>' + esc(c.seller_sku) + '</code></td>'
                        + '<td>' + esc(c.amazon_asin || '') + '</td>'
                        + '<td>' + esc(c.ps_name || '') + '</td>'
                        + '<td>' + esc(c.reason) + '</td></tr>';
                }
                tbody.innerHTML = h;
                table.style.display = rows.length ? '' : 'none';
                sendBtn.disabled = rows.length === 0;
                out.innerHTML = '<div class="alert alert-' + (rows.length ? 'warning' : 'success') + '">'
                    + esc(rows.length) + ' listing(s) would be deleted. Tick the ones you really want gone.</div>';
            });
        });

        var allBox = document.getElementById('deletions-all');
        allBox.addEventListener('change', function () {
            var boxes = table.querySelectorAll('.deletion-pick');
            for (var i = 0; i < boxes.length; i++) boxes[i].checked = allBox.checked;
        });

        sendBtn.addEventListener('click', function () {
            var picked = [];
            var boxes = table.querySelectorAll('.deletion-pick');
            for (var i = 0; i < boxes.length; i++) { if (boxes[i].checked) picked.push(boxes[i].value); }
            if (!picked.length) {
                out.innerHTML = '<div class="alert alert-danger">Nothing selected.</div>';
                return;
            }
            if (!confirm('Delete ' + picked.length + ' listing(s) from Amazon? The offers and their history go with them.')) return;

            sendBtn.disabled = true;
            out.innerHTML = '<div class="alert alert-info">Deleting from Amazon...</div>';
            ajaxPost('{$ajax_delete_listings_url|escape:'javascript':'UTF-8'}', function (data) {
                sendBtn.disabled = false;
                if (!data || !data.success) { out.innerHTML = '<div class="alert alert-danger">' + esc((data && data.error) ? data.error : 'Deletion failed') + '</div>'; return; }
                var s = data.summary;
                var h = '<div class="alert alert-' + (s.failed ? 'warning' : 'success') + '">'
                    + esc(s.requested) + ' requested &middot; ' + esc(s.deleted) + ' deleted &middot; ' + esc(s.failed) + ' failed</div>';
                if (s.results && s.results.length) {
                    h += '<table class="table"><thead><tr><th>SKU</th><th>Status</th><th>Issues</th></tr></thead><tbody>';
                    for (var i = 0; i < s.results.length; i++) {
                        h += '<tr><td>' + esc(s.results[i].sku) + '</td><td>' + statusBadge(s.results[i].status)
                            + '</td><td>' + esc(s.results[i].issues) + '</td></tr>';
                    }
                    h += '</tbody></table>';
                }
                out.innerHTML = h;
            }, 'skus=' + encodeURIComponent(JSON.stringify(picked)));
        });

        /* Feed payload download */
        var dl = document.getElementById('feed-payload-download');
        dl.addEventListener('click', function (e) {
            e.preventDefault();
            var id = document.getElementById('feed-payload-id').value.replace(/^\s+|\s+$/g, '');
            if (!id) { alert('Enter a feed id — you will find it on the Products tab after submitting a bulk feed.'); return; }
            window.location.href = '{$download_feed_url|escape:'javascript':'UTF-8'}&feed_id=' + encodeURIComponent(id);
        });
    })();

    /* ──────── Remember the active Settings sub-tab ────────
       The URL hash is already taken by the main tabs, and saving reloads
       the page - without this a merchant editing Orders settings would be
       thrown back to Connection every time they pressed Save. ──────── */
    (function () {
        var KEY = 'mkproSettingsTab';
        var links = document.querySelectorAll('#mkpro-settings-tabs a[data-toggle="tab"]');
        if (!links.length) return;

        try {
            var saved = window.localStorage.getItem(KEY);
            if (saved) {
                var restore = document.querySelector('#mkpro-settings-tabs a[href="' + saved + '"]');
                if (restore) restore.click();
            }
        } catch (e) { /* private mode, or storage disabled - just start on the first tab */ }

        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function () {
                try { window.localStorage.setItem(KEY, this.getAttribute('href')); } catch (e) {}
            });
        }
    })();

    /* ──────── Remember active tab ────────
       The section is kept in the URL so a reload comes back to it.

       It must not be written with window.location.hash. Each pane's id
       is the same string the hash carries, so assigning it asks the
       browser to bring that element into view and the page jumps down a
       little on every click. history.replaceState records the same URL
       without navigating to it, and leaves the Back button pointing at
       the page you arrived from rather than at the tab you just left. */
    (function () {
        var hash = window.location.hash;
        if (hash) {
            var restoreTab = function () {
                var tab = document.querySelector('#mkpro-tabs a[href="'+hash+'"]');
                if (!tab) {
                    return;
                }
                tab.click();
                /* Deferred by a further task. The browser jump to the id and
                   the back office own load handlers both settle around this
                   point, and a reset issued inline is undone by them. */
                window.setTimeout(function () { window.scrollTo(0, 0); }, 0);
            };
            /* Deferred until load, for two reasons that both have to hold.
               The click depends on the tab plugin, which is not necessarily
               parsed when this inline script runs - on PrestaShop 9 it was
               not, so the anchor fell through to its default behaviour and
               the browser scrolled to the pane without opening it. And the
               browser's own jump to the matching id happens somewhere in
               here too, so resetting the scroll any earlier is undone. */
            if (document.readyState === 'complete') {
                restoreTab();
            } else {
                window.addEventListener('load', restoreTab);
            }
        }
        var tabs = document.querySelectorAll('#mkpro-tabs a[data-toggle="tab"]');
        for (var i=0; i<tabs.length; i++) {
            tabs[i].addEventListener('click', function (e) {
                var target = this.getAttribute('href');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', target);
                } else {
                    window.location.hash = target;
                }
            });
        }

        /* A form submit has to come back to the section it was sent from.
           The section lives in the fragment, and a fragment is never sent
           to the server, so a POST that reloads the page lands on the
           first section and whatever message the handler produced sits in
           a pane the merchant is no longer looking at - "I clicked it and
           nothing happened". Carrying the fragment on the form action
           keeps it through the round trip; browsers apply it to the
           response, redirects included. */
        var forms = document.querySelectorAll('#mkpro-tab-content form');
        for (var f=0; f<forms.length; f++) {
            forms[f].addEventListener('submit', function () {
                var hash = window.location.hash;
                if (!hash) {
                    return;
                }
                var action = this.getAttribute('action') || (window.location.pathname + window.location.search);
                if (action.indexOf('#') === -1) {
                    this.setAttribute('action', action + hash);
                }
            });
        }
    })();
})();
</script>
