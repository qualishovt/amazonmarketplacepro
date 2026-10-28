{**
 * Amazon Marketplace Pro
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 *}
{*
 * Checkboxes and radio buttons as PrestaShop 1.7+ draws them (.md-checkbox,
 * .md-radio), applied to the plain inputs inside a .mkpro-ps17 container, so
 * no markup or script changes. PrestaShop 1.6 keeps its own controls.
 *}
<style>
    .mkpro-ps17 input[type=checkbox],
    .mkpro-ps17 input[type=radio] { -webkit-appearance: none; -moz-appearance: none; appearance: none;
        position: relative; display: inline-block; width: 20px; height: 20px; margin: 0 6px 0 0;
        vertical-align: middle; background: #fff; border: 2px solid #b3c7cd;
        cursor: pointer; transition: background .3s, border-color .3s; box-shadow: none; flex-shrink: 0; }
    .mkpro-ps17 input[type=checkbox] { border-radius: 2px; }
    .mkpro-ps17 input[type=radio] { border-radius: 50%; }
    .mkpro-ps17 input[type=checkbox]:checked { background: #25b9d7; border-color: #25b9d7; }
    .mkpro-ps17 input[type=checkbox]:checked::after { content: ""; position: absolute; top: 2.5px; left: 1px;
        width: 14px; height: 7px; border: 2px solid #fff; border-top-style: none; border-right-style: none;
        transform: rotate(-45deg); }
    .mkpro-ps17 input[type=radio]:checked { border-color: #25b9d7; }
    .mkpro-ps17 input[type=radio]:checked::after { content: ""; position: absolute; top: 3px; left: 3px;
        width: 10px; height: 10px; border-radius: 50%; background: #25b9d7; }
    .mkpro-ps17 input[type=checkbox]:focus,
    .mkpro-ps17 input[type=radio]:focus { outline: none; box-shadow: 0 0 0 3px rgba(37, 185, 215, .25); }
    .mkpro-ps17 input[type=checkbox]:disabled,
    .mkpro-ps17 input[type=radio]:disabled { opacity: .5; cursor: not-allowed; }
    .mkpro-ps17 label > input[type=checkbox],
    .mkpro-ps17 label > input[type=radio] { margin-top: -2px; }
    .mkpro-ps17 .form-inline > label { margin-right: 12px; font-weight: normal; }
    /* Yes / No switches in tables */
    button.mp-toggle { border: 0; cursor: pointer; }
    button.mp-toggle:disabled { cursor: not-allowed; opacity: .6; }
</style>
