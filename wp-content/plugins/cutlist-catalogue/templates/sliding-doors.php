<?php
/**
 * Frontend Template for Sliding Doors Configurator: [sliding_doors]
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = 'slidingdoors';
include CUTLIST_CATALOGUE_PATH . 'templates/parts/header-tabs.php';

$currency_sym = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '&pound;';
?>

<div class="container tab-panel active" data-panel="slidingdoors" id="slidingDoorsContainer">

    <div class="sd-configurator-wrapper">
        <div class="sd-sets-container" id="sdSetsContainer"></div>
        <div class="sd-basket-summary" id="sdBasketSummary" style="display:none"></div>
    </div>

</div>

