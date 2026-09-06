# Translations

The module ships in English, French, Spanish, German, Italian and Polish.

Translations live in `translations/<iso>.php` in the module root. Those files
are **generated** — the English source of every string, the five translations,
and the scripts that turn one into the other all live here.

## Why the legacy format

PrestaShop 1.7 introduced XLIFF catalogues, but it never dropped the older
`translations/<iso>.php` files: `Translate::getModuleTranslation()` still reads
them in PrestaShop 9, with the same key format it used in 1.6. Since this
module supports 1.6 through 9, one legacy file per language covers every
version, and an XLIFF catalogue would cover only the newer half.

The key is

```
strtolower('<{amazonmarketplacepro}prestashop>' . <source>) . '_' . md5(<english>)
```

where `<source>` is the template's basename (`configure`, `product_tab`) for
`{l s='...'}` strings and the module name for `$this->l('...')` strings, and
`<english>` has had its apostrophes backslash-escaped first, exactly as
PrestaShop does before hashing.

## Changing an English string

**The key is an md5 of the English text, so editing an English string in a
template silently orphans its translations** — PrestaShop finds no key and
quietly falls back to English. Nothing warns you in the admin.

So after touching any `{l s='...'}` or `$this->l('...')` text:

1. Update the matching key in `data/chunkNN.php` (and its five translations).
2. Rebuild and verify.

`build.php` refuses to write anything while a string is untranslated, so an
orphan cannot slip through unnoticed. It also lists translations no template
uses any more, which is what a renamed string looks like from the other side.

## Commands

```bash
php tools/i18n/extract.php                 # what is translatable, and how much
php tools/i18n/build.php                   # data/ -> translations/*.php
php tools/i18n/verify.php fr <shop root>   # ask PrestaShop to translate it all
```

`verify.php` does not re-implement the key format: it boots a real shop and
calls `Translate::getModuleTranslation()` the way `smartyTranslate` and
`Module::l()` do, then checks the answer is the value `data/` intended. Run it
once per language, and against both a 1.6 and a 9 shop:

```bash
for L in fr es de it pl; do php tools/i18n/verify.php $L C:/xampp/7.0.33/htdocs/p16124; done
```

One language per process is deliberate — PrestaShop 1.6 caches a module's
translation file once per request, keyed by module name without the language,
so a loop inside one process would test the first language five times over.
(That caching is harmless in production: a request only renders one language.)

## Conventions in the translations

- Amazon's own vocabulary stays in English in every locale — SKU, ASIN, FBA,
  MFN, AFN, Buy Box, Prime, Amazon Business, Seller Central, GTIN, GPSR, the
  LWA field names, browse node, and the literal Seller Central menu paths.
  These appear untranslated in every locale of Seller Central, so translating
  them would send the merchant looking for a screen that does not exist.
- PrestaShop's own vocabulary follows PrestaShop's translations, not a literal
  rendering: *reference* is `Artikelnummer` in German, *credit slip* is
  `avoir` in French, and so on.
- The formal register throughout (`vous`, `Sie`, `usted` implied).
- Three strings are embedded in JavaScript in `configure.tpl` and are tagged
  `js=1` there, so PrestaShop applies `addslashes` and a French apostrophe is
  safe. Any new JS-embedded string must carry `js=1`, and its translations
  must avoid a literal `"` — the value lands inside a double-quoted HTML
  attribute where `htmlspecialchars` is deliberately not applied.
