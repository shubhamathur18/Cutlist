/**
 * Cutlist Sliding Wardrobe Doors Configurator
 * Logic for Step Tabs 1-5 (Opening size, Frame style, Door design, Panel design, Summary)
 */

(function () {
    'use strict';

    // Global feed injected via wp_localize_script
    var DATA = window.slidingDoorsData || {};
    var CURRENCY = DATA.currencySymbol || '£';
    var FRAMES = (DATA.frames && DATA.frames.length) ? DATA.frames : [
        {
            id: 'shaker', name: 'Shaker', code: 'shaker', basePrice: 68.00,
            description: 'Classic Shaker profile with clean borders and built-in finger pull.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Pure White', hex: '#f5f5f5', surcharge: 0.00 },
                { name: 'Cashmere', hex: '#d9d0c7', surcharge: 12.00 }
            ]
        },
        {
            id: 'palermo', name: 'Palermo', code: 'palermo', basePrice: 74.00,
            description: 'Soft curved ergonomic profile providing modern elegance and ease of glide.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Bronze', hex: '#6e5849', surcharge: 15.00 },
                { name: 'Brushed Brass', hex: '#c5a059', surcharge: 18.00 }
            ]
        },
        {
            id: 'supreme', name: 'Supreme', code: 'supreme', basePrice: 82.00,
            description: 'Sturdy architectural profile with deep recessed channel for large wardrobes.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Anthracite', hex: '#383e42', surcharge: 10.00 },
                { name: 'Pure White', hex: '#f5f5f5', surcharge: 0.00 }
            ]
        },
        {
            id: 'prestige', name: 'Prestige', code: 'prestige', basePrice: 88.00,
            description: 'Sculpted luxury profile with premium anodised and painted finishes.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Bronze', hex: '#6e5849', surcharge: 15.00 },
                { name: 'Brushed Brass', hex: '#c5a059', surcharge: 18.00 }
            ]
        },
        {
            id: 'slim', name: 'Slim', code: 'slim', basePrice: 78.00,
            description: 'Minimalist slimline edge maximizing the visible surface of glass and decorative wood decors.',
            colors: [
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Anthracite', hex: '#383e42', surcharge: 10.00 },
                { name: 'Pure White', hex: '#f5f5f5', surcharge: 0.00 }
            ]
        }
    ];

    var DESIGNS = (DATA.designs && DATA.designs.length) ? DATA.designs : [
        { id: '1-panel', name: 'Single panel', panels: 1, orientation: 'horizontal', subtitle: '', hBarPrice: 0.00 },
        { id: '2-panel', name: 'Two panels', panels: 2, orientation: 'both', subtitle: 'Horizontal or Vertical', hBarPrice: 16.00 },
        { id: '3-panel', name: 'Three panels', panels: 3, orientation: 'both', subtitle: 'Horizontal or Vertical', hBarPrice: 32.00 },
        { id: '4-panel', name: 'Four panels', panels: 4, orientation: 'horizontal', subtitle: '', hBarPrice: 48.00 },
        { id: '5-panel', name: 'Five panels', panels: 5, orientation: 'horizontal', subtitle: '', hBarPrice: 64.00 }
    ];

    var GLASS_LIST = (DATA.glass && DATA.glass.length) ? DATA.glass : [
        { id: 'silver-mirror', name: 'Silver Mirror', category: 'mirrors', pricePerSqm: 30.00, swatchColor: '#dbe4eb', thickness: '4mm safety backed' },
        { id: 'grey-mirror', name: 'Grey Mirror', category: 'mirrors', pricePerSqm: 35.00, swatchColor: '#5c646b', thickness: '4mm safety backed' },
        { id: 'bronze-mirror', name: 'Bronze Mirror', category: 'mirrors', pricePerSqm: 38.00, swatchColor: '#9e7358', thickness: '4mm safety backed' },
        { id: 'pure-white', name: 'Pure White', category: 'colours', pricePerSqm: 45.00, swatchColor: '#ffffff', thickness: '4mm safety backed' },
        { id: 'soft-beige', name: 'Soft Beige', category: 'colours', pricePerSqm: 45.00, swatchColor: '#f2ecdf', thickness: '4mm safety backed' },
        { id: 'light-grey', name: 'Light Grey', category: 'colours', pricePerSqm: 45.00, swatchColor: '#e2e8f0', thickness: '4mm safety backed' },
        { id: 'stone-grey', name: 'Stone Grey', category: 'colours', pricePerSqm: 48.00, swatchColor: '#b3bcc5', thickness: '4mm safety backed' },
        { id: 'denim-blue', name: 'Denim Blue', category: 'colours', pricePerSqm: 48.00, swatchColor: '#5b7891', thickness: '4mm safety backed' },
        { id: 'dusty-lavender', name: 'Dusty Lavender', category: 'colours', pricePerSqm: 48.00, swatchColor: '#8e8494', thickness: '4mm safety backed' },
        { id: 'aubergine-gloss', name: 'Aubergine Gloss', category: 'premium_colours', pricePerSqm: 58.00, swatchColor: '#3b1029', thickness: '4mm safety backed' },
        { id: 'deep-charcoal-gloss', name: 'Deep Charcoal Gloss', category: 'premium_colours', pricePerSqm: 58.00, swatchColor: '#21262d', thickness: '4mm safety backed' },
        { id: 'sparkle-black-metallic', name: 'Sparkle Black Metallic', category: 'premium_colours', pricePerSqm: 65.00, swatchColor: '#0d1117', thickness: '4mm safety backed' }
    ];

    var BOARDS = DATA.boards || [];
    var PRICING = DATA.pricing || {
        trackPerMeter: 42.00,
        softClosePair: 34.00,
        mfcBasePerSqm: 28.00,
        minWidth: 1000,
        maxWidth: 5500,
        minHeight: 1000,
        maxHeight: 3000,
        vatRate: 0.20
    };

    // SVGs for clean UI elements
    var ICONS = {
        doorHeader: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/><line x1="7.5" y1="10" x2="7.5" y2="14"/><line x1="16.5" y1="10" x2="16.5" y2="14"/></svg>',
        chevronUp: '<svg class="sd-chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>',
        widthArrow: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 8 3 12 7 16"/><polyline points="17 8 21 12 17 16"/><line x1="3" y1="12" x2="21" y2="12"/></svg>',
        heightArrow: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="18" y2="3"/><line x1="6" y1="21" x2="18" y2="21"/><line x1="12" y1="3" x2="12" y2="21"/><polyline points="9 6 12 3 15 6"/><polyline points="9 18 12 21 15 18"/></svg>',
        doorSquare: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="1.5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>',
        selectArrow: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
        measuringRuler: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3l-6.6-6.6a1 1 0 0 0-1.4 0l-1.4 1.4-1.4-1.4 1.4-1.4a1 1 0 0 0 0-1.4L10.5 4.5a1 1 0 0 0-1.4 0L2.7 10.9a1 1 0 0 0 0 1.4l6.6 6.6a1 1 0 0 0 1.4 0l1.4-1.4 1.4 1.4-1.4 1.4a1 1 0 0 0 0 1.4l1.4 1.4a1 1 0 0 0 1.4 0l6.4-6.4a1 1 0 0 0 0-1.4z"/></svg>',
        installDoc: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        switchArrows: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 4 4 8 8 12"/><polyline points="16 12 20 16 16 20"/><line x1="4" y1="8" x2="16" y2="8"/><line x1="8" y1="16" x2="20" y2="16"/></svg>',
        trashCan: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
        plusIcon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        cartIcon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>'
    };

    var SD_MAX_DOORS = 9;
    var sdSetCount = 0;
    var sdAnimState = {};
    var sdSetStates = {}; // key = setId -> state object
    var activeModalSetId = null;
    var tempModalSelection = {};

    var activeFrameModalSetId = null;
    var tempFrameStyle = null;
    var tempFrameColor = null;
    var tempActiveThumbIdx = 0;

    var activeDoorDesignModalSetId = null;
    var tempDoorDesign = null;
    var tempDoorOrientation = 'horizontal';
    var tempCustomSplits = null;

    var container = document.getElementById('sdSetsContainer');
    if (!container) return;

    /**
     * Get frame icon SVG by code
     */
    function getFrameIconSVG(code) {
        switch (code) {
            case 'shaker':
                return '<svg viewBox="0 0 60 70" fill="none"><rect x="12" y="5" width="36" height="60" rx="1" stroke="#475569" stroke-width="3" fill="#f8fafc"/><rect x="18" y="10" width="24" height="50" fill="#e2e8f0"/><line x1="38" y1="22" x2="38" y2="48" stroke="#475569" stroke-width="2.5" stroke-linecap="round"/></svg>';
            case 'palermo':
                return '<svg viewBox="0 0 60 70" fill="none"><path d="M14 5 C24 5, 46 15, 46 35 C46 55, 24 65, 14 65 Z" stroke="#475569" stroke-width="3" fill="#f8fafc"/><path d="M20 12 C28 12, 38 20, 38 35 C38 50, 28 58, 20 58 Z" fill="#e2e8f0"/></svg>';
            case 'supreme':
                return '<svg viewBox="0 0 60 70" fill="none"><rect x="10" y="5" width="40" height="60" rx="2" stroke="#475569" stroke-width="3.5" fill="#f8fafc"/><rect x="18" y="12" width="24" height="46" stroke="#64748b" stroke-width="2" fill="#e2e8f0"/><line x1="30" y1="12" x2="30" y2="58" stroke="#475569" stroke-width="2"/></svg>';
            case 'prestige':
                return '<svg viewBox="0 0 60 70" fill="none"><rect x="12" y="5" width="36" height="60" rx="3" stroke="#475569" stroke-width="3" fill="#f8fafc"/><path d="M18 12 L36 18 L36 52 L18 58 Z" fill="#e2e8f0"/><line x1="36" y1="18" x2="36" y2="52" stroke="#475569" stroke-width="2.5"/></svg>';
            default: // slim
                return '<svg viewBox="0 0 60 70" fill="none"><rect x="16" y="5" width="28" height="60" rx="1" stroke="#475569" stroke-width="2" fill="#f8fafc"/><rect x="20" y="8" width="20" height="54" fill="#e2e8f0"/><line x1="32" y1="20" x2="32" y2="50" stroke="#475569" stroke-width="1.5"/></svg>';
        }
    }

    /**
     * Get Door Design SVG graphic matching Screenshot 1
     */
    function getDesignSVG(panels, orientation) {
        var base = '<rect x="6" y="6" width="68" height="108" stroke="#1e293b" stroke-width="3.5" fill="#ffffff"/>';
        if (panels === 1) {
            return '<svg viewBox="0 0 80 120" fill="none">' + base + '</svg>';
        } else if (panels === 2) {
            if (orientation === 'vertical') {
                return '<svg viewBox="0 0 80 120" fill="none">' + base + '<line x1="40" y1="6" x2="40" y2="114" stroke="#1e293b" stroke-width="2.5"/></svg>';
            }
            return '<svg viewBox="0 0 80 120" fill="none">' + base + '<line x1="6" y1="60" x2="74" y2="60" stroke="#1e293b" stroke-width="2.5"/></svg>';
        } else if (panels === 3) {
            if (orientation === 'vertical') {
                return '<svg viewBox="0 0 80 120" fill="none">' + base + '<line x1="28" y1="6" x2="28" y2="114" stroke="#1e293b" stroke-width="2.5"/><line x1="52" y1="6" x2="52" y2="114" stroke="#1e293b" stroke-width="2.5"/></svg>';
            }
            return '<svg viewBox="0 0 80 120" fill="none">' + base + '<line x1="6" y1="42" x2="74" y2="42" stroke="#1e293b" stroke-width="2.5"/><line x1="6" y1="78" x2="74" y2="78" stroke="#1e293b" stroke-width="2.5"/></svg>';
        } else if (panels === 4) {
            return '<svg viewBox="0 0 80 120" fill="none">' + base + '<line x1="6" y1="33" x2="74" y2="33" stroke="#1e293b" stroke-width="2.5"/><line x1="6" y1="60" x2="74" y2="60" stroke="#1e293b" stroke-width="2.5"/><line x1="6" y1="87" x2="74" y2="87" stroke="#1e293b" stroke-width="2.5"/></svg>';
        } else if (panels === 5) {
            return '<svg viewBox="0 0 80 120" fill="none">' + base + '<line x1="6" y1="27.6" x2="74" y2="27.6" stroke="#1e293b" stroke-width="2.5"/><line x1="6" y1="49.2" x2="74" y2="49.2" stroke="#1e293b" stroke-width="2.5"/><line x1="6" y1="70.8" x2="74" y2="70.8" stroke="#1e293b" stroke-width="2.5"/><line x1="6" y1="92.4" x2="74" y2="92.4" stroke="#1e293b" stroke-width="2.5"/></svg>';
        }
        return '<svg viewBox="0 0 80 120" fill="none">' + base + '</svg>';
    }

    /**
     * Calculate allowed door count options based on opening width (in mm)
     */
    function sdDoorOptions(width) {
        var w = parseInt(width, 10) || 0;
        var maxDoors = w < 1480 ? 2 : 2 + Math.floor((w - 990) / 490);
        maxDoors = Math.min(maxDoors, SD_MAX_DOORS);
        var opts = [];
        for (var i = 2; i <= maxDoors; i++) {
            opts.push(i);
        }
        return opts;
    }

    /**
     * Build HTML structure for a single Sliding Doors Set card
     */
    function sdBuildSet(num) {
        var defaultFrame = FRAMES[0] || {};
        var defaultColor = (defaultFrame.colors && defaultFrame.colors[0]) || { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0 };
        var defaultDesign = DESIGNS[0] || { id: '1-panel', name: 'Single panel', panels: 1, orientation: 'horizontal', subtitle: '', hBarPrice: 0 };
        var defaultGlass = GLASS_LIST[0] || { id: 'silver-mirror', name: 'Silver Mirror', category: 'mirrors', pricePerSqm: 30.00, swatchColor: '#dbe4eb', thickness: '4mm safety backed' };
        var defaultBoard = (BOARDS && BOARDS.length) ? BOARDS[0] : { id: 0, title: 'Natural Halifax Oak', code: 'H1180 ST37', brand: 'Egger', price: 168.50 };

        sdSetStates[num] = {
            width: 3000,
            height: 3000,
            doorCount: 2,
            frameStyle: defaultFrame,
            frameColor: defaultColor,
            doorDesign: defaultDesign,
            orientation: 'horizontal',
            panelType: 'glass', // 'glass' or 'board'
            glassItem: defaultGlass,
            boardItem: defaultBoard,
            softClose: true,
            switched: 0,
            doorOrder: [0, 1]
        };

        var el = document.createElement('div');
        el.className = 'sd-set';
        el.dataset.setId = num;
        el.dataset.switched = '0';
        el.innerHTML =
            // Card Header
            '<div class="sd-set-title">' +
            '<div class="sd-set-title-left">' +
            '<span class="sd-set-icon">' + ICONS.doorHeader + '</span>' +
            '<h3>Sliding doors – Set <span class="sd-set-num">' + num + '</span></h3>' +
            '</div>' +
            '<div class="sd-set-collapse">' +
            '<span class="sd-collapse-text"></span>' +
            ICONS.chevronUp +
            '</div>' +
            '</div>' +

            '<div class="sd-set-content">' +

            // Step Navigation Bar (Chevron tabs with number badges)
            '<div class="sd-steps-wrap">' +
            '<div class="sd-steps">' +
            '<div class="sd-step current" data-step="0"><span class="sd-step-badge">1</span>Opening size</div>' +
            '<div class="sd-step enabled" data-step="1"><span class="sd-step-badge">2</span>Frame style</div>' +
            '<div class="sd-step enabled" data-step="2"><span class="sd-step-badge">3</span>Door design</div>' +
            '<div class="sd-step enabled" data-step="3"><span class="sd-step-badge">4</span>Panel design</div>' +
            '<div class="sd-step enabled sd-step-last" data-step="4"><span class="sd-step-badge">5</span>Summary</div>' +
            '</div>' +
            '</div>' +

            // Step Content Panels
            '<div class="sd-step-panels">' +

            // STEP 0: Opening size controls
            '<div class="sd-step-panel active" data-panel="0">' +
            '<div class="sd-opening-size">' +
            '<div class="sd-input-col">' +
            '<label>Opening width [mm]</label>' +
            '<div class="sd-input-wrap">' +
            '<span class="sd-field-icon">' + ICONS.widthArrow + '</span>' +
            '<input class="sd-text-input sd-width" type="text" inputmode="numeric" maxlength="4" value="3000" placeholder="Enter size" autocomplete="off">' +
            '<span class="sd-input-error-msg sd-width-err">Maximum opening width is 5500mm</span>' +
            '</div>' +
            '</div>' +

            '<div class="sd-input-col">' +
            '<label>Opening height [mm]</label>' +
            '<div class="sd-input-wrap">' +
            '<span class="sd-field-icon">' + ICONS.heightArrow + '</span>' +
            '<input class="sd-text-input sd-height" type="text" inputmode="numeric" maxlength="4" value="3000" placeholder="Enter size" autocomplete="off">' +
            '<span class="sd-input-error-msg sd-height-err">Maximum opening height is 3000mm</span>' +
            '</div>' +
            '</div>' +

            '<div class="sd-input-col">' +
            '<label>Door number</label>' +
            '<div class="Select2 Select2--has-arrow sd-door-select">' +
            '<div class="Select2__input-wrapper">' +
            '<span class="Select2__left-icon">' + ICONS.doorSquare + '</span>' +
            '<span class="Select2__input">2</span>' +
            '<span class="Select2__placeholder">Select</span>' +
            '<span class="Select2__arrow">' + ICONS.selectArrow + '</span>' +
            '</div>' +
            '<div class="Select2__dropdown"></div>' +
            '</div>' +
            '</div>' +

            '<div class="sd-doc-btns">' +
            '<button type="button" class="sd-doc-btn sd-measuring-guide">' + ICONS.measuringRuler + '<span>Measuring guide</span></button>' +
            '<button type="button" class="sd-doc-btn sd-install-guide">' + ICONS.installDoc + '<span>Installation guides</span></button>' +
            '</div>' +
            '</div>' +

            // Info Notice Banner (Only for Step 0 Opening Size)
            '<div class="sd-warning-bar-wrap">' +
            '<div class="sd-warning-bar">' +
            '<div class="sd-warning-bar-left">' +
            '<span class="sd-info-circle-icon">i</span>' +
            '<span>You can switch the doors to better fit your design.</span>' +
            '</div>' +
            '<button class="sd-reset-btn" type="button" style="display:none">&#8635; Reset</button>' +
            '</div>' +
            '</div>' +

            '</div>' + // .sd-step-panel[data-panel="0"]

            // STEP 1: Frame style panel
            '<div class="sd-step-panel" data-panel="1">' +
            '<div class="sd-frame-style-panel">' +
            '<div class="sd-frame-grid"></div>' +
            '</div>' +
            '</div>' +

            // STEP 2: Door design panel (Screenshot 1)
            '<div class="sd-step-panel" data-panel="2">' +
            '<div class="sd-door-design-panel">' +
            '<div class="sd-designs-grid"></div>' +
            '<div class="sd-orientation-toggle" style="display:none;">' +
            '<span style="font-size:13px; font-weight:600; color:#475569;">Panel Split Orientation:</span>' +
            '<button type="button" class="sd-orientation-btn active" data-orient="horizontal">Horizontal</button>' +
            '<button type="button" class="sd-orientation-btn" data-orient="vertical">Vertical</button>' +
            '</div>' +
            '</div>' +
            '</div>' +

            // STEP 3: Panel design panel (Screenshot 2)
            '<div class="sd-step-panel" data-panel="3">' +
            '<div class="sd-panel-design-step">' +
            '<div class="sd-panel-design-grid">' +

            // Coloured glass card
            '<div class="sd-panel-card active" data-type="glass">' +
            '<div class="sd-panel-swatches-grid">' +
            '<div class="sd-panel-mini-swatch" style="background:#ffffff;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#f2ecdf;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#dbe4eb;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#e2e8f0;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#b3bcc5;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#5b7891;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#8e8494;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#3b1029;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#21262d;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#0d1117;"></div>' +
            '</div>' +
            '<div class="sd-panel-card-footer">Coloured glass</div>' +
            '</div>' +

            // Decorative boards card
            '<div class="sd-panel-card" data-type="board">' +
            '<div class="sd-panel-swatches-grid">' +
            '<div class="sd-panel-mini-swatch" style="background:#b89778;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#8f8577;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#4a3b32;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#705335;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#22252a;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#d4d8db;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#e8dcc4;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#333538;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#9ea3a8;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#635952;"></div>' +
            '</div>' +
            '<div class="sd-panel-card-footer">Decorative boards</div>' +
            '</div>' +

            '</div>' +
            '</div>' +
            '</div>' +

            // STEP 4: Summary panel
            '<div class="sd-step-panel" data-panel="4">' +
            '<div class="sd-summary-panel">' +
            '<div class="sd-summary-grid">' +
            '<div class="sd-summary-spec-box">' +
            '<div class="sd-summary-spec-title">Specification Breakdown</div>' +
            '<div class="sd-summary-rows"></div>' +
            '</div>' +
            '<div class="sd-summary-pricing-box">' +
            '<div class="sd-summary-price-breakdown"></div>' +
            '<button type="button" class="sd-btn-add-cart">' + ICONS.cartIcon + '<span>Add to Basket</span></button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +

            '</div>' + // .sd-step-panels +

            // Canvas Render Stage
            '<div class="sd-render-wrap">' +
            '<div class="sd-render-box">' +
            '<div class="sd-render">' +
            '<canvas class="sd-canvas"></canvas>' +
            '<div class="sd-switch-container"></div>' +
            '</div>' +
            '</div>' +
            '</div>' +

            // Bottom Actions
            '<div class="sd-actions">' +
            '<button type="button" class="sd-btn-delete">' + ICONS.trashCan + '<span>Delete this set of doors</span></button>' +
            '<button type="button" class="sd-btn-add-set">' + ICONS.plusIcon + '<span>Add new set of doors</span></button>' +
            '</div>' +

            '</div>'; // .sd-set-content

        return el;
    }

    /**
     * Render Frame Styles Step 1 Cards and Colour Chips
     */
    function sdRenderFrameStyles(set) {
        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var grid = set.querySelector('.sd-frame-grid');
        if (!grid) return;

        grid.innerHTML = FRAMES.map(function (f) {
            var isActive = (st.frameStyle && st.frameStyle.code === f.code);
            var displayImg = (isActive && st.frameColor && st.frameColor.image) ? st.frameColor.image : (f.profileImage || f.featuredImage || '');
            var activePrice = (isActive && st.frameColor && st.frameColor.basePrice) ? st.frameColor.basePrice : (f.basePrice || 68.00);
            return '<div class="sd-frame-card ' + (isActive ? 'active' : '') + '" data-code="' + f.code + '">' +
                '<div class="sd-frame-card-icon">' + (displayImg ? '<img src="' + displayImg + '" style="max-height:100%;max-width:100%;object-fit:contain;">' : getFrameIconSVG(f.code)) + '</div>' +
                '<div class="sd-frame-card-title">' + f.name + '</div>' +
                '<div class="sd-frame-card-price">From ' + CURRENCY + activePrice.toFixed(2) + (isActive && st.frameColor ? ' &bull; ' + st.frameColor.name : '') + '</div>' +
                '<div class="sd-frame-card-badge">' + (isActive ? 'Selected (Change colour)' : 'Select profile & colours') + '</div>' +
                '</div>';
        }).join('');
    }

    /**
     * Render Door Designs Step 2 (Matching Screenshot 1)
     */
    function sdRenderDoorDesigns(set) {
        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var grid = set.querySelector('.sd-designs-grid');
        var orientWrap = set.querySelector('.sd-orientation-toggle');
        if (!grid) return;

        grid.innerHTML = DESIGNS.map(function (d) {
            var isActive = (st.doorDesign && st.doorDesign.panels === d.panels);
            var curOrient = (isActive && st.orientation) ? st.orientation : 'horizontal';
            return '<div class="sd-design-card ' + (isActive ? 'active' : '') + '" data-panels="' + d.panels + '">' +
                '<div class="sd-design-preview">' + getDesignSVG(d.panels, curOrient) + '</div>' +
                '<div class="sd-design-footer">' +
                '<div class="sd-design-title">' + d.name + '</div>' +
                (d.subtitle ? '<div class="sd-design-subtitle">' + d.subtitle + '</div>' : '') +
                '</div>' +
                '</div>';
        }).join('');

        if (orientWrap) {
            var isMulti = (st.doorDesign && (st.doorDesign.panels === 2 || st.doorDesign.panels === 3));
            orientWrap.style.display = isMulti ? 'flex' : 'none';
            var maxWardrobeHeightForVertical = 2557;
            var isVerticalAllowed = ((st.height || 2000) <= maxWardrobeHeightForVertical);

            if (!isVerticalAllowed && st.orientation === 'vertical') {
                st.orientation = 'horizontal';
            }

            orientWrap.querySelectorAll('.sd-orientation-btn').forEach(function (btn) {
                var isVertBtn = (btn.dataset.orient === 'vertical');
                var isBtnDisabled = (isVertBtn && !isVerticalAllowed);

                btn.classList.toggle('active', btn.dataset.orient === st.orientation);
                btn.classList.toggle('disabled', isBtnDisabled);
                btn.disabled = isBtnDisabled;

                var existingTip = btn.querySelector('.input-info-tooltip');
                if (existingTip) existingTip.remove();

                if (isBtnDisabled) {
                    var tip = document.createElement('div');
                    tip.className = 'input-info-tooltip';
                    tip.style.bottom = 'calc(100% + 6px)';
                    tip.style.width = '200px';
                    tip.innerHTML = 'The design is not possible.<br>                    Maximum panel height is 2440mm. To use this design, reduce the height of the wardrobe to ' + maxWardrobeHeightForVertical + 'mm.';
                    btn.appendChild(tip);
                }
            });
        }
    }

    /**
     * Render Panel Design Step 3 Selection
     */
    function sdRenderPanelDesign(set) {
        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var cards = set.querySelectorAll('.sd-panel-card');
        cards.forEach(function (card) {
            card.classList.toggle('active', card.dataset.type === st.panelType);
        });
    }

    /**
     * Render Summary Step 4 Breakdown and Pricing
     */
    function sdRenderSummary(set) {
        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var specBox = set.querySelector('.sd-summary-rows');
        var priceBox = set.querySelector('.sd-summary-price-breakdown');
        if (!specBox || !priceBox) return;

        var w = st.width;
        var h = st.height;
        var dc = st.doorCount;

        // Individual Door calculation
        var overlapMm = 25;
        var doorWidthMm = Math.round((w + (dc - 1) * overlapMm) / dc);
        var doorHeightMm = h - 40; // 40mm top track clearance
        var totalAreaSqm = (w / 1000) * (h / 1000);

        // Frame Pricing
        var frameBase = (st.frameStyle && st.frameStyle.basePrice) ? st.frameStyle.basePrice : 68.00;
        var frameSurcharge = (st.frameColor && st.frameColor.surcharge) ? st.frameColor.surcharge : 0.00;
        var totalFrameCost = dc * (frameBase + frameSurcharge);

        // Running Track Cost
        var trackCost = (w / 1000) * (PRICING.trackPerMeter || 42.00);

        // Divider H-Bars Cost
        var panelsCount = (st.doorDesign && st.doorDesign.panels) ? st.doorDesign.panels : 1;
        var hBarPrice = (st.doorDesign && st.doorDesign.hBarPrice) ? st.doorDesign.hBarPrice : 0;
        var dividersCost = dc * hBarPrice;

        // Infill Panels Cost
        var infillName = '';
        var infillCost = 0;
        if (st.panelType === 'glass') {
            var g = st.glassItem || GLASS_LIST[0];
            infillName = g.name + ' (' + g.category + ')';
            infillCost = totalAreaSqm * (g.pricePerSqm || 30.00);
        } else {
            var b = st.boardItem || BOARDS[0] || { title: 'Decorative MFC Board', brand: 'Egger' };
            infillName = b.title + ' [' + (b.brand || 'Egger') + ']';
            infillCost = totalAreaSqm * (PRICING.mfcBasePerSqm || 28.00);
        }

        // Soft Closers
        var softCloseCost = st.softClose ? (PRICING.softClosePair || 34.00) : 0;

        // Totals
        var subtotal = trackCost + totalFrameCost + dividersCost + infillCost + softCloseCost;
        var vat = subtotal * (PRICING.vatRate || 0.20);
        var totalIncVat = subtotal + vat;

        specBox.innerHTML =
            '<div class="sd-summary-row"><span>Opening Dimensions:</span><strong>' + w + 'mm (W) &times; ' + h + 'mm (H)</strong></div>' +
            '<div class="sd-summary-row"><span>Door Count & Size:</span><strong>' + dc + ' doors (' + doorWidthMm + 'mm &times; ' + doorHeightMm + 'mm)</strong></div>' +
            '<div class="sd-summary-row"><span>Frame Profile:</span><strong>' + (st.frameStyle.name || 'Shaker') + ' (' + (st.frameColor.name || 'Satin Silver') + ')</strong></div>' +
            '<div class="sd-summary-row"><span>Door Layout:</span><strong>' + (st.doorDesign.name || 'Single panel') + (st.doorDesign.panels > 1 ? ' [' + st.orientation + ']' : '') + '</strong></div>' +
            '<div class="sd-summary-row"><span>Infill Material:</span><strong>' + infillName + '</strong></div>' +
            '<div class="sd-summary-row"><span>Soft Close Dampers:</span><strong>' + (st.softClose ? 'Included (Pair)' : 'No') + '</strong></div>' +
            '<div class="sd-summary-row"><span>Running Tracks:</span><strong>Top &amp; Bottom Aluminium Tracks Included</strong></div>';

        priceBox.innerHTML =
            '<div class="sd-summary-price-row"><span>Aluminium Running Tracks:</span><span>' + CURRENCY + trackCost.toFixed(2) + '</span></div>' +
            '<div class="sd-summary-price-row"><span>Door Perimeter Frames (' + dc + 'x):</span><span>' + CURRENCY + totalFrameCost.toFixed(2) + '</span></div>' +
            (dividersCost > 0 ? '<div class="sd-summary-price-row"><span>Divider H-Bars:</span><span>' + CURRENCY + dividersCost.toFixed(2) + '</span></div>' : '') +
            '<div class="sd-summary-price-row"><span>Panel Infills (' + infillName + '):</span><span>' + CURRENCY + infillCost.toFixed(2) + '</span></div>' +
            (softCloseCost > 0 ? '<div class="sd-summary-price-row"><span>Soft Close Dampers:</span><span>' + CURRENCY + softCloseCost.toFixed(2) + '</span></div>' : '') +
            '<div class="sd-summary-price-row" style="margin-top:8px;font-weight:600;"><span>Subtotal (Net):</span><span>' + CURRENCY + subtotal.toFixed(2) + '</span></div>' +
            '<div class="sd-summary-price-row"><span>VAT (20%):</span><span>' + CURRENCY + vat.toFixed(2) + '</span></div>' +
            '<div class="sd-summary-price-row sd-summary-price-total"><span>Total (Inc. VAT):</span><span>' + CURRENCY + totalIncVat.toFixed(2) + '</span></div>';
    }

    /**
     * Draw 2D architectural representation on HTML5 Canvas
     */
    function sdDrawCanvas(set, opts) {
        var render = set.querySelector('.sd-render');
        var canvas = set.querySelector('.sd-canvas');
        var switchBtn = set.querySelector('.sd-switch-btn');
        var resetBtn = set.querySelector('.sd-reset-btn');

        if (!render || !canvas) return;

        var cw = render.offsetWidth || 800;
        var ch = render.offsetHeight || 520;
        canvas.width = cw;
        canvas.height = ch;
        var ctx = canvas.getContext('2d');

        var setId = set.dataset.setId;
        var st = sdSetStates[setId] || {};

        var doorCount = parseInt(set.querySelector('.sd-door-select .Select2__input').textContent, 10) || st.doorCount || 2;
        var switched = set.dataset.switched === '1';
        var xOffsets = opts && opts.xOffsets;
        var arrowAlpha = (opts && opts.arrowAlpha != null) ? opts.arrowAlpha : (switched ? 1 : 0);

        // Background Gradient
        var bgGrad = ctx.createLinearGradient(0, 0, 0, ch);
        bgGrad.addColorStop(0, '#f8fafc');
        bgGrad.addColorStop(0.7, '#f1f5f9');
        bgGrad.addColorStop(1, '#e2e8f0');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, cw, ch);

        var floorY = Math.round(ch * 0.84);

        // Floor Base line
        ctx.strokeStyle = '#94a3b8';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(0, floorY);
        ctx.lineTo(cw, floorY);
        ctx.stroke();

        // Below floor reflection
        var floorGrad = ctx.createLinearGradient(0, floorY, 0, ch);
        floorGrad.addColorStop(0, '#e2e8f0');
        floorGrad.addColorStop(1, '#cbd5e1');
        ctx.fillStyle = floorGrad;
        ctx.fillRect(0, floorY + 1.5, cw, ch - floorY - 1.5);

        if (!doorCount) {
            if (switchBtn) switchBtn.style.display = 'none';
            if (resetBtn) resetBtn.style.display = 'none';
            return;
        }

        // Proportioned to entered dimensions
        var entW = parseInt(set.querySelector('.sd-width').value, 10) || st.width || 3000;
        var entH = parseInt(set.querySelector('.sd-height').value, 10) || st.height || 3000;

        var maxFW = Math.round(cw * 0.85);
        var maxFH = Math.round(floorY * 0.90);
        var scale = (cw * 0.72) / 4000;
        var fwRaw = entW * scale;
        var fhRaw = entH * scale;

        if (fwRaw > maxFW || fhRaw > maxFH) {
            var sfW = maxFW / fwRaw, sfH = maxFH / fhRaw;
            var sf = sfW < sfH ? sfW : sfH;
            fwRaw *= sf;
            fhRaw *= sf;
        }

        var fw = Math.round(fwRaw);
        var fh = Math.round(fhRaw);
        var fx = Math.round((cw - fw) / 2);
        var fy = Math.max(24, floorY - fh); // sits on floor

        var railH = Math.max(12, Math.round(fh * 0.055));
        var btmH = Math.max(8, Math.round(fh * 0.04));
        var postW = Math.max(8, Math.round(fw * 0.032));
        var innerY = fy + railH;
        var innerH = fh - railH - btmH;

        // Custom Profile Colors
        var frameColorHex = (st.frameColor && st.frameColor.hex) ? st.frameColor.hex : '#8596a6';
        var frameFill = frameColorHex;
        var frameStroke = '#334155';
        var panelBorder = '#475569';

        // Panels inside frame
        var panelAreaX = fx + postW;
        var panelW = (fw - postW * 2) / doorCount;

        // Compute per-panel x positions
        var panelX = [];
        if (xOffsets) {
            panelX = xOffsets;
        } else {
            for (var i = 0; i < doorCount; i++) {
                panelX.push(Math.round(panelAreaX + i * panelW));
            }
        }

        var panelsCount = (st.doorDesign && st.doorDesign.panels) ? st.doorDesign.panels : 1;
        var orient = st.orientation || 'horizontal';

        // Draw door panels
        for (var pi = 0; pi < doorCount; pi++) {
            var px = panelX[pi];
            var pw = Math.ceil(panelW);

            // Infill Fill (Clean flat / uniform color without gradient shading or gloss overlay)
            if (st.panelType === 'glass') {
                var glassItem = st.glassItem || GLASS_LIST[0];
                var glassHex = glassItem.swatchColor || '#ffffff';
                ctx.fillStyle = glassHex;
                ctx.fillRect(px, innerY, pw, innerH);
            } else {
                // Decorative Board Fill
                var bItem = st.boardItem || BOARDS[0];
                ctx.fillStyle = (bItem && bItem.color) ? bItem.color : '#b89778';
                ctx.fillRect(px, innerY, pw, innerH);

                // Subtle plank lines
                ctx.strokeStyle = 'rgba(0, 0, 0, 0.08)';
                ctx.lineWidth = 1;
                for (var li = innerY + 15; li < innerY + innerH; li += 18) {
                    ctx.beginPath();
                    ctx.moveTo(px + 4, li);
                    ctx.lineTo(px + pw - 4, li);
                    ctx.stroke();
                }
            }

            // Draw Divider Splits
            if (panelsCount > 1) {
                ctx.strokeStyle = frameFill;
                ctx.lineWidth = Math.max(3, postW * 0.65);
                if (orient === 'vertical') {
                    var subW = pw / panelsCount;
                    for (var vi = 1; vi < panelsCount; vi++) {
                        var vx = Math.round(px + vi * subW);
                        ctx.beginPath();
                        ctx.moveTo(vx, innerY);
                        ctx.lineTo(vx, innerY + innerH);
                        ctx.stroke();
                    }
                } else {
                    var custom = (st.customSplits && st.doorDesign && st.customSplits[st.doorDesign.id]);
                    if (custom && custom.length === panelsCount) {
                        var totalCustom = custom.reduce(function (a, b) { return a + b; }, 0);
                        var curHAccum = 0;
                        for (var hi = 0; hi < panelsCount - 1; hi++) {
                            curHAccum += custom[hi];
                            var hy = Math.round(innerY + (curHAccum / totalCustom) * innerH);
                            ctx.beginPath();
                            ctx.moveTo(px, hy);
                            ctx.lineTo(px + pw, hy);
                            ctx.stroke();
                        }
                    } else {
                        var subH = innerH / panelsCount;
                        for (var hi = 1; hi < panelsCount; hi++) {
                            var hy = Math.round(innerY + hi * subH);
                            ctx.beginPath();
                            ctx.moveTo(px, hy);
                            ctx.lineTo(px + pw, hy);
                            ctx.stroke();
                        }
                    }
                }
            }

            // Inner panel frame stroke
            ctx.strokeStyle = panelBorder;
            ctx.lineWidth = 1.5;
            ctx.strokeRect(px, innerY, pw, innerH);
        }

        // Middle Vertical Stile(s) between adjacent doors in frame color
        var stileW = postW;
        for (var si = 0; si < doorCount - 1; si++) {
            var seamX;
            if (xOffsets && xOffsets[si] != null && xOffsets[si + 1] != null) {
                seamX = Math.round((xOffsets[si] + panelW + xOffsets[si + 1]) / 2);
            } else {
                seamX = Math.round(panelAreaX + (si + 1) * panelW);
            }
            var stileX = Math.round(seamX - stileW / 2);

            ctx.fillStyle = frameFill;
            ctx.fillRect(stileX, innerY, stileW, innerH);
            ctx.strokeStyle = frameStroke;
            ctx.lineWidth = 1.5;
            ctx.strokeRect(stileX + 0.5, innerY + 0.5, stileW - 1, innerH - 1);
        }

        // Outer Frame: Top Rail
        ctx.fillStyle = frameFill;
        ctx.fillRect(fx, fy, fw, railH);
        ctx.strokeStyle = frameStroke;
        ctx.lineWidth = 1.5;
        ctx.strokeRect(fx + 0.5, fy + 0.5, fw - 1, railH - 1);

        // Bottom Rail
        ctx.fillStyle = frameFill;
        ctx.fillRect(fx, fy + fh - btmH, fw, btmH);
        ctx.strokeStyle = frameStroke;
        ctx.strokeRect(fx + 0.5, fy + fh - btmH + 0.5, fw - 1, btmH - 1);

        // Left Post
        ctx.fillStyle = frameFill;
        ctx.fillRect(fx, innerY, postW, innerH);
        ctx.strokeStyle = frameStroke;
        ctx.strokeRect(fx + 0.5, innerY + 0.5, postW - 1, innerH - 1);

        // Right Post
        ctx.fillStyle = frameFill;
        ctx.fillRect(fx + fw - postW, innerY, postW, innerH);
        ctx.strokeStyle = frameStroke;
        ctx.strokeRect(fx + fw - postW + 0.5, innerY + 0.5, postW - 1, innerH - 1);

        // Directional chevrons during animated switch
        if (arrowAlpha > 0 && opts && opts.animActiveSeam != null) {
            var sz = Math.max(12, Math.min(22, panelW * 0.14));
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            var animD1 = opts.animActiveSeam;
            var animD2 = animD1 + 1;
            var targetDoors = [animD1, animD2];

            for (var idx = 0; idx < targetDoors.length; idx++) {
                var ai = targetDoors[idx];
                if (ai >= doorCount) continue;
                var acx = panelX[ai] + panelW / 2;
                var adir = (ai === animD1) ? 1 : -1;
                var acy = (ai % 2 === 0)
                    ? innerY + innerH * 0.70
                    : innerY + innerH * 0.30;
                ctx.save();
                ctx.globalAlpha = arrowAlpha * 0.9;
                ctx.strokeStyle = '#c2410c';
                ctx.lineWidth = Math.max(2, sz * 0.3);
                var sp = sz * 0.68;
                for (var k = 0; k < 2; k++) {
                    var ox = adir * k * sp;
                    ctx.beginPath();
                    ctx.moveTo(acx + ox - adir * sz * 0.45, acy - sz * 0.75);
                    ctx.lineTo(acx + ox + adir * sz * 0.45, acy);
                    ctx.lineTo(acx + ox - adir * sz * 0.45, acy + sz * 0.75);
                    ctx.stroke();
                }
                ctx.restore();
            }
        }

        // Top-view running track below floor
        var trkH = 8;
        var trkGap = 4;
        var trkPW = Math.round((fw - postW * 2) / doorCount);
        var trkY0 = floorY + 14;
        var trkY1 = trkY0 + trkH + trkGap;
        var trkLineColor = '#94a3b8';
        var trkBarFront = '#ffffff';
        var trkBarBack = '#cbd5e1';
        var trkBarStroke = '#64748b';

        if (!st.doorOrder || st.doorOrder.length !== doorCount) {
            st.doorOrder = [];
            for (var doi = 0; doi < doorCount; doi++) {
                st.doorOrder.push(doi);
            }
        }

        // Guide track continuous rails
        ctx.strokeStyle = trkLineColor;
        ctx.lineWidth = 1;
        var lineMidY0 = trkY0 + Math.floor(trkH / 2) + 0.5;
        var lineMidY1 = trkY1 + Math.floor(trkH / 2) + 0.5;

        ctx.beginPath();
        ctx.moveTo(fx, lineMidY0);
        ctx.lineTo(fx + fw, lineMidY0);
        ctx.moveTo(fx, lineMidY1);
        ctx.lineTo(fx + fw, lineMidY1);
        ctx.stroke();

        // Individual door runner bars based on slot door position
        for (var ti = 0; ti < doorCount; ti++) {
            var doorId = st.doorOrder[ti];
            var isBack = (doorId % 2 === 0);
            var trkX = panelX[ti];
            var trkY = isBack ? trkY0 : trkY1;
            if (opts && opts.trackYOffsets && opts.trackYOffsets[ti] != null) {
                trkY = opts.trackYOffsets[ti];
            }

            ctx.fillStyle = isBack ? trkBarBack : trkBarFront;
            ctx.fillRect(trkX, trkY, trkPW, trkH);
            ctx.strokeStyle = trkBarStroke;
            ctx.strokeRect(trkX + 0.5, trkY + 0.5, trkPW - 1, trkH - 1);
        }

        var isStep0 = set.querySelector('.sd-step[data-step="0"]') ? set.querySelector('.sd-step[data-step="0"]').classList.contains('current') : true;

        // Render Switch buttons on every vertical seam between doors (ONLY ON STEP 0: Opening size)
        var switchContainer = set.querySelector('.sd-switch-container');
        if (switchContainer) {
            if (!isStep0 || doorCount < 2) {
                switchContainer.innerHTML = '';
            } else {
                var doorCenterY = Math.round(fy + fh / 2);
                var seamCount = doorCount - 1;
                var btnsHtml = '';
                for (var si = 0; si < seamCount; si++) {
                    var seamX = Math.round(panelAreaX + (si + 1) * panelW);
                    var leftDoorId = st.doorOrder[si];
                    var rightDoorId = st.doorOrder[si + 1];
                    var leftTrack = (leftDoorId % 2 === 0) ? 1 : 0;
                    var rightTrack = (rightDoorId % 2 === 0) ? 1 : 0;
                    var isDisabled = (leftTrack === rightTrack);

                    btnsHtml += '<div class="sd-switch-wrap" style="position:absolute; top:' + doorCenterY + 'px; left:' + seamX + 'px; transform:translate(-50%, -50%); z-index:10;">' +
                        '<button type="button" class="sd-switch-btn' + (isDisabled ? ' disabled' : '') + '" data-seam="' + si + '"' + (isDisabled ? ' disabled' : '') + '>Switch</button>' +
                        '</div>';
                }
                switchContainer.innerHTML = btnsHtml;
            }
        }
        var isDefault = true;
        if (st.doorOrder && st.doorOrder.length === doorCount) {
            for (var cki = 0; cki < doorCount; cki++) {
                if (st.doorOrder[cki] !== cki) {
                    isDefault = false;
                    break;
                }
            }
        }
        var isSwitched = !isDefault;
        set.dataset.switched = isSwitched ? '1' : '0';
        st.switched = isSwitched ? 1 : 0;
        if (resetBtn) resetBtn.style.display = (isStep0 && isSwitched) ? 'inline-flex' : 'none';
    }

    /**
     * Navigate to step index
     */
    function sdGoToStep(set, stepNum) {
        set.querySelectorAll('.sd-step').forEach(function (s) {
            var n = parseInt(s.dataset.step, 10);
            s.classList.toggle('current', n === stepNum);
        });
        set.querySelectorAll('.sd-step-panel').forEach(function (p) {
            p.classList.toggle('active', parseInt(p.dataset.panel, 10) === stepNum);
        });

        if (stepNum === 1) sdRenderFrameStyles(set);
        if (stepNum === 2) sdRenderDoorDesigns(set);
        if (stepNum === 3) sdRenderPanelDesign(set);
        if (stepNum === 4) sdRenderSummary(set);

        sdDrawCanvas(set);
    }

    /**
     * Enable steps up to step number
     */
    function sdEnableSteps(set, upToStep) {
        set.querySelectorAll('.sd-step').forEach(function (s) {
            var n = parseInt(s.dataset.step, 10);
            s.classList.toggle('enabled', n <= upToStep);
            s.classList.toggle('disabled', n > upToStep);
        });
    }

    /**
     * Validate Opening Width (Min: 1000mm, Max: 5500mm)
     */
    function sdValidateWidth(set, isBlur) {
        var input = set.querySelector('.sd-width');
        var errEl = set.querySelector('.sd-width-err');
        var str = input.value.trim();
        var val = parseInt(str, 10);

        if (!str) {
            input.classList.remove('sd-input-error');
            if (errEl) errEl.classList.remove('visible');
            return false;
        }

        if (!isNaN(val) && val > 5500) {
            if (errEl) {
                errEl.textContent = 'Maximum opening width is 5500mm';
                errEl.classList.add('visible');
            }
            input.classList.add('sd-input-error');
            return false;
        }

        if ((isBlur || str.length >= 4) && !isNaN(val) && val < 1000) {
            if (errEl) {
                errEl.textContent = 'Minimum opening width is 1000mm';
                errEl.classList.add('visible');
            }
            input.classList.add('sd-input-error');
            return false;
        }

        if (!isNaN(val) && val >= 1000 && val <= 5500) {
            input.classList.remove('sd-input-error');
            if (errEl) errEl.classList.remove('visible');
            var setId = set.dataset.setId;
            if (sdSetStates[setId]) sdSetStates[setId].width = val;
            return true;
        }

        if (!isBlur && str.length < 4 && val < 1000) {
            input.classList.remove('sd-input-error');
            if (errEl) errEl.classList.remove('visible');
        }

        return false;
    }

    /**
     * Validate Opening Height (Min: 1000mm, Max: 3000mm)
     */
    function sdValidateHeight(set, isBlur) {
        var input = set.querySelector('.sd-height');
        var errEl = set.querySelector('.sd-height-err');
        var str = input.value.trim();
        var val = parseInt(str, 10);

        if (!str) {
            input.classList.remove('sd-input-error');
            if (errEl) errEl.classList.remove('visible');
            return false;
        }

        if (!isNaN(val) && val > 3000) {
            if (errEl) {
                errEl.textContent = 'Maximum opening height is 3000mm';
                errEl.classList.add('visible');
            }
            input.classList.add('sd-input-error');
            return false;
        }

        if ((isBlur || str.length >= 4) && !isNaN(val) && val < 1000) {
            if (errEl) {
                errEl.textContent = 'Minimum opening height is 1000mm';
                errEl.classList.add('visible');
            }
            input.classList.add('sd-input-error');
            return false;
        }

        if (!isNaN(val) && val >= 1000 && val <= 3000) {
            input.classList.remove('sd-input-error');
            if (errEl) errEl.classList.remove('visible');
            var setId = set.dataset.setId;
            if (sdSetStates[setId]) sdSetStates[setId].height = val;
            return true;
        }

        if (!isBlur && str.length < 4 && val < 1000) {
            input.classList.remove('sd-input-error');
            if (errEl) errEl.classList.remove('visible');
        }

        return false;
    }

    /**
     * Update dropdown options based on width
     */
    function sdUpdateDoorDropdown(set) {
        var wInput = set.querySelector('.sd-width');
        var hInput = set.querySelector('.sd-height');
        var sel = set.querySelector('.sd-door-select');

        var wVal = parseInt(wInput.value, 10);
        var hVal = parseInt(hInput.value, 10);

        var hasValidSize = !isNaN(wVal) && wVal >= 1000 && wVal <= 5500 &&
                           !isNaN(hVal) && hVal >= 1000 && hVal <= 3000;

        sel.classList.toggle('isDisabled', !hasValidSize);

        if (hasValidSize) {
            var opts = sdDoorOptions(wVal);
            sel.querySelector('.Select2__dropdown').innerHTML =
                opts.map(function (n) {
                    return '<div class="Select2__option" data-val="' + n + '">' + n + '</div>';
                }).join('');

            var curVal = parseInt(sel.querySelector('.Select2__input').textContent, 10);
            if (!curVal || opts.indexOf(curVal) === -1) {
                sel.querySelector('.Select2__input').textContent = opts[0];
                sel.classList.remove('isEmpty');
                var setId = set.dataset.setId;
                if (sdSetStates[setId]) sdSetStates[setId].doorCount = opts[0];
            }
            sdEnableSteps(set, 4); // allow all steps once size is entered
        } else {
            sdEnableSteps(set, 0);
        }
    }

    /**
     * Add a new set card
     */
    function sdAddSet() {
        sdSetCount++;
        var set = sdBuildSet(sdSetCount);
        container.appendChild(set);
        sdUpdateDoorDropdown(set);
        sdRenderFrameStyles(set);
        sdRenderDoorDesigns(set);
        sdRenderPanelDesign(set);
        requestAnimationFrame(function () {
            sdDrawCanvas(set);
        });
    }

    /**
     * Renumber sets sequentially
     */
    function sdRenumber() {
        container.querySelectorAll('.sd-set').forEach(function (set, i) {
            var numEl = set.querySelector('.sd-set-num');
            if (numEl) numEl.textContent = i + 1;
            set.dataset.setId = i + 1;
        });
    }

    // =========================================================================
    // MODALS (MATCHING SCREENSHOTS 3 & USER MOCKUP)
    // =========================================================================
    function createModals() {
        if (document.getElementById('sdGlassModalOverlay')) return;

        var modalHTML =
            // 1. Frame Style & Colours Modal (Matching Mockup)
            '<div class="sd-modal-overlay" id="sdFrameModalOverlay">' +
            '<div class="sd-modal-dialog sd-frame-modal-dialog">' +
            '<button type="button" class="sd-modal-close" id="sdFrameModalClose" aria-label="Close">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>' +
            '<div class="sd-frame-modal-layout">' +
            // Left: Gallery, Big Render Preview, Zoom, Thumbnails & Details
            '<div class="sd-frame-modal-left">' +
            '<div class="sd-frame-main-preview-box" id="sdFrameMainPreviewBox">' +
            '<button type="button" class="sd-frame-zoom-btn" id="sdFrameZoomBtn" title="Zoom">' +
            '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>' +
            '</button>' +
            '<img class="sd-frame-main-preview-img" id="sdFrameMainPreviewImg" src="" alt="Frame Profile">' +
            '</div>' +
            '<div class="sd-frame-thumbs-carousel">' +
            '<button type="button" class="sd-frame-thumb-nav sd-frame-thumb-prev" id="sdFrameThumbPrev" title="Previous"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>' +
            '<div class="sd-frame-thumbs-row" id="sdFrameThumbsRow"></div>' +
            '<button type="button" class="sd-frame-thumb-nav sd-frame-thumb-next" id="sdFrameThumbNext" title="Next"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>' +
            '</div>' +
            '<div class="sd-frame-modal-info">' +
            '<div class="sd-frame-modal-style-title" id="sdFrameModalStyleTitle">Shaker</div>' +
            '<div class="sd-frame-modal-style-color" id="sdFrameModalColorName">Cashmere</div>' +
            '</div>' +
            '</div>' +
            // Right: Category, Heading, Description, Available Colours, Green Notice, Cancel & Save buttons
            '<div class="sd-frame-modal-right">' +
            '<div>' +
            '<div class="sd-frame-modal-category">FRAME COLOURS</div>' +
            '<h2 class="sd-frame-modal-heading">Frame colours</h2>' +
            '<p class="sd-frame-modal-desc">' +
            'The profiles we use to construct sliding doors come in several different colours.<br>' +
            'We will supply top and bottom tracks in the same colour.' +
            '</p>' +
            '<div class="sd-frame-modal-divider"></div>' +
            '<div class="sd-frame-modal-subheading">Available colours</div>' +
            '<div class="sd-frame-colors-grid" id="sdFrameColorsGrid"></div>' +
            '<div class="sd-frame-modal-notice">' +
            '<span class="sd-frame-notice-icon">i</span>' +
            '<span>Top and bottom tracks will be supplied in the same colour as the selected frame.</span>' +
            '</div>' +
            '</div>' +
            '<div class="sd-frame-modal-footer">' +
            '<button type="button" class="sd-frame-modal-cancel-btn" id="sdFrameCancelBtn">Cancel</button>' +
            '<button type="button" class="sd-frame-modal-save-btn" id="sdFrameSaveBtn">Save</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +

            // 2. Glass Panels Modal (Screenshot 3)
            '<div class="sd-modal-overlay" id="sdGlassModalOverlay">' +
            '<div class="sd-modal-dialog">' +
            '<button type="button" class="sd-modal-close" id="sdGlassModalClose">&times;</button>' +
            '<div class="sd-modal-header">' +
            '<h3>Glass panels</h3>' +
            '<p class="sd-modal-intro">All our glass is 4mm backed with safety film meeting BS EN 12600 2B2 safety class. The biggest available single glass panel size is 2440mm in height.</p>' +
            '<div class="sd-modal-subhead">Glass colour</div>' +
            '<p class="sd-modal-subtitle">You can specify each panel\'s colour to suit your design. Glass is divided into several different categories and is priced per square meter.</p>' +
            '</div>' +

            '<div class="sd-modal-tabs">' +
            '<button type="button" class="sd-modal-tab-btn active" data-cat="mirrors">Mirrors</button>' +
            '<button type="button" class="sd-modal-tab-btn" data-cat="colours">Colours</button>' +
            '<button type="button" class="sd-modal-tab-btn" data-cat="premium_colours">Premium colours</button>' +
            '</div>' +

            '<div class="sd-modal-body-layout">' +
            '<div class="sd-modal-preview-card">' +
            '<div class="sd-modal-preview-img-box" id="sdGlassPreviewBox"></div>' +
            '<div class="sd-modal-preview-title" id="sdGlassPreviewTitle">Silver Mirror</div>' +
            '<div class="sd-modal-preview-price" id="sdGlassPreviewPrice">Square meter price: £30.00 plus VAT</div>' +
            '</div>' +
            '<div class="sd-modal-swatches-grid" id="sdGlassSwatchesGrid"></div>' +
            '</div>' +

            '<div class="sd-modal-footer">' +
            '<button type="button" class="sd-modal-save-btn" id="sdGlassSaveBtn">Save</button>' +
            '</div>' +
            '</div>' +
            '</div>' +

            // 3. Decorative Boards Modal
            '<div class="sd-modal-overlay" id="sdBoardModalOverlay">' +
            '<div class="sd-modal-dialog">' +
            '<button type="button" class="sd-modal-close" id="sdBoardModalClose">&times;</button>' +
            '<div class="sd-modal-header">' +
            '<h3>Decorative boards</h3>' +
            '<p class="sd-modal-intro">Choose from our premium catalogue of MFC decorative woodgrain and uni decors for your sliding door infill panels.</p>' +
            '<div class="sd-modal-subhead">Board decor</div>' +
            '<p class="sd-modal-subtitle">Select any decor from the catalogue. Infills are precision cut to fit within the aluminium perimeter frame.</p>' +
            '</div>' +

            '<div class="sd-modal-body-layout">' +
            '<div class="sd-modal-preview-card">' +
            '<div class="sd-modal-preview-img-box" id="sdBoardPreviewBox" style="background:#b89778;"></div>' +
            '<div class="sd-modal-preview-title" id="sdBoardPreviewTitle">Natural Halifax Oak</div>' +
            '<div class="sd-modal-preview-price" id="sdBoardPreviewPrice">Brand: Egger</div>' +
            '</div>' +
            '<div class="sd-modal-swatches-grid" id="sdBoardSwatchesGrid" style="max-height:260px;overflow-y:auto;"></div>' +
            '</div>' +

            '<div class="sd-modal-footer">' +
            '<button type="button" class="sd-modal-save-btn" id="sdBoardSaveBtn">Save</button>' +
            '</div>' +
            '</div>' +
            '</div>' +

            // 4. Door Design & Panel Size Modal (Matching User Mockup)
            '<div class="sd-modal-overlay" id="sdDoorDesignModalOverlay">' +
            '<div class="sd-modal-dialog sd-door-design-modal-dialog">' +
            '<button type="button" class="sd-modal-close" id="sdDoorDesignModalClose" aria-label="Close">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>' +
            '<div class="sd-door-design-modal-layout">' +
            // Left: Visual Door Preview & Dimension Badges
            '<div class="sd-door-design-modal-left">' +
            '<div class="sd-door-design-preview-container" id="sdDoorDesignPreviewContainer"></div>' +
            '</div>' +
            // Right: Door Size Info, Panel Size Rules, Orientation Cards, Save Button
            '<div class="sd-door-design-modal-right">' +
            '<div>' +
            '<div class="sd-design-sec">' +
            '<h3 class="sd-design-sec-title">Door size</h3>' +
            '<p class="sd-design-sec-desc">Calculated from your opening size (opening height – 40mm).</p>' +
            '<div class="sd-design-calc-cards">' +
            '<div class="sd-design-calc-card">' +
            '<div class="sd-calc-card-left">' +
            '<span class="sd-calc-icon">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="4" x2="12" y2="20"/><polyline points="8 8 12 4 16 8"/><polyline points="8 16 12 20 16 16"/><line x1="6" y1="4" x2="18" y2="4"/><line x1="6" y1="20" x2="18" y2="20"/></svg>' +
            '</span>' +
            '<span class="sd-calc-label">Door height</span>' +
            '</div>' +
            '<div class="sd-calc-card-right">' +
            '<span class="sd-calc-val" id="sdDoorDesignModalHeight">1960 mm</span>' +
            '<span class="sd-calc-sub">Calculated</span>' +
            '</div>' +
            '</div>' +
            '<div class="sd-design-calc-card">' +
            '<div class="sd-calc-card-left">' +
            '<span class="sd-calc-icon">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="8 8 4 12 8 16"/><polyline points="16 8 20 12 16 16"/><line x1="4" y1="6" x2="4" y2="18"/><line x1="20" y1="6" x2="20" y2="18"/></svg>' +
            '</span>' +
            '<span class="sd-calc-label">Door width</span>' +
            '</div>' +
            '<div class="sd-calc-card-right">' +
            '<span class="sd-calc-val" id="sdDoorDesignModalWidth">1013 mm</span>' +
            '<span class="sd-calc-sub">Calculated</span>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="sd-design-sec" style="margin-top:24px;">' +
            '<h3 class="sd-design-sec-title">Panel size</h3>' +
            '<p class="sd-design-sec-desc">You can adjust each door panel size to better suit your design.</p>' +
            '<div class="sd-design-rules-box">' +
            '<p>Minimum panel height is 200mm.</p>' +
            '<p>Maximum panel height is 2440mm.</p>' +
            '<p>Minimum panel width is 100mm.</p>' +
            '</div>' +
            '</div>' +
            '<div class="sd-design-sec" id="sdDoorDesignOrientSection" style="margin-top:20px;">' +
            '<label class="sd-design-orient-heading">Panel orientation</label>' +
            '<div class="sd-design-orient-picker" id="sdDoorDesignOrientPicker">' +
            '<div class="sd-design-orient-card active" data-orient="horizontal">' +
            '<span class="sd-orient-check-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
            '<div class="sd-orient-icon">' +
            '<svg width="46" height="32" viewBox="0 0 48 34" fill="none" stroke="currentColor" stroke-width="2">' +
            '<rect x="3" y="3" width="42" height="12" rx="1.5"/>' +
            '<rect x="3" y="19" width="42" height="12" rx="1.5"/>' +
            '</svg>' +
            '</div>' +
            '<div class="sd-orient-title">Horizontal</div>' +
            '<div class="sd-orient-sub">(Top &amp; bottom)</div>' +
            '</div>' +
            '<div class="sd-design-orient-card" data-orient="vertical">' +
            '<span class="sd-orient-check-badge"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>' +
            '<div class="sd-orient-icon">' +
            '<svg width="36" height="36" viewBox="0 0 38 40" fill="none" stroke="currentColor" stroke-width="2">' +
            '<rect x="3" y="3" width="14" height="34" rx="1.5"/>' +
            '<rect x="21" y="3" width="14" height="34" rx="1.5"/>' +
            '</svg>' +
            '</div>' +
            '<div class="sd-orient-title">Vertical</div>' +
            '<div class="sd-orient-sub">(Left &amp; right)</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="sd-door-design-modal-footer">' +
            '<button type="button" class="sd-door-design-cancel-btn" id="sdDoorDesignModalCloseBtn">Cancel</button>' +
            '<button type="button" class="sd-door-design-save-btn" id="sdDoorDesignSaveBtn">Save</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';

        var div = document.createElement('div');
        div.innerHTML = modalHTML;
        document.body.appendChild(div);
    }

    createModals();

    /**
     * Populate and open Frame Style & Colours Modal (Matching Mockup)
     */
    function openFrameModal(setId, frameCode) {
        activeFrameModalSetId = setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var fMatch = FRAMES.find(function (f) { return f.code === frameCode; }) || st.frameStyle || FRAMES[0];
        tempFrameStyle = fMatch;

        // If currently selected color belongs to this frame, keep it, otherwise pick first color
        var curColor = st.frameColor;
        var colorExists = (fMatch.colors || []).find(function (c) { return curColor && c.name === curColor.name; });
        tempFrameColor = colorExists || (fMatch.colors && fMatch.colors[0]) || { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0 };
        tempActiveThumbIdx = 0;

        var overlay = document.getElementById('sdFrameModalOverlay');
        if (!overlay) return;

        renderFrameModalContent();
        overlay.classList.add('is-visible');
    }

    function renderFrameModalContent() {
        if (!tempFrameStyle || !tempFrameColor) return;

        var titleEl = document.getElementById('sdFrameModalStyleTitle');
        var colorNameEl = document.getElementById('sdFrameModalColorName');
        var mainImgEl = document.getElementById('sdFrameMainPreviewImg');
        var thumbsRowEl = document.getElementById('sdFrameThumbsRow');
        var colorsGridEl = document.getElementById('sdFrameColorsGrid');

        if (titleEl) titleEl.textContent = tempFrameStyle.name;
        if (colorNameEl) {
            var colorPriceText = tempFrameColor.basePrice ? ' &bull; ' + CURRENCY + tempFrameColor.basePrice.toFixed(2) : '';
            colorNameEl.innerHTML = tempFrameColor.name + colorPriceText;
        }

        // Prepare gallery thumbnails for left side:
        // Prioritize this specific colour's gallery (where 1st image is its featured render)
        var defaultFirstImg = (tempFrameStyle.gallery && tempFrameStyle.gallery.length > 0) ? tempFrameStyle.gallery[0] : (tempFrameStyle.featuredImage || '');
        var colorImg = (tempFrameColor && tempFrameColor.image) ? tempFrameColor.image : defaultFirstImg;

        var galleryList = [];
        if (tempFrameColor && tempFrameColor.gallery && Array.isArray(tempFrameColor.gallery) && tempFrameColor.gallery.length > 0) {
            tempFrameColor.gallery.forEach(function (src, gIdx) {
                if (src) {
                    galleryList.push({ type: 'color_gallery_' + gIdx, src: src });
                }
            });
        } else {
            if (colorImg) {
                galleryList.push({ type: 'profile', src: colorImg });
            }
            if (tempFrameStyle.gallery && Array.isArray(tempFrameStyle.gallery)) {
                for (var gIdx = 1; gIdx < tempFrameStyle.gallery.length; gIdx++) {
                    var gSrc = tempFrameStyle.gallery[gIdx];
                    if (gSrc && gSrc !== colorImg) {
                        galleryList.push({ type: 'gallery_' + gIdx, src: gSrc });
                    }
                }
            }
        }

        if (galleryList.length === 0 && defaultFirstImg) {
            galleryList.push({ type: 'default', src: defaultFirstImg });
        }

        if (tempActiveThumbIdx >= galleryList.length) {
            tempActiveThumbIdx = 0;
        }

        if (mainImgEl && galleryList.length > 0) {
            mainImgEl.src = galleryList[tempActiveThumbIdx].src;
        }

        // Render Thumbnails Row
        if (thumbsRowEl) {
            thumbsRowEl.innerHTML = galleryList.map(function (g, idx) {
                var isActive = (idx === tempActiveThumbIdx);
                return '<div class="sd-frame-thumb ' + (isActive ? 'active' : '') + '" data-idx="' + idx + '">' +
                    '<img src="' + g.src + '" alt="Thumbnail ' + (idx + 1) + '">' +
                    '</div>';
            }).join('');
        }

        // Render Available Colours on Right Side
        // 1st image of each colour's gallery is used for that colour's swatch image box
        if (colorsGridEl) {
            var colors = tempFrameStyle.colors || [];
            colorsGridEl.innerHTML = colors.map(function (c) {
                var isSel = (tempFrameColor && tempFrameColor.name === c.name);
                var imgSrc = c.image || (c.gallery && c.gallery[0]) || defaultFirstImg;
                var imgHtml = imgSrc
                    ? '<img src="' + imgSrc + '" alt="' + c.name + '">'
                    : '<span class="sd-fallback-dot" style="background:' + c.hex + '"></span>';
                var checkBadge = isSel
                    ? '<span class="sd-frame-color-check"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
                    : '';
                return '<div class="sd-frame-modal-color-card ' + (isSel ? 'active' : '') + '" data-color="' + c.name + '">' +
                    '<div class="sd-frame-modal-color-box">' + imgHtml + checkBadge + '</div>' +
                    '<div class="sd-frame-modal-color-label">' + c.name + '</div>' +
                    '</div>';
            }).join('');
        }
    }

    /**
     * Populate and open Glass Modal
     */
    function openGlassModal(setId) {
        activeModalSetId = setId;
        var st = sdSetStates[setId];
        tempModalSelection = st.glassItem || GLASS_LIST[0];

        var overlay = document.getElementById('sdGlassModalOverlay');
        if (!overlay) return;

        renderGlassSwatches('mirrors');
        updateGlassPreview(tempModalSelection);
        overlay.classList.add('is-visible');
    }

    function renderGlassSwatches(cat) {
        var grid = document.getElementById('sdGlassSwatchesGrid');
        if (!grid) return;

        var items = GLASS_LIST.filter(function (g) {
            return g.category === cat;
        });

        grid.innerHTML = items.map(function (g) {
            var isSel = (tempModalSelection && tempModalSelection.id === g.id);
            return '<div class="sd-modal-swatch-item ' + (isSel ? 'active' : '') + '" data-id="' + g.id + '">' +
                '<div class="sd-modal-swatch-thumb" style="background:' + g.swatchColor + ';"></div>' +
                '<div class="sd-modal-swatch-name">' + g.name + '</div>' +
                '</div>';
        }).join('');
    }

    function updateGlassPreview(item) {
        if (!item) return;
        var box = document.getElementById('sdGlassPreviewBox');
        var title = document.getElementById('sdGlassPreviewTitle');
        var price = document.getElementById('sdGlassPreviewPrice');

        if (box) {
            if (item.category === 'mirrors') {
                box.style.background = 'linear-gradient(135deg, #ffffff 0%, ' + item.swatchColor + ' 50%, #94a3b8 100%)';
            } else {
                box.style.background = item.swatchColor;
            }
        }
        if (title) title.textContent = item.name;
        if (price) price.textContent = 'Square meter price: ' + CURRENCY + item.pricePerSqm.toFixed(2) + ' plus VAT';
    }

    /**
     * Populate and open Board Modal
     */
    function openBoardModal(setId) {
        activeModalSetId = setId;
        var st = sdSetStates[setId];
        tempModalSelection = st.boardItem || BOARDS[0];

        var overlay = document.getElementById('sdBoardModalOverlay');
        if (!overlay) return;

        var grid = document.getElementById('sdBoardSwatchesGrid');
        if (grid) {
            grid.innerHTML = BOARDS.map(function (b) {
                var isSel = (tempModalSelection && tempModalSelection.id === b.id);
                var bgStyle = b.image ? 'background-image:url(' + b.image + ');' : 'background:#b89778;';
                return '<div class="sd-modal-swatch-item ' + (isSel ? 'active' : '') + '" data-id="' + b.id + '">' +
                    '<div class="sd-modal-swatch-thumb" style="' + bgStyle + '"></div>' +
                    '<div class="sd-modal-swatch-name">' + (b.code || b.title) + '</div>' +
                    '</div>';
            }).join('');
        }

        updateBoardPreview(tempModalSelection);
        overlay.classList.add('is-visible');
    }

    function updateBoardPreview(b) {
        if (!b) return;
        var box = document.getElementById('sdBoardPreviewBox');
        var title = document.getElementById('sdBoardPreviewTitle');
        var price = document.getElementById('sdBoardPreviewPrice');

        if (box) {
            box.style.backgroundImage = b.image ? 'url(' + b.image + ')' : 'none';
            if (!b.image) box.style.background = '#b89778';
        }
        if (title) title.textContent = b.title || b.code;
        if (price) price.textContent = 'Brand: ' + (b.brand || 'Egger');
    }

    /**
     * Populate and open Door Design & Panel Size Modal (Matching Mockup)
     */
    function openDoorDesignModal(setId, design) {
        activeDoorDesignModalSetId = setId;
        var st = sdSetStates[setId];
        if (!st) return;

        tempDoorDesign = design || st.doorDesign || DESIGNS[0];
        var maxWardrobeHeightForVertical = 2557;
        var isVerticalAllowed = ((st.height || 2000) <= maxWardrobeHeightForVertical);
        var isOrientApplicable = (tempDoorDesign.panels === 2 || tempDoorDesign.panels === 3);

        var desiredOrient = (isOrientApplicable && st.doorDesign && st.doorDesign.panels === tempDoorDesign.panels && st.orientation)
            ? st.orientation
            : (tempDoorDesign.orientation === 'vertical' ? 'vertical' : 'horizontal');

        if (!isVerticalAllowed || !isOrientApplicable) {
            desiredOrient = 'horizontal';
        }

        tempDoorOrientation = desiredOrient;

        tempCustomSplits = (st.customSplits && st.customSplits[tempDoorDesign.id])
            ? st.customSplits[tempDoorDesign.id].slice()
            : null;

        var overlay = document.getElementById('sdDoorDesignModalOverlay');
        if (!overlay) return;

        renderDoorDesignModalContent();
        overlay.classList.add('is-visible');
    }

    function renderDoorDesignModalContent() {
        if (!activeDoorDesignModalSetId || !tempDoorDesign) return;
        var st = sdSetStates[activeDoorDesignModalSetId];
        if (!st) return;

        var w = st.width || 3000;
        var h = st.height || 2000;
        var dc = st.doorCount || 2;
        var overlapMm = 25;
        var doorWidthMm = Math.round((w + (dc - 1) * overlapMm) / dc);
        var doorHeightMm = h - 40;

        var heightEl = document.getElementById('sdDoorDesignModalHeight');
        var widthEl = document.getElementById('sdDoorDesignModalWidth');
        if (heightEl) heightEl.textContent = doorHeightMm + ' mm';
        if (widthEl) widthEl.textContent = doorWidthMm + ' mm';

        // Check vertical height limit (2440mm max panel height -> 2557mm wardrobe height)
        var maxWardrobeHeightForVertical = 2557;
        var isVerticalAllowed = (h <= maxWardrobeHeightForVertical);
        var isOrientApplicable = (tempDoorDesign.panels === 2 || tempDoorDesign.panels === 3);

        if (!isVerticalAllowed || !isOrientApplicable) {
            tempDoorOrientation = 'horizontal';
        }

        // Show/hide entire Panel orientation section (only for 2 & 3 panels; removed for 1, 4, and 5 panels)
        var orientSection = document.getElementById('sdDoorDesignOrientSection');
        if (orientSection) {
            orientSection.style.display = isOrientApplicable ? 'block' : 'none';
        }

        // Update Orientation selector cards
        var orientPicker = document.getElementById('sdDoorDesignOrientPicker');
        if (orientPicker && isOrientApplicable) {
            orientPicker.querySelectorAll('.sd-design-orient-card').forEach(function (card) {
                var oType = card.dataset.orient;
                var isCardDisabled = (oType === 'vertical' && !isVerticalAllowed);

                card.classList.toggle('active', oType === tempDoorOrientation);
                card.classList.toggle('is-disabled', isCardDisabled);

                var existingTip = card.querySelector('.input-info-tooltip');
                if (existingTip) existingTip.remove();

                if (isCardDisabled) {
                    var tip = document.createElement('div');
                    tip.className = 'input-info-tooltip';
                    tip.style.bottom = 'calc(100% + 6px)';
                    tip.style.width = '200px';
                    tip.innerHTML = 'The design is not possible.<br>Maximum panel height is 2440mm. To use this design, reduce the height of the wardrobe to ' + maxWardrobeHeightForVertical + 'mm.';
                    card.appendChild(tip);
                }
            });
        }

        // Render visual door preview on left
        var previewContainer = document.getElementById('sdDoorDesignPreviewContainer');
        if (!previewContainer) return;

        var panelsCount = tempDoorDesign.panels || 1;
        var isVert = (tempDoorOrientation === 'vertical' && panelsCount > 1);
        var frameColorHex = (st.frameColor && st.frameColor.hex) ? st.frameColor.hex : '#c8c8c8';

        // Calculate panel sizes in mm
        var frameTopH = 45;
        var frameBtmH = 35;
        var frameStileW = 30;
        var dividerThick = 15;

        var totalInfillH = Math.max(200, doorHeightMm - frameTopH - frameBtmH);
        var totalInfillW = Math.max(100, doorWidthMm - frameStileW * 2);

        var panelSizes = [];
        if (isVert) {
            var availW = totalInfillW - dividerThick * (panelsCount - 1);
            if (tempCustomSplits && tempCustomSplits.length === panelsCount) {
                panelSizes = tempCustomSplits.slice();
            } else {
                var eachW = Math.round(availW / panelsCount);
                for (var vi = 0; vi < panelsCount; vi++) {
                    panelSizes.push(eachW);
                }
            }
        } else {
            var availH = totalInfillH - dividerThick * (panelsCount - 1);
            if (tempCustomSplits && tempCustomSplits.length === panelsCount) {
                panelSizes = tempCustomSplits.slice();
            } else {
                var eachH = Math.round(availH / panelsCount);
                for (var hi = 0; hi < panelsCount; hi++) {
                    panelSizes.push(eachH);
                }
            }
        }

        // Scale door mockup appropriately
        var mockupH = 300;
        var aspect = doorWidthMm / doorHeightMm;
        var mockupW = Math.round(mockupH * aspect);
        if (mockupW > 170) {
            mockupW = 170;
            mockupH = Math.round(mockupW / aspect);
        }
        if (mockupW < 115) {
            mockupW = 115;
        }

        var html = '<div class="sd-door-preview-area">';

        // Top: Reset Button (Centered pill)
        if (panelsCount > 1) {
            html += '<div class="sd-door-modal-top-bar">' +
                '<button type="button" class="sd-door-modal-reset-btn" id="sdDoorMockupResetBtn" title="Reset panel splits">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>' +
                '<span>Reset</span>' +
                '</button>' +
                '</div>';
        } else {
            html += '<div class="sd-door-modal-top-bar" style="height:32px;"></div>';
        }

        // Center Stage with Dimension Lines around the Door
        html += '<div class="sd-door-preview-stage ' + (isVert ? 'orient-vert' : 'orient-horiz') + '">';

        // 1. Total Height Dimension (Left of Door)
        html += '<div class="sd-dim-total-left" style="height:' + mockupH + 'px;">' +
            '<div class="sd-dim-arrow-tick sd-dim-tick-top"></div>' +
            '<div class="sd-dim-arrow-head sd-arrow-up"></div>' +
            '<div class="sd-dim-line-seg"></div>' +
            '<div class="sd-dim-total-badge">' + doorHeightMm + ' mm</div>' +
            '<div class="sd-dim-line-seg"></div>' +
            '<div class="sd-dim-arrow-head sd-arrow-down"></div>' +
            '<div class="sd-dim-arrow-tick sd-dim-tick-bottom"></div>' +
            '</div>';

        // 2. Center Door Mockup with Infill, Panels & Circular Drag Handle
        html += '<div class="sd-door-mockup-wrapper">' +
            '<div class="sd-door-mockup" style="width:' + mockupW + 'px; height:' + mockupH + 'px; border-color:' + frameColorHex + '; background:' + frameColorHex + ';">' +
            '<div class="sd-door-mockup-infill ' + (isVert ? 'orient-vert' : 'orient-horiz') + '">';

        for (var pi = 0; pi < panelsCount; pi++) {
            var curSz = panelSizes[pi] || 1;
            var minLimit = isVert ? 100 : 200;
            var isMin = (curSz <= minLimit);
            // Top panel warm decor (#ede4d8), bottom panel white (#ffffff)
            var pBg = isMin ? '#fed7aa' : (pi === 0 && panelsCount > 1 ? '#ede4d8' : '#ffffff');
            var pFlex = curSz;

            html += '<div class="sd-door-mockup-panel ' + (isMin ? 'is-min-size' : '') + '" style="flex:' + pFlex + '; background:' + pBg + ';">';
            if (isMin) {
                if (isVert) {
                    html += '<div class="sd-min-size-popup">Min size ' + minLimit + 'mm</div>';
                } else {
                    html += '<div class="sd-min-size-alert">Min size ' + minLimit + 'mm</div>';
                }
            }
            html += '</div>';

            if (pi < panelsCount - 1) {
                var arrowSvg = isVert
                    ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 8 2 12 6 16"/><polyline points="18 8 22 12 18 16"/><line x1="2" y1="12" x2="22" y2="12"/></svg>'
                    : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 6 12 2 16 6"/><polyline points="8 18 12 22 16 18"/><line x1="12" y1="2" x2="12" y2="22"/></svg>';

                html += '<div class="sd-door-mockup-divider" style="background:' + frameColorHex + ';">' +
                    '<button type="button" class="sd-design-adjust-circle" data-seam="' + pi + '" title="Drag to adjust panel split">' +
                    arrowSvg +
                    '</button>' +
                    '</div>';
            }
        }

        html += '</div>'; // .sd-door-mockup-infill
        html += '</div>'; // .sd-door-mockup

        // 3. Total Width Dimension (Below Door)
        html += '<div class="sd-dim-total-bottom" style="width:' + mockupW + 'px;">' +
            '<div class="sd-dim-arrow-tick sd-dim-tick-left"></div>' +
            '<div class="sd-dim-arrow-head sd-arrow-left"></div>' +
            '<div class="sd-dim-line-seg"></div>' +
            '<div class="sd-dim-total-badge">' + doorWidthMm + ' mm</div>' +
            '<div class="sd-dim-line-seg"></div>' +
            '<div class="sd-dim-arrow-head sd-arrow-right"></div>' +
            '<div class="sd-dim-arrow-tick sd-dim-tick-right"></div>' +
            '</div>';

        html += '</div>'; // .sd-door-mockup-wrapper

        // 4. Panel Dimension Lines & Input Badges: Right side for horizontal, Bottom for vertical
        if (isVert) {
            html += '<div class="sd-dim-panels-row" style="width:' + mockupW + 'px;">';
            for (var bi = 0; bi < panelsCount; bi++) {
                var sizeValV = panelSizes[bi] || Math.round(doorWidthMm / panelsCount);
                html += '<div class="sd-dim-panel-item-vert" style="flex:' + (panelSizes[bi] || 1) + ';">' +
                    '<div class="sd-dim-arrow-tick sd-dim-tick-left"></div>' +
                    '<div class="sd-dim-arrow-head sd-arrow-left"></div>' +
                    '<div class="sd-dim-line-seg"></div>' +
                    '<div class="sd-door-badge-tag">' +
                    '<input type="text" inputmode="numeric" class="sd-door-badge-input" data-idx="' + bi + '" value="' + sizeValV + '" maxlength="4" aria-label="Panel ' + (bi + 1) + ' width">' +
                    '<span class="sd-badge-unit">mm</span>' +
                    '</div>' +
                    '<div class="sd-dim-line-seg"></div>' +
                    '<div class="sd-dim-arrow-head sd-arrow-right"></div>' +
                    '<div class="sd-dim-arrow-tick sd-dim-tick-right"></div>' +
                    '</div>';
            }
            html += '</div>';
        } else {
            html += '<div class="sd-dim-panels-col" style="height:' + mockupH + 'px;">';
            for (var bi = 0; bi < panelsCount; bi++) {
                var sizeValH = panelSizes[bi] || Math.round(doorHeightMm / panelsCount);
                html += '<div class="sd-dim-panel-item-horiz" style="flex:' + (panelSizes[bi] || 1) + ';">' +
                    '<div class="sd-dim-arrow-tick sd-dim-tick-top"></div>' +
                    '<div class="sd-dim-arrow-head sd-arrow-up"></div>' +
                    '<div class="sd-dim-line-seg"></div>' +
                    '<div class="sd-door-badge-tag">' +
                    '<input type="text" inputmode="numeric" class="sd-door-badge-input" data-idx="' + bi + '" value="' + sizeValH + '" maxlength="4" aria-label="Panel ' + (bi + 1) + ' height">' +
                    '<span class="sd-badge-unit">mm</span>' +
                    '</div>' +
                    '<div class="sd-dim-line-seg"></div>' +
                    '<div class="sd-dim-arrow-head sd-arrow-down"></div>' +
                    '<div class="sd-dim-arrow-tick sd-dim-tick-bottom"></div>' +
                    '</div>';
            }
            html += '</div>';
        }

        html += '</div>'; // .sd-door-preview-stage

        // Bottom Notice Bar
        html += '<div class="sd-door-modal-info-bar">' +
            '<span class="sd-door-info-icon">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="#16a34a">' +
            '<circle cx="12" cy="12" r="10"/>' +
            '<path d="M12 16v-4M12 8h.01" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/>' +
            '</svg>' +
            '</span>' +
            '<span class="sd-door-info-text">Drag the divider to adjust panel sizes</span>' +
            '</div>';

        html += '</div>'; // .sd-door-preview-area

        previewContainer.innerHTML = html;
    }

    // Draggable Adjust Button in Door Design Modal
    (function () {
        var isDragging = false;
        var dragSeamIdx = 0;
        var startY = 0;
        var startX = 0;
        var isVert = false;
        var startSizes = [];
        var totalAvail = 0;
        var infillPx = 0;
        var activeBtn = null;
        var activeInfill = null;

        document.addEventListener('mousedown', startDrag);
        document.addEventListener('touchstart', startDrag, { passive: false });

        function startDrag(e) {
            var btn = e.target.closest('#sdDoorDesignModalOverlay .sd-design-adjust-circle, #sdDoorDesignModalOverlay .sd-door-mockup-divider');
            if (!btn) return;

            var modal = document.getElementById('sdDoorDesignModalOverlay');
            if (!modal || !modal.classList.contains('is-visible')) return;

            var st = sdSetStates[activeDoorDesignModalSetId];
            if (!st) return;

            var dividerEl = btn.classList.contains('sd-door-mockup-divider') ? btn : btn.closest('.sd-door-mockup-divider');
            var adjustBtn = dividerEl ? dividerEl.querySelector('.sd-design-adjust-circle') : btn;
            if (!dividerEl || !adjustBtn) return;

            e.preventDefault();
            dragSeamIdx = parseInt(adjustBtn.dataset.seam, 10) || 0;
            activeBtn = adjustBtn;
            activeInfill = dividerEl.closest('.sd-door-mockup-infill');
            if (!activeInfill) return;

            isVert = activeInfill.classList.contains('orient-vert');

            var touch = e.touches ? e.touches[0] : e;
            startY = touch.clientY;
            startX = touch.clientX;

            // Get current panel sizes in mm
            var panelsCount = tempDoorDesign.panels || 1;
            var w = st.width || 3000;
            var h = st.height || 2000;
            var dc = st.doorCount || 2;
            var overlapMm = 25;
            var doorWidthMm = Math.round((w + (dc - 1) * overlapMm) / dc);
            var doorHeightMm = h - 40;

            var frameTopH = 45;
            var frameBtmH = 35;
            var frameStileW = 30;
            var dividerThick = 15;

            var totalInfillH = Math.max(200, doorHeightMm - frameTopH - frameBtmH);
            var totalInfillW = Math.max(100, doorWidthMm - frameStileW * 2);

            totalAvail = isVert ? totalInfillW - dividerThick * (panelsCount - 1) : totalInfillH - dividerThick * (panelsCount - 1);

            startSizes = [];
            if (tempCustomSplits && tempCustomSplits.length === panelsCount) {
                startSizes = tempCustomSplits.slice();
            } else {
                var defSize = Math.round(totalAvail / panelsCount);
                for (var i = 0; i < panelsCount; i++) {
                    startSizes.push(defSize);
                }
            }

            var infillRect = activeInfill.getBoundingClientRect();
            infillPx = isVert ? infillRect.width : infillRect.height;
            if (infillPx <= 0) infillPx = isVert ? 200 : 300;

            isDragging = true;
            activeBtn.classList.add('is-dragging');
            document.body.style.userSelect = 'none';
            document.body.style.cursor = isVert ? 'ew-resize' : 'ns-resize';

            document.addEventListener('mousemove', onDragMove);
            document.addEventListener('touchmove', onDragMove, { passive: false });
            document.addEventListener('mouseup', endDrag);
            document.addEventListener('touchend', endDrag);
        }

        function onDragMove(e) {
            if (!isDragging) return;
            e.preventDefault();

            var touch = e.touches ? e.touches[0] : e;
            var deltaPx = isVert ? (touch.clientX - startX) : (touch.clientY - startY);
            var pxToMm = totalAvail / infillPx;
            var deltaMm = Math.round(deltaPx * pxToMm);

            var p1 = dragSeamIdx;
            var p2 = dragSeamIdx + 1;
            var combinedMm = startSizes[p1] + startSizes[p2];

            var minVal = isVert ? 100 : 200;
            var maxVal = isVert ? Math.min(combinedMm - 100, 2440) : Math.min(combinedMm - 200, 2440);

            var newSizeP1 = Math.max(minVal, Math.min(maxVal, startSizes[p1] + deltaMm));
            var newSizeP2 = combinedMm - newSizeP1;

            if (newSizeP2 < minVal) {
                newSizeP2 = minVal;
                newSizeP1 = combinedMm - newSizeP2;
            }
            if (newSizeP2 > maxVal) {
                newSizeP2 = maxVal;
                newSizeP1 = combinedMm - newSizeP2;
            }

            var currentSizes = startSizes.slice();
            currentSizes[p1] = newSizeP1;
            currentSizes[p2] = newSizeP2;
            tempCustomSplits = currentSizes;

            // Dynamically update DOM elements (flex styles, badge inputs, min size indicators)
            var stage = activeInfill.closest('.sd-door-preview-stage');
            if (stage) {
                var panels = activeInfill.querySelectorAll('.sd-door-mockup-panel');
                var minLimit = isVert ? 100 : 200;

                for (var k = 0; k < currentSizes.length; k++) {
                    var sz = currentSizes[k];
                    var isAtMin = (sz <= minLimit);

                    if (panels[k]) {
                        panels[k].style.flex = sz;
                        if (isAtMin) {
                            panels[k].classList.add('is-min-size');
                            panels[k].style.backgroundColor = '#fed7aa';
                            if (isVert) {
                                if (!panels[k].querySelector('.sd-min-size-popup')) {
                                    panels[k].innerHTML = '<div class="sd-min-size-popup">Min size 100mm</div>';
                                }
                            } else {
                                if (!panels[k].querySelector('.sd-min-size-alert')) {
                                    panels[k].innerHTML = '<div class="sd-min-size-alert">Min size 200mm</div>';
                                }
                            }
                        } else {
                            panels[k].classList.remove('is-min-size');
                            panels[k].style.backgroundColor = (k === 0 && currentSizes.length > 1) ? '#ede4d8' : '#ffffff';
                            var alertEl = panels[k].querySelector('.sd-min-size-alert, .sd-min-size-popup');
                            if (alertEl) {
                                alertEl.remove();
                            }
                        }
                    }
                }

                if (isVert) {
                    var vertBadges = stage.querySelectorAll('.sd-dim-panel-item-vert');
                    for (var vi = 0; vi < currentSizes.length; vi++) {
                        if (vertBadges[vi]) {
                            vertBadges[vi].style.flex = currentSizes[vi];
                            var tagV = vertBadges[vi].querySelector('.sd-door-badge-input');
                            if (tagV && document.activeElement !== tagV) {
                                tagV.value = currentSizes[vi];
                            }
                        }
                    }
                } else {
                    var horizBadges = stage.querySelectorAll('.sd-dim-panel-item-horiz');
                    for (var hi = 0; hi < currentSizes.length; hi++) {
                        if (horizBadges[hi]) {
                            horizBadges[hi].style.flex = currentSizes[hi];
                            var tagH = horizBadges[hi].querySelector('.sd-door-badge-input');
                            if (tagH && document.activeElement !== tagH) {
                                tagH.value = currentSizes[hi];
                            }
                        }
                    }
                }
            }
        }

        function endDrag() {
            if (!isDragging) return;
            isDragging = false;
            if (activeBtn) activeBtn.classList.remove('is-dragging');
            document.body.style.userSelect = '';
            document.body.style.cursor = '';

            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('touchmove', onDragMove);
            document.removeEventListener('mouseup', endDrag);
            document.removeEventListener('touchend', endDrag);
        }
    })();

    // Direct manual input for Panel Dimension Badges
    document.addEventListener('input', function (e) {
        var input = e.target.closest('#sdDoorDesignModalOverlay .sd-door-badge-input');
        if (!input) return;

        var modal = document.getElementById('sdDoorDesignModalOverlay');
        if (!modal || !modal.classList.contains('is-visible')) return;

        var st = sdSetStates[activeDoorDesignModalSetId];
        if (!st) return;

        var idx = parseInt(input.dataset.idx, 10);
        if (isNaN(idx)) return;

        var panelsCount = tempDoorDesign.panels || 1;
        if (panelsCount <= 1) return;

        var isVert = (tempDoorOrientation === 'vertical' && panelsCount > 1);
        var w = st.width || 3000;
        var h = st.height || 2000;
        var dc = st.doorCount || 2;
        var overlapMm = 25;
        var doorWidthMm = Math.round((w + (dc - 1) * overlapMm) / dc);
        var doorHeightMm = h - 40;

        var frameTopH = 45;
        var frameBtmH = 35;
        var frameStileW = 30;
        var dividerThick = 15;

        var totalInfillH = Math.max(200, doorHeightMm - frameTopH - frameBtmH);
        var totalInfillW = Math.max(100, doorWidthMm - frameStileW * 2);
        var totalAvail = isVert ? totalInfillW - dividerThick * (panelsCount - 1) : totalInfillH - dividerThick * (panelsCount - 1);

        var currentSizes = [];
        if (tempCustomSplits && tempCustomSplits.length === panelsCount) {
            currentSizes = tempCustomSplits.slice();
        } else {
            var defSz = Math.round(totalAvail / panelsCount);
            for (var si = 0; si < panelsCount; si++) currentSizes.push(defSz);
        }

        var rawStr = input.value.replace(/\D/g, '');
        var parsedVal = parseInt(rawStr, 10);
        if (isNaN(parsedVal) || parsedVal < 10) return;

        var minLimit = isVert ? 100 : 200;
        var maxLimit = Math.min(2440, totalAvail - minLimit * (panelsCount - 1));
        var targetVal = Math.max(minLimit, Math.min(maxLimit, parsedVal));

        // Distribute changes across other panels
        var newSizes = currentSizes.slice();
        var diff = targetVal - newSizes[idx];
        if (diff === 0) return;

        newSizes[idx] = targetVal;

        if (panelsCount === 2) {
            var otherIdx = (idx === 0) ? 1 : 0;
            newSizes[otherIdx] = totalAvail - targetVal;
        } else {
            var remainingIndices = [];
            for (var ri = 0; ri < panelsCount; ri++) {
                if (ri !== idx) remainingIndices.push(ri);
            }
            var remToDist = -diff;
            var otherSum = 0;
            for (var osi = 0; osi < remainingIndices.length; osi++) {
                otherSum += currentSizes[remainingIndices[osi]];
            }
            var acc = 0;
            for (var osi = 0; osi < remainingIndices.length; osi++) {
                var ri = remainingIndices[osi];
                var share = (osi === remainingIndices.length - 1)
                    ? (remToDist - acc)
                    : Math.round(remToDist * (currentSizes[ri] / (otherSum || 1)));
                var calcVal = Math.max(minLimit, Math.min(2440, currentSizes[ri] + share));
                acc += (calcVal - currentSizes[ri]);
                newSizes[ri] = calcVal;
            }
            var totSum = 0;
            for (var ts = 0; ts < panelsCount; ts++) totSum += newSizes[ts];
            var disc = totalAvail - totSum;
            if (disc !== 0) {
                for (var di = 0; di < remainingIndices.length; di++) {
                    var rdi = remainingIndices[di];
                    if (newSizes[rdi] + disc >= minLimit && newSizes[rdi] + disc <= 2440) {
                        newSizes[rdi] += disc;
                        break;
                    }
                }
            }
        }

        tempCustomSplits = newSizes;

        // Dynamically update panels flex, background colors, min-size warnings, and OTHER badge inputs
        var stage = input.closest('.sd-door-preview-stage');
        if (stage) {
            var panels = stage.querySelectorAll('.sd-door-mockup-panel');
            for (var k = 0; k < newSizes.length; k++) {
                var sz = newSizes[k];
                var isAtMin = (sz <= minLimit);

                if (panels[k]) {
                    panels[k].style.flex = sz;
                    if (isAtMin) {
                        panels[k].classList.add('is-min-size');
                        panels[k].style.backgroundColor = '#fed7aa';
                        if (isVert) {
                            if (!panels[k].querySelector('.sd-min-size-popup')) {
                                panels[k].innerHTML = '<div class="sd-min-size-popup">Min size 100mm</div>';
                            }
                        } else {
                            if (!panels[k].querySelector('.sd-min-size-alert')) {
                                panels[k].innerHTML = '<div class="sd-min-size-alert">Min size 200mm</div>';
                            }
                        }
                    } else {
                        panels[k].classList.remove('is-min-size');
                        panels[k].style.backgroundColor = (k === 0 && newSizes.length > 1) ? '#ede4d8' : '#ffffff';
                        var alertEl = panels[k].querySelector('.sd-min-size-alert, .sd-min-size-popup');
                        if (alertEl) alertEl.remove();
                    }
                }
            }

            // Update badge item flex styles and update OTHER input values (preserving cursor in current input)
            var badgeItems = isVert ? stage.querySelectorAll('.sd-dim-panel-item-vert') : stage.querySelectorAll('.sd-dim-panel-item-horiz');
            for (var bi = 0; bi < newSizes.length; bi++) {
                if (badgeItems[bi]) {
                    badgeItems[bi].style.flex = newSizes[bi];
                    var badgeInp = badgeItems[bi].querySelector('.sd-door-badge-input');
                    if (badgeInp && badgeInp !== input) {
                        badgeInp.value = newSizes[bi];
                    }
                }
            }
        }
    });

    // Clean up / normalize on blur
    document.addEventListener('focusout', function (e) {
        var input = e.target.closest('#sdDoorDesignModalOverlay .sd-door-badge-input');
        if (!input) return;

        var idx = parseInt(input.dataset.idx, 10);
        if (isNaN(idx)) return;

        if (tempCustomSplits && tempCustomSplits[idx] != null) {
            input.value = tempCustomSplits[idx];
        }
    });

    // Enter key submits/blurs input
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            var input = e.target.closest('#sdDoorDesignModalOverlay .sd-door-badge-input');
            if (input) {
                input.blur();
            }
        }
    });

    // Modal Events
    document.addEventListener('click', function (e) {
        // Reset Custom Splits in Door Design Modal
        if (e.target.id === 'sdDoorMockupResetBtn' || e.target.closest('#sdDoorMockupResetBtn')) {
            tempCustomSplits = null;
            renderDoorDesignModalContent();
            return;
        }

        // Close Door Design Modal
        if (e.target.id === 'sdDoorDesignModalClose' || e.target.closest('#sdDoorDesignModalClose') || e.target.id === 'sdDoorDesignModalCloseBtn' || e.target.closest('#sdDoorDesignModalCloseBtn') || e.target.id === 'sdDoorDesignModalOverlay') {
            var dOverlay = document.getElementById('sdDoorDesignModalOverlay');
            if (dOverlay) dOverlay.classList.remove('is-visible');
            return;
        }

        // Change Orientation in Door Design Modal
        var orientCard = e.target.closest('.sd-design-orient-card');
        if (orientCard) {
            if (orientCard.classList.contains('is-disabled')) return;
            tempDoorOrientation = orientCard.dataset.orient;
            tempCustomSplits = null;
            renderDoorDesignModalContent();
            return;
        }

        // Save Door Design Modal
        if (e.target.id === 'sdDoorDesignSaveBtn' || e.target.closest('#sdDoorDesignSaveBtn')) {
            if (activeDoorDesignModalSetId && sdSetStates[activeDoorDesignModalSetId]) {
                var stSave = sdSetStates[activeDoorDesignModalSetId];
                stSave.doorDesign = tempDoorDesign;
                stSave.orientation = tempDoorOrientation;
                if (tempCustomSplits) {
                    if (!stSave.customSplits) stSave.customSplits = {};
                    stSave.customSplits[tempDoorDesign.id] = tempCustomSplits;
                }
                var setD = container.querySelector('.sd-set[data-set-id="' + activeDoorDesignModalSetId + '"]');
                if (setD) {
                    sdRenderDoorDesigns(setD);
                    sdDrawCanvas(setD);
                }
            }
            var dOverlaySave = document.getElementById('sdDoorDesignModalOverlay');
            if (dOverlaySave) dOverlaySave.classList.remove('is-visible');
            return;
        }

        // Close Frame Modal
        if (e.target.id === 'sdFrameModalClose' || e.target.closest('#sdFrameModalClose') || e.target.id === 'sdFrameCancelBtn' || e.target.id === 'sdFrameModalOverlay') {
            var fOverlay = document.getElementById('sdFrameModalOverlay');
            if (fOverlay) fOverlay.classList.remove('is-visible');
            return;
        }

        // Thumbnail Previous Navigation
        if (e.target.id === 'sdFrameThumbPrev' || e.target.closest('#sdFrameThumbPrev')) {
            var thumbsPrev = document.querySelectorAll('#sdFrameThumbsRow .sd-frame-thumb');
            if (thumbsPrev.length > 0) {
                tempActiveThumbIdx = (tempActiveThumbIdx - 1 + thumbsPrev.length) % thumbsPrev.length;
                renderFrameModalContent();
            }
            return;
        }

        // Thumbnail Next Navigation
        if (e.target.id === 'sdFrameThumbNext' || e.target.closest('#sdFrameThumbNext')) {
            var thumbsNext = document.querySelectorAll('#sdFrameThumbsRow .sd-frame-thumb');
            if (thumbsNext.length > 0) {
                tempActiveThumbIdx = (tempActiveThumbIdx + 1) % thumbsNext.length;
                renderFrameModalContent();
            }
            return;
        }

        // Zoom Main Image
        if (e.target.id === 'sdFrameZoomBtn' || e.target.closest('#sdFrameZoomBtn')) {
            var pImg = document.getElementById('sdFrameMainPreviewImg');
            if (pImg && pImg.src) {
                window.open(pImg.src, '_blank');
            }
            return;
        }

        // Select Thumbnail in Frame Modal
        var frameThumbEl = e.target.closest('#sdFrameModalOverlay .sd-frame-thumb');
        if (frameThumbEl) {
            tempActiveThumbIdx = parseInt(frameThumbEl.dataset.idx, 10) || 0;
            renderFrameModalContent();
            return;
        }

        // Select Color in Frame Modal
        var frameColorEl = e.target.closest('#sdFrameModalOverlay .sd-frame-modal-color-card');
        if (frameColorEl) {
            var colName = frameColorEl.dataset.color;
            var cMatch = (tempFrameStyle.colors || []).find(function (c) { return c.name === colName; });
            if (cMatch) {
                tempFrameColor = cMatch;
                tempActiveThumbIdx = 0; // reset preview to new profile render
                renderFrameModalContent();
            }
            return;
        }

        // Save Frame Modal
        if (e.target.id === 'sdFrameSaveBtn') {
            if (activeFrameModalSetId && sdSetStates[activeFrameModalSetId]) {
                sdSetStates[activeFrameModalSetId].frameStyle = tempFrameStyle;
                sdSetStates[activeFrameModalSetId].frameColor = tempFrameColor;
                var setF = container.querySelector('.sd-set[data-set-id="' + activeFrameModalSetId + '"]');
                if (setF) {
                    sdRenderFrameStyles(setF);
                    sdDrawCanvas(setF);
                }
            }
            var fOverlay = document.getElementById('sdFrameModalOverlay');
            if (fOverlay) fOverlay.classList.remove('is-visible');
            return;
        }

        // Close Glass Modal
        if (e.target.id === 'sdGlassModalClose' || e.target.id === 'sdGlassModalOverlay') {
            document.getElementById('sdGlassModalOverlay').classList.remove('is-visible');
        }
        // Close Board Modal
        if (e.target.id === 'sdBoardModalClose' || e.target.id === 'sdBoardModalOverlay') {
            document.getElementById('sdBoardModalOverlay').classList.remove('is-visible');
        }

        // Switch Glass Modal Categories
        var catBtn = e.target.closest('#sdGlassModalOverlay .sd-modal-tab-btn');
        if (catBtn) {
            document.querySelectorAll('#sdGlassModalOverlay .sd-modal-tab-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            catBtn.classList.add('active');
            renderGlassSwatches(catBtn.dataset.cat);
            return;
        }

        // Select Swatch in Glass Modal
        var glassItemEl = e.target.closest('#sdGlassModalOverlay .sd-modal-swatch-item');
        if (glassItemEl) {
            var gid = glassItemEl.dataset.id;
            var match = GLASS_LIST.find(function (g) { return g.id === gid; });
            if (match) {
                tempModalSelection = match;
                document.querySelectorAll('#sdGlassModalOverlay .sd-modal-swatch-item').forEach(function (el) {
                    el.classList.toggle('active', el.dataset.id === gid);
                });
                updateGlassPreview(match);
            }
            return;
        }

        // Save Glass Modal
        if (e.target.id === 'sdGlassSaveBtn') {
            if (activeModalSetId && sdSetStates[activeModalSetId]) {
                sdSetStates[activeModalSetId].panelType = 'glass';
                sdSetStates[activeModalSetId].glassItem = tempModalSelection;
                var set = container.querySelector('.sd-set[data-set-id="' + activeModalSetId + '"]');
                if (set) {
                    sdRenderPanelDesign(set);
                    sdDrawCanvas(set);
                }
            }
            document.getElementById('sdGlassModalOverlay').classList.remove('is-visible');
            return;
        }

        // Select Swatch in Board Modal
        var boardItemEl = e.target.closest('#sdBoardModalOverlay .sd-modal-swatch-item');
        if (boardItemEl) {
            var bid = parseInt(boardItemEl.dataset.id, 10);
            var matchB = BOARDS.find(function (b) { return b.id === bid; });
            if (matchB) {
                tempModalSelection = matchB;
                document.querySelectorAll('#sdBoardModalOverlay .sd-modal-swatch-item').forEach(function (el) {
                    el.classList.toggle('active', parseInt(el.dataset.id, 10) === bid);
                });
                updateBoardPreview(matchB);
            }
            return;
        }

        // Save Board Modal
        if (e.target.id === 'sdBoardSaveBtn') {
            if (activeModalSetId && sdSetStates[activeModalSetId]) {
                sdSetStates[activeModalSetId].panelType = 'board';
                sdSetStates[activeModalSetId].boardItem = tempModalSelection;
                var setB = container.querySelector('.sd-set[data-set-id="' + activeModalSetId + '"]');
                if (setB) {
                    sdRenderPanelDesign(setB);
                    sdDrawCanvas(setB);
                }
            }
            document.getElementById('sdBoardModalOverlay').classList.remove('is-visible');
            return;
        }
    });

    // Initialize first set on load
    sdAddSet();

    // Event Delegation for Container
    container.addEventListener('click', function (e) {
        var set = e.target.closest('.sd-set');
        if (!set) return;

        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        var curStepEl = set.querySelector('.sd-step.current');
        var currentStep = curStepEl ? parseInt(curStepEl.dataset.step, 10) : 0;

        // Collapse/expand header toggle
        if (e.target.closest('.sd-set-title') && !e.target.closest('button, .sd-door-select, input')) {
            set.classList.toggle('collapsed');
            return;
        }

        // Step tab navigation
        var stepEl = e.target.closest('.sd-step.enabled');
        if (stepEl) {
            sdGoToStep(set, parseInt(stepEl.dataset.step, 10));
            return;
        }

        // STEP 1: Select Frame Style Card -> Opens popup modal! (Only in Step 1)
        var frameCard = e.target.closest('.sd-frame-card');
        if (frameCard && currentStep === 1) {
            var fCode = frameCard.dataset.code;
            openFrameModal(setId, fCode);
            return;
        }

        // STEP 2: Select Door Design Card -> Opens Door Design Modal! (Only in Step 2)
        var designCard = e.target.closest('.sd-design-card');
        if (designCard && currentStep === 2) {
            var pCount = parseInt(designCard.dataset.panels, 10);
            var dMatch = DESIGNS.find(function (d) { return d.panels === pCount; }) || DESIGNS[0];
            openDoorDesignModal(setId, dMatch);
            return;
        }

        // STEP 2: Orientation Toggle Button (Only in Step 2)
        var orientBtn = e.target.closest('.sd-orientation-btn');
        if (orientBtn && currentStep === 2) {
            if (orientBtn.disabled || orientBtn.classList.contains('disabled')) return;
            st.orientation = orientBtn.dataset.orient;
            sdRenderDoorDesigns(set);
            sdDrawCanvas(set);
            return;
        }

        // STEP 3: Click Panel Design Cards -> Open Modals (Only in Step 3)
        var panelCard = e.target.closest('.sd-panel-card');
        if (panelCard && currentStep === 3) {
            var pType = panelCard.dataset.type;
            if (pType === 'glass') {
                openGlassModal(setId);
            } else {
                openBoardModal(setId);
            }
            return;
        }

        // STEP 4: Add to Basket AJAX (Only in Step 4)
        if (e.target.closest('.sd-btn-add-cart') && currentStep === 4) {
            var btn = set.querySelector('.sd-btn-add-cart');
            btn.disabled = true;
            btn.textContent = DATA.i18n ? DATA.i18n.addingToBasket : 'Adding to basket...';

            var payload = {
                action: 'cutlist_add_sliding_doors_to_cart',
                nonce: DATA.nonce,
                setId: setId,
                width: st.width,
                height: st.height,
                doorCount: st.doorCount,
                frameStyle: st.frameStyle.name,
                frameColor: st.frameColor.name,
                doorDesign: st.doorDesign.name + (st.doorDesign.panels > 1 ? ' (' + st.orientation + ')' : ''),
                panelType: st.panelType,
                panelMaterial: st.panelType === 'glass' ? (st.glassItem.name + ' [' + st.glassItem.category + ']') : (st.boardItem.title + ' [' + st.boardItem.brand + ']'),
                softClose: st.softClose ? 1 : 0
            };

            var xhr = new XMLHttpRequest();
            xhr.open('POST', DATA.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onload = function () {
                btn.disabled = false;
                btn.innerHTML = ICONS.cartIcon + '<span>Add to Basket</span>';
                if (xhr.status >= 200 && xhr.status < 400) {
                    alert('Sliding doors configuration added to your basket!');
                    window.location.href = DATA.cartUrl;
                } else {
                    alert('Could not add to basket. Please try again.');
                }
            };
            xhr.onerror = function () {
                btn.disabled = false;
                btn.innerHTML = ICONS.cartIcon + '<span>Add to Basket</span>';
                alert('Connection error. Please try again.');
            };

            var params = Object.keys(payload).map(function (k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(payload[k]);
            }).join('&');
            xhr.send(params);
            return;
        }

        // STEP 0: Dropdown toggle (Only in Step 0)
        var wrapper = e.target.closest('.Select2__input-wrapper');
        if (wrapper && currentStep === 0 && !set.querySelector('.sd-door-select').classList.contains('isDisabled')) {
            var sel = wrapper.closest('.Select2');
            var isOpen = sel.classList.contains('is-open');
            container.querySelectorAll('.Select2.is-open').forEach(function (s) {
                s.classList.remove('is-open');
            });
            if (!isOpen) sel.classList.add('is-open');
            return;
        }

        // STEP 0: Dropdown option select (Only in Step 0)
        var opt = e.target.closest('.Select2__option');
        if (opt && currentStep === 0) {
            var doorSel = opt.closest('.Select2');
            var val = parseInt(opt.dataset.val, 10);
            doorSel.querySelector('.Select2__input').textContent = val;
            doorSel.classList.remove('isEmpty', 'is-open');
            set.dataset.switched = '0';
            st.doorCount = val;
            st.doorOrder = [];
            for (var odi = 0; odi < val; odi++) {
                st.doorOrder.push(odi);
            }
            sdEnableSteps(set, 4);
            sdDrawCanvas(set);
            return;
        }

        // STEP 0: Switch animation on specific vertical seam (Only in Step 0)
        var switchBtn = e.target.closest('.sd-switch-btn');
        if (switchBtn && currentStep === 0) {
            if (switchBtn.disabled || switchBtn.classList.contains('disabled')) return;
            if (sdAnimState[setId] && sdAnimState[setId].running) return;

            var seamIdx = parseInt(switchBtn.dataset.seam, 10);
            if (isNaN(seamIdx)) seamIdx = 0;

            var sDoorCount = parseInt(set.querySelector('.sd-door-select .Select2__input').textContent, 10) || st.doorCount || 2;
            if (sDoorCount < 2 || seamIdx < 0 || seamIdx >= sDoorCount - 1) return;

            if (!st.doorOrder || st.doorOrder.length !== sDoorCount) {
                st.doorOrder = [];
                for (var doi = 0; doi < sDoorCount; doi++) {
                    st.doorOrder.push(doi);
                }
            }

            var d1 = seamIdx;
            var d2 = seamIdx + 1;
            var leftDoor = st.doorOrder[d1];
            var rightDoor = st.doorOrder[d2];
            var leftTrack = (leftDoor % 2 === 0) ? 1 : 0;
            var rightTrack = (rightDoor % 2 === 0) ? 1 : 0;

            // Cannot switch if adjacent doors are on the same track
            if (leftTrack === rightTrack) return;

            var sRender = set.querySelector('.sd-render');
            var sCw = sRender.offsetWidth || 800;
            var sCh = sRender.offsetHeight || 520;
            var sFloorY = Math.round(sCh * 0.84);
            var sEntW = parseInt(set.querySelector('.sd-width').value, 10) || st.width || 3000;
            var sEntH = parseInt(set.querySelector('.sd-height').value, 10) || st.height || 3000;

            var sMaxFW = Math.round(sCw * 0.85);
            var sMaxFH = Math.round(sFloorY * 0.90);
            var sScale = (sCw * 0.72) / 4000;
            var sFwRaw = sEntW * sScale;
            var sFhRaw = sEntH * sScale;

            if (sFwRaw > sMaxFW || sFhRaw > sMaxFH) {
                var ssfW = sMaxFW / sFwRaw, ssfH = sMaxFH / sFhRaw;
                var ssf = ssfW < ssfH ? ssfW : ssfH;
                sFwRaw *= ssf;
                sFhRaw *= ssf;
            }

            var sFw = Math.round(sFwRaw);
            var sFx = Math.round((sCw - sFw) / 2);
            var sPostW = Math.max(8, Math.round(sFw * 0.032));
            var sPanelW = (sFw - sPostW * 2) / sDoorCount;
            var sPanelAreaX = sFx + sPostW;

            var trkH = 8;
            var trkGap = 4;
            var trkY0 = sFloorY + 14;
            var trkY1 = trkY0 + trkH + trkGap;

            // Start positions
            var sStartX = [];
            for (var si = 0; si < sDoorCount; si++) {
                sStartX.push(Math.round(sPanelAreaX + si * sPanelW));
            }
            var sEndX = sStartX.slice();
            sEndX[d1] = sStartX[d2];
            sEndX[d2] = sStartX[d1];

            // Runner track Y positions based on door id (0, 2, 4 = Back/trkY0; 1, 3, 5 = Front/trkY1)
            var startTrkY = [];
            var endTrkY = [];
            for (var tri = 0; tri < sDoorCount; tri++) {
                var curDoor = st.doorOrder[tri];
                var isBack = (curDoor % 2 === 0);
                startTrkY.push(isBack ? trkY0 : trkY1);
                endTrkY.push(isBack ? trkY0 : trkY1);
            }
            endTrkY[d1] = startTrkY[d2];
            endTrkY[d2] = startTrkY[d1];

            var sDuration = 360;
            var sStartTime = null;

            sdAnimState[setId] = { running: true };

            function sdAnimStep(ts) {
                if (!sStartTime) sStartTime = ts;
                var t = Math.min((ts - sStartTime) / sDuration, 1);
                var ease = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;

                var curX = [];
                var curTrkY = [];
                for (var k = 0; k < sDoorCount; k++) {
                    curX.push(sStartX[k] + (sEndX[k] - sStartX[k]) * ease);
                    curTrkY.push(startTrkY[k] + (endTrkY[k] - startTrkY[k]) * ease);
                }

                var aA = t < 0.8 ? Math.sin(t / 0.8 * Math.PI) : 0;
                sdDrawCanvas(set, {
                    xOffsets: curX,
                    trackYOffsets: curTrkY,
                    animActiveSeam: seamIdx,
                    arrowAlpha: aA
                });

                if (t < 1) {
                    sdAnimState[setId].raf = requestAnimationFrame(sdAnimStep);
                } else {
                    sdAnimState[setId].running = false;
                    var tmpDoor = st.doorOrder[d1];
                    st.doorOrder[d1] = st.doorOrder[d2];
                    st.doorOrder[d2] = tmpDoor;
                    sdDrawCanvas(set);
                }
            }

            sdAnimState[setId].raf = requestAnimationFrame(sdAnimStep);
            return;
        }

        // STEP 0: Reset button (Only in Step 0)
        if (e.target.closest('.sd-reset-btn') && currentStep === 0) {
            var rDoorCount = parseInt(set.querySelector('.sd-door-select .Select2__input').textContent, 10) || st.doorCount || 2;
            st.doorOrder = [];
            for (var ri = 0; ri < rDoorCount; ri++) {
                st.doorOrder.push(ri);
            }
            set.dataset.switched = '0';
            st.switched = 0;
            sdDrawCanvas(set);
            return;
        }

        // Delete set
        if (e.target.closest('.sd-btn-delete')) {
            if (container.querySelectorAll('.sd-set').length > 1) {
                delete sdSetStates[setId];
                set.remove();
                sdRenumber();
            } else {
                alert('You must have at least one set of doors.');
            }
            return;
        }

        // Add new set
        if (e.target.closest('.sd-btn-add-set')) {
            sdAddSet();
            return;
        }
    });

    // Input events (Width & Height validation) - Only for Step 0 Opening Size
    container.addEventListener('input', function (e) {
        var set = e.target.closest('.sd-set');
        if (!set) return;

        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var curStepEl = set.querySelector('.sd-step.current');
        var currentStep = curStepEl ? parseInt(curStepEl.dataset.step, 10) : 0;
        if (currentStep !== 0) return;

        if (e.target.classList.contains('sd-width')) {
            sdValidateWidth(set, false);
            st.doorOrder = [];
            for (var dwi = 0; dwi < (st.doorCount || 2); dwi++) {
                st.doorOrder.push(dwi);
            }
            set.dataset.switched = '0';
            sdUpdateDoorDropdown(set);
            sdDrawCanvas(set);
        }

        if (e.target.classList.contains('sd-height')) {
            sdValidateHeight(set, false);
            st.doorOrder = [];
            for (var dhi = 0; dhi < (st.doorCount || 2); dhi++) {
                st.doorOrder.push(dhi);
            }
            set.dataset.switched = '0';
            sdUpdateDoorDropdown(set);
            sdDrawCanvas(set);
        }
    });

    // Blur events (check minimum 1000mm on focus out) - Only for Step 0
    container.addEventListener('focusout', function (e) {
        var set = e.target.closest('.sd-set');
        if (!set) return;

        var curStepEl = set.querySelector('.sd-step.current');
        var currentStep = curStepEl ? parseInt(curStepEl.dataset.step, 10) : 0;
        if (currentStep !== 0) return;

        if (e.target.classList.contains('sd-width')) {
            sdValidateWidth(set, true);
            sdUpdateDoorDropdown(set);
        }

        if (e.target.classList.contains('sd-height')) {
            sdValidateHeight(set, true);
            sdUpdateDoorDropdown(set);
        }
    });

    // Close Select2 on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.sd-door-select')) {
            container.querySelectorAll('.Select2.is-open').forEach(function (s) {
                s.classList.remove('is-open');
            });
        }
    });

    // Redraw on window resize
    window.addEventListener('resize', function () {
        container.querySelectorAll('.sd-set:not(.collapsed)').forEach(function (set) {
            sdDrawCanvas(set);
        });
    });

})();
