/**
 * Hinged Doors Frontend JavaScript
 * Modular, scalable architecture for Hinged Door Products & Configurator.
 * Handles independent variations (Door, End Panel, Drawer Front), dynamic dimensions,
 * drilling availability, finish surcharges, and dedicated galleries.
 */

(function () {
    'use strict';

    var i18n = (typeof hingedDoorsI18n !== 'undefined') ? hingedDoorsI18n : {
        collapse: 'Collapse',
        expand: 'Expand',
        quickView: 'Quick View',
        close: 'Close'
    };

    var currencySym = '£';

    // =========================================================================
    // REUSABLE HELPER FUNCTIONS (Modular Variation Architecture)
    // =========================================================================

    /**
     * Formats numeric amount to currency string.
     */
    function formatMoney(amount) {
        return currencySym + Number(amount || 0).toFixed(2);
    }

    /**
     * Retrieves variation data object for a given product and variation key ('door', 'end_panel', 'drawer_front').
     */
    function getHingedDoorVariationData(product, variationKey) {
        if (!product) return null;
        if (product.variations && product.variations[variationKey]) {
            return product.variations[variationKey];
        }
        // Fallback default variation object if JSON was not available
        return {
            key: variationKey,
            name: variationKey === 'drawer_front' ? 'Drawer Front' : (variationKey === 'end_panel' ? 'End Panel' : 'Door'),
            code_suffix: variationKey === 'drawer_front' ? '-DF' : (variationKey === 'end_panel' ? '-EP' : '-D'),
            starting_price: 41.50,
            min_height: 250,
            max_height: 2800,
            min_width: 200,
            max_width: 1200,
            drilling_available: variationKey === 'door',
            drilling_holes: [2, 3, 4, 5],
            finish: {
                raw: 0,
                primer: 18.50,
                painted: 38.00
            },
            gallery: {
                quick_view: '',
                image_1: '',
                image_2: '',
                image_3: ''
            }
        };
    }

    /**
     * Validates and clamps height and width according to the variation's constraints.
     */
    function validateVariationDimensions(variation, height, width) {
        var minH = variation && variation.min_height ? parseInt(variation.min_height, 10) : 250;
        var maxH = variation && variation.max_height ? parseInt(variation.max_height, 10) : 2800;
        var minW = variation && variation.min_width ? parseInt(variation.min_width, 10) : 200;
        var maxW = variation && variation.max_width ? parseInt(variation.max_width, 10) : 1200;

        var validH = parseInt(height, 10) || minH;
        var validW = parseInt(width, 10) || minW;

        if (validH < minH) validH = minH;
        if (validH > maxH) validH = maxH;
        if (validW < minW) validW = minW;
        if (validW > maxW) validW = maxW;

        return {
            height: validH,
            width: validW,
            minH: minH,
            maxH: maxH,
            minW: minW,
            maxW: maxW
        };
    }

    /**
     * Checks whether drilling options are available for the variation.
     */
    function isDrillingAvailable(variation) {
        return !!(variation && variation.drilling_available);
    }

    /**
     * Calculates pricing breakdown (base, finish, drilling, total unit price) for a variation.
     */
    function getVariationPrice(variation, finishType, hasDrilling, holesCount, pricePerHinge) {
        if (!variation) {
            return { basePrice: 0, finishSurcharge: 0, drillingSurcharge: 0, unitPrice: 0 };
        }
        var base = parseFloat(variation.starting_price) || 0;

        // Finish surcharge
        var finishSurcharge = 0;
        if (variation.finish) {
            if (finishType === 'primer' || finishType === 'primed') {
                finishSurcharge = parseFloat(variation.finish.primer) || 0;
            } else if (finishType === 'painted') {
                finishSurcharge = parseFloat(variation.finish.painted) || 0;
            }
        }

        // Drilling surcharge
        var drillingSurcharge = 0;
        if (isDrillingAvailable(variation) && hasDrilling) {
            var holes = parseInt(holesCount, 10) || 0;
            var perHoleRate = parseFloat(pricePerHinge) || 3.00;
            drillingSurcharge = holes * perHoleRate;
        }

        return {
            basePrice: base,
            finishSurcharge: finishSurcharge,
            drillingSurcharge: drillingSurcharge,
            unitPrice: base + finishSurcharge + drillingSurcharge
        };
    }

    /**
     * Retrieves dedicated gallery images for the variation with fallbacks.
     */
    function getVariationGallery(variation, fallbackImage) {
        var fallback = fallbackImage || '';
        if (!variation || !variation.gallery) {
            return {
                quick_view: fallback,
                image_1: fallback,
                image_2: fallback,
                image_3: fallback
            };
        }
        return {
            quick_view: variation.gallery.quick_view || fallback,
            image_1: variation.gallery.image_1 || fallback,
            image_2: variation.gallery.image_2 || fallback,
            image_3: variation.gallery.image_3 || fallback
        };
    }

    // =========================================================================
    // DOM INITIALIZATION
    // =========================================================================

    document.addEventListener('DOMContentLoaded', function () {

        // 1. Collapse / Expand Category Sections
        var collapseBtns = document.querySelectorAll('.hd-category-collapse-btn');
        collapseBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var section = this.closest('.hd-category-card') || this.closest('.hd-category-section');
                if (!section) return;

                var isCollapsed = section.classList.toggle('is-collapsed');
                this.setAttribute('aria-expanded', !isCollapsed);

                var labelSpan = this.querySelector('.hd-collapse-text');
                if (labelSpan) {
                    labelSpan.textContent = isCollapsed ? i18n.expand : i18n.collapse;
                }
            });
        });

        // 2. Configurator Modal DOM References
        var configModal = document.getElementById('hdConfigModal');
        var configCloseBtn = document.getElementById('hdConfigCloseBtn');
        var mainImg = document.getElementById('hdConfigMainImg');
        var thumbs = document.querySelectorAll('.hd-config-thumb');
        var productCodeEl = document.getElementById('hdConfigProductCode');
        var productSubtitleEl = document.getElementById('hdConfigProductSubtitle');
        var productDescEl = document.getElementById('hdConfigProductDesc');
        var specStyleNameEl = document.getElementById('hdSpecStyleName');

        // Wizard Tabs & Panes
        var wizardTabs = document.querySelectorAll('.hd-config-tab');
        var tabPanes = document.querySelectorAll('.hd-tab-pane');

        // Quantity & Totals
        var qtyInput = document.getElementById('hdQtyInput');
        var qtyMinus = document.getElementById('hdQtyMinus');
        var qtyPlus = document.getElementById('hdQtyPlus');
        var addToBasketBtn = document.getElementById('hdAddToBasketBtn');
        var totalAmountEl = document.getElementById('hdTotalAmount');
        var totalVatAmountEl = document.getElementById('hdTotalVatAmount');

        // Inputs & Selectors
        var heightInput = document.getElementById('hdDoorHeight');
        var widthInput = document.getElementById('hdDoorWidth');
        var thicknessSelect = document.getElementById('hdDoorThickness');

        var drillingToggleBtns = document.querySelectorAll('.hd-toggle-btn');
        var drillingFields = document.getElementById('hdDrillingFields');
        var holesCountSelect = document.getElementById('hdHolesCount');
        var drillingAvailWrap = document.getElementById('hdDrillingAvailableWrap');
        var drillingUnavailNotice = document.getElementById('hdDrillingUnavailableNotice');

        var finishCards = document.querySelectorAll('.hd-finish-card');
        var paintFields = document.getElementById('hdPaintFields');
        var productTypeOptions = document.querySelectorAll('.hd-product-type-option');

        // Active State Model
        var activeProduct = null;
        var activeVariationKey = 'door'; // 'door' | 'end_panel' | 'drawer_front'
        var activeHasDrilling = false;
        var activeFinishType = 'raw';
        var activeQty = 1;

        /**
         * Computes and displays the live total and VAT.
         */
        function calculateTotal() {
            if (!activeProduct) return;

            var variation = getHingedDoorVariationData(activeProduct, activeVariationKey);
            var holesCount = holesCountSelect ? holesCountSelect.value : 2;
            var pricePerHinge = activeProduct.general ? activeProduct.general.price_per_hinge_hole : 3.00;

            var pricing = getVariationPrice(variation, activeFinishType, activeHasDrilling, holesCount, pricePerHinge);
            var qty = parseInt(qtyInput ? qtyInput.value : '1', 10) || 1;
            if (qty < 1) qty = 1;

            var totalNet = pricing.unitPrice * qty;
            var totalGross = totalNet * 1.20; // 20% Standard UK VAT

            if (totalAmountEl) totalAmountEl.textContent = formatMoney(totalNet);
            if (totalVatAmountEl) totalVatAmountEl.textContent = formatMoney(totalGross);

            if (addToBasketBtn) {
                if (pricing.unitPrice > 0) {
                    addToBasketBtn.classList.add('is-ready');
                } else {
                    addToBasketBtn.classList.remove('is-ready');
                }
            }
        }

        /**
         * Updates hole count options dynamically based on variation configuration.
         */
        function updateHolesCountOptions(drillingHoles, pricePerHinge) {
            if (!holesCountSelect) return;
            var allowed = Array.isArray(drillingHoles) && drillingHoles.length > 0 ? drillingHoles : [2, 3, 4, 5];
            
            var currentVal = holesCountSelect.value;
            holesCountSelect.innerHTML = '';

            allowed.forEach(function (count) {
                var opt = document.createElement('option');
                opt.value = count;
                var cost = count * pricePerHinge;
                opt.textContent = count + ' hinge holes (+' + formatMoney(cost) + ')';
                holesCountSelect.appendChild(opt);
            });

            if (allowed.indexOf(parseInt(currentVal, 10)) !== -1) {
                holesCountSelect.value = currentVal;
            } else if (allowed.length > 0) {
                holesCountSelect.value = allowed[0];
            }
        }

        /**
         * Switches the active variation (Door / End Panel / Drawer Front) and refreshes all UI elements.
         */
        function applyVariation(variationKey) {
            if (!activeProduct) return;
            activeVariationKey = variationKey;

            var variation = getHingedDoorVariationData(activeProduct, activeVariationKey);
            if (!variation) return;

            // 1. Update Product Code & Titles
            var baseCode = (activeProduct.general && activeProduct.general.title) ? activeProduct.general.title : 'SHAKER-1';
            var cleanCode = baseCode.replace(/-(D|EP|DF)$/i, '');
            var fullCode = cleanCode + (variation.code_suffix || '-D');
            var subtitle = activeProduct.general ? activeProduct.general.subtitle : '';
            var fullSub = variation.name + (subtitle ? ' – ' + subtitle : '');

            if (productCodeEl) productCodeEl.textContent = fullCode;
            if (productSubtitleEl) productSubtitleEl.textContent = fullSub;
            if (specStyleNameEl) specStyleNameEl.textContent = 'Shaker-style ' + variation.name.toLowerCase();

            // 2. Validate & Clamp Dimensions
            var dims = validateVariationDimensions(variation, heightInput ? heightInput.value : 0, widthInput ? widthInput.value : 0);

            if (heightInput) {
                heightInput.min = dims.minH;
                heightInput.max = dims.maxH;
                heightInput.placeholder = 'e.g. ' + dims.minH;
                heightInput.value = dims.height;
            }
            var hHint = document.getElementById('hdDoorHeightHint');
            if (hHint) {
                hHint.textContent = 'Min: ' + dims.minH + 'mm, Max: ' + dims.maxH + 'mm';
            }

            if (widthInput) {
                widthInput.min = dims.minW;
                widthInput.max = dims.maxW;
                widthInput.placeholder = 'e.g. ' + dims.minW;
                widthInput.value = dims.width;
            }
            var wHint = document.getElementById('hdDoorWidthHint');
            if (wHint) {
                wHint.textContent = 'Min: ' + dims.minW + 'mm, Max: ' + dims.maxW + 'mm';
            }

            // 3. Dynamic Drilling Options
            var pricePerHinge = activeProduct.general ? activeProduct.general.price_per_hinge_hole : 3.00;
            var canDrill = isDrillingAvailable(variation);

            if (canDrill) {
                if (drillingAvailWrap) drillingAvailWrap.style.display = 'block';
                if (drillingUnavailNotice) drillingUnavailNotice.style.display = 'none';
                updateHolesCountOptions(variation.drilling_holes, pricePerHinge);
            } else {
                if (drillingAvailWrap) drillingAvailWrap.style.display = 'none';
                if (drillingUnavailNotice) drillingUnavailNotice.style.display = 'block';
            }

            // 4. Dynamic Finish Surcharges
            var primerPrice = variation.finish ? variation.finish.primer : 18.50;
            var paintedPrice = variation.finish ? variation.finish.painted : 38.00;

            var finishPricePrimedEl = document.getElementById('hdFinishPricePrimed');
            var finishPricePaintedEl = document.getElementById('hdFinishPricePainted');
            if (finishPricePrimedEl) finishPricePrimedEl.textContent = '+' + formatMoney(primerPrice);
            if (finishPricePaintedEl) finishPricePaintedEl.textContent = '+' + formatMoney(paintedPrice);

            // 5. Dedicated Variation Gallery & Thumbnails
            var fallbackThumb = (activeProduct.general && activeProduct.general.thumbnail_url) ? activeProduct.general.thumbnail_url : '';
            var gallery = getVariationGallery(variation, fallbackThumb);

            if (mainImg) {
                mainImg.src = gallery.image_1 || fallbackThumb;
                mainImg.alt = fullCode;
            }

            var t0 = document.getElementById('hdThumb0');
            var t1 = document.getElementById('hdThumb1');
            var t2 = document.getElementById('hdThumb2');
            if (t0) t0.src = gallery.image_1 || fallbackThumb;
            if (t1) t1.src = gallery.image_2 || fallbackThumb;
            if (t2) t2.src = gallery.image_3 || fallbackThumb;

            thumbs.forEach(function (t, i) {
                t.classList.toggle('active', i === 0);
            });

            calculateTotal();
        }

        /**
         * Opens the configurator modal for a clicked product card.
         */
        function openConfigurator(doorCard) {
            if (!doorCard || !configModal) return;

            // Parse complete structured JSON data from card attribute
            var jsonDataStr = doorCard.getAttribute('data-door-json');
            if (jsonDataStr) {
                try {
                    activeProduct = JSON.parse(jsonDataStr);
                } catch (err) {
                    activeProduct = null;
                }
            }

            // Fallback object parsing if JSON was not available
            if (!activeProduct) {
                var title = doorCard.getAttribute('data-door-title') || 'SHAKER-1';
                var priceFrom = parseFloat(doorCard.getAttribute('data-door-price')) || 41.50;
                var img = doorCard.getAttribute('data-door-img') || '';

                activeProduct = {
                    id: doorCard.getAttribute('data-door-id'),
                    title: title,
                    general: {
                        title: title,
                        subtitle: doorCard.getAttribute('data-door-subtitle') || '',
                        description: doorCard.getAttribute('data-door-desc') || '',
                        price_from: priceFrom,
                        price_per_hinge_hole: parseFloat(doorCard.getAttribute('data-price-hinge')) || 3.00,
                        spec_material: doorCard.getAttribute('data-spec-material') || 'Moisture-resistant MDF',
                        spec_build: doorCard.getAttribute('data-spec-build') || 'Mortise and tenon',
                        spec_overall_thickness: doorCard.getAttribute('data-spec-overall-thick') || '21mm',
                        spec_panel_thickness: doorCard.getAttribute('data-spec-panel-thick') || '9mm',
                        spec_finish: doorCard.getAttribute('data-spec-finish') || 'can be supplied raw, primed or painted',
                        spec_disclaimer: doorCard.getAttribute('data-spec-disclaimer') || 'No fixings are supplied; they are to be purchased separately.',
                        thumbnail_url: img
                    },
                    variations: {
                        door: {
                            key: 'door',
                            name: 'Door',
                            code_suffix: '-D',
                            starting_price: parseFloat(doorCard.getAttribute('data-price-door')) || priceFrom,
                            min_height: parseInt(doorCard.getAttribute('data-door-min-h'), 10) || 250,
                            max_height: parseInt(doorCard.getAttribute('data-door-max-h'), 10) || 2800,
                            min_width: parseInt(doorCard.getAttribute('data-door-min-w'), 10) || 200,
                            max_width: parseInt(doorCard.getAttribute('data-door-max-w'), 10) || 1200,
                            drilling_available: doorCard.getAttribute('data-door-drilling') !== '0',
                            drilling_holes: [2, 3, 4, 5],
                            finish: {
                                raw: 0,
                                primer: parseFloat(doorCard.getAttribute('data-door-primer')) || 18.50,
                                painted: parseFloat(doorCard.getAttribute('data-door-painted')) || 38.00
                            },
                            gallery: {
                                quick_view: doorCard.getAttribute('data-door-zoom') || img,
                                image_1: doorCard.getAttribute('data-gallery-1') || img,
                                image_2: doorCard.getAttribute('data-gallery-2') || img,
                                image_3: doorCard.getAttribute('data-gallery-3') || img
                            }
                        },
                        end_panel: {
                            key: 'end_panel',
                            name: 'End Panel',
                            code_suffix: '-EP',
                            starting_price: parseFloat(doorCard.getAttribute('data-price-ep')) || 48.00,
                            min_height: parseInt(doorCard.getAttribute('data-ep-min-h'), 10) || 300,
                            max_height: parseInt(doorCard.getAttribute('data-ep-max-h'), 10) || 3000,
                            min_width: parseInt(doorCard.getAttribute('data-ep-min-w'), 10) || 200,
                            max_width: parseInt(doorCard.getAttribute('data-ep-max-w'), 10) || 1500,
                            drilling_available: doorCard.getAttribute('data-ep-drilling') === '1',
                            drilling_holes: [2, 3, 4, 5],
                            finish: {
                                raw: 0,
                                primer: parseFloat(doorCard.getAttribute('data-ep-primer')) || 22.50,
                                painted: parseFloat(doorCard.getAttribute('data-ep-painted')) || 45.00
                            },
                            gallery: {
                                quick_view: doorCard.getAttribute('data-door-zoom') || img,
                                image_1: doorCard.getAttribute('data-gallery-1') || img,
                                image_2: doorCard.getAttribute('data-gallery-2') || img,
                                image_3: doorCard.getAttribute('data-gallery-3') || img
                            }
                        },
                        drawer_front: {
                            key: 'drawer_front',
                            name: 'Drawer Front',
                            code_suffix: '-DF',
                            starting_price: parseFloat(doorCard.getAttribute('data-price-df')) || 28.00,
                            min_height: parseInt(doorCard.getAttribute('data-df-min-h'), 10) || 100,
                            max_height: parseInt(doorCard.getAttribute('data-df-max-h'), 10) || 450,
                            min_width: parseInt(doorCard.getAttribute('data-df-min-w'), 10) || 150,
                            max_width: parseInt(doorCard.getAttribute('data-df-max-w'), 10) || 1200,
                            drilling_available: doorCard.getAttribute('data-df-drilling') === '1',
                            drilling_holes: [2, 3, 4, 5],
                            finish: {
                                raw: 0,
                                primer: parseFloat(doorCard.getAttribute('data-df-primer')) || 12.00,
                                painted: parseFloat(doorCard.getAttribute('data-df-painted')) || 25.00
                            },
                            gallery: {
                                quick_view: doorCard.getAttribute('data-door-zoom') || img,
                                image_1: doorCard.getAttribute('data-gallery-1') || img,
                                image_2: doorCard.getAttribute('data-gallery-2') || img,
                                image_3: doorCard.getAttribute('data-gallery-3') || img
                            }
                        }
                    }
                };
            }

            // Populate Specs Tab
            var gen = activeProduct.general || {};
            if (productDescEl) productDescEl.textContent = gen.description || '';

            var specMaterialEl = document.getElementById('hdSpecMaterial');
            var specBuildEl = document.getElementById('hdSpecBuild');
            var specOverallThickEl = document.getElementById('hdSpecOverallThick');
            var specPanelThickEl = document.getElementById('hdSpecPanelThick');
            var specFinishEl = document.getElementById('hdSpecFinish');
            var specDisclaimerEl = document.getElementById('hdSpecDisclaimer');

            if (specMaterialEl) specMaterialEl.textContent = 'Material: ' + (gen.spec_material || 'Moisture-resistant MDF');
            if (specBuildEl) specBuildEl.textContent = 'Build: ' + (gen.spec_build || 'Mortise and tenon');
            if (specOverallThickEl) specOverallThickEl.textContent = 'Overall thickness: ' + (gen.spec_overall_thickness || '21mm');
            if (specPanelThickEl) specPanelThickEl.textContent = 'Panel thickness: ' + (gen.spec_panel_thickness || '9mm');
            if (specFinishEl) specFinishEl.textContent = 'Finish: ' + (gen.spec_finish || 'can be supplied raw, primed or painted');
            if (specDisclaimerEl) specDisclaimerEl.textContent = gen.spec_disclaimer || 'No fixings are supplied; they are to be purchased separately.';

            // Reset Quantity
            if (qtyInput) qtyInput.value = '1';
            activeQty = 1;

            // Reset Variation Selection (Default: Door)
            activeVariationKey = 'door';
            productTypeOptions.forEach(function (opt) {
                var radio = opt.querySelector('input[type="radio"]');
                if (radio && radio.value === 'door') {
                    opt.classList.add('selected');
                    radio.checked = true;
                } else {
                    opt.classList.remove('selected');
                    if (radio) radio.checked = false;
                }
            });

            // Reset Drilling (Default: No)
            activeHasDrilling = false;
            drillingToggleBtns.forEach(function (b) {
                b.classList.toggle('active', b.getAttribute('data-val') === 'no');
            });
            if (drillingFields) drillingFields.style.display = 'none';

            // Reset Finish (Default: Raw)
            activeFinishType = 'raw';
            finishCards.forEach(function (c) {
                var radio = c.querySelector('input[type="radio"]');
                var isRaw = radio && radio.value === 'raw';
                c.classList.toggle('selected', isRaw);
                if (radio) radio.checked = isRaw;
            });
            if (paintFields) paintFields.style.display = 'none';

            // Apply selected variation
            applyVariation('door');

            // Switch to Details tab
            switchWizardTab('details');

            // Open Modal
            configModal.classList.add('is-open');
            configModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Closes the configurator modal.
         */
        function closeConfigurator() {
            if (!configModal) return;
            configModal.classList.remove('is-open');
            configModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        /**
         * Switches the active wizard tab in the right pane.
         */
        function switchWizardTab(tabKey) {
            wizardTabs.forEach(function (t) {
                var isCurrent = t.getAttribute('data-tab') === tabKey;
                t.classList.toggle('active', isCurrent);
            });

            tabPanes.forEach(function (pane) {
                var isCurrent = pane.id === 'hdTab' + tabKey.charAt(0).toUpperCase() + tabKey.slice(1);
                pane.classList.toggle('active', isCurrent);
            });
        }

        // =========================================================================
        // EVENT LISTENERS
        // =========================================================================

        // 1. "View details & buy" CTA button on catalogue cards
        var viewDetailsBtns = document.querySelectorAll('.hd-view-details-btn');
        viewDetailsBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var card = this.closest('.hd-door-card');
                openConfigurator(card);
            });
        });

        // 2. Close modal triggers
        if (configCloseBtn) {
            configCloseBtn.addEventListener('click', function (e) {
                e.preventDefault();
                closeConfigurator();
            });
        }

        if (configModal) {
            configModal.addEventListener('click', function (e) {
                if (e.target === configModal) {
                    closeConfigurator();
                }
            });
        }

        // 3. Wizard Tab clicks
        wizardTabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var tabKey = this.getAttribute('data-tab');
                switchWizardTab(tabKey);
            });
        });

        // 4. Thumbnail image switcher
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                thumbs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');
                var img = this.querySelector('img');
                if (img && mainImg) {
                    mainImg.src = img.src;
                }
            });
        });

        // 5. Product Type Radio Selector (Door, End Panel, Drawer Front)
        productTypeOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                productTypeOptions.forEach(function (opt) { opt.classList.remove('selected'); });
                this.classList.add('selected');

                var radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    applyVariation(radio.value);
                }
            });
        });

        // 6. Quantity Inputs
        if (qtyMinus && qtyInput) {
            qtyMinus.addEventListener('click', function () {
                var val = parseInt(qtyInput.value, 10) || 1;
                if (val > 1) {
                    qtyInput.value = val - 1;
                    calculateTotal();
                }
            });
        }

        if (qtyPlus && qtyInput) {
            qtyPlus.addEventListener('click', function () {
                var val = parseInt(qtyInput.value, 10) || 1;
                qtyInput.value = val + 1;
                calculateTotal();
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', calculateTotal);
        }

        // 7. Drilling Toggle Buttons
        drillingToggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                drillingToggleBtns.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');

                var val = this.getAttribute('data-val');
                activeHasDrilling = (val === 'yes');

                if (drillingFields) {
                    drillingFields.style.display = activeHasDrilling ? 'block' : 'none';
                }

                calculateTotal();
            });
        });

        if (holesCountSelect) {
            holesCountSelect.addEventListener('change', calculateTotal);
        }

        // 8. Finish Option Cards
        finishCards.forEach(function (card) {
            card.addEventListener('click', function () {
                finishCards.forEach(function (c) { c.classList.remove('selected'); });
                this.classList.add('selected');

                var radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    activeFinishType = radio.value;

                    if (paintFields) {
                        paintFields.style.display = (radio.value === 'painted') ? 'block' : 'none';
                    }

                    calculateTotal();
                }
            });
        });

        // 9. Add to basket CTA
        if (addToBasketBtn) {
            addToBasketBtn.addEventListener('click', function (e) {
                e.preventDefault();
                addToBasketBtn.textContent = 'Added to basket ✓';
                setTimeout(function () {
                    addToBasketBtn.textContent = 'Add to basket';
                    closeConfigurator();
                }, 1000);
            });
        }

        // 10. Lightbox Quick View / Zoom Lightbox
        var qvModal = document.getElementById('hdQuickViewModal');
        var qvCloseBtn = document.getElementById('hdQuickViewCloseBtn');
        var zoomBtns = document.querySelectorAll('.hd-zoom-btn');

        zoomBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var data = {
                    title: this.getAttribute('data-title'),
                    subtitle: this.getAttribute('data-subtitle'),
                    image: this.getAttribute('data-image'),
                    price: this.getAttribute('data-price'),
                    desc: this.getAttribute('data-desc')
                };

                if (qvModal) {
                    var qvImg = document.getElementById('hdQuickViewImage');
                    var qvTitle = document.getElementById('hdQuickViewTitle');
                    var qvSub = document.getElementById('hdQuickViewSubtitle');
                    var qvPrice = document.getElementById('hdQuickViewPrice');
                    var qvDesc = document.getElementById('hdQuickViewDesc');

                    if (qvImg) qvImg.src = data.image;
                    if (qvTitle) qvTitle.textContent = data.title;
                    if (qvSub) qvSub.textContent = data.subtitle;
                    if (qvPrice) qvPrice.innerHTML = data.price;
                    if (qvDesc) qvDesc.textContent = data.desc;

                    qvModal.classList.add('is-open');
                    qvModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        if (qvCloseBtn && qvModal) {
            qvCloseBtn.addEventListener('click', function () {
                qvModal.classList.remove('is-open');
                qvModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            });
        }

        // 11. Escape key to close modals
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeConfigurator();
                if (qvModal) {
                    qvModal.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
            }
        });

    });

})();
