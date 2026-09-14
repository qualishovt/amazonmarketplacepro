<?php

/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/*
 * Amazon Repricing Engine.
 *
 * Handles:
 * - Fetching competitive pricing data (Buy Box, lowest offers)
 * - Managing pricing rules (match lowest, beat by %, fixed margin, etc.)
 * - Automated repricing based on rules with min/max price safeguards
 * - Buy Box win/loss tracking
 *
 * Uses SP-API Product Pricing API v0.
 *
 * Multistore: fetching, rule application and pushes work on the current
 * shop's listings and write that shop's prices. Pricing rules are shared by
 * every shop (id_shop 0) and a shop can add rules of its own.
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/AmazonI18n.php';
require_once dirname(__FILE__) . '/AmzproShop.php';

class AmazonRepricingEngine
{
    /** Rule types */
    const RULE_MATCH_LOWEST = 'match_lowest';
    const RULE_BEAT_LOWEST = 'beat_lowest';
    const RULE_MATCH_BUYBOX = 'match_buybox';
    const RULE_BEAT_BUYBOX = 'beat_buybox';
    const RULE_FIXED_MARGIN = 'fixed_margin';

    /** @var AmazonSpApiClient */
    private $client;
    private $marketplaceId;
    private $sellerId;
    private $lastError;
    private $notices = [];

    public function __construct(AmazonSpApiClient $client, $marketplaceId, $sellerId = '')
    {
        $this->client = $client;
        $this->marketplaceId = $marketplaceId;
        $this->sellerId = trim((string) $sellerId);
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * Fetch competitive pricing data for all synced products.
     *
     * Uses the Product Pricing API to get Buy Box prices and lowest offers.
     * Processes in batches of 20 ASINs (API limit).
     *
     * @return array Summary
     */
    public function fetchCompetitivePricing()
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'checked' => 0,
            'updated' => 0,
            'buybox_wins' => 0,
            'buybox_losses' => 0,
            'errors' => 0,
        ];

        // Get the shop's products with ASINs
        $products = Db::getInstance()->executeS(
            'SELECT `seller_sku`, `amazon_asin`, `ps_price`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
             WHERE `amazon_asin` <> \'\'
               AND `amazon_exists` = 1
               AND `id_shop` = ' . (int) AmzproShop::actingId() . '
             ORDER BY `seller_sku` ASC
             LIMIT 200'
        );

        if (!is_array($products) || empty($products)) {
            $this->notices[] = AmazonI18n::get()->l('No products with ASINs found. Sync products first.', 'amazonrepricingengine');

            return $summary;
        }

        // Process in batches of 20 (API limit)
        $batches = array_chunk($products, 20);
        $now = date('Y-m-d H:i:s');

        foreach ($batches as $batch) {
            $asins = [];
            $skuByAsin = [];
            $priceByAsin = [];

            foreach ($batch as $p) {
                $asin = $p['amazon_asin'];
                $asins[] = $asin;
                $skuByAsin[$asin] = $p['seller_sku'];
                $priceByAsin[$asin] = (float) $p['ps_price'];
            }

            $resp = $this->client->request(
                'GET',
                '/products/pricing/v0/competitivePrice',
                [
                    'MarketplaceId' => $this->marketplaceId,
                    'Asins' => implode(',', $asins),
                    'ItemType' => 'Asin',
                ]
            );

            if ($resp === false || $resp['status'] >= 400) {
                $summary['errors'] += count($batch);
                continue;
            }

            $payload = [];
            if (is_array($resp['body']) && isset($resp['body']['payload'])) {
                $payload = $resp['body']['payload'];
            }

            foreach ($payload as $item) {
                $asin = isset($item['ASIN']) ? $item['ASIN'] : '';
                if ($asin === '' || !isset($skuByAsin[$asin])) {
                    continue;
                }

                $sku = $skuByAsin[$asin];
                $ourPrice = $priceByAsin[$asin];
                ++$summary['checked'];

                $buyboxPrice = 0;
                $buyboxShipping = 0;
                $buyboxLanded = 0;
                $buyboxSeller = '';
                $isBuyboxWinner = 0;
                $lowestPrice = 0;
                $lowestShipping = 0;
                $lowestLanded = 0;
                $numberOfOffers = 0;

                // Extract competitive prices
                if (isset($item['Product']['CompetitivePricing']['CompetitivePrices'])) {
                    foreach ($item['Product']['CompetitivePricing']['CompetitivePrices'] as $cp) {
                        $type = isset($cp['CompetitivePriceId']) ? $cp['CompetitivePriceId'] : '';
                        $lpAmount = isset($cp['Price']['ListingPrice']['Amount'])
                            ? (float) $cp['Price']['ListingPrice']['Amount'] : 0;
                        $shipAmount = isset($cp['Price']['Shipping']['Amount'])
                            ? (float) $cp['Price']['Shipping']['Amount'] : 0;
                        $landedAmount = isset($cp['Price']['LandedPrice']['Amount'])
                            ? (float) $cp['Price']['LandedPrice']['Amount'] : 0;

                        if ($type === '1') {
                            // Buy Box price
                            $buyboxPrice = $lpAmount;
                            $buyboxShipping = $shipAmount;
                            $buyboxLanded = $landedAmount > 0 ? $landedAmount : ($lpAmount + $shipAmount);
                            $belongsToMe = isset($cp['belongsToRequester']) && $cp['belongsToRequester'];
                            $isBuyboxWinner = $belongsToMe ? 1 : 0;
                        } elseif ($type === '2') {
                            // Lowest price
                            $lowestPrice = $lpAmount;
                            $lowestShipping = $shipAmount;
                            $lowestLanded = $landedAmount > 0 ? $landedAmount : ($lpAmount + $shipAmount);
                        }
                    }
                }

                // Number of offers
                if (isset($item['Product']['CompetitivePricing']['NumberOfOfferListings'])) {
                    foreach ($item['Product']['CompetitivePricing']['NumberOfOfferListings'] as $nol) {
                        if (isset($nol['Count'])) {
                            $numberOfOffers += (int) $nol['Count'];
                        }
                    }
                }

                if ($isBuyboxWinner) {
                    ++$summary['buybox_wins'];
                } else {
                    ++$summary['buybox_losses'];
                }

                // Upsert competitive price data
                $this->upsertCompetitivePrice(
                    $sku, $asin, $buyboxPrice, $buyboxShipping, $buyboxLanded,
                    $buyboxSeller, $isBuyboxWinner, $lowestPrice, $lowestShipping,
                    $lowestLanded, $numberOfOffers, $ourPrice, $now
                );
                ++$summary['updated'];
            }
        }

        return $summary;
    }

    /**
     * Apply pricing rules to generate suggested prices.
     *
     * Reads active rules, matches them to products (by category or all),
     * calculates suggested prices respecting min/max safeguards.
     *
     * @return array Summary
     */
    public function applyPricingRules()
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'rules_applied' => 0,
            'prices_suggested' => 0,
            'prices_pushed' => 0,
            'prices_capped' => 0,
        ];

        $idShop = (int) AmzproShop::actingId();

        // Get active rules: the shared ones, then the shop's own (which run
        // last, so they have the final word on a product both cover).
        $rules = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `active` = 1
               AND (`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\' OR `marketplace_id` = \'\')
               AND ' . AmzproShop::sqlShared('', $idShop) . '
             ORDER BY `id_shop` ASC, `id_amazonmarketplacepro_pricing_rule` ASC'
        );

        if (!is_array($rules) || empty($rules)) {
            $this->notices[] = AmazonI18n::get()->l('No active pricing rules found.', 'amazonrepricingengine');

            return $summary;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($rules as $rule) {
            ++$summary['rules_applied'];

            // Get the shop's products matching this rule
            $where = '`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\' AND `id_shop` = ' . $idShop;
            if ((int) $rule['id_category'] > 0) {
                $where .= ' AND `seller_sku` IN (
                    SELECT `seller_sku` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_product`
                    WHERE `ps_id_category_default` = ' . (int) $rule['id_category'] . '
                      AND `id_shop` = ' . $idShop . ')';
            }

            $prices = Db::getInstance()->executeS(
                'SELECT `seller_sku`, `asin`, `buybox_price`, `buybox_landed`,
                        `lowest_price`, `lowest_landed`, `our_price`, `is_buybox_winner`
                 FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price`
                 WHERE ' . $where . '
                 ORDER BY `seller_sku` ASC'
            );

            if (!is_array($prices)) {
                continue;
            }

            foreach ($prices as $cp) {
                $suggested = $this->calculateSuggestedPrice($cp, $rule);
                if ($suggested === false) {
                    continue;
                }

                // Apply min/max safeguards
                $minPrice = (float) $rule['min_price'];
                $maxPrice = (float) $rule['max_price'];

                if ($minPrice > 0 && $suggested < $minPrice) {
                    $suggested = $minPrice;
                    ++$summary['prices_capped'];
                }
                if ($maxPrice > 0 && $suggested > $maxPrice) {
                    $suggested = $maxPrice;
                    ++$summary['prices_capped'];
                }

                // Update suggested price
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` SET
                        `suggested_price` = ' . (float) $suggested . ',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `seller_sku` = \'' . pSQL($cp['seller_sku']) . '\'
                       AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                       AND `id_shop` = ' . $idShop
                );
                ++$summary['prices_suggested'];
            }
        }

        return $summary;
    }

    /**
     * Push suggested prices to Amazon for products where suggested != current.
     *
     * @param int $limit Max products to push per run
     *
     * @return array Summary
     */
    public function pushSuggestedPrices($limit = 25)
    {
        $this->lastError = null;
        $this->notices = [];

        $summary = [
            'candidates' => 0,
            'pushed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $idShop = (int) AmzproShop::actingId();
        // The listings belong to the seller account this shop is connected to.
        $sellerId = AmzproShop::isMultistore()
            ? trim((string) AmzproShop::get('AMZPRO_SELLER_ID', $idShop))
            : $this->sellerId;

        if ($sellerId === '') {
            $this->notices[] = AmazonI18n::get()->l('Cannot push prices: your seller ID is missing. Click "Connect to Amazon" in Settings > Connection to fill it in.', 'amazonrepricingengine');

            return $summary;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT cp.`seller_sku`, cp.`suggested_price`, cp.`our_price`
             FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
             WHERE cp.`suggested_price` > 0
               AND cp.`suggested_price` <> cp.`our_price`
               AND cp.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
               AND cp.`id_shop` = ' . $idShop . '
             ORDER BY cp.`seller_sku` ASC
             LIMIT ' . (int) $limit
        );

        if (!is_array($rows)) {
            $rows = [];
        }

        $summary['candidates'] = count($rows);
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $r) {
            $sku = $r['seller_sku'];
            $newPrice = (float) $r['suggested_price'];

            // Push to Amazon via Listings API
            $body = [
                'productType' => 'PRODUCT',
                'requirements' => 'LISTING_OFFER_ONLY',
                'attributes' => [
                    'condition_type' => [['value' => AmazonSpApiClient::listingCondition()]],
                    'purchasable_offer' => [[
                        'currency' => AmazonSpApiClient::currencyForMarketplace($this->marketplaceId),
                        'marketplace_id' => $this->marketplaceId,
                        'our_price' => [[
                            'schedule' => [['value_with_tax' => $newPrice]],
                        ]],
                    ]],
                ],
            ];

            $resp = $this->client->request(
                'PUT',
                '/listings/2021-08-01/items/' . rawurlencode($sellerId) . '/' . rawurlencode($sku),
                ['marketplaceIds' => $this->marketplaceId],
                $body
            );

            if ($resp !== false && $resp['status'] < 400) {
                // Update our_price and last_repriced
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` SET
                        `our_price` = ' . $newPrice . ',
                        `last_repriced` = \'' . pSQL($now) . '\',
                        `date_upd` = \'' . pSQL($now) . '\'
                     WHERE `seller_sku` = \'' . pSQL($sku) . '\'
                       AND `marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                       AND `id_shop` = ' . $idShop
                );

                // Also update PS product price
                $this->updatePsPrice($sku, $newPrice, $idShop);

                ++$summary['pushed'];
            } else {
                ++$summary['failed'];
            }
        }

        return $summary;
    }

    /**
     * Calculate suggested price based on a rule and competitive data.
     *
     * @param array $cp Competitive price row
     * @param array $rule Pricing rule row
     *
     * @return float|false Suggested price or false if no change needed
     */
    private function calculateSuggestedPrice($cp, $rule)
    {
        $ruleType = $rule['rule_type'];
        $adjustment = (float) $rule['price_adjustment'];
        $adjustmentType = $rule['adjustment_type'];

        $buyboxLanded = (float) $cp['buybox_landed'];
        $lowestLanded = (float) $cp['lowest_landed'];
        $ourPrice = (float) $cp['our_price'];

        $targetPrice = 0;

        switch ($ruleType) {
            case self::RULE_MATCH_LOWEST:
                if ($lowestLanded <= 0) {
                    return false;
                }
                $targetPrice = $lowestLanded;
                break;

            case self::RULE_BEAT_LOWEST:
                if ($lowestLanded <= 0) {
                    return false;
                }
                $targetPrice = $this->applyAdjustment($lowestLanded, $adjustment, $adjustmentType, 'below');
                break;

            case self::RULE_MATCH_BUYBOX:
                if ($buyboxLanded <= 0) {
                    return false;
                }
                $targetPrice = $buyboxLanded;
                break;

            case self::RULE_BEAT_BUYBOX:
                if ($buyboxLanded <= 0) {
                    return false;
                }
                $targetPrice = $this->applyAdjustment($buyboxLanded, $adjustment, $adjustmentType, 'below');
                break;

            case self::RULE_FIXED_MARGIN:
                // Fixed margin above cost (use PS price as base cost)
                $targetPrice = $this->applyAdjustment($ourPrice, $adjustment, $adjustmentType, 'above');
                break;

            default:
                return false;
        }

        // Round to 2 decimal places
        $targetPrice = round($targetPrice, 2);

        // Don't suggest if already at target
        if (abs($targetPrice - $ourPrice) < 0.01) {
            return false;
        }

        return $targetPrice;
    }

    /**
     * Apply a price adjustment.
     */
    private function applyAdjustment($basePrice, $amount, $type, $direction)
    {
        if ($type === 'percentage') {
            $delta = $basePrice * ($amount / 100);
        } else {
            $delta = $amount;
        }

        if ($direction === 'below') {
            return $basePrice - $delta;
        }

        return $basePrice + $delta;
    }

    /**
     * Update the PrestaShop price of the product or combination with this
     * reference in one shop. The shop's price (product_shop /
     * product_attribute_shop) always changes; the product's own row, which
     * PrestaShop keeps in step with the product's default shop, only when
     * this is that shop (always with multistore off).
     */
    private function updatePsPrice($sku, $newPrice, $idShop)
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $ref = pSQL(trim($sku));
        $idShop = (int) $idShop;
        $oneShop = !AmzproShop::isMultistore();

        // Try combination first
        $row = $db->getRow(
            'SELECT pa.`id_product`, pa.`id_product_attribute`, pr.`id_shop_default`
             FROM `' . $p . 'product_attribute` pa
             INNER JOIN `' . $p . 'product_attribute_shop` pas
                 ON (pas.`id_product_attribute` = pa.`id_product_attribute` AND pas.`id_shop` = ' . $idShop . ')
             INNER JOIN `' . $p . 'product` pr ON (pr.`id_product` = pa.`id_product`)
             WHERE pa.`reference` = \'' . $ref . '\''
        );

        if ($row && (int) $row['id_product_attribute']) {
            $idProduct = (int) $row['id_product'];
            $idPa = (int) $row['id_product_attribute'];
            // Update combination price impact (delta from the shop's base price)
            $basePrice = (float) $db->getValue(
                'SELECT `price` FROM `' . $p . 'product_shop`
                 WHERE `id_product` = ' . $idProduct . ' AND `id_shop` = ' . $idShop
            );
            $impact = (float) ($newPrice - $basePrice);
            $db->execute(
                'UPDATE `' . $p . 'product_attribute_shop` SET `price` = ' . $impact . '
                 WHERE `id_product_attribute` = ' . $idPa . ' AND `id_shop` = ' . $idShop
            );
            if ($oneShop || (int) $row['id_shop_default'] === $idShop) {
                $db->execute(
                    'UPDATE `' . $p . 'product_attribute` SET `price` = ' . $impact . '
                     WHERE `id_product_attribute` = ' . $idPa
                );
            }
            Product::flushPriceCache();

            return;
        }

        // Try base product
        $products = $db->executeS(
            'SELECT pr.`id_product`, pr.`id_shop_default`
             FROM `' . $p . 'product` pr
             INNER JOIN `' . $p . 'product_shop` ps
                 ON (ps.`id_product` = pr.`id_product` AND ps.`id_shop` = ' . $idShop . ')
             WHERE pr.`reference` = \'' . $ref . '\''
        );
        foreach ((is_array($products) ? $products : []) as $product) {
            $idProduct = (int) $product['id_product'];
            $db->execute(
                'UPDATE `' . $p . 'product_shop` SET `price` = ' . (float) $newPrice . '
                 WHERE `id_product` = ' . $idProduct . ' AND `id_shop` = ' . $idShop
            );
            if ($oneShop || (int) $product['id_shop_default'] === $idShop) {
                $db->execute(
                    'UPDATE `' . $p . 'product` SET `price` = ' . (float) $newPrice . '
                     WHERE `id_product` = ' . $idProduct
                );
            }
        }
        Product::flushPriceCache();
    }

    /**
     * Upsert competitive price data.
     */
    private function upsertCompetitivePrice(
        $sku, $asin, $buyboxPrice, $buyboxShipping, $buyboxLanded,
        $buyboxSeller, $isBuyboxWinner, $lowestPrice, $lowestShipping,
        $lowestLanded, $numberOfOffers, $ourPrice, $now
    ) {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price`
            (`id_shop`, `seller_sku`, `asin`, `buybox_price`, `buybox_shipping`, `buybox_landed`,
             `buybox_seller`, `is_buybox_winner`, `lowest_price`, `lowest_shipping`,
             `lowest_landed`, `number_of_offers`, `our_price`, `marketplace_id`,
             `date_add`, `date_upd`)
            VALUES (
                ' . (int) AmzproShop::actingId() . ',
                \'' . pSQL($sku) . '\',
                \'' . pSQL($asin) . '\',
                ' . (float) $buyboxPrice . ',
                ' . (float) $buyboxShipping . ',
                ' . (float) $buyboxLanded . ',
                \'' . pSQL($buyboxSeller) . '\',
                ' . (int) $isBuyboxWinner . ',
                ' . (float) $lowestPrice . ',
                ' . (float) $lowestShipping . ',
                ' . (float) $lowestLanded . ',
                ' . (int) $numberOfOffers . ',
                ' . (float) $ourPrice . ',
                \'' . pSQL($this->marketplaceId) . '\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )
            ON DUPLICATE KEY UPDATE
                `buybox_price` = VALUES(`buybox_price`),
                `buybox_shipping` = VALUES(`buybox_shipping`),
                `buybox_landed` = VALUES(`buybox_landed`),
                `buybox_seller` = VALUES(`buybox_seller`),
                `is_buybox_winner` = VALUES(`is_buybox_winner`),
                `lowest_price` = VALUES(`lowest_price`),
                `lowest_shipping` = VALUES(`lowest_shipping`),
                `lowest_landed` = VALUES(`lowest_landed`),
                `number_of_offers` = VALUES(`number_of_offers`),
                `our_price` = VALUES(`our_price`),
                `date_upd` = VALUES(`date_upd`)';
        Db::getInstance()->execute($sql);
    }

    /* ─────────────────── Pricing Rules CRUD ─────────────────── */

    /**
     * The rules for all shops plus the current shop's own (only the shared
     * ones in "All shops"). Each row carries its id_shop.
     */
    public function listPricingRules()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
                WHERE ' . AmzproShop::sqlShared() . '
                ORDER BY `name` ASC';
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Add a rule for the scope being edited (a shop's own, or shared in "All
     * shops"), or change one. A shop may change only its own rules. Returns
     * false with getLastError() set when refused.
     */
    public function savePricingRule($data)
    {
        $this->lastError = null;
        $now = date('Y-m-d H:i:s');
        $id = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id > 0) {
            if (!$this->checkRuleChange($id)) {
                return false;
            }

            return Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule` SET
                    `name` = \'' . pSQL($data['name']) . '\',
                    `rule_type` = \'' . pSQL($data['rule_type']) . '\',
                    `price_adjustment` = ' . (float) $data['price_adjustment'] . ',
                    `adjustment_type` = \'' . pSQL($data['adjustment_type']) . '\',
                    `min_price` = ' . (float) $data['min_price'] . ',
                    `max_price` = ' . (float) $data['max_price'] . ',
                    `target_buybox` = ' . (int) $data['target_buybox'] . ',
                    `id_category` = ' . (int) $data['id_category'] . ',
                    `marketplace_id` = \'' . pSQL($data['marketplace_id']) . '\',
                    `active` = ' . (int) $data['active'] . ',
                    `date_upd` = \'' . pSQL($now) . '\'
                 WHERE `id_amazonmarketplacepro_pricing_rule` = ' . $id
            );
        }

        return Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             (`id_shop`, `name`, `rule_type`, `price_adjustment`, `adjustment_type`,
              `min_price`, `max_price`, `target_buybox`, `id_category`,
              `marketplace_id`, `active`, `date_add`, `date_upd`)
             VALUES (
                ' . (int) AmzproShop::sharedWriteId() . ',
                \'' . pSQL($data['name']) . '\',
                \'' . pSQL($data['rule_type']) . '\',
                ' . (float) $data['price_adjustment'] . ',
                \'' . pSQL($data['adjustment_type']) . '\',
                ' . (float) $data['min_price'] . ',
                ' . (float) $data['max_price'] . ',
                ' . (int) $data['target_buybox'] . ',
                ' . (int) $data['id_category'] . ',
                \'' . pSQL($data['marketplace_id']) . '\',
                ' . (int) $data['active'] . ',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
             )'
        );
    }

    /** Delete a rule, with the same rule as savePricingRule(). */
    public function deletePricingRule($id)
    {
        $this->lastError = null;
        if (!$this->checkRuleChange((int) $id)) {
            return false;
        }

        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `id_amazonmarketplacepro_pricing_rule` = ' . (int) $id
        );
    }

    /**
     * True when the current context may change this rule: a shop its own
     * rules, "All shops" the shared ones (with multistore off, every rule).
     * Sets the error otherwise.
     */
    private function checkRuleChange($id)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_pricing_rule`
             WHERE `id_amazonmarketplacepro_pricing_rule` = ' . (int) $id . '
               AND ' . AmzproShop::sqlShared()
        );
        if (!$row) {
            $this->lastError = AmazonI18n::get()->l('This pricing rule was not found. Reload the page and try again.', 'amazonrepricingengine');

            return false;
        }
        if (AmzproShop::isMultistore() && (int) $row['id_shop'] !== (int) AmzproShop::sharedWriteId()) {
            $this->lastError = AmazonI18n::get()->l('This pricing rule is shared by all shops. Select "All shops" at the top of the page to change it.', 'amazonrepricingengine');

            return false;
        }

        return true;
    }

    /**
     * List competitive pricing data for admin display: the current shop's,
     * or every shop's in "All shops" (with shop_name).
     */
    public function listCompetitivePrices($limit = 100)
    {
        $sql = 'SELECT cp.*, p.`ps_name`, s.`name` AS shop_name
                FROM `' . _DB_PREFIX_ . 'amazonmarketplacepro_competitive_price` cp
                LEFT JOIN `' . _DB_PREFIX_ . 'amazonmarketplacepro_product` p
                    ON (p.`seller_sku` = cp.`seller_sku` AND p.`id_shop` = cp.`id_shop`)
                LEFT JOIN `' . _DB_PREFIX_ . 'shop` s ON (s.`id_shop` = cp.`id_shop`)
                WHERE cp.`marketplace_id` = \'' . pSQL($this->marketplaceId) . '\'
                  AND ' . AmzproShop::sqlWhere('cp') . '
                ORDER BY cp.`is_buybox_winner` ASC, cp.`seller_sku` ASC
                LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }
}
