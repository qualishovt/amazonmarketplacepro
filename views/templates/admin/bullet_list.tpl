{**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *}
{*
 * Summary of a product imported from Amazon: the listing's bullet points.
 *}
<ul>{foreach from=$mkpro_bullets item=mkpro_bullet}<li>{$mkpro_bullet|escape:'html':'UTF-8'}</li>{/foreach}</ul>
