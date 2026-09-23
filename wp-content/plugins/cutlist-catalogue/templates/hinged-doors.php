<?php
/**
 * Frontend Template for Hinged Doors Catalogue: [hinged_doors]
 *
 * @var array $catalogue Array of door categories and their doors
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = 'hingeddoors';
include CUTLIST_CATALOGUE_PATH . 'templates/parts/header-tabs.php';

if (!isset($catalogue)) {
    $catalogue = cutlist_get_hinged_doors_catalogue();
}

$currency_sym = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '&pound;';
?>

<div class="container tab-panel active" data-panel="hingeddoors" id="hingedDoorsContainer">

    <?php if (!empty($catalogue)): ?>
        <?php foreach ($catalogue as $cat_index => $category): ?>
            <div class="hd-category-card" id="hdCatSection-<?php echo esc_attr($category['id']); ?>">
                <!-- Section Header -->
                <div class="hd-category-header">
                    <div class="hd-category-title-wrap">
                        <span class="hd-category-icon-badge" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                                <rect x="7" y="5" width="10" height="14" rx="1"></rect>
                            </svg>
                        </span>
                        <div class="hd-category-heading-text">
                            <h2 class="hd-category-title"><?php echo esc_html($category['name']); ?></h2>
                            <p class="hd-category-subtitle"><?php echo esc_html($category['subtitle']); ?></p>
                        </div>
                    </div>
                    <button type="button" class="hd-category-collapse-btn" data-target="hdCatBody-<?php echo esc_attr($category['id']); ?>" aria-expanded="true">
                        <span class="hd-collapse-text"><?php esc_html_e('Collapse', 'cutlist-catalogue'); ?></span>
                        <svg class="hd-collapse-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="18 15 12 9 6 15"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- Section Body & 4-Column Grid -->
                <div class="hd-category-body" id="hdCatBody-<?php echo esc_attr($category['id']); ?>">
                    <div class="hd-doors-grid">
                        <?php foreach ($category['doors'] as $door): ?>
                            <div class="hd-door-card" 
                                 data-door-id="<?php echo esc_attr($door['id']); ?>" 
                                 data-door-code="<?php echo esc_attr($door['title']); ?>"
                                 data-door-title="<?php echo esc_attr($door['title']); ?>"
                                 data-door-subtitle="<?php echo esc_attr($door['subtitle']); ?>"
                                 data-door-desc="<?php echo esc_attr($door['description']); ?>"
                                 data-door-price="<?php echo esc_attr($door['price_from']); ?>"
                                 data-door-panels="<?php echo esc_attr($door['panels_count']); ?>"
                                 data-door-img="<?php echo esc_url($door['image_url']); ?>"
                                 data-door-zoom="<?php echo esc_url($door['quick_view_url']); ?>"
                                 data-gallery-1="<?php echo esc_url($door['gallery_1']); ?>"
                                 data-gallery-2="<?php echo esc_url($door['gallery_2']); ?>"
                                 data-gallery-3="<?php echo esc_url($door['gallery_3']); ?>"
                                 data-door-json="<?php echo esc_attr(wp_json_encode($door)); ?>"
                                 data-spec-material="<?php echo esc_attr($door['spec_material']); ?>"
                                 data-spec-build="<?php echo esc_attr($door['spec_build']); ?>"
                                 data-spec-overall-thick="<?php echo esc_attr($door['spec_overall_thickness']); ?>"
                                 data-spec-panel-thick="<?php echo esc_attr($door['spec_panel_thickness']); ?>"
                                 data-spec-finish="<?php echo esc_attr($door['spec_finish']); ?>"
                                 data-spec-disclaimer="<?php echo esc_attr($door['spec_disclaimer']); ?>"
                                 data-price-primer="<?php echo esc_attr($door['price_white_primer']); ?>"
                                 data-price-painted="<?php echo esc_attr($door['price_painted']); ?>"
                                 data-price-hinge="<?php echo esc_attr($door['price_per_hinge_hole']); ?>"
                                 data-price-door="<?php echo esc_attr($door['price_door']); ?>"
                                 data-price-ep="<?php echo esc_attr($door['price_end_panel']); ?>"
                                 data-price-df="<?php echo esc_attr($door['price_drawer_front']); ?>"
                                 data-door-min-h="<?php echo esc_attr($door['door_min_h']); ?>"
                                 data-door-max-h="<?php echo esc_attr($door['door_max_h']); ?>"
                                 data-door-min-w="<?php echo esc_attr($door['door_min_w']); ?>"
                                 data-door-max-w="<?php echo esc_attr($door['door_max_w']); ?>"
                                 data-door-drilling="<?php echo esc_attr($door['door_allow_drilling']); ?>"
                                 data-door-primer="<?php echo esc_attr($door['door_price_primer']); ?>"
                                 data-door-painted="<?php echo esc_attr($door['door_price_painted']); ?>"
                                 data-ep-min-h="<?php echo esc_attr($door['ep_min_h']); ?>"
                                 data-ep-max-h="<?php echo esc_attr($door['ep_max_h']); ?>"
                                 data-ep-min-w="<?php echo esc_attr($door['ep_min_w']); ?>"
                                 data-ep-max-w="<?php echo esc_attr($door['ep_max_w']); ?>"
                                 data-ep-drilling="<?php echo esc_attr($door['ep_allow_drilling']); ?>"
                                 data-ep-primer="<?php echo esc_attr($door['ep_price_primer']); ?>"
                                 data-ep-painted="<?php echo esc_attr($door['ep_price_painted']); ?>"
                                 data-df-min-h="<?php echo esc_attr($door['df_min_h']); ?>"
                                 data-df-max-h="<?php echo esc_attr($door['df_max_h']); ?>"
                                 data-df-min-w="<?php echo esc_attr($door['df_min_w']); ?>"
                                 data-df-max-w="<?php echo esc_attr($door['df_max_w']); ?>"
                                 data-df-drilling="<?php echo esc_attr($door['df_allow_drilling']); ?>"
                                 data-df-primer="<?php echo esc_attr($door['df_price_primer']); ?>"
                                 data-df-painted="<?php echo esc_attr($door['df_price_painted']); ?>">
                                <!-- Door Graphic / Image Box -->
                                <div class="hd-door-image-wrap">
                                    <!-- Base Image -->
                                    <div class="hd-door-image-inner">
                                        <?php if (!empty($door['image_url'])): ?>
                                            <img src="<?php echo esc_url($door['image_url']); ?>" alt="<?php echo esc_attr($door['title']); ?>" class="hd-door-img" loading="lazy">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Hover Dark Overlay (Matches Active State in Screenshot) -->
                                    <div class="hd-door-hover-overlay">
                                        <!-- Magnifying Glass Zoom Icon -->
                                        <button type="button" 
                                                class="hd-zoom-btn" 
                                                data-title="<?php echo esc_attr($door['title']); ?>"
                                                data-subtitle="<?php echo esc_attr($door['subtitle']); ?>"
                                                data-image="<?php echo esc_url($door['quick_view_url'] ? $door['quick_view_url'] : $door['image_url']); ?>"
                                                data-price="<?php echo esc_attr($door['price_html']); ?>"
                                                data-desc="<?php echo esc_attr($door['description']); ?>"
                                                aria-label="<?php esc_attr_e('Zoom image', 'cutlist-catalogue'); ?>">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                                <line x1="8" y1="11" x2="14" y2="11"></line>
                                            </svg>
                                        </button>

                                        <!-- Center Title, Subtitle, and CTA Button -->
                                        <div class="hd-hover-content">
                                            <div class="hd-hover-title"><?php echo esc_html($door['title']); ?></div>
                                            <?php if (!empty($door['subtitle'])): ?>
                                                <div class="hd-hover-subtitle"><?php echo esc_html($door['subtitle']); ?></div>
                                            <?php endif; ?>
                                            <button type="button" class="hd-view-details-btn" data-door-id="<?php echo esc_attr($door['id']); ?>">
                                                <span><?php esc_html_e('View details & buy', 'cutlist-catalogue'); ?></span>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                    <polyline points="12 5 19 12 12 19"></polyline>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Content Area -->
                                <div class="hd-door-info-body">
                                    <h3 class="hd-door-title"><?php echo esc_html($door['title']); ?></h3>
                                    <?php if (!empty($door['subtitle'])): ?>
                                        <div class="hd-door-subtitle"><?php echo esc_html($door['subtitle']); ?></div>
                                    <?php endif; ?>

                                    <!-- Specs with Icons -->
                                    <div class="hd-door-specs-list">
                                        <?php if (!empty($door['feature_1'])): ?>
                                            <div class="hd-spec-item">
                                                <span class="hd-spec-icon-wrap" aria-hidden="true">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                                        <path d="M2 17l10 5 10-5"></path>
                                                        <path d="M2 12l10 5 10-5"></path>
                                                    </svg>
                                                </span>
                                                <span class="hd-spec-text"><?php echo esc_html($door['feature_1']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($door['feature_2'])): ?>
                                            <div class="hd-spec-item">
                                                <span class="hd-spec-icon-wrap" aria-hidden="true">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                                                        <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
                                                    </svg>
                                                </span>
                                                <span class="hd-spec-text"><?php echo esc_html($door['feature_2']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Card Price Footer Bar -->
                                <div class="hd-door-price-footer">
                                    <span class="hd-door-price-label"><?php esc_html_e('From:', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-door-price-val"><?php echo wp_kses_post($door['price_html']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bottom Info Banner -->
                    <div class="hd-bottom-notice">
                        <span class="hd-notice-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span class="hd-notice-text"><?php echo esc_html($category['notice']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="hd-empty-state">
            <p><?php esc_html_e('No hinged doors available in the catalogue yet.', 'cutlist-catalogue'); ?></p>
        </div>
    <?php endif; ?>

</div>

<!-- =========================================================================
     DOOR CONFIGURATOR & ORDER POPUP MODAL
     ========================================================================= -->
<div class="hd-config-overlay" id="hdConfigModal" aria-hidden="true">
    <div class="hd-config-modal">
        <!-- Close Button -->
        <button type="button" class="hd-config-close" id="hdConfigCloseBtn" aria-label="<?php esc_attr_e('Close', 'cutlist-catalogue'); ?>">&times;</button>

        <div class="hd-config-layout">
            <!-- Left Column: Gallery & Description Pane -->
            <div class="hd-config-gallery">
                <!-- Main Showcase Image -->
                <div class="hd-config-main-image-wrap">
                    <img src="" alt="" id="hdConfigMainImg" class="hd-config-main-img">
                </div>

                <!-- Thumbnail Row -->
                <div class="hd-config-thumbs" id="hdConfigThumbs">
                    <div class="hd-config-thumb active" data-thumb-idx="0">
                        <img src="" alt="" class="hd-config-thumb-img" id="hdThumb0">
                    </div>
                    <div class="hd-config-thumb" data-thumb-idx="1">
                        <img src="" alt="" class="hd-config-thumb-img" id="hdThumb1">
                    </div>
                    <div class="hd-config-thumb" data-thumb-idx="2">
                        <img src="" alt="" class="hd-config-thumb-img" id="hdThumb2">
                    </div>
                </div>

                <!-- Product Code & Subtitle -->
                <div class="hd-config-product-info">
                    <h2 class="hd-config-product-title" id="hdConfigProductCode">SHAKER-1-D</h2>
                    <div class="hd-config-product-subtitle" id="hdConfigProductSubtitle">Door – Shaker with one panel</div>
                </div>

                <!-- Product Description -->
                <div class="hd-config-desc-block">
                    <h3 class="hd-config-desc-heading"><?php esc_html_e('Product description', 'cutlist-catalogue'); ?></h3>
                    <p class="hd-config-desc-text" id="hdConfigProductDesc">
                        <?php esc_html_e('Mortise and tenon structure. Built using moisture resistant MDF.', 'cutlist-catalogue'); ?>
                    </p>
                </div>
            </div>

            <!-- Right Column: Configurator Steps & Purchase Controls -->
            <div class="hd-config-main">
                <!-- Top Row: Stepper Wizard Tabs + Purchase Actions -->
                <div class="hd-config-top-row">
                    <!-- Chevron Stepper Tabs -->
                    <div class="hd-config-tabs">
                        <button type="button" class="hd-config-tab active" data-tab="details">
                            <span><?php esc_html_e('Details', 'cutlist-catalogue'); ?></span>
                        </button>
                        <button type="button" class="hd-config-tab" data-tab="size">
                            <span><?php esc_html_e('Size', 'cutlist-catalogue'); ?></span>
                        </button>
                        <button type="button" class="hd-config-tab" data-tab="drilling">
                            <span><?php esc_html_e('Drilling', 'cutlist-catalogue'); ?></span>
                        </button>
                        <button type="button" class="hd-config-tab" data-tab="finish">
                            <span><?php esc_html_e('Finish', 'cutlist-catalogue'); ?></span>
                        </button>
                    </div>

                    <!-- Purchase Actions & Live Total -->
                    <div class="hd-config-purchase">
                        <div class="hd-config-qty">
                            <button type="button" class="hd-qty-btn" id="hdQtyMinus" aria-label="Decrease">&minus;</button>
                            <input type="number" id="hdQtyInput" value="1" min="1" max="999">
                            <button type="button" class="hd-qty-btn" id="hdQtyPlus" aria-label="Increase">&#43;</button>
                        </div>
                        <button type="button" class="hd-config-add-basket" id="hdAddToBasketBtn">
                            <?php esc_html_e('Add to basket', 'cutlist-catalogue'); ?>
                        </button>
                        <div class="hd-config-total-wrap">
                            <div class="hd-config-total">Total: <span id="hdTotalAmount">£0.00</span></div>
                            <div class="hd-config-total-vat">With VAT: <span id="hdTotalVatAmount">£0.00</span></div>
                        </div>
                    </div>
                </div>

                <!-- Configurator Body Panes -->
                <div class="hd-config-body">
                    <!-- TAB 1: DETAILS -->
                    <div class="hd-tab-pane active" id="hdTabDetails">
                        <h3 class="hd-details-heading"><?php esc_html_e('Product details', 'cutlist-catalogue'); ?></h3>
                        <div class="hd-details-specs">
                            <div class="hd-spec-row" id="hdSpecStyleName"><?php esc_html_e('Shaker-style door', 'cutlist-catalogue'); ?></div>
                            <div class="hd-spec-row" id="hdSpecMaterial"><?php esc_html_e('Material: Moisture-resistant MDF', 'cutlist-catalogue'); ?></div>
                            <div class="hd-spec-row" id="hdSpecBuild"><?php esc_html_e('Build: Mortise and tenon', 'cutlist-catalogue'); ?></div>
                            <div class="hd-spec-row" id="hdSpecOverallThick"><?php esc_html_e('Overall thickness: 21mm', 'cutlist-catalogue'); ?></div>
                            <div class="hd-spec-row" id="hdSpecPanelThick"><?php esc_html_e('Panel thickness: 9mm', 'cutlist-catalogue'); ?></div>
                            <div class="hd-spec-row" id="hdSpecFinish"><?php esc_html_e('Finish: can be supplied raw, primed or painted', 'cutlist-catalogue'); ?></div>
                        </div>

                        <div class="hd-details-note" id="hdSpecDisclaimer">
                            <?php esc_html_e('No fixings are supplied; they are to be purchased separately.', 'cutlist-catalogue'); ?>
                        </div>

                        <!-- Available products Selector Box -->
                        <div class="hd-available-products-wrap">
                            <div class="hd-available-heading"><?php esc_html_e('Available products:', 'cutlist-catalogue'); ?></div>
                            <div class="hd-available-box">
                                <label class="hd-product-type-option selected">
                                    <span class="hd-pt-label"><?php esc_html_e('Door', 'cutlist-catalogue'); ?></span>
                                    <input type="radio" name="hd_product_type" value="door" checked>
                                </label>
                                <label class="hd-product-type-option">
                                    <span class="hd-pt-label"><?php esc_html_e('End panel', 'cutlist-catalogue'); ?></span>
                                    <input type="radio" name="hd_product_type" value="end_panel">
                                </label>
                                <label class="hd-product-type-option">
                                    <span class="hd-pt-label"><?php esc_html_e('Drawer front', 'cutlist-catalogue'); ?></span>
                                    <input type="radio" name="hd_product_type" value="drawer_front">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: SIZE -->
                    <div class="hd-tab-pane" id="hdTabSize">
                        <h3 class="hd-details-heading"><?php esc_html_e('Select dimensions', 'cutlist-catalogue'); ?></h3>
                        <div class="hd-size-form">
                            <div class="hd-field-group">
                                <label for="hdDoorHeight"><?php esc_html_e('Height [mm]', 'cutlist-catalogue'); ?></label>
                                <input type="number" id="hdDoorHeight" min="250" max="2800" placeholder="e.g. 720" value="720">
                                <span class="hd-field-hint" id="hdDoorHeightHint"><?php esc_html_e('Min: 250mm, Max: 2800mm', 'cutlist-catalogue'); ?></span>
                            </div>
                            <div class="hd-field-group">
                                <label for="hdDoorWidth"><?php esc_html_e('Width [mm]', 'cutlist-catalogue'); ?></label>
                                <input type="number" id="hdDoorWidth" min="200" max="1200" placeholder="e.g. 595" value="595">
                                <span class="hd-field-hint" id="hdDoorWidthHint"><?php esc_html_e('Min: 200mm, Max: 1200mm', 'cutlist-catalogue'); ?></span>
                            </div>
                            <div class="hd-field-group">
                                <label for="hdDoorThickness"><?php esc_html_e('Thickness', 'cutlist-catalogue'); ?></label>
                                <select id="hdDoorThickness">
                                    <option value="21" selected>21mm (Standard)</option>
                                    <option value="18">18mm</option>
                                    <option value="25">25mm</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: DRILLING -->
                    <div class="hd-tab-pane" id="hdTabDrilling">
                        <h3 class="hd-details-heading"><?php esc_html_e('Hinge hole drilling', 'cutlist-catalogue'); ?></h3>
                        
                        <div id="hdDrillingAvailableWrap">
                            <p class="hd-drilling-intro"><?php esc_html_e('Would you like us to pre-drill 35mm hinge holes for your doors?', 'cutlist-catalogue'); ?></p>
                            
                            <div class="hd-btn-group-toggle">
                                <button type="button" class="hd-toggle-btn active" data-val="no"><?php esc_html_e('No hinge holes', 'cutlist-catalogue'); ?></button>
                                <button type="button" class="hd-toggle-btn" data-val="yes"><?php esc_html_e('Yes, drill hinge holes', 'cutlist-catalogue'); ?></button>
                            </div>

                            <div class="hd-drilling-options" id="hdDrillingFields" style="display:none;">
                                <div class="hd-field-group">
                                    <label for="hdHolesCount"><?php esc_html_e('Number of hinge holes', 'cutlist-catalogue'); ?></label>
                                    <select id="hdHolesCount">
                                        <option value="2" selected>2 hinge holes (+£6.00)</option>
                                        <option value="3">3 hinge holes (+£9.00)</option>
                                        <option value="4">4 hinge holes (+£12.00)</option>
                                    </select>
                                </div>
                                <div class="hd-field-group">
                                    <label for="hdHolesHingeSide"><?php esc_html_e('Hinge side', 'cutlist-catalogue'); ?></label>
                                    <select id="hdHolesHingeSide">
                                        <option value="left" selected><?php esc_html_e('Left edge', 'cutlist-catalogue'); ?></option>
                                        <option value="right"><?php esc_html_e('Right edge', 'cutlist-catalogue'); ?></option>
                                        <option value="top"><?php esc_html_e('Top edge (Flap)', 'cutlist-catalogue'); ?></option>
                                    </select>
                                </div>
                                <div class="hd-field-group">
                                    <label for="hdHolesMargin"><?php esc_html_e('Distance from top & bottom edges [mm]', 'cutlist-catalogue'); ?></label>
                                    <input type="number" id="hdHolesMargin" value="100" min="50" max="300">
                                </div>
                            </div>
                        </div>

                        <div id="hdDrillingUnavailableNotice" class="hd-details-note" style="display:none; margin-top: 15px;">
                            <?php esc_html_e('Hinge hole drilling is not required or applicable for this product type.', 'cutlist-catalogue'); ?>
                        </div>
                    </div>

                    <!-- TAB 4: FINISH -->
                    <div class="hd-tab-pane" id="hdTabFinish">
                        <h3 class="hd-details-heading"><?php esc_html_e('Select finish option', 'cutlist-catalogue'); ?></h3>
                        <div class="hd-finish-cards">
                            <label class="hd-finish-card selected">
                                <input type="radio" name="hd_finish_type" value="raw" checked>
                                <div class="hd-finish-card-body">
                                    <span class="hd-finish-title"><?php esc_html_e('Raw MR MDF', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-finish-sub"><?php esc_html_e('Unpainted sanded surface', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-finish-price" id="hdFinishPriceRaw"><?php esc_html_e('+£0.00', 'cutlist-catalogue'); ?></span>
                                </div>
                            </label>

                            <label class="hd-finish-card">
                                <input type="radio" name="hd_finish_type" value="primed">
                                <div class="hd-finish-card-body">
                                    <span class="hd-finish-title"><?php esc_html_e('White Primer', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-finish-sub"><?php esc_html_e('Ready for topcoat painting', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-finish-price" id="hdFinishPricePrimed"><?php esc_html_e('+£18.50', 'cutlist-catalogue'); ?></span>
                                </div>
                            </label>

                            <label class="hd-finish-card">
                                <input type="radio" name="hd_finish_type" value="painted">
                                <div class="hd-finish-card-body">
                                    <span class="hd-finish-title"><?php esc_html_e('Fully Painted', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-finish-sub"><?php esc_html_e('Spray painted in any colour', 'cutlist-catalogue'); ?></span>
                                    <span class="hd-finish-price" id="hdFinishPricePainted"><?php esc_html_e('+£38.00', 'cutlist-catalogue'); ?></span>
                                </div>
                            </label>
                        </div>

                        <div class="hd-paint-custom-fields" id="hdPaintFields" style="display:none;">
                            <div class="hd-field-group">
                                <label for="hdPaintBrand"><?php esc_html_e('Paint brand / colour code', 'cutlist-catalogue'); ?></label>
                                <input type="text" id="hdPaintBrand" placeholder="e.g. Farrow & Ball - Hague Blue No. 30">
                            </div>
                            <div class="hd-field-group">
                                <label for="hdPaintSheen"><?php esc_html_e('Sheen level', 'cutlist-catalogue'); ?></label>
                                <select id="hdPaintSheen">
                                    <option value="matt" selected>Matt (10% Sheen)</option>
                                    <option value="satin">Satin (25% Sheen)</option>
                                    <option value="semi_gloss">Semi-Gloss (50% Sheen)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Quick View (Simple Image Zoom Modal) -->
<div class="hd-quickview-modal-backdrop" id="hdQuickViewModal" aria-hidden="true">
    <div class="hd-quickview-modal-dialog">
        <button type="button" class="hd-quickview-close-btn" id="hdQuickViewCloseBtn" aria-label="<?php esc_attr_e('Close', 'cutlist-catalogue'); ?>">&times;</button>
        <div class="hd-quickview-modal-body">
            <div class="hd-quickview-image-pane">
                <img src="" alt="" id="hdQuickViewImage" class="hd-quickview-big-img">
            </div>
            <div class="hd-quickview-info-pane">
                <h3 class="hd-quickview-title" id="hdQuickViewTitle"></h3>
                <div class="hd-quickview-subtitle" id="hdQuickViewSubtitle"></div>
                <div class="hd-quickview-price" id="hdQuickViewPrice"></div>
                <div class="hd-quickview-desc" id="hdQuickViewDesc"></div>
            </div>
        </div>
    </div>
</div>
