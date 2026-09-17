<?php
/**
 * Shared Header Tabs & Topbar template part.
 *
 * @var string $active_tab Current active tab slug ('cutspray' | 'cncmachining' | etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($active_tab) ? $active_tab : 'cutspray';

$wc_cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#';
$wc_checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '#';
$currency_sym = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '&pound;';
$zero_price_formatted = function_exists('wc_price') ? wp_strip_all_tags(wc_price(0)) : ($currency_sym . '0.00');
$wc_cart_total = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_total() : (function_exists('wc_price') ? wc_price(0) : ($currency_sym . '0.00'));
$cutlist_page_url = function_exists('cutlist_get_cutlist_page_url') ? cutlist_get_cutlist_page_url() : home_url('/cutlist/');
$cnc_page_url = function_exists('cutlist_get_cnc_page_url') ? cutlist_get_cnc_page_url() : home_url('/cnc-machining/');
?>
<div class="cb-topbar" id="cbTopbar" style="display:none">
    <a href="<?php echo esc_url($wc_cart_url); ?>"
        class="cb-topbar-btn cb-topbar-view"><?php esc_html_e('View basket', 'cutlist-catalogue'); ?></a>
    <a href="<?php echo esc_url($wc_checkout_url); ?>"
        class="cb-topbar-btn cb-topbar-checkout"><?php esc_html_e('Checkout', 'cutlist-catalogue'); ?></a>
</div>
<div class="header">
    <div class="tabs">
        <a href="<?php echo $active_tab === 'cutspray' ? 'javascript:void(0)' : esc_url($cutlist_page_url); ?>"
            class="tab<?php echo $active_tab === 'cutspray' ? ' active' : ''; ?>">
            <span class="tab-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                    <line x1="3" y1="9" x2="21" y2="9" />
                    <line x1="9" y1="21" x2="9" y2="9" />
                </svg>
            </span>
            <span class="tab-content">
                <span class="tab-label"><?php esc_html_e('Cut, Edge & Spray', 'cutlist-catalogue'); ?></span>
                <span class="tab-price"><?php echo esc_html($zero_price_formatted); ?></span>
            </span>
        </a>
        <a href="<?php echo $active_tab === 'cncmachining' ? 'javascript:void(0)' : esc_url($cnc_page_url); ?>"
            class="tab<?php echo $active_tab === 'cncmachining' ? ' active' : ''; ?>">
            <span class="tab-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3" />
                    <path
                        d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83" />
                </svg>
            </span>
            <span class="tab-content">
                <span class="tab-label"><?php esc_html_e('CNC Machining', 'cutlist-catalogue'); ?></span>
                <span class="tab-price"><?php echo esc_html($zero_price_formatted); ?></span>
            </span>
        </a>
        <a href="#" class="tab disabled" aria-disabled="true"
            title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>">
            <span class="tab-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <line x1="12" y1="3" x2="12" y2="21" />
                </svg>
            </span>
            <span class="tab-content">
                <span class="tab-label"><?php esc_html_e('Sliding Doors', 'cutlist-catalogue'); ?></span>
                <span class="tab-price"><?php echo esc_html($zero_price_formatted); ?></span>
            </span>
        </a>
        <a href="#" class="tab disabled" aria-disabled="true"
            title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>">
            <span class="tab-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path d="M3 12h18" />
                </svg>
            </span>
            <span class="tab-content">
                <span class="tab-label"><?php esc_html_e('Hinged Doors', 'cutlist-catalogue'); ?></span>
                <span class="tab-price"><?php echo esc_html($zero_price_formatted); ?></span>
            </span>
        </a>
        <a href="#" class="tab disabled" aria-disabled="true"
            title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>">
            <span class="tab-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="M2 8h20M8 4v16" />
                </svg>
            </span>
            <span class="tab-content">
                <span class="tab-label"><?php esc_html_e('Furniture Fittings', 'cutlist-catalogue'); ?></span>
                <span class="tab-price"><?php echo esc_html($zero_price_formatted); ?></span>
            </span>
        </a>
        <a href="<?php echo esc_url($wc_cart_url); ?>" class="tab-basket-btn">
            <span class="tab-basket-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
            </span>
            <span class="tab-basket-text">
                <span class="tab-basket-label"><?php esc_html_e('View Basket', 'cutlist-catalogue'); ?></span>
                <span class="tab-basket-price"><?php echo wp_kses_post($wc_cart_total); ?></span>
            </span>
            <span class="tab-basket-arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </span>
        </a>
    </div>
</div>
