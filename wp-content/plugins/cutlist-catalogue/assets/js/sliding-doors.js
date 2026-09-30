/**
 * Cutlist Sliding Wardrobe Doors Configurator
 * Logic for Step Tabs 1-5 (Opening size, Frame style, Door design, Panel design, Summary)
 */

(function () {
    'use strict';

    var DATA = window.slidingDoorsData || {};
    var CURRENCY = DATA.currencySymbol || '£';
    var ASSETS_URL = DATA.assetsUrl || '/wp-content/plugins/cutlist-catalogue/assets/';
    var ROOM_IMAGE_URL = (DATA.roomImageUrl) ? DATA.roomImageUrl : ASSETS_URL + 'images/room-reflection.jpg';
    var FRAMES = (DATA.frames && DATA.frames.length) ? DATA.frames : [
        {
            id: 'shaker', name: 'Shaker', code: 'shaker', basePrice: 0.00,
            description: 'Classic Shaker profile with clean borders and built-in finger pull.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Pure White', hex: '#f5f5f5', surcharge: 0.00 },
                { name: 'Cashmere', hex: '#d9d0c7', surcharge: 0.00 }
            ]
        },
        {
            id: 'palermo', name: 'Palermo', code: 'palermo', basePrice: 0.00,
            description: 'Soft curved ergonomic profile providing modern elegance and ease of glide.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Bronze', hex: '#6e5849', surcharge: 0.00 },
                { name: 'Brushed Brass', hex: '#c5a059', surcharge: 0.00 }
            ]
        },
        {
            id: 'supreme', name: 'Supreme', code: 'supreme', basePrice: 0.00,
            description: 'Sturdy architectural profile with deep recessed channel for large wardrobes.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Anthracite', hex: '#383e42', surcharge: 0.00 },
                { name: 'Pure White', hex: '#f5f5f5', surcharge: 0.00 }
            ]
        },
        {
            id: 'prestige', name: 'Prestige', code: 'prestige', basePrice: 0.00,
            description: 'Sculpted luxury profile with premium anodised and painted finishes.',
            colors: [
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Bronze', hex: '#6e5849', surcharge: 0.00 },
                { name: 'Brushed Brass', hex: '#c5a059', surcharge: 0.00 }
            ]
        },
        {
            id: 'slim', name: 'Slim', code: 'slim', basePrice: 0.00,
            description: 'Minimalist slimline edge maximizing the visible surface of glass and decorative wood decors.',
            colors: [
                { name: 'Matt Black', hex: '#1a1a1a', surcharge: 0.00 },
                { name: 'Satin Silver', hex: '#c8c8c8', surcharge: 0.00 },
                { name: 'Anthracite', hex: '#383e42', surcharge: 0.00 },
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
        { id: 'silver-mirror', name: 'Silver Mirror', category: 'mirrors', pricePerSqm: 30.00, swatchColor: '#ffffff', thickness: '4mm safety backed' },
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

    var DEFAULT_BRANDS = [
        'Egger',
        'Kronospan',
        'Xylo Surfaces',
        'Cleaf',
        'Saviola',
        'Swiss Krono',
        'Alvic',
        'Senoplast',
        'Serica',
        'Plywood'
    ];

    var FALLBACK_BOARDS = [
        // Egger - PerfectSense Matt & Feelwood & Eurodekor
        { id: '101', code: 'H1227-TM12', title: 'Abano Ash Brown', brand: 'Egger', collection: 'PerfectSense Matt', color: '#4a3b32', price: 82.00 },
        { id: '102', code: 'H1228-TM12', title: 'Abano Ash Anthracite', brand: 'Egger', collection: 'PerfectSense Matt', color: '#2b2a2e', price: 82.00 },
        { id: '103', code: 'H3041-TM12', title: 'Natural Eucalyptus', brand: 'Egger', collection: 'PerfectSense Matt', color: '#684d3e', price: 82.00 },
        { id: '104', code: 'U201-TM', title: 'Pebble Grey', brand: 'Egger', collection: 'PerfectSense Matt', color: '#b5b0a3', price: 78.00 },
        { id: '105', code: 'U250-TM', title: 'Cashmere Grey', brand: 'Egger', collection: 'PerfectSense Matt', color: '#a8947b', price: 78.00 },
        { id: '106', code: 'U398-TM', title: 'Truffle Brown', brand: 'Egger', collection: 'PerfectSense Matt', color: '#564440', price: 78.00 },
        { id: '107', code: 'U399-PM', title: 'Garnet Red', brand: 'Egger', collection: 'PerfectSense Matt', color: '#4a2529', price: 84.00 },
        { id: '108', code: 'H1180-ST37', title: 'Natural Halifax Oak', brand: 'Egger', collection: 'Feelwood', color: '#b89778', price: 86.00 },
        { id: '109', code: 'H3303-ST10', title: 'Natural Hamilton Oak', brand: 'Egger', collection: 'Feelwood', color: '#8f8577', price: 76.00 },
        { id: '110', code: 'H3730-ST10', title: 'Natural Hickory', brand: 'Egger', collection: 'Feelwood', color: '#705335', price: 78.00 },
        { id: '111', code: 'W980-SM', title: 'Platinum White', brand: 'Egger', collection: 'Eurodekor', color: '#f8fafc', price: 68.00 },
        { id: '112', code: 'U708-ST9', title: 'Light Grey', brand: 'Egger', collection: 'Eurodekor', color: '#d4d8db', price: 70.00 },
        { id: '113', code: 'U999-ST89', title: 'Black Mineral', brand: 'Egger', collection: 'Eurodekor', color: '#22252a', price: 72.00 },
        { id: '114', code: 'F186-ST9', title: 'Light Grey Chicago Concrete', brand: 'Egger', collection: 'Eurodekor', color: '#9ea3a8', price: 74.00 },
        { id: '115', code: 'H3131-ST12', title: 'Natural Davos Oak', brand: 'Egger', collection: 'Eurodekor', color: '#a08a75', price: 72.00 },
        { id: '116', code: 'U732-ST9', title: 'Dust Grey', brand: 'Egger', collection: 'Eurodekor', color: '#635952', price: 70.00 },
        { id: '117', code: 'H1145-ST10', title: 'Natural Bardolino Oak', brand: 'Egger', collection: 'Eurodekor', color: '#a6907c', price: 72.00 },

        // Kronospan
        { id: '201', code: '0164-MG', title: 'Anthracite', brand: 'Kronospan', collection: 'Trends', color: '#4d4640', price: 72.00 },
        { id: '202', code: '0190-PE', title: 'Black', brand: 'Kronospan', collection: 'Color Collection', color: '#1a1a1a', price: 68.00 },
        { id: '203', code: '0112-PE', title: 'Light Grey', brand: 'Kronospan', collection: 'Color Collection', color: '#cfd2d6', price: 68.00 },
        { id: '204', code: '0101-PE', title: 'Front White', brand: 'Kronospan', collection: 'Color Collection', color: '#f5f5f5', price: 65.00 },
        { id: '205', code: 'K003-PW', title: 'Gold Craft Oak', brand: 'Kronospan', collection: 'Contempo', color: '#ba9468', price: 76.00 },
        { id: '206', code: 'K006-PW', title: 'Amber Urban Oak', brand: 'Kronospan', collection: 'Contempo', color: '#9a754f', price: 76.00 },
        { id: '207', code: 'K021-SN', title: 'Barley Blackwood', brand: 'Kronospan', collection: 'Contempo', color: '#38322e', price: 76.00 },
        { id: '208', code: 'K086-PW', title: 'White Weathered Oak', brand: 'Kronospan', collection: 'Contempo', color: '#d8cfc4', price: 76.00 },

        // Xylo Surfaces
        { id: '301', code: 'LB01-LINETTE', title: 'Linette Cream', brand: 'Xylo Surfaces', collection: 'Syncro', color: '#dcd2bf', price: 85.00 },
        { id: '302', code: 'LB02-LINETTE', title: 'Linette Grey', brand: 'Xylo Surfaces', collection: 'Syncro', color: '#9e978e', price: 85.00 },
        { id: '303', code: 'XS-101', title: 'Smoked Oak', brand: 'Xylo Surfaces', collection: 'Natural Wood', color: '#544234', price: 88.00 },
        { id: '304', code: 'XS-204', title: 'Bleached Walnut', brand: 'Xylo Surfaces', collection: 'Natural Wood', color: '#b09e8b', price: 88.00 },

        // Cleaf
        { id: '401', code: 'FB02-PRIMOFIORE', title: 'Primofiore Leather', brand: 'Cleaf', collection: 'Hyper Ready', color: '#383531', price: 92.00 },
        { id: '402', code: 'FA44-PIOMBO', title: 'Piombo Matt', brand: 'Cleaf', collection: 'Texture Collection', color: '#4f5052', price: 92.00 },
        { id: '403', code: 'UA10-SHERWOOD', title: 'Sherwood Dark', brand: 'Cleaf', collection: 'Texture Collection', color: '#3a2d24', price: 92.00 },

        // Saviola
        { id: '501', code: 'SAV-01', title: 'Artisan Grey', brand: 'Saviola', collection: 'Design Collection', color: '#686a6c', price: 75.00 },
        { id: '502', code: 'SAV-02', title: 'Industrial Elm', brand: 'Saviola', collection: 'Ecological Panels', color: '#7a6752', price: 75.00 },
        { id: '503', code: 'SAV-03', title: 'Vintage Pine', brand: 'Saviola', collection: 'Ecological Panels', color: '#a48b6f', price: 75.00 },

        // Swiss Krono
        { id: '601', code: 'D3033-MX', title: 'Makalo Teak', brand: 'Swiss Krono', collection: 'One World', color: '#634d38', price: 80.00 },
        { id: '602', code: 'D4100-OW', title: 'OneWorld White', brand: 'Swiss Krono', collection: 'One World', color: '#f3f4f6', price: 72.00 },
        { id: '603', code: 'D2800-SD', title: 'Super Dark', brand: 'Swiss Krono', collection: 'Swiss CDF', color: '#232528', price: 86.00 },

        // Alvic
        { id: '701', code: 'ALV-L01', title: 'Luxe Blanco', brand: 'Alvic', collection: 'Luxe', color: '#ffffff', price: 95.00 },
        { id: '702', code: 'ALV-Z02', title: 'Zenit Antracita', brand: 'Alvic', collection: 'Zenit', color: '#383b40', price: 95.00 },
        { id: '703', code: 'ALV-S03', title: 'Syncron Titanio', brand: 'Alvic', collection: 'Syncron', color: '#787b80', price: 90.00 },

        // Senoplast
        { id: '801', code: 'SEN-8546', title: 'Senosan Gloss White', brand: 'Senoplast', collection: 'Senosan Acrylic', color: '#ffffff', price: 98.00 },
        { id: '802', code: 'SEN-8538', title: 'Senosan Matt Black', brand: 'Senoplast', collection: 'High Gloss', color: '#16181b', price: 98.00 },
        { id: '803', code: 'SEN-8522', title: 'Senosan Metallic Grey', brand: 'Senoplast', collection: 'Glass Effect', color: '#7c8088', price: 105.00 },

        // Serica
        { id: '901', code: 'SER-01', title: 'Serica Matt Denim', brand: 'Serica', collection: 'Supermatt', color: '#445668', price: 88.00 },
        { id: '902', code: 'SER-02', title: 'Serica Matt Sage', brand: 'Serica', collection: 'Supermatt', color: '#68786a', price: 88.00 },
        { id: '903', code: 'SER-03', title: 'Serica Matt Charcoal', brand: 'Serica', collection: 'Anti-fingerprint', color: '#2a2d32', price: 92.00 },

        // Plywood
        { id: '1001', code: 'PLY-BB', title: 'Birch Plywood Clear', brand: 'Plywood', collection: 'Birch Plywood', color: '#e2d3b8', price: 82.00 },
        { id: '1002', code: 'PLY-OAK', title: 'Crown Cut Oak Plywood', brand: 'Plywood', collection: 'Oak Veneered', color: '#c4a67e', price: 95.00 },
        { id: '1003', code: 'PLY-WAL', title: 'American Walnut Plywood', brand: 'Plywood', collection: 'Walnut Veneered', color: '#584334', price: 110.00 }
    ];

    function getBoardTextureImage(b) {
        if (!b) return '';
        if (b.image && typeof b.image === 'string' && b.image.trim() !== '') {
            return b.image;
        }
        var code = (b.code || '').toLowerCase();
        var title = (b.title || '').toLowerCase();
        var brand = (b.brand || '').toLowerCase();
        var collection = (b.collection || '').toLowerCase();
        var combined = code + ' ' + title + ' ' + brand + ' ' + collection;

        if (combined.indexOf('ash') !== -1 || combined.indexOf('h1227') !== -1 || combined.indexOf('h1228') !== -1 || combined.indexOf('truffle') !== -1 || combined.indexOf('u398') !== -1 || combined.indexOf('h3041') !== -1 || combined.indexOf('eucalyptus') !== -1) {
            return ASSETS_URL + 'images/decors/dark-ash.jpg';
        }
        if (combined.indexOf('walnut') !== -1 || combined.indexOf('teak') !== -1 || combined.indexOf('elm') !== -1 || combined.indexOf('pine') !== -1 || combined.indexOf('makalo') !== -1 || combined.indexOf('d3033') !== -1 || combined.indexOf('xs-101') !== -1 || combined.indexOf('xs-204') !== -1 || combined.indexOf('sav-02') !== -1 || combined.indexOf('sav-03') !== -1 || combined.indexOf('ply-wal') !== -1) {
            return ASSETS_URL + 'images/decors/walnut.jpg';
        }
        if (combined.indexOf('black') !== -1 || combined.indexOf('anthracite') !== -1 || combined.indexOf('u999') !== -1 || combined.indexOf('0190') !== -1 || combined.indexOf('0164') !== -1 || combined.indexOf('k021') !== -1 || combined.indexOf('sherwood') !== -1 || combined.indexOf('ua10') !== -1 || combined.indexOf('super dark') !== -1 || combined.indexOf('d2800') !== -1) {
            return ASSETS_URL + 'images/decors/black-oak.jpg';
        }
        if (combined.indexOf('concrete') !== -1 || combined.indexOf('piombo') !== -1 || combined.indexOf('fa44') !== -1 || combined.indexOf('primofiore') !== -1 || combined.indexOf('fb02') !== -1 || combined.indexOf('f186') !== -1 || combined.indexOf('mineral') !== -1 || combined.indexOf('artisan') !== -1 || combined.indexOf('sav-01') !== -1 || combined.indexOf('chicago') !== -1 || combined.indexOf('stucco') !== -1) {
            return ASSETS_URL + 'images/decors/concrete.jpg';
        }
        if (combined.indexOf('white') !== -1 || combined.indexOf('w980') !== -1 || combined.indexOf('0101') !== -1 || combined.indexOf('d4100') !== -1 || combined.indexOf('blanco') !== -1 || combined.indexOf('alv-l01') !== -1) {
            return '';
        }
        // Default warm woodgrain
        return ASSETS_URL + 'images/decors/halifax-oak.jpg';
    }

    var rawBoards = (DATA.boards && DATA.boards.length) ? DATA.boards : [];
    var BOARDS = rawBoards.slice();
    FALLBACK_BOARDS.forEach(function (fb) {
        var exists = BOARDS.find(function (b) {
            return (b.code && b.code.toLowerCase() === fb.code.toLowerCase()) || String(b.id) === String(fb.id);
        });
        if (!exists) {
            BOARDS.push(fb);
        }
    });
    BOARDS.forEach(function (b) {
        if (!b.image) {
            b.image = getBoardTextureImage(b);
        }
    });
    var PRICING = DATA.pricing || {
        trackPerMeter: 42.00,
        softCloseUnitPrice: 38.50,
        softClosePair: 77.00,
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
    var currentBoardBrand = 'Egger';
    var currentBoardCollection = '';
    var currentBoardSearch = '';

    var activeFrameModalSetId = null;
    var tempFrameStyle = null;
    var tempFrameColor = null;
    var tempActiveThumbIdx = 0;

    var activeDoorDesignModalSetId = null;
    var tempDoorDesign = null;
    var tempDoorOrientation = 'horizontal';
    var tempCustomSplits = null;

    /**
     * Helpers for multi-selection check & image caching
     */
    function isDoorSelected(st, dIdx) {
        if (!st || !st.selectedDoorIndices || !Array.isArray(st.selectedDoorIndices)) return false;
        return st.selectedDoorIndices.indexOf(dIdx) !== -1;
    }

    function isPanelSelected(st, dIdx, pIdx) {
        if (!st || !st.selectedPanels || !Array.isArray(st.selectedPanels)) return false;
        return st.selectedPanels.some(function (p) {
            return p && p.doorIdx === dIdx && p.panelIdx === pIdx;
        });
    }

    var imageCache = {};

    function getCachedImage(src, onLoaded) {
        if (!src) return null;
        if (imageCache[src]) {
            return imageCache[src];
        }
        var img = new Image();
        img.crossOrigin = 'anonymous';
        img.src = src;
        img.onload = function () {
            if (typeof onLoaded === 'function') {
                onLoaded();
            }
        };
        imageCache[src] = img;
        return img;
    }

    function drawCoverImage(ctx, img, x, y, w, h) {
        var imgW = img.naturalWidth || img.width;
        var imgH = img.naturalHeight || img.height;
        if (!imgW || !imgH) {
            ctx.drawImage(img, x, y, w, h);
            return;
        }
        var scale = Math.max(w / imgW, h / imgH);
        var sw = w / scale;
        var sh = h / scale;
        var sx = (imgW - sw) / 2;
        var sy = (imgH - sh) / 2;

        ctx.save();
        ctx.beginPath();
        ctx.rect(x, y, w, h);
        ctx.clip();
        ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
        ctx.restore();
    }

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
        var defaultGlass = GLASS_LIST[0] || { id: 'silver-mirror', name: 'Silver Mirror', category: 'mirrors', pricePerSqm: 30.00, swatchColor: '#ffffff', thickness: '4mm safety backed' };
        var defaultBoard = (BOARDS && BOARDS.length) ? BOARDS[0] : { id: 0, title: 'Natural Halifax Oak', code: 'H1180 ST37', brand: 'Egger', price: 168.50 };

        var initialDoorDesigns = [
            { design: defaultDesign, orientation: 'horizontal', customSplits: null, panels: null },
            { design: defaultDesign, orientation: 'horizontal', customSplits: null, panels: null }
        ];

        sdSetStates[num] = {
            width: 3000,
            height: 3000,
            doorCount: 2,
            frameStyle: defaultFrame,
            frameColor: defaultColor,
            doorDesign: defaultDesign,
            orientation: 'horizontal',
            doorDesigns: initialDoorDesigns,
            selectedDoorIndices: [],
            selectedPanels: [],
            panelType: null, // default white until customized
            glassItem: null,
            boardItem: null,
            doorSoftClose: [
                { left: false, right: false },
                { left: false, right: false }
            ],
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
            '<div class="sd-selection-warning-wrap sd-door-warning" style="display:none;">' +
            '<div class="sd-warning-bar">' +
            '<div class="sd-warning-bar-left">' +
            '<span class="sd-info-circle-icon">!</span>' +
            '<span>Please select a door from the preview above to change its design layout.</span>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="sd-designs-grid"></div>' +
            '</div>' +
            '</div>' +

            // STEP 3: Panel design panel (Screenshot 2)
            '<div class="sd-step-panel" data-panel="3">' +
            '<div class="sd-panel-design-step">' +
            '<div class="sd-selection-warning-wrap sd-panel-warning" style="display:none;">' +
            '<div class="sd-warning-bar">' +
            '<div class="sd-warning-bar-left">' +
            '<span class="sd-info-circle-icon">!</span>' +
            '<span>Please select a panel from the preview above to change its material.</span>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="sd-panel-design-grid">' +

            // Coloured glass card
            '<div class="sd-panel-card active" data-type="glass">' +
            '<div class="sd-panel-swatches-grid">' +
            '<div class="sd-panel-mini-swatch" style="background:#ffffff;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#f2ecdf;"></div>' +
            '<div class="sd-panel-mini-swatch" style="background:#ffffff;"></div>' +
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
            '<div class="sd-door-overlay-container"></div>' +
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
            var activePrice = (isActive && st.frameColor && typeof st.frameColor.basePrice === 'number') ? st.frameColor.basePrice : ((f && typeof f.basePrice === 'number') ? f.basePrice : 0.00);
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
        if (!grid) return;

        var hasSelectedDoors = (st.selectedDoorIndices && st.selectedDoorIndices.length > 0);
        var doorIdx = hasSelectedDoors ? st.selectedDoorIndices[0] : null;
        var curDoorObj = (doorIdx !== null && st.doorDesigns && st.doorDesigns[doorIdx]) ? st.doorDesigns[doorIdx] : null;
        var curDesign = curDoorObj ? curDoorObj.design : null;
        var curOrient = curDoorObj ? (curDoorObj.orientation || 'horizontal') : 'horizontal';

        grid.innerHTML = DESIGNS.map(function (d) {
            var isActive = Boolean(curDesign && curDesign.panels === d.panels);
            var orientToUse = (isActive && curOrient) ? curOrient : 'horizontal';
            return '<div class="sd-design-card ' + (isActive ? 'active' : '') + '" data-panels="' + d.panels + '">' +
                '<div class="sd-design-preview">' + getDesignSVG(d.panels, orientToUse) + '</div>' +
                '<div class="sd-design-footer">' +
                '<div class="sd-design-title">' + d.name + '</div>' +
                (d.subtitle ? '<div class="sd-design-subtitle">' + d.subtitle + '</div>' : '') +
                '</div>' +
                '</div>';
        }).join('');
    }

    /**
     * Render Panel Design Step 3 Selection
     */
    function sdRenderPanelDesign(set) {
        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var hasSelectedPanels = (st.selectedPanels && st.selectedPanels.length > 0);
        var curMatType = null;
        if (hasSelectedPanels) {
            var firstSp = st.selectedPanels[0];
            if (st.doorDesigns && st.doorDesigns[firstSp.doorIdx]) {
                var dObj = st.doorDesigns[firstSp.doorIdx];
                if (dObj.panels && dObj.panels[firstSp.panelIdx]) {
                    curMatType = dObj.panels[firstSp.panelIdx] ? dObj.panels[firstSp.panelIdx].type : null;
                }
            }
        }

        var cards = set.querySelectorAll('.sd-panel-card');
        cards.forEach(function (card) {
            card.classList.toggle('active', Boolean(curMatType && card.dataset.type === curMatType));
        });
    }

    /**
     * Generate miniature door SVG for summary card thumbnail
     */
    function getSummaryDoorMiniSVG(doorIndex, st) {
        var dObj = (st.doorDesigns && st.doorDesigns[doorIndex]) ? st.doorDesigns[doorIndex] : { design: DESIGNS[0], orientation: 'horizontal', panels: [] };
        var pCount = (dObj.design && dObj.design.panels) ? dObj.design.panels : 1;
        var orient = dObj.orientation || 'horizontal';
        var frameColorHex = (st.frameColor && st.frameColor.hex) ? st.frameColor.hex : '#8596a6';

        var svgW = 44;
        var svgH = 62;
        var postW = 3;
        var railH = 3.5;
        var innerW = svgW - postW * 2;
        var innerH = svgH - railH * 2;

        var panelsSvg = '';

        if (pCount === 1) {
            var pMat = (dObj.panels && dObj.panels[0]) ? dObj.panels[0] : null;
            var fill = '#f8fafc';
            if (pMat && pMat.type === 'glass' && pMat.item) {
                fill = pMat.item.swatchColor || '#f1f5f9';
            } else if (pMat && pMat.type === 'board' && pMat.item) {
                fill = pMat.item.color || '#b89778';
            }
            panelsSvg += '<rect x="' + postW + '" y="' + railH + '" width="' + innerW + '" height="' + innerH + '" fill="' + fill + '"/>';
        } else if (orient === 'vertical') {
            var subW = innerW / pCount;
            for (var vi = 0; vi < pCount; vi++) {
                var pMatV = (dObj.panels && dObj.panels[vi]) ? dObj.panels[vi] : null;
                var fillV = '#f8fafc';
                if (pMatV && pMatV.type === 'glass' && pMatV.item) {
                    fillV = pMatV.item.swatchColor || '#f1f5f9';
                } else if (pMatV && pMatV.type === 'board' && pMatV.item) {
                    fillV = pMatV.item.color || '#b89778';
                }
                var subX = postW + vi * subW;
                panelsSvg += '<rect x="' + subX + '" y="' + railH + '" width="' + subW + '" height="' + innerH + '" fill="' + fillV + '"/>';
                if (pCount > 1) {
                    var letV = String.fromCharCode(65 + vi);
                    panelsSvg += '<text x="' + (subX + subW / 2) + '" y="' + (railH + innerH / 2) + '" text-anchor="middle" dominant-baseline="central" font-size="7.5" font-family="system-ui, -apple-system, sans-serif" font-weight="700" fill="#334155" opacity="0.85">' + letV + '</text>';
                }
                if (vi > 0) {
                    panelsSvg += '<line x1="' + subX + '" y1="' + railH + '" x2="' + subX + '" y2="' + (railH + innerH) + '" stroke="' + frameColorHex + '" stroke-width="1.5"/>';
                }
            }
        } else {
            var subH = innerH / pCount;
            for (var hi = 0; hi < pCount; hi++) {
                var pMatH = (dObj.panels && dObj.panels[hi]) ? dObj.panels[hi] : null;
                var fillH = '#f8fafc';
                if (pMatH && pMatH.type === 'glass' && pMatH.item) {
                    fillH = pMatH.item.swatchColor || '#f1f5f9';
                } else if (pMatH && pMatH.type === 'board' && pMatH.item) {
                    fillH = pMatH.item.color || '#b89778';
                }
                var subY = railH + hi * subH;
                panelsSvg += '<rect x="' + postW + '" y="' + subY + '" width="' + innerW + '" height="' + subH + '" fill="' + fillH + '"/>';
                if (pCount > 1) {
                    var letH = String.fromCharCode(65 + hi);
                    panelsSvg += '<text x="' + (postW + innerW / 2) + '" y="' + (subY + subH / 2) + '" text-anchor="middle" dominant-baseline="central" font-size="7.5" font-family="system-ui, -apple-system, sans-serif" font-weight="700" fill="#334155" opacity="0.85">' + letH + '</text>';
                }
                if (hi > 0) {
                    panelsSvg += '<line x1="' + postW + '" y1="' + subY + '" x2="' + (postW + innerW) + '" y2="' + subY + '" stroke="' + frameColorHex + '" stroke-width="1.5"/>';
                }
            }
        }

        return '<svg width="' + svgW + '" height="' + svgH + '" viewBox="0 0 ' + svgW + ' ' + svgH + '" fill="none">' +
            '<rect x="0.75" y="0.75" width="' + (svgW - 1.5) + '" height="' + (svgH - 1.5) + '" rx="2" fill="' + frameColorHex + '" stroke="#475569" stroke-width="1.2"/>' +
            panelsSvg +
            '<rect x="' + postW + '" y="' + railH + '" width="' + innerW + '" height="' + innerH + '" fill="none" stroke="#64748b" stroke-width="1"/>' +
            '</svg>';
    }

    /**
     * Render Summary Step 4 Breakdown and Pricing (Matching Reference Screenshot)
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
        var overlapMm = 30;
        var doorWidthMm = Math.round((w + (dc - 1) * overlapMm) / dc);
        var doorHeightMm = h - 40; // 40mm top track clearance
        var totalAreaSqm = (w / 1000) * (h / 1000);
        var singleDoorArea = (doorWidthMm / 1000) * (doorHeightMm / 1000);
        var isGlassMaterial = st.panelType === 'glass';
        var doorKgPerSqm = isGlassMaterial ? 10.5 : 12.0;
        var doorEstWeightKg = Math.round((singleDoorArea * doorKgPerSqm + 2.8) * 10) / 10;

        // Frame Pricing
        var frameBase = (st.frameColor && typeof st.frameColor.basePrice === 'number') ? st.frameColor.basePrice : ((st.frameStyle && typeof st.frameStyle.basePrice === 'number') ? st.frameStyle.basePrice : 0.00);
        var frameSurcharge = (st.frameColor && st.frameColor.surcharge) ? st.frameColor.surcharge : 0.00;
        var totalFrameCost = dc * (frameBase + frameSurcharge);

        // Running Track Cost
        var trackCost = (w / 1000) * (PRICING.trackPerMeter || 42.00);

        // Divider H-Bars Cost
        var dividersCost = 0;
        var layoutNames = [];
        for (var dsi = 0; dsi < dc; dsi++) {
            var dObj = (st.doorDesigns && st.doorDesigns[dsi]) ? st.doorDesigns[dsi] : { design: st.doorDesign || DESIGNS[0], orientation: st.orientation || 'horizontal' };
            var dDes = dObj.design || DESIGNS[0];
            var dHbar = (dDes && dDes.hBarPrice) ? dDes.hBarPrice : 0;
            dividersCost += dHbar;
            layoutNames.push(dDes.name + (dDes.panels > 1 ? ' [' + (dObj.orientation || 'horizontal') + ']' : ''));
        }
        var uniqueLayouts = Array.from(new Set(layoutNames));
        var layoutSummaryText = (uniqueLayouts.length === 1) ? uniqueLayouts[0] : layoutNames.join(', ');

        // Infill Panels Cost & per-door panel labels
        var infillCost = 0;
        var matDescriptions = [];
        var doorPanelLabels = [];

        for (var di = 0; di < dc; di++) {
            var dObj = (st.doorDesigns && st.doorDesigns[di]) ? st.doorDesigns[di] : { design: DESIGNS[0], panels: [] };
            var pCount = (dObj.design && dObj.design.panels) ? dObj.design.panels : 1;
            var dArea = totalAreaSqm / dc;
            var pArea = dArea / pCount;
            var thisDoorPanels = [];

            for (var pi = 0; pi < pCount; pi++) {
                var pMat = (dObj.panels && dObj.panels[pi]) ? dObj.panels[pi] : null;
                var pName = 'White Panel';
                if (pMat && pMat.type === 'glass' && pMat.item) {
                    var g = pMat.item;
                    infillCost += pArea * (g.pricePerSqm || 30.00);
                    pName = g.name;
                } else if (pMat && pMat.type === 'board' && pMat.item) {
                    var b = pMat.item;
                    infillCost += pArea * (PRICING.mfcBasePerSqm || 28.00);
                    pName = b.title || b.code || 'Decorative Board';
                } else {
                    infillCost += pArea * (PRICING.mfcBasePerSqm || 28.00);
                    pName = 'White Panel';
                }
                matDescriptions.push(pName);
                var letter = String.fromCharCode(65 + pi);
                thisDoorPanels.push({ label: (pCount > 1 ? 'Panel ' + letter : 'Panel'), name: pName });
            }
            doorPanelLabels.push(thisDoorPanels);
        }

        var uniqueMats = Array.from(new Set(matDescriptions));
        var infillName = (uniqueMats.length === 1) ? uniqueMats[0] : (uniqueMats.slice(0, 3).join(', ') + (uniqueMats.length > 3 ? '...' : ''));

        // Combined Door Package Cost (Doors = Frames + Panel Infill + Dividers)
        var totalDoorsCost = totalFrameCost + dividersCost + infillCost;

        // Soft-closers calculation (38.50 per door where Left, Right, or Both is selected)
        if (!st.doorSoftClose) st.doorSoftClose = [];
        while (st.doorSoftClose.length < dc) {
            st.doorSoftClose.push({ left: false, right: false });
        }

        var softCloseDoorsCount = 0;
        for (var sci = 0; sci < dc; sci++) {
            var scObj = st.doorSoftClose[sci] || { left: false, right: false };
            if (scObj.left || scObj.right) {
                softCloseDoorsCount++;
            }
        }
        var softCloseUnitPrice = (PRICING.softCloseUnitPrice || 38.50);
        var softCloseCost = softCloseDoorsCount * softCloseUnitPrice;

        // Totals
        var subtotal = trackCost + totalDoorsCost + softCloseCost;
        var vat = subtotal * (PRICING.vatRate || 0.20);
        var totalIncVat = subtotal + vat;

        var frameStyleName = (st.frameStyle && st.frameStyle.name) ? st.frameStyle.name : 'Palermo';
        var frameColorName = (st.frameColor && st.frameColor.name) ? st.frameColor.name : 'Silver satin';
        var frameFullName = frameStyleName + ' (' + frameColorName + ')';

        // 1. Build Left Column HTML (Your Configuration Card + Door Details Section)
        var specHtml =
            '<div class="sd-summary-config-card">' +
                '<div class="sd-summary-card-header">' +
                    '<div class="sd-summary-card-header-left">' +
                        '<div class="sd-summary-header-icon">' +
                            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                                '<rect x="3" y="3" width="18" height="18" rx="2"/>' +
                                '<line x1="12" y1="3" x2="12" y2="21"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div>' +
                            '<h3 class="sd-summary-header-title">Your Configuration</h3>' +
                            '<p class="sd-summary-header-desc">Review all details of your sliding doors before adding to basket.</p>' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="sd-summary-btn-edit-config" data-step="0">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
                        '<span>Edit configuration</span>' +
                    '</button>' +
                '</div>' +

                '<div class="sd-summary-info-grid">' +
                    '<div class="sd-summary-info-item">' +
                        '<div class="sd-summary-info-icon">' +
                            '<svg width="22" height="26" viewBox="0 0 24 28" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
                                '<rect x="3" y="3" width="18" height="22" rx="1.5"/>' +
                                '<line x1="12" y1="3" x2="12" y2="25"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-summary-info-text">' +
                            '<div class="sd-summary-info-label">Opening size</div>' +
                            '<div class="sd-summary-info-val">' + w + 'mm (W) &times; ' + h + 'mm (H)</div>' +
                        '</div>' +
                    '</div>' +

                    '<div class="sd-summary-info-item">' +
                        '<div class="sd-summary-info-icon">' +
                            '<svg width="22" height="26" viewBox="0 0 24 28" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
                                '<rect x="3" y="4" width="18" height="20" rx="2"/>' +
                                '<path d="M7 8h10v12"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-summary-info-text">' +
                            '<div class="sd-summary-info-label">Frame profile &amp; colour</div>' +
                            '<div class="sd-summary-info-val">' + frameFullName + '</div>' +
                        '</div>' +
                    '</div>' +

                    '<div class="sd-summary-info-item">' +
                        '<div class="sd-summary-info-icon">' +
                            '<svg width="22" height="26" viewBox="0 0 24 28" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
                                '<rect x="5" y="3" width="14" height="22" rx="1.5"/>' +
                                '<circle cx="15" cy="14" r="1.2" fill="#64748b"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-summary-info-text">' +
                            '<div class="sd-summary-info-label">Door count</div>' +
                            '<div class="sd-summary-info-val">' + dc + ' doors</div>' +
                        '</div>' +
                    '</div>' +

                    '<div class="sd-summary-info-item">' +
                        '<div class="sd-summary-info-icon">' +
                            '<svg width="26" height="22" viewBox="0 0 28 24" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' +
                                '<line x1="2" y1="18" x2="26" y2="6"/>' +
                                '<line x1="2" y1="22" x2="26" y2="10"/>' +
                                '<line x1="2" y1="18" x2="2" y2="22"/>' +
                                '<line x1="26" y1="6" x2="26" y2="10"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-summary-info-text">' +
                            '<div class="sd-summary-info-label">Running tracks</div>' +
                            '<div class="sd-summary-info-val">Top &amp; Bottom Track Set Included</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        // 2. Door Details Section
        specHtml +=
            '<div class="sd-summary-doors-section">' +
                '<div class="sd-summary-doors-header">' +
                    '<div class="sd-summary-doors-title">' +
                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                            '<rect x="3" y="3" width="18" height="18" rx="2"/>' +
                            '<line x1="12" y1="3" x2="12" y2="21"/>' +
                        '</svg>' +
                        '<span>Door Details (' + dc + ' doors)</span>' +
                    '</div>' +
                    '<button type="button" class="sd-summary-btn-add-doors" data-step="2">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                        '<span>Add / Edit Doors</span>' +
                    '</button>' +
                '</div>' +
                '<div class="sd-summary-doors-list">';

        for (var dii = 0; dii < dc; dii++) {
            var dSc = st.doorSoftClose[dii] || { left: false, right: false };
            var panelsList = doorPanelLabels[dii] || [{ label: 'Panel', name: infillName }];
            var isMultiPanel = (panelsList.length > 1);
            var doorPanelsHtml = panelsList.map(function(p) {
                if (isMultiPanel) {
                    return '<span class="sd-summary-panel-entry"><strong class="sd-summary-panel-letter">' + p.label + ':</strong> ' + p.name + '</span>';
                }
                return '<span class="sd-summary-panel-entry">' + p.name + '</span>';
            }).join('<span class="sd-summary-panel-sep"> / </span>');
            var panelsLabel = isMultiPanel ? 'Panels' : 'Panel';

            specHtml +=
                '<div class="sd-summary-door-card-new">' +
                    '<div class="sd-summary-door-thumb-box">' +
                        getSummaryDoorMiniSVG(dii, st) +
                    '</div>' +
                    '<div class="sd-summary-door-main">' +
                        '<div class="sd-summary-door-top">' +
                            '<span class="sd-summary-door-name">Door ' + (dii + 1) + '</span>' +
                        '</div>' +
                        '<div class="sd-summary-door-specs-row">' +
                            '<div class="sd-summary-door-spec-item">' +
                                '<span class="sd-summary-spec-icon">' +
                                    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16L12 5z"/><circle cx="12" cy="14" r="1.8"/></svg>' +
                                '</span>' +
                                '<div class="sd-summary-spec-detail">' +
                                    '<span class="sd-summary-spec-label">Size</span>' +
                                    '<strong class="sd-summary-spec-value">' + doorWidthMm + ' &times; ' + doorHeightMm + 'mm</strong>' +
                                '</div>' +
                            '</div>' +

                            '<div class="sd-summary-spec-divider"></div>' +

                            '<div class="sd-summary-door-spec-item">' +
                                '<span class="sd-summary-spec-icon">' +
                                    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 10h12l1.5 10a2 2 0 0 1-2 2H6.5a2 2 0 0 1-2-2L6 10z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/><circle cx="12" cy="15" r="1.5"/></svg>' +
                                '</span>' +
                                '<div class="sd-summary-spec-detail">' +
                                    '<span class="sd-summary-spec-label">Weight</span>' +
                                    '<strong class="sd-summary-spec-value">' + doorEstWeightKg + ' kg</strong>' +
                                    '</div>' +
                            '</div>' +

                            '<div class="sd-summary-spec-divider"></div>' +

                            '<div class="sd-summary-door-spec-item sd-summary-door-panels-item">' +
                                '<span class="sd-summary-spec-icon">' +
                                    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="12" height="16" rx="1.5"/><path d="M9 4V2.5A1.5 1.5 0 0 1 10.5 1h7A1.5 1.5 0 0 1 19 2.5V16a1.5 1.5 0 0 1-1.5 1.5H17"/></svg>' +
                                '</span>' +
                                '<div class="sd-summary-spec-detail">' +
                                    '<span class="sd-summary-spec-label">' + panelsLabel + '</span>' +
                                    '<div class="sd-summary-spec-value sd-summary-panels-val">' + doorPanelsHtml + '</div>' +
                                '</div>' +
                            '</div>' +

                            '<div class="sd-summary-spec-divider"></div>' +

                            '<div class="sd-summary-door-spec-item sd-summary-door-softclose-item">' +
                                '<span class="sd-summary-spec-label sd-softclose-title">Soft-closer</span>' +
                                '<div class="sd-softclose-controls">' +
                                    '<label>' +
                                        '<input type="checkbox" class="sd-door-softclose-chk" data-door="' + dii + '" data-side="left"' + (dSc.left ? ' checked' : '') + '>' +
                                        '<span>Left</span>' +
                                    '</label>' +
                                    '<label>' +
                                        '<input type="checkbox" class="sd-door-softclose-chk" data-door="' + dii + '" data-side="right"' + (dSc.right ? ' checked' : '') + '>' +
                                        '<span>Right</span>' +
                                    '</label>' +
                                    '<span class="sd-softclose-price-tag">(+' + CURRENCY + softCloseUnitPrice.toFixed(2) + '/door)</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }

        specHtml +=
                '</div>' +
            '</div>';

        specBox.innerHTML = specHtml;

        // 2. Build Right Column HTML (Quotation Summary Card)
        var priceHtml =
            '<div class="sd-quotation-header">' +
                '<div class="sd-quotation-icon-box">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
                        '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>' +
                    '</svg>' +
                '</div>' +
                '<div>' +
                    '<h3 class="sd-quotation-title">Quotation Summary</h3>' +
                    '<p class="sd-quotation-desc">Here is the breakdown of your configuration.</p>' +
                '</div>' +
            '</div>' +

            '<div class="sd-quotation-items">' +
                // Item 1: Doors package
                '<div class="sd-quotation-item">' +
                    '<div class="sd-quotation-item-left">' +
                        '<div class="sd-quotation-item-thumb">' +
                            '<svg width="34" height="42" viewBox="0 0 34 42" fill="none">' +
                                '<rect x="2" y="2" width="16" height="38" rx="1.5" stroke="#64748b" stroke-width="1.8" fill="#f8fafc"/>' +
                                '<rect x="16" y="2" width="16" height="38" rx="1.5" stroke="#475569" stroke-width="1.8" fill="#ffffff"/>' +
                                '<line x1="2" y1="21" x2="18" y2="21" stroke="#cbd5e1" stroke-width="1"/>' +
                                '<line x1="16" y1="21" x2="32" y2="21" stroke="#cbd5e1" stroke-width="1"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-quotation-item-info">' +
                            '<h4 class="sd-quotation-item-name">' + dc + ' &times; Custom Sliding Doors</h4>' +
                            '<p class="sd-quotation-item-sub">' + frameStyleName + ' frame &bull; ' + infillName + '</p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="sd-quotation-item-price">' + CURRENCY + totalDoorsCost.toFixed(2) + '</div>' +
                '</div>' +

                // Item 2: Running tracks
                '<div class="sd-quotation-item">' +
                    '<div class="sd-quotation-item-left">' +
                        '<div class="sd-quotation-item-thumb">' +
                            '<svg width="34" height="42" viewBox="0 0 34 42" fill="none">' +
                                '<rect x="4" y="8" width="26" height="10" rx="1" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1.2"/>' +
                                '<rect x="4" y="24" width="26" height="10" rx="1" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1.2"/>' +
                                '<line x1="9" y1="8" x2="9" y2="18" stroke="#94a3b8" stroke-width="1"/>' +
                                '<line x1="25" y1="8" x2="25" y2="18" stroke="#94a3b8" stroke-width="1"/>' +
                                '<line x1="9" y1="24" x2="9" y2="34" stroke="#94a3b8" stroke-width="1"/>' +
                                '<line x1="25" y1="24" x2="25" y2="34" stroke="#94a3b8" stroke-width="1"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-quotation-item-info">' +
                            '<h4 class="sd-quotation-item-name">Top &amp; Bottom Running Tracks</h4>' +
                            '<p class="sd-quotation-item-sub">Aluminium top &amp; bottom track set (' + w + 'mm width)</p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="sd-quotation-item-price">' + CURRENCY + trackCost.toFixed(2) + '</div>' +
                '</div>';

        // Item 3: Soft-closers (if any)
        if (softCloseDoorsCount > 0) {
            priceHtml +=
                '<div class="sd-quotation-item">' +
                    '<div class="sd-quotation-item-left">' +
                        '<div class="sd-quotation-item-thumb">' +
                            '<svg width="34" height="42" viewBox="0 0 34 42" fill="none">' +
                                '<rect x="4" y="14" width="26" height="14" rx="2" fill="#dcfce7" stroke="#16a34a" stroke-width="1.2"/>' +
                                '<circle cx="12" cy="21" r="3" fill="#16a34a"/>' +
                                '<line x1="16" y1="21" x2="24" y2="21" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/>' +
                            '</svg>' +
                        '</div>' +
                        '<div class="sd-quotation-item-info">' +
                            '<h4 class="sd-quotation-item-name">Soft-closer Dampers (' + softCloseDoorsCount + ' ' + (softCloseDoorsCount === 1 ? 'Door' : 'Doors') + ')</h4>' +
                            '<p class="sd-quotation-item-sub">Smooth soft-closing mechanism (+' + CURRENCY + softCloseUnitPrice.toFixed(2) + '/door)</p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="sd-quotation-item-price">' + CURRENCY + softCloseCost.toFixed(2) + '</div>' +
                '</div>';
        }

        priceHtml +=
            '</div>' +

            '<div class="sd-quotation-breakdown">' +
                '<div class="sd-quotation-row">' +
                    '<span class="sd-quotation-row-label">Subtotal (Net)</span>' +
                    '<strong class="sd-quotation-row-val">' + CURRENCY + subtotal.toFixed(2) + '</strong>' +
                '</div>' +
                '<div class="sd-quotation-row">' +
                    '<span class="sd-quotation-row-label">VAT (' + Math.round((PRICING.vatRate || 0.20) * 100) + '%)</span>' +
                    '<strong class="sd-quotation-row-val">' + CURRENCY + vat.toFixed(2) + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="sd-quotation-total-box">' +
                '<span class="sd-quotation-total-label">Total (Inc. VAT)</span>' +
                '<span class="sd-quotation-total-val">' + CURRENCY + totalIncVat.toFixed(2) + '</span>' +
            '</div>';

        priceBox.innerHTML = priceHtml;

        var addCartBtn = set.querySelector('.sd-btn-add-cart');
        if (addCartBtn) {
            if (st.isAddedToCart) {
                addCartBtn.classList.add('is-added');
                addCartBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><span>View Cart</span>';
            } else {
                addCartBtn.classList.remove('is-added');
                addCartBtn.innerHTML = ICONS.cartIcon + '<span>Add to Basket</span>';
            }
        }
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

        // Studio Background Gradient
        var bgGrad = ctx.createLinearGradient(0, 0, 0, ch);
        bgGrad.addColorStop(0, '#f8fafc');
        bgGrad.addColorStop(0.65, '#f1f5f9');
        bgGrad.addColorStop(1, '#e2e8f0');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, cw, ch);

        var floorY = Math.round(ch * 0.84);

        // Skirting / Floor Base line
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(0, floorY);
        ctx.lineTo(cw, floorY);
        ctx.stroke();

        // Floor surface below baseline
        var floorGrad = ctx.createLinearGradient(0, floorY, 0, ch);
        floorGrad.addColorStop(0, '#e2e8f0');
        floorGrad.addColorStop(0.3, '#f1f5f9');
        floorGrad.addColorStop(1, '#f8fafc');
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

        // Wardrobe floor contact shadow
        var floorShadowGrad = ctx.createRadialGradient(
            fx + fw / 2, floorY + 2, fw * 0.1,
            fx + fw / 2, floorY + 2, fw * 0.52
        );
        floorShadowGrad.addColorStop(0, 'rgba(15, 23, 42, 0.22)');
        floorShadowGrad.addColorStop(0.6, 'rgba(15, 23, 42, 0.06)');
        floorShadowGrad.addColorStop(1, 'rgba(15, 23, 42, 0)');
        ctx.fillStyle = floorShadowGrad;
        ctx.fillRect(fx - 15, floorY - 2, fw + 30, 14);

        var railH = Math.max(12, Math.round(fh * 0.035));
        var btmH = Math.max(8, Math.round(fh * 0.04));
        var postW = Math.max(8, Math.round(fw * 0.016));
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

        var curStepEl = set.querySelector('.sd-step.current');
        var currentStep = curStepEl ? parseInt(curStepEl.dataset.step, 10) : 0;
        var isStep2 = (currentStep === 2);
        var isStep3 = (currentStep === 3);

        // Helper to draw realistic room interior reflection seamlessly across door panels
        function drawDoorRoomReflection(pRect, tintType, withSheen) {
            var roomImg = getCachedImage(ROOM_IMAGE_URL, function () {
                sdDrawCanvas(set, opts);
            });

            if (roomImg && roomImg.complete && roomImg.naturalWidth > 0) {
                var imgW = roomImg.naturalWidth || roomImg.width;
                var imgH = roomImg.naturalHeight || roomImg.height;

                var fullW = fw - postW * 2;
                var fullH = innerH;
                if (fullW <= 0 || fullH <= 0) return;

                var apertureAspect = fullW / fullH;
                var imgAspect = imgW / imgH;

                var srcX = 0, srcY = 0, srcW = imgW, srcH = imgH;
                if (apertureAspect > imgAspect) {
                    srcH = imgW / apertureAspect;
                    srcY = (imgH - srcH) / 2;
                } else {
                    srcW = imgH * apertureAspect;
                    srcX = (imgW - srcW) / 2;
                }

                var relX = (pRect.x - panelAreaX) / fullW;
                var relY = (pRect.y - innerY) / fullH;
                var relW = pRect.w / fullW;
                var relH = pRect.h / fullH;

                var subSrcX = Math.max(0, srcX + relX * srcW);
                var subSrcY = Math.max(0, srcY + relY * srcH);
                var subSrcW = Math.min(imgW - subSrcX, relW * srcW);
                var subSrcH = Math.min(imgH - subSrcY, relH * srcH);

                ctx.save();
                ctx.beginPath();
                ctx.rect(pRect.x, pRect.y, pRect.w, pRect.h);
                ctx.clip();

                ctx.drawImage(roomImg, subSrcX, subSrcY, subSrcW, subSrcH, pRect.x, pRect.y, pRect.w, pRect.h);

                // Specific Mirror & Tint Treatments
                if (tintType === 'grey' || tintType === 'grey-mirror') {
                    // Modern smoked grey tint
                    ctx.fillStyle = 'rgba(23, 29, 40, 0.48)';
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                } else if (tintType === 'bronze' || tintType === 'bronze-mirror') {
                    // Warm luxury bronze tint
                    ctx.fillStyle = 'rgba(120, 53, 15, 0.35)';
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                    var warmGrad = ctx.createLinearGradient(pRect.x, pRect.y, pRect.x + pRect.w, pRect.y + pRect.h);
                    warmGrad.addColorStop(0, 'rgba(217, 119, 6, 0.12)');
                    warmGrad.addColorStop(1, 'rgba(120, 53, 15, 0.22)');
                    ctx.fillStyle = warmGrad;
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                } else if (tintType === 'frosted' || tintType === 'frosted-mirror' || tintType === 'frosted-satin-mirror') {
                    // Frosted satin translucent mist
                    ctx.fillStyle = 'rgba(241, 245, 249, 0.65)';
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                }

                // Photorealistic glass sheen / reflection highlights
                if (withSheen !== false) {
                    var glassSheen = ctx.createLinearGradient(pRect.x, pRect.y, pRect.x + pRect.w * 0.85, pRect.y + pRect.h);
                    glassSheen.addColorStop(0, 'rgba(255, 255, 255, 0.18)');
                    glassSheen.addColorStop(0.28, 'rgba(255, 255, 255, 0.05)');
                    glassSheen.addColorStop(0.65, 'rgba(255, 255, 255, 0)');
                    glassSheen.addColorStop(1, 'rgba(255, 255, 255, 0.08)');
                    ctx.fillStyle = glassSheen;
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                }

                ctx.restore();
            } else {
                // Fallback while image loads
                var mirrorGrad = ctx.createLinearGradient(pRect.x, pRect.y, pRect.x + pRect.w, pRect.y + pRect.h);
                if (tintType === 'grey' || tintType === 'grey-mirror') {
                    mirrorGrad.addColorStop(0, '#64748b');
                    mirrorGrad.addColorStop(0.5, '#475569');
                    mirrorGrad.addColorStop(1, '#334155');
                } else if (tintType === 'bronze' || tintType === 'bronze-mirror') {
                    mirrorGrad.addColorStop(0, '#c29272');
                    mirrorGrad.addColorStop(0.5, '#9e7358');
                    mirrorGrad.addColorStop(1, '#714e38');
                } else {
                    mirrorGrad.addColorStop(0, '#f8fafc');
                    mirrorGrad.addColorStop(0.35, '#e2e8f0');
                    mirrorGrad.addColorStop(0.65, '#cbd5e1');
                    mirrorGrad.addColorStop(1, '#94a3b8');
                }
                ctx.fillStyle = mirrorGrad;
                ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
            }
        }

        // Ensure st.doorDesigns exists and matches doorCount
        if (!st.doorDesigns || !Array.isArray(st.doorDesigns)) {
            st.doorDesigns = [];
        }
        while (st.doorDesigns.length < doorCount) {
            st.doorDesigns.push({
                design: DESIGNS[0],
                orientation: 'horizontal',
                customSplits: null,
                panels: null
            });
        }
        if (st.selectedDoorIndices && Array.isArray(st.selectedDoorIndices)) {
            st.selectedDoorIndices = st.selectedDoorIndices.filter(function (idx) { return idx < doorCount; });
        } else {
            st.selectedDoorIndices = [];
        }
        if (st.selectedPanels && Array.isArray(st.selectedPanels)) {
            st.selectedPanels = st.selectedPanels.filter(function (sp) {
                return sp && sp.doorIdx < doorCount && st.doorDesigns[sp.doorIdx] && sp.panelIdx < ((st.doorDesigns[sp.doorIdx].design && st.doorDesigns[sp.doorIdx].design.panels) || 1);
            });
        } else {
            st.selectedPanels = [];
        }

        // Helper to get sub-panel rects for any door
        function getDoorPanelRects(doorIndex) {
            var dpx = panelX[doorIndex];
            var dpw = Math.ceil(panelW);
            var dObj = st.doorDesigns[doorIndex] || { design: DESIGNS[0], orientation: 'horizontal', customSplits: null };
            var dDes = dObj.design || DESIGNS[0];
            var pCount = dDes.panels || 1;
            var dOrient = dObj.orientation || 'horizontal';
            var dSplits = dObj.customSplits;
            var rects = [];

            if (pCount === 1) {
                rects.push({ x: dpx, y: innerY, w: dpw, h: innerH });
            } else if (dOrient === 'vertical') {
                if (dSplits && dSplits.length === pCount) {
                    var totalCustom = dSplits.reduce(function (a, b) { return a + b; }, 0);
                    var curXAccum = 0;
                    for (var vi = 0; vi < pCount; vi++) {
                        var subW = (dSplits[vi] / totalCustom) * dpw;
                        rects.push({
                            x: Math.round(dpx + curXAccum),
                            y: innerY,
                            w: Math.round(subW),
                            h: innerH
                        });
                        curXAccum += subW;
                    }
                } else {
                    var subW = dpw / pCount;
                    for (var vi = 0; vi < pCount; vi++) {
                        rects.push({
                            x: Math.round(dpx + vi * subW),
                            y: innerY,
                            w: Math.round(subW),
                            h: innerH
                        });
                    }
                }
            } else {
                // Horizontal
                if (dSplits && dSplits.length === pCount) {
                    var totalCustom = dSplits.reduce(function (a, b) { return a + b; }, 0);
                    var curHAccum = 0;
                    for (var hi = 0; hi < pCount; hi++) {
                        var subH = (dSplits[hi] / totalCustom) * innerH;
                        rects.push({
                            x: dpx,
                            y: Math.round(innerY + curHAccum),
                            w: dpw,
                            h: Math.round(subH)
                        });
                        curHAccum += subH;
                    }
                } else {
                    var subH = innerH / pCount;
                    for (var hi = 0; hi < pCount; hi++) {
                        rects.push({
                            x: dpx,
                            y: Math.round(innerY + hi * subH),
                            w: dpw,
                            h: Math.round(subH)
                        });
                    }
                }
            }
            return rects;
        }

        // Draw door panels
        for (var pi = 0; pi < doorCount; pi++) {
            var px = panelX[pi];
            var pw = Math.ceil(panelW);
            var isThisDoorSelected = (isStep2 && isDoorSelected(st, pi));

            var doorObj = st.doorDesigns[pi] || { design: DESIGNS[0], orientation: 'horizontal', customSplits: null, panels: [] };
            var dDesign = doorObj.design || DESIGNS[0];
            var panelsCount = dDesign.panels || 1;
            var orient = doorObj.orientation || 'horizontal';

            if (!doorObj.panels || !Array.isArray(doorObj.panels)) {
                doorObj.panels = [];
            }
            while (doorObj.panels.length < panelsCount) {
                doorObj.panels.push(null);
            }

            var pRects = getDoorPanelRects(pi);

            // Draw each panel individually
            for (var pji = 0; pji < panelsCount; pji++) {
                var pRect = pRects[pji] || { x: px, y: innerY, w: pw, h: innerH };
                var pMat = doorObj.panels[pji];
                var isThisPanelSelected = (isStep3 && isPanelSelected(st, pi, pji));

                if (pMat && pMat.type === 'glass' && pMat.item) {
                    var gItem = pMat.item;
                    var gImg = gItem.image ? getCachedImage(gItem.image, function () {
                        sdDrawCanvas(set, opts);
                    }) : null;

                    if (gItem.category === 'mirrors' || (gItem.id && gItem.id.indexOf('mirror') !== -1)) {
                        drawDoorRoomReflection(pRect, gItem.id || 'silver', true);
                    } else if (gImg && gImg.complete && gImg.naturalWidth > 0) {
                        // Draw room scene behind first
                        drawDoorRoomReflection(pRect, 'silver', false);
                        ctx.save();
                        ctx.globalAlpha = 0.68;
                        drawCoverImage(ctx, gImg, pRect.x, pRect.y, pRect.w, pRect.h);
                        ctx.restore();
                    } else {
                        // Draw room scene behind first
                        drawDoorRoomReflection(pRect, 'silver', false);
                        var glassHex = (gItem.swatchColor && gItem.swatchColor !== '#dbe4eb') ? gItem.swatchColor : '#ffffff';
                        ctx.save();
                        ctx.globalAlpha =0.72;
                        ctx.fillStyle = glassHex;
                        ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                        ctx.restore();

                        // Subtle lacquered glass sheen
                        var lacqSheen = ctx.createLinearGradient(pRect.x, pRect.y, pRect.x + pRect.w * 0.75, pRect.y + pRect.h);
                        lacqSheen.addColorStop(0, 'rgba(255, 255, 255, 0.28)');
                        lacqSheen.addColorStop(0.3, 'rgba(255, 255, 255, 0.07)');
                        lacqSheen.addColorStop(0.7, 'rgba(255, 255, 255, 0)');
                        lacqSheen.addColorStop(1, 'rgba(255, 255, 255, 0.12)');
                        ctx.fillStyle = lacqSheen;
                        ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                    }
                } else if (pMat && pMat.type === 'board' && pMat.item) {
                    var bItem = pMat.item;
                    var bImgSrc = bItem.image || getBoardTextureImage(bItem);
                    var bImg = bImgSrc ? getCachedImage(bImgSrc, function () {
                        sdDrawCanvas(set, opts);
                    }) : null;

                    // 1. Draw base room interior background image first
                    drawDoorRoomReflection(pRect, 'silver', false);

                    // 2. Draw board decor with reduced opacity so the room scene remains visible
                    ctx.save();
                    ctx.globalAlpha = 0.72;

                    if (bImg && bImg.complete && bImg.naturalWidth > 0) {
                        drawCoverImage(ctx, bImg, pRect.x, pRect.y, pRect.w, pRect.h);
                    } else {
                        ctx.fillStyle = bItem.color || '#b89778';
                        ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);

                        // Subtle woodgrain lines
                        ctx.strokeStyle = 'rgba(0, 0, 0, 0.10)';
                        ctx.lineWidth = 1;
                        for (var li = pRect.y + 15; li < pRect.y + pRect.h; li += 18) {
                            ctx.beginPath();
                            ctx.moveTo(pRect.x + 4, li);
                            ctx.lineTo(pRect.x + pRect.w - 4, li);
                            ctx.stroke();
                        }
                    }
                    ctx.restore();

                    // 3. Subtle ambient furniture depth sheen
                    var woodSheen = ctx.createLinearGradient(pRect.x, pRect.y, pRect.x + pRect.w, pRect.y + pRect.h);
                    woodSheen.addColorStop(0, 'rgba(255, 255, 255, 0.10)');
                    woodSheen.addColorStop(0.35, 'rgba(255, 255, 255, 0)');
                    woodSheen.addColorStop(1, 'rgba(0, 0, 0, 0.05)');
                    ctx.fillStyle = woodSheen;
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                } else {
                    // Default door surface: Real room interior reflection
                    drawDoorRoomReflection(pRect, 'silver', true);
                }

                // Selection Highlights
                if (isThisDoorSelected) {
                    // Warm golden overlay for selected door in Step 2
                    ctx.fillStyle = 'rgba(251, 191, 36, 0.20)';
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                } else if (isThisPanelSelected) {
                    // Warm golden overlay & border for selected panel in Step 3
                    ctx.fillStyle = 'rgba(251, 191, 36, 0.22)';
                    ctx.fillRect(pRect.x, pRect.y, pRect.w, pRect.h);
                    ctx.strokeStyle = '#f59e0b';
                    ctx.lineWidth = 2.5;
                    ctx.strokeRect(pRect.x + 1, pRect.y + 1, pRect.w - 2, pRect.h - 2);
                }
            }

            // Draw Divider Splits
            if (panelsCount > 1) {
                ctx.strokeStyle = frameFill;
                ctx.lineWidth = Math.max(3, postW * 0.65);
                if (orient === 'vertical') {
                    for (var vi = 1; vi < panelsCount; vi++) {
                        var vx = pRects[vi] ? pRects[vi].x : Math.round(px + vi * (pw / panelsCount));
                        ctx.beginPath();
                        ctx.moveTo(vx, innerY);
                        ctx.lineTo(vx, innerY + innerH);
                        ctx.stroke();
                    }
                } else {
                    for (var hi = 1; hi < panelsCount; hi++) {
                        var hy = pRects[hi] ? pRects[hi].y : Math.round(innerY + hi * (innerH / panelsCount));
                        ctx.beginPath();
                        ctx.moveTo(px, hy);
                        ctx.lineTo(px + pw, hy);
                        ctx.stroke();
                    }
                }
            }

            // Inner panel frame stroke
            ctx.strokeStyle = isThisDoorSelected ? '#f59e0b' : panelBorder;
            ctx.lineWidth = isThisDoorSelected ? 2 : 1.5;
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
        var trkH = 10;
        var trkGap = 4;
        var trkPW = Math.round((fw - postW * 2) / doorCount);
        var trkY0 = floorY + 14;
        var trkY1 = trkY0 + trkH + trkGap;
        var trkLineColor = '#86efac';
        var trkBarFront = '#ffffff';
        var trkBarBack = '#bbf7d0';
        var trkBarStroke = '#16a34a';

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

        // Helper to draw individual door runner bar with soft-closer chevron arrows
        function drawRunnerBarWithChevrons(ctxRef, bX, bY, bW, bH, isBackSlot, hasLeftSc, hasRightSc) {
            ctxRef.save();

            var hasAnySc = hasLeftSc || hasRightSc;
            var barBg = hasAnySc ? '#181b18ff' : (isBackSlot ? trkBarBack : trkBarFront);
            var barBorder = hasAnySc ? '#09090b' : trkBarStroke;

            // Base bar
            ctxRef.fillStyle = barBg;
            ctxRef.fillRect(bX, bY, bW, bH);

            var slant = Math.max(3, Math.round(bH * 0.45));
            var solidW = Math.max(12, Math.min(24, Math.round(bW * 0.16)));
            var stripeW = Math.max(3, Math.round(bH * 0.32));
            var gapW = Math.max(2.5, Math.round(bH * 0.22));

            // Clip chevron patterns within the runner bar bounds
            ctxRef.beginPath();
            ctxRef.rect(bX, bY, bW, bH);
            ctxRef.clip();

            // 1. Left Soft Closer Chevrons (Pointing Left <)
            if (hasLeftSc) {
                ctxRef.fillStyle = '#16a34a';

                // Solid head at left
                ctxRef.beginPath();
                ctxRef.moveTo(bX, bY + bH / 2);
                ctxRef.lineTo(bX + slant, bY);
                ctxRef.lineTo(bX + solidW + slant, bY);
                ctxRef.lineTo(bX + solidW, bY + bH / 2);
                ctxRef.lineTo(bX + solidW + slant, bY + bH);
                ctxRef.lineTo(bX + slant, bY + bH);
                ctxRef.closePath();
                ctxRef.fill();

                // 2 Chevron stripes following the solid head
                for (var c = 0; c < 2; c++) {
                    var cx = bX + solidW + gapW + c * (stripeW + gapW);
                    ctxRef.beginPath();
                    ctxRef.moveTo(cx, bY + bH / 2);
                    ctxRef.lineTo(cx + slant, bY);
                    ctxRef.lineTo(cx + slant + stripeW, bY);
                    ctxRef.lineTo(cx + stripeW, bY + bH / 2);
                    ctxRef.lineTo(cx + slant + stripeW, bY + bH);
                    ctxRef.lineTo(cx + slant, bY + bH);
                    ctxRef.closePath();
                    ctxRef.fill();
                }
            }

            // 2. Right Soft Closer Chevrons (Pointing Right >)
            if (hasRightSc) {
                ctxRef.fillStyle = '#16a34a';

                // Solid head at right
                ctxRef.beginPath();
                ctxRef.moveTo(bX + bW, bY + bH / 2);
                ctxRef.lineTo(bX + bW - slant, bY);
                ctxRef.lineTo(bX + bW - solidW - slant, bY);
                ctxRef.lineTo(bX + bW - solidW, bY + bH / 2);
                ctxRef.lineTo(bX + bW - solidW - slant, bY + bH);
                ctxRef.lineTo(bX + bW - slant, bY + bH);
                ctxRef.closePath();
                ctxRef.fill();

                // 2 Chevron stripes following the solid head
                for (var rc = 0; rc < 2; rc++) {
                    var rcx = bX + bW - solidW - gapW - rc * (stripeW + gapW);
                    ctxRef.beginPath();
                    ctxRef.moveTo(rcx, bY + bH / 2);
                    ctxRef.lineTo(rcx - slant, bY);
                    ctxRef.lineTo(rcx - slant - stripeW, bY);
                    ctxRef.lineTo(rcx - stripeW, bY + bH / 2);
                    ctxRef.lineTo(rcx - slant - stripeW, bY + bH);
                    ctxRef.lineTo(rcx - slant, bY + bH);
                    ctxRef.closePath();
                    ctxRef.fill();
                }
            }

            ctxRef.restore();

            // Outer stroke
            ctxRef.strokeStyle = barBorder;
            ctxRef.lineWidth = 1;
            ctxRef.strokeRect(bX + 0.5, bY + 0.5, bW - 1, bH - 1);
        }

        // Individual door runner bars based on slot door position
        for (var ti = 0; ti < doorCount; ti++) {
            var doorId = st.doorOrder[ti];
            var isBack = (doorId % 2 === 0);
            var trkX = panelX[ti];
            var trkY = isBack ? trkY0 : trkY1;
            if (opts && opts.trackYOffsets && opts.trackYOffsets[ti] != null) {
                trkY = opts.trackYOffsets[ti];
            }

            var scObj = (st.doorSoftClose && st.doorSoftClose[doorId]) ? st.doorSoftClose[doorId] : { left: false, right: false };
            var hasLeft = Boolean(scObj.left);
            var hasRight = Boolean(scObj.right);

            drawRunnerBarWithChevrons(ctx, trkX, trkY, trkPW, trkH, isBack, hasLeft, hasRight);
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

        // Render Overlays (Step 2: Whole door selection, Step 3: Individual panel selection)
        var doorOverlayContainer = set.querySelector('.sd-door-overlay-container');
        if (doorOverlayContainer) {
            if (isStep2) {
                var overlayHtml = '';
                for (var oi = 0; oi < doorCount; oi++) {
                    var opx = panelX[oi];
                    var opw = Math.ceil(panelW);
                    var isSelected = isDoorSelected(st, oi);
                    var btnHtml = isSelected
                        ? '<button type="button" class="sd-door-edit-badge" data-door="' + oi + '">Edit design</button>'
                        : '';

                    overlayHtml += '<div class="sd-door-select-zone' + (isSelected ? ' is-selected' : '') + '" data-door="' + oi + '" style="position:absolute; left:' + opx + 'px; top:' + innerY + 'px; width:' + opw + 'px; height:' + innerH + 'px;">' +
                        btnHtml +
                        '</div>';
                }
                doorOverlayContainer.innerHTML = overlayHtml;
            } else if (isStep3) {
                var overlayHtml = '';
                for (var oi = 0; oi < doorCount; oi++) {
                    var dObj = st.doorDesigns[oi] || { design: DESIGNS[0] };
                    var pCount = (dObj.design && dObj.design.panels) ? dObj.design.panels : 1;
                    var dRects = getDoorPanelRects(oi);
                    for (var pji = 0; pji < pCount; pji++) {
                        var pr = dRects[pji] || { x: panelX[oi], y: innerY, w: Math.ceil(panelW), h: innerH };
                        var isSelected = isPanelSelected(st, oi, pji);
                        var btnHtml = isSelected
                            ? '<span class="sd-panel-edit-badge" data-door="' + oi + '" data-panel="' + pji + '"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
                            : '';

                        overlayHtml += '<div class="sd-panel-select-zone' + (isSelected ? ' is-selected' : '') + '" data-door="' + oi + '" data-panel="' + pji + '" style="position:absolute; left:' + pr.x + 'px; top:' + pr.y + 'px; width:' + pr.w + 'px; height:' + pr.h + 'px;">' +
                            btnHtml +
                            '</div>';
                    }
                }
                doorOverlayContainer.innerHTML = overlayHtml;
            } else {
                doorOverlayContainer.innerHTML = '';
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
        if (stepNum === 2) {
            var stStep = sdSetStates[set.dataset.setId];
            if (stStep) stStep.selectedDoorIndices = [0];
            var dWarn = set.querySelector('.sd-door-warning');
            if (dWarn) dWarn.style.display = 'none';
            sdRenderDoorDesigns(set);
        }
        if (stepNum === 3) {
            var stStep3 = sdSetStates[set.dataset.setId];
            if (stStep3) stStep3.selectedPanels = [{ doorIdx: 0, panelIdx: 0 }];
            var pWarn = set.querySelector('.sd-panel-warning');
            if (pWarn) pWarn.style.display = 'none';
            sdRenderPanelDesign(set);
        }
        if (stepNum === 4) sdRenderSummary(set);

        sdDrawCanvas(set);
        sdDebouncedSaveState();
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
            if (sdSetStates[setId]) {
                sdSetStates[setId].width = val;
                sdSetStates[setId].isAddedToCart = false;
            }
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
            if (sdSetStates[setId]) {
                sdSetStates[setId].height = val;
                sdSetStates[setId].isAddedToCart = false;
            }
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

    var STORAGE_KEY = 'cutlist_sliding_doors_draft';
    var _sdSaveDebounceTimer = null;

    function sdSaveStateToLocalStorage() {
        try {
            var setsData = [];
            var setElements = container.querySelectorAll('.sd-set');
            setElements.forEach(function (setEl) {
                var sId = setEl.dataset.setId;
                var st = sdSetStates[sId];
                if (!st) return;

                var curStepEl = setEl.querySelector('.sd-step.current');
                var currentStep = curStepEl ? parseInt(curStepEl.dataset.step, 10) : 0;
                var isCollapsed = setEl.classList.contains('collapsed');

                var wVal = parseInt(setEl.querySelector('.sd-width') ? setEl.querySelector('.sd-width').value : st.width, 10) || st.width || 3000;
                var hVal = parseInt(setEl.querySelector('.sd-height') ? setEl.querySelector('.sd-height').value : st.height, 10) || st.height || 3000;
                var dcVal = parseInt(setEl.querySelector('.sd-door-select .Select2__input') ? setEl.querySelector('.sd-door-select .Select2__input').textContent : st.doorCount, 10) || st.doorCount || 2;

                setsData.push({
                    setId: sId,
                    width: wVal,
                    height: hVal,
                    doorCount: dcVal,
                    frameStyle: st.frameStyle,
                    frameColor: st.frameColor,
                    doorDesign: st.doorDesign,
                    orientation: st.orientation || 'horizontal',
                    doorDesigns: st.doorDesigns,
                    customSplits: st.customSplits || null,
                    panelType: st.panelType || null,
                    glassItem: st.glassItem || null,
                    boardItem: st.boardItem || null,
                    doorSoftClose: st.doorSoftClose || [],
                    switched: st.switched || 0,
                    doorOrder: st.doorOrder || [],
                    currentStep: currentStep,
                    collapsed: isCollapsed
                });
            });

            if (setsData.length > 0) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(setsData));
            }
        } catch (e) {
            console.warn('Could not save sliding doors state to localStorage', e);
        }
    }

    function sdDebouncedSaveState() {
        if (_sdSaveDebounceTimer) clearTimeout(_sdSaveDebounceTimer);
        _sdSaveDebounceTimer = setTimeout(function () {
            sdSaveStateToLocalStorage();
        }, 150);
    }

    function sdRestoreStateFromLocalStorage() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return false;
            var setsData = JSON.parse(raw);
            if (!Array.isArray(setsData) || setsData.length === 0) return false;

            container.innerHTML = '';
            sdSetStates = {};
            sdSetCount = 0;

            setsData.forEach(function (savedSet) {
                sdSetCount++;
                var curNum = sdSetCount;
                var set = sdBuildSet(curNum);

                var st = sdSetStates[curNum];
                if (st) {
                    if (savedSet.width) st.width = savedSet.width;
                    if (savedSet.height) st.height = savedSet.height;
                    if (savedSet.doorCount) st.doorCount = savedSet.doorCount;
                    if (savedSet.frameStyle) st.frameStyle = savedSet.frameStyle;
                    if (savedSet.frameColor) st.frameColor = savedSet.frameColor;
                    if (savedSet.doorDesign) st.doorDesign = savedSet.doorDesign;
                    if (savedSet.orientation) st.orientation = savedSet.orientation;
                    if (savedSet.doorDesigns) st.doorDesigns = savedSet.doorDesigns;
                    if (savedSet.customSplits) st.customSplits = savedSet.customSplits;
                    if (savedSet.panelType !== undefined) st.panelType = savedSet.panelType;
                    if (savedSet.glassItem !== undefined) st.glassItem = savedSet.glassItem;
                    if (savedSet.boardItem !== undefined) st.boardItem = savedSet.boardItem;
                    if (savedSet.doorSoftClose) st.doorSoftClose = savedSet.doorSoftClose;
                    if (savedSet.switched !== undefined) st.switched = savedSet.switched;
                    if (savedSet.doorOrder) st.doorOrder = savedSet.doorOrder;
                }

                var widthInput = set.querySelector('.sd-width');
                if (widthInput && savedSet.width) widthInput.value = savedSet.width;

                var heightInput = set.querySelector('.sd-height');
                if (heightInput && savedSet.height) heightInput.value = savedSet.height;

                var doorSelectInput = set.querySelector('.sd-door-select .Select2__input');
                if (doorSelectInput && savedSet.doorCount) doorSelectInput.textContent = savedSet.doorCount;

                set.dataset.switched = savedSet.switched ? '1' : '0';

                if (savedSet.collapsed) {
                    set.classList.add('collapsed');
                } else {
                    set.classList.remove('collapsed');
                }

                container.appendChild(set);

                sdUpdateDoorDropdown(set);

                if (savedSet.doorCount) {
                    if (doorSelectInput) doorSelectInput.textContent = savedSet.doorCount;
                    if (st) st.doorCount = savedSet.doorCount;
                }

                sdEnableSteps(set, 4);
                sdRenderFrameStyles(set);
                sdRenderDoorDesigns(set);
                sdRenderPanelDesign(set);
                sdRenderSummary(set);

                var targetStep = (typeof savedSet.currentStep === 'number') ? savedSet.currentStep : 0;
                sdGoToStep(set, targetStep);

                (function (s) {
                    requestAnimationFrame(function () {
                        sdDrawCanvas(s);
                    });
                })(set);
            });

            return true;
        } catch (e) {
            console.warn('Error restoring sliding doors state from localStorage', e);
            return false;
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
        sdSaveStateToLocalStorage();
    }

    /**
     * Renumber sets sequentially
     */
    function sdRenumber() {
        var newStates = {};
        container.querySelectorAll('.sd-set').forEach(function (set, i) {
            var oldId = set.dataset.setId;
            var newId = i + 1;
            var numEl = set.querySelector('.sd-set-num');
            if (numEl) numEl.textContent = newId;
            set.dataset.setId = newId;
            if (sdSetStates[oldId]) {
                newStates[newId] = sdSetStates[oldId];
            }
        });
        sdSetStates = newStates;
        sdSetCount = container.querySelectorAll('.sd-set').length;
        sdSaveStateToLocalStorage();
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
            '<div class="sd-modal-dialog sd-board-modal-dialog">' +
            '<button type="button" class="sd-modal-close" id="sdBoardModalClose">&times;</button>' +
            '<div class="sd-modal-header">' +
            '<h3>Decorative boards</h3>' +
            '<p class="sd-modal-intro">Choose from our premium catalogue of MFC decorative woodgrain and uni decors for your sliding door infill panels.</p>' +
            '<div class="sd-modal-subhead">Board decor</div>' +
            '<p class="sd-modal-subtitle">Select any decor from the catalogue. Infills are precision cut to fit within the aluminium perimeter frame.</p>' +
            '</div>' +

            // Search Header row (Matching mockup)
            '<div class="sd-board-filter-header">' +
            '<div class="sd-board-filter-title">Browse decors by brand or search for a specific decor</div>' +
            '<div class="sd-board-search-wrap">' +
            '<span class="sd-board-search-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>' +
            '<input type="text" class="sd-board-search-input" id="sdBoardSearchInput" placeholder="Enter decor code or name" autocomplete="off">' +
            '<button type="button" class="sd-board-search-clear" id="sdBoardSearchClear" style="display:none;" title="Clear search">&times;</button>' +
            '</div>' +
            '</div>' +

            // Brand tabs grid (4 columns matching mockup)
            '<div class="sd-board-brand-tabs" id="sdBoardBrandTabs"></div>' +

            // Collection dropdown row (Matching mockup)
            '<div class="sd-board-collection-row" id="sdBoardCollectionRow">' +
            '<div class="sd-board-collection-select-wrap">' +
            '<select class="sd-board-collection-select" id="sdBoardCollectionSelect">' +
            '<option value="">All collections</option>' +
            '</select>' +
            '</div>' +
            '</div>' +

            // Body layout: Left preview card + Right swatches grid
            '<div class="sd-modal-body-layout sd-board-body-layout">' +
            '<div class="sd-modal-preview-card">' +
            '<div class="sd-modal-preview-img-box" id="sdBoardPreviewBox" style="background:#b89778;"></div>' +
            '<div class="sd-modal-preview-title" id="sdBoardPreviewTitle">Natural Halifax Oak</div>' +
            '<div class="sd-modal-preview-price" id="sdBoardPreviewPrice">Brand: Egger</div>' +
            '</div>' +
            '<div class="sd-board-swatches-container">' +
            '<div class="sd-board-swatches-grid" id="sdBoardSwatchesGrid"></div>' +
            '</div>' +
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
            '<div class="sd-design-sec sd-design-sec-panels">' +
            '<h3 class="sd-design-sec-title">Panel size</h3>' +
            '<p class="sd-design-sec-desc">You can adjust each door panel size to better suit your design.</p>' +
            '<div class="sd-design-rules-box">' +
            '<p>Minimum panel height is 200mm.</p>' +
            '<p>Maximum panel height is 2440mm.</p>' +
            '<p>Minimum panel width is 100mm.</p>' +
            '</div>' +
            '</div>' +
            '<div class="sd-design-sec" id="sdDoorDesignOrientSection">' +
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
            var colorPriceText = (typeof tempFrameColor.basePrice === 'number') ? ' &bull; ' + CURRENCY + tempFrameColor.basePrice.toFixed(2) : '';
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
        if (!st) return;

        var selectedMat = null;
        if (st.selectedPanels && st.selectedPanels.length > 0) {
            var firstSp = st.selectedPanels[0];
            if (st.doorDesigns && st.doorDesigns[firstSp.doorIdx]) {
                var dObj = st.doorDesigns[firstSp.doorIdx];
                if (dObj.panels && dObj.panels[firstSp.panelIdx]) {
                    var pMat = dObj.panels[firstSp.panelIdx];
                    if (pMat && pMat.type === 'glass') selectedMat = pMat.item;
                }
            }
        }

        tempModalSelection = selectedMat || GLASS_LIST[0];

        var overlay = document.getElementById('sdGlassModalOverlay');
        if (!overlay) return;

        var initCat = tempModalSelection.category || 'mirrors';
        document.querySelectorAll('#sdGlassModalOverlay .sd-modal-tab-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.cat === initCat);
        });

        renderGlassSwatches(initCat);
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
            var isOos = (g.isOutOfStock || g.stockStatus === 'outofstock');
            var bgStyle = g.image
                ? 'background-image:url(' + g.image + ');background-size:cover;background-position:center;'
                : (g.category === 'mirrors'
                    ? 'background:linear-gradient(135deg, #ffffff 0%, ' + g.swatchColor + ' 50%, #94a3b8 100%);'
                    : 'background:' + g.swatchColor + ';');
            var stockBadge = isOos ? '<span class="sd-swatch-stock-tag out-of-stock">Out of Stock</span>' : '';
            return '<div class="sd-modal-swatch-item ' + (isSel ? 'active' : '') + (isOos ? ' is-out-of-stock' : '') + '" data-id="' + g.id + '"' + (isOos ? ' data-out-of-stock="1"' : '') + '>' +
                '<div class="sd-modal-swatch-thumb" style="' + bgStyle + '">' +
                (g.image ? '<img src="' + g.image + '" alt="' + g.name + '" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">' : '') +
                stockBadge +
                '</div>' +
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
            if (item.image) {
                box.style.background = 'none';
                box.style.backgroundImage = 'url(' + item.image + ')';
                box.style.backgroundSize = 'cover';
                box.style.backgroundPosition = 'center';
            } else if (item.category === 'mirrors') {
                box.style.backgroundImage = 'none';
                box.style.background = 'linear-gradient(135deg, #ffffff 0%, ' + item.swatchColor + ' 50%, #94a3b8 100%)';
            } else {
                box.style.backgroundImage = 'none';
                box.style.background = item.swatchColor;
            }
        }
        if (title) title.textContent = item.name;
        if (price) price.textContent = 'Square meter price: ' + CURRENCY + item.pricePerSqm.toFixed(2) + ' plus VAT';
    }

    /**
     * Helpers and rendering for Decorative Boards Modal (Brand Tabs, Collection Dropdown, Search)
     */
    function getBoardBrandsList() {
        var brands = DEFAULT_BRANDS.slice();
        if (DATA.boardBrands && Array.isArray(DATA.boardBrands)) {
            DATA.boardBrands.forEach(function (b) {
                if (b && brands.indexOf(b) === -1) {
                    brands.push(b);
                }
            });
        }
        BOARDS.forEach(function (b) {
            if (b.brand && brands.indexOf(b.brand) === -1) {
                brands.push(b.brand);
            }
        });
        return brands;
    }

    function renderBoardBrandTabs() {
        var tabsContainer = document.getElementById('sdBoardBrandTabs');
        if (!tabsContainer) return;
        var brands = getBoardBrandsList();
        tabsContainer.innerHTML = brands.map(function (brand) {
            var isActive = (!currentBoardSearch && currentBoardBrand.toLowerCase() === brand.toLowerCase());
            return '<button type="button" class="sd-board-brand-btn ' + (isActive ? 'active' : '') + '" data-brand="' + brand + '">' +
                brand +
                '</button>';
        }).join('');
    }

    function populateBoardCollectionDropdown(selectedBrand) {
        var select = document.getElementById('sdBoardCollectionSelect');
        var row = document.getElementById('sdBoardCollectionRow');
        if (!select) return;

        var brand = selectedBrand || currentBoardBrand || 'Egger';
        var collectionsSet = [];
        BOARDS.forEach(function (b) {
            var bBrand = b.brand || 'Egger';
            if (bBrand.toLowerCase() === brand.toLowerCase() && b.collection && b.collection.trim() !== '') {
                var col = b.collection.trim();
                if (collectionsSet.indexOf(col) === -1) {
                    collectionsSet.push(col);
                }
            }
        });

        var html = '<option value="">All collections</option>';
        collectionsSet.forEach(function (col) {
            var isSel = (currentBoardCollection === col);
            html += '<option value="' + col + '" ' + (isSel ? 'selected' : '') + '>' + col + '</option>';
        });
        select.innerHTML = html;
        select.value = currentBoardCollection || '';

        if (row) {
            row.style.display = 'block';
        }
    }

    function renderBoardSwatchesGrid() {
        var grid = document.getElementById('sdBoardSwatchesGrid');
        if (!grid) return;

        var search = (currentBoardSearch || '').trim().toLowerCase();
        var brand = (currentBoardBrand || '').trim().toLowerCase();
        var collection = (currentBoardCollection || '').trim().toLowerCase();

        var filtered = BOARDS.filter(function (b) {
            var bBrand = (b.brand || 'Egger').toLowerCase();
            var bCollection = (b.collection || '').toLowerCase();
            var bCode = (b.code || '').toLowerCase();
            var bTitle = (b.title || '').toLowerCase();

            if (search) {
                return (bCode.indexOf(search) !== -1 ||
                    bTitle.indexOf(search) !== -1 ||
                    bBrand.indexOf(search) !== -1 ||
                    bCollection.indexOf(search) !== -1);
            }

            var matchesBrand = (bBrand === brand);
            if (!matchesBrand) return false;

            if (collection) {
                return (bCollection === collection);
            }
            return true;
        });

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="sd-board-empty-msg">No decors found matching your search or filter.</div>';
            return;
        }

        grid.innerHTML = filtered.map(function (b) {
            var isSel = (tempModalSelection && (String(tempModalSelection.id) === String(b.id) || (tempModalSelection.code && tempModalSelection.code === b.code)));
            var isOos = (b.isOutOfStock || b.stockStatus === 'outofstock');
            var bImgSrc = b.image || getBoardTextureImage(b);
            var bgStyle = bImgSrc
                ? 'background-image:url(' + bImgSrc + ');background-size:cover;background-position:center;'
                : (b.color ? 'background:' + b.color + ';' : 'background:#b89778;');
            var displayCode = b.code || b.title;
            var stockBadge = isOos ? '<span class="sd-swatch-stock-tag out-of-stock">Out of Stock</span>' : '';

            return '<div class="sd-modal-swatch-item ' + (isSel ? 'active' : '') + (isOos ? ' is-out-of-stock' : '') + '" data-id="' + b.id + '" data-code="' + (b.code || '') + '"' + (isOos ? ' data-out-of-stock="1"' : '') + '>' +
                '<div class="sd-modal-swatch-thumb" style="' + bgStyle + '">' +
                (bImgSrc ? '<img src="' + bImgSrc + '" alt="' + displayCode + '" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">' : '') +
                stockBadge +
                '</div>' +
                '<div class="sd-modal-swatch-name">' + displayCode + '</div>' +
                '</div>';
        }).join('');
    }

    function updateBoardPreview(b) {
        if (!b) return;
        var box = document.getElementById('sdBoardPreviewBox');
        var title = document.getElementById('sdBoardPreviewTitle');
        var price = document.getElementById('sdBoardPreviewPrice');

        var bImgSrc = b.image || getBoardTextureImage(b);
        if (box) {
            if (bImgSrc) {
                box.style.background = 'none';
                box.style.backgroundImage = 'url(' + bImgSrc + ')';
                box.style.backgroundSize = 'cover';
                box.style.backgroundPosition = 'center';
            } else {
                box.style.backgroundImage = 'none';
                box.style.background = b.color || '#b89778';
            }
        }
        if (title) {
            var codePrefix = b.code ? b.code + ' ' : '';
            var fullTitle = b.title ? (b.code && b.title.indexOf(b.code) === -1 ? codePrefix + b.title : b.title) : (b.code || 'Decorative Board');
            title.textContent = fullTitle;
        }
        if (price) {
            var brandText = 'Brand: ' + (b.brand || 'Egger');
            if (b.collection) {
                brandText += ' &bull; ' + b.collection;
            }
            price.innerHTML = brandText;
        }
    }

    /**
     * Populate and open Board Modal
     */
    function openBoardModal(setId) {
        activeModalSetId = setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var selectedMat = null;
        if (st.selectedPanels && st.selectedPanels.length > 0) {
            var firstSpB = st.selectedPanels[0];
            if (st.doorDesigns && st.doorDesigns[firstSpB.doorIdx]) {
                var dObjB = st.doorDesigns[firstSpB.doorIdx];
                if (dObjB.panels && dObjB.panels[firstSpB.panelIdx]) {
                    var pMatB = dObjB.panels[firstSpB.panelIdx];
                    if (pMatB && pMatB.type === 'board') selectedMat = pMatB.item;
                }
            }
        }

        tempModalSelection = selectedMat || BOARDS[0];

        if (tempModalSelection && tempModalSelection.brand) {
            currentBoardBrand = tempModalSelection.brand;
            currentBoardCollection = tempModalSelection.collection || '';
        } else {
            currentBoardBrand = 'Egger';
            currentBoardCollection = '';
        }
        currentBoardSearch = '';

        var overlay = document.getElementById('sdBoardModalOverlay');
        if (!overlay) return;

        var searchInput = document.getElementById('sdBoardSearchInput');
        var searchClear = document.getElementById('sdBoardSearchClear');
        if (searchInput) searchInput.value = '';
        if (searchClear) searchClear.style.display = 'none';

        renderBoardBrandTabs();
        populateBoardCollectionDropdown(currentBoardBrand);
        renderBoardSwatchesGrid();
        updateBoardPreview(tempModalSelection);

        overlay.classList.add('is-visible');
    }

    var activeDoorDesignModalDoorIndices = [];

    /**
     * Populate and open Door Design & Panel Size Modal (Matching Mockup)
     */
    function openDoorDesignModal(setId, design, doorIndices) {
        activeDoorDesignModalSetId = setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var targetIndices = [];
        if (Array.isArray(doorIndices) && doorIndices.length > 0) {
            targetIndices = doorIndices.slice();
        } else if (doorIndices != null && typeof doorIndices === 'number') {
            targetIndices = [doorIndices];
        } else if (st.selectedDoorIndices && st.selectedDoorIndices.length > 0) {
            targetIndices = st.selectedDoorIndices.slice();
        } else {
            targetIndices = [0];
        }

        activeDoorDesignModalDoorIndices = targetIndices;
        st.selectedDoorIndices = targetIndices.slice();

        var firstIdx = targetIndices[0] || 0;
        var curDoorObj = (st.doorDesigns && st.doorDesigns[firstIdx]) ? st.doorDesigns[firstIdx] : { design: st.doorDesign || DESIGNS[0], orientation: st.orientation || 'horizontal', customSplits: null };

        tempDoorDesign = design || curDoorObj.design || DESIGNS[0];
        var maxWardrobeHeightForVertical = 2557;
        var isVerticalAllowed = ((st.height || 2000) <= maxWardrobeHeightForVertical);
        var isOrientApplicable = (tempDoorDesign.panels === 2 || tempDoorDesign.panels === 3);

        var desiredOrient = (isOrientApplicable && curDoorObj.design && curDoorObj.design.panels === tempDoorDesign.panels && curDoorObj.orientation)
            ? curDoorObj.orientation
            : (tempDoorDesign.orientation === 'vertical' ? 'vertical' : 'horizontal');

        if (!isVerticalAllowed || !isOrientApplicable) {
            desiredOrient = 'horizontal';
        }

        tempDoorOrientation = desiredOrient;

        tempCustomSplits = (curDoorObj.customSplits && curDoorObj.customSplits.length === tempDoorDesign.panels)
            ? curDoorObj.customSplits.slice()
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
        var overlapMm = 30;
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

        html += '<div class="sd-door-stage-unit">';

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

        // 2. Center Column: Door Mockup + Width Dimensions
        html += '<div class="sd-door-center-col" style="width:' + mockupW + 'px;">' +
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

        // If vertical orientation, place panel width dimensions directly below mockup
        if (isVert) {
            html += '<div class="sd-dim-panels-row" style="width:' + mockupW + 'px;">';
            for (var bi = 0; bi < panelsCount; bi++) {
                var sizeValV = panelSizes[bi] || Math.round(doorWidthMm / panelsCount);
                html += '<div class="sd-dim-panel-item-vert" style="flex:' + (panelSizes[bi] || 1) + ';">' +
                    '<div class="sd-door-badge-tag">' +
                    '<input type="text" inputmode="numeric" class="sd-door-badge-input" data-idx="' + bi + '" value="' + sizeValV + '" maxlength="4" aria-label="Panel ' + (bi + 1) + ' width">' +
                    '<span class="sd-badge-unit">mm</span>' +
                    '</div>' +
                    '</div>';
            }
            html += '</div>';
        }

        // Total Width Dimension (Below Door)
        html += '<div class="sd-dim-total-bottom" style="width:' + mockupW + 'px;">' +
            '<div class="sd-dim-arrow-tick sd-dim-tick-left"></div>' +
            '<div class="sd-dim-arrow-head sd-arrow-left"></div>' +
            '<div class="sd-dim-line-seg"></div>' +
            '<div class="sd-dim-total-badge">' + doorWidthMm + ' mm</div>' +
            '<div class="sd-dim-line-seg"></div>' +
            '<div class="sd-dim-arrow-head sd-arrow-right"></div>' +
            '<div class="sd-dim-arrow-tick sd-dim-tick-right"></div>' +
            '</div>';

        html += '</div>'; // .sd-door-center-col

        // 3. Right Side: Panel Dimension Lines for horizontal, or symmetrical spacer for vertical
        if (!isVert) {
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
        } else {
            html += '<div class="sd-dim-panels-spacer" style="width:80px; height:' + mockupH + 'px;"></div>';
        }

        html += '</div>'; // .sd-door-stage-unit

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
            var overlapMm = 30;
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
        var overlapMm = 30;
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
    document.addEventListener('click',  function (e) {
        // Reset Custom Splits in Door Design Modal
        if (e.target.id === 'sdDoorMockupResetBtn' || e.target.closest('#sdDoorMockupResetBtn')) {
            tempCustomSplits = null;
            renderDoorDesignModalContent();
            return;
        }

        // Close Door Design Modal
        if (e.target.id === 'sdDoorDesignModalClose' || e.target.closest('#sdDoorDesignModalClose') || e.target.id === 'sdDoorDesignModalCloseBtn' || e.target.closest('#sdDoorDesignModalCloseBtn') || e.target.id === 'sdDoorDesignModalOverlay') {
            if (activeDoorDesignModalSetId && sdSetStates[activeDoorDesignModalSetId]) {
                sdSetStates[activeDoorDesignModalSetId].selectedDoorIndices = [];
                var setC = container.querySelector('.sd-set[data-set-id="' + activeDoorDesignModalSetId + '"]');
                if (setC) {
                    sdRenderDoorDesigns(setC);
                    sdDrawCanvas(setC);
                }
            }
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
                if (!stSave.doorDesigns) stSave.doorDesigns = [];
                var targetDoors = (activeDoorDesignModalDoorIndices && activeDoorDesignModalDoorIndices.length > 0)
                    ? activeDoorDesignModalDoorIndices
                    : [0];

                targetDoors.forEach(function (dIdx) {
                    var curPanels = (stSave.doorDesigns[dIdx] && stSave.doorDesigns[dIdx].panels) ? stSave.doorDesigns[dIdx].panels.slice(0, tempDoorDesign.panels) : [];
                    while (curPanels.length < tempDoorDesign.panels) {
                        curPanels.push(null);
                    }
                    stSave.doorDesigns[dIdx] = {
                        design: tempDoorDesign,
                        orientation: tempDoorOrientation,
                        customSplits: tempCustomSplits ? tempCustomSplits.slice() : null,
                        panels: curPanels
                    };
                });

                stSave.doorDesign = tempDoorDesign;
                stSave.orientation = tempDoorOrientation;
                if (tempCustomSplits) {
                    if (!stSave.customSplits) stSave.customSplits = {};
                    stSave.customSplits[tempDoorDesign.id] = tempCustomSplits;
                }
                stSave.selectedDoorIndices = [];
                stSave.isAddedToCart = false;
                var setD = container.querySelector('.sd-set[data-set-id="' + activeDoorDesignModalSetId + '"]');
                if (setD) {
                    sdRenderDoorDesigns(setD);
                    sdRenderSummary(setD);
                    sdDrawCanvas(setD);
                }
                sdSaveStateToLocalStorage();
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
                sdSetStates[activeFrameModalSetId].isAddedToCart = false;
                var setF = container.querySelector('.sd-set[data-set-id="' + activeFrameModalSetId + '"]');
                if (setF) {
                    sdRenderFrameStyles(setF);
                    sdRenderSummary(setF);
                    sdDrawCanvas(setF);
                }
                sdSaveStateToLocalStorage();
            }
            var fOverlay = document.getElementById('sdFrameModalOverlay');
            if (fOverlay) fOverlay.classList.remove('is-visible');
            return;
        }

        // Close Glass Modal
        if (e.target.id === 'sdGlassModalClose' || e.target.id === 'sdGlassModalOverlay') {
            if (activeModalSetId && sdSetStates[activeModalSetId]) {
                sdSetStates[activeModalSetId].selectedPanels = [];
                var setGC = container.querySelector('.sd-set[data-set-id="' + activeModalSetId + '"]');
                if (setGC) {
                    sdRenderPanelDesign(setGC);
                    sdDrawCanvas(setGC);
                }
            }
            document.getElementById('sdGlassModalOverlay').classList.remove('is-visible');
            return;
        }
        // Close Board Modal
        if (e.target.id === 'sdBoardModalClose' || e.target.closest('#sdBoardModalClose') || e.target.id === 'sdBoardModalOverlay') {
            if (activeModalSetId && sdSetStates[activeModalSetId]) {
                sdSetStates[activeModalSetId].selectedPanels = [];
                var setBC = container.querySelector('.sd-set[data-set-id="' + activeModalSetId + '"]');
                if (setBC) {
                    sdRenderPanelDesign(setBC);
                    sdDrawCanvas(setBC);
                }
            }
            document.getElementById('sdBoardModalOverlay').classList.remove('is-visible');
            return;
        }

        // Switch Board Modal Brand Tabs
        var brandBtn = e.target.closest('#sdBoardModalOverlay .sd-board-brand-btn');
        if (brandBtn) {
            var selectedBrand = brandBtn.dataset.brand;
            currentBoardBrand = selectedBrand;
            currentBoardSearch = '';
            var searchInputB = document.getElementById('sdBoardSearchInput');
            var searchClearB = document.getElementById('sdBoardSearchClear');
            if (searchInputB) searchInputB.value = '';
            if (searchClearB) searchClearB.style.display = 'none';

            currentBoardCollection = '';
            populateBoardCollectionDropdown(selectedBrand);

            document.querySelectorAll('#sdBoardBrandTabs .sd-board-brand-btn').forEach(function (b) {
                b.classList.toggle('active', b.dataset.brand.toLowerCase() === selectedBrand.toLowerCase());
            });

            renderBoardSwatchesGrid();
            return;
        }

        // Clear Board Modal Search
        if (e.target.id === 'sdBoardSearchClear' || e.target.closest('#sdBoardSearchClear')) {
            var searchInputC = document.getElementById('sdBoardSearchInput');
            var searchClearC = document.getElementById('sdBoardSearchClear');
            if (searchInputC) searchInputC.value = '';
            if (searchClearC) searchClearC.style.display = 'none';
            currentBoardSearch = '';

            document.querySelectorAll('#sdBoardBrandTabs .sd-board-brand-btn').forEach(function (b) {
                b.classList.toggle('active', b.dataset.brand.toLowerCase() === currentBoardBrand.toLowerCase());
            });

            renderBoardSwatchesGrid();
            return;
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
            if (glassItemEl.dataset.outOfStock === '1' || glassItemEl.classList.contains('is-out-of-stock')) {
                alert('This glass finish is currently out of stock.');
                return;
            }
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
                var stG = sdSetStates[activeModalSetId];

                if (stG.selectedPanels && stG.selectedPanels.length > 0) {
                    stG.selectedPanels.forEach(function (sp) {
                        var sDoor = sp.doorIdx;
                        var sPan = sp.panelIdx;
                        if (stG.doorDesigns && stG.doorDesigns[sDoor]) {
                            if (!stG.doorDesigns[sDoor].panels) stG.doorDesigns[sDoor].panels = [];
                            stG.doorDesigns[sDoor].panels[sPan] = {
                                type: 'glass',
                                item: tempModalSelection
                            };
                        }
                    });
                    stG.selectedPanels = [];
                }

                var set = container.querySelector('.sd-set[data-set-id="' + activeModalSetId + '"]');
                stG.isAddedToCart = false;
                if (set) {
                    sdRenderPanelDesign(set);
                    sdRenderSummary(set);
                    sdDrawCanvas(set);
                }
                sdSaveStateToLocalStorage();
            }
            document.getElementById('sdGlassModalOverlay').classList.remove('is-visible');
            return;
        }

        // Select Swatch in Board Modal
        var boardItemEl = e.target.closest('#sdBoardModalOverlay .sd-modal-swatch-item');
        if (boardItemEl) {
            if (boardItemEl.dataset.outOfStock === '1' || boardItemEl.classList.contains('is-out-of-stock')) {
                alert('This decorative board is currently out of stock.');
                return;
            }
            var bid = boardItemEl.dataset.id;
            var bcode = boardItemEl.dataset.code;
            var matchB = BOARDS.find(function (b) {
                return (bid && String(b.id) === String(bid)) || (bcode && b.code === bcode);
            });
            if (matchB) {
                tempModalSelection = matchB;
                document.querySelectorAll('#sdBoardModalOverlay .sd-modal-swatch-item').forEach(function (el) {
                    el.classList.toggle('active', (bid && String(el.dataset.id) === String(bid)) || (bcode && el.dataset.code === bcode));
                });
                updateBoardPreview(matchB);
            }
            return;
        }

        // Save Board Modal
        if (e.target.id === 'sdBoardSaveBtn') {
            if (activeModalSetId && sdSetStates[activeModalSetId]) {
                var stB = sdSetStates[activeModalSetId];

                if (stB.selectedPanels && stB.selectedPanels.length > 0) {
                    stB.selectedPanels.forEach(function (sp) {
                        var sDoor = sp.doorIdx;
                        var sPan = sp.panelIdx;
                        if (stB.doorDesigns && stB.doorDesigns[sDoor]) {
                            if (!stB.doorDesigns[sDoor].panels) stB.doorDesigns[sDoor].panels = [];
                            stB.doorDesigns[sDoor].panels[sPan] = {
                                type: 'board',
                                item: tempModalSelection
                            };
                        }
                    });
                    stB.selectedPanels = [];
                }

                stB.isAddedToCart = false;
                var setB = container.querySelector('.sd-set[data-set-id="' + activeModalSetId + '"]');
                if (setB) {
                    sdRenderPanelDesign(setB);
                    sdRenderSummary(setB);
                    sdDrawCanvas(setB);
                }
                sdSaveStateToLocalStorage();
            }
            document.getElementById('sdBoardModalOverlay').classList.remove('is-visible');
            return;
        }
    });

    // Search input listener for Decorative Boards Modal
    document.addEventListener('input', function (e) {
        if (e.target && e.target.id === 'sdBoardSearchInput') {
            currentBoardSearch = e.target.value;
            var clearBtn = document.getElementById('sdBoardSearchClear');
            if (clearBtn) {
                clearBtn.style.display = currentBoardSearch.length > 0 ? 'flex' : 'none';
            }

            if (currentBoardSearch) {
                document.querySelectorAll('#sdBoardBrandTabs .sd-board-brand-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
            } else {
                document.querySelectorAll('#sdBoardBrandTabs .sd-board-brand-btn').forEach(function (b) {
                    b.classList.toggle('active', b.dataset.brand.toLowerCase() === currentBoardBrand.toLowerCase());
                });
            }

            renderBoardSwatchesGrid();
        }
    });

    // Collection dropdown listener for Decorative Boards Modal
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'sdBoardCollectionSelect') {
            currentBoardCollection = e.target.value;
            renderBoardSwatchesGrid();
        }
    });

    // Initialize sets from localStorage or default
    if (!sdRestoreStateFromLocalStorage()) {
        sdAddSet();
    }

    // Change listener for Container (Per-door Soft-closer checkboxes, etc.)
    container.addEventListener('change', function (e) {
        var set = e.target.closest('.sd-set');
        if (!set) return;

        var setId = set.dataset.setId;
        var st = sdSetStates[setId];
        if (!st) return;

        var chk = e.target.closest('.sd-door-softclose-chk');
        if (chk) {
            var doorIdx = parseInt(chk.dataset.door, 10);
            var side = chk.dataset.side;
            if (isNaN(doorIdx)) doorIdx = 0;

            if (!st.doorSoftClose) st.doorSoftClose = [];
            while (st.doorSoftClose.length <= doorIdx) {
                st.doorSoftClose.push({ left: false, right: false });
            }

            if (side === 'left') {
                st.doorSoftClose[doorIdx].left = chk.checked;
            } else if (side === 'right') {
                st.doorSoftClose[doorIdx].right = chk.checked;
            }
            st.isAddedToCart = false;
            sdRenderSummary(set);
            sdDrawCanvas(set);
            sdSaveStateToLocalStorage();
        }
    });

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
            sdSaveStateToLocalStorage();
            return;
        }

        // Step tab navigation
        var stepEl = e.target.closest('.sd-step.enabled');
        if (stepEl) {
            sdGoToStep(set, parseInt(stepEl.dataset.step, 10));
            return;
        }

        // Summary: Edit configuration button -> Go to Step 0
        var editConfigBtn = e.target.closest('.sd-summary-btn-edit-config');
        if (editConfigBtn) {
            sdGoToStep(set, 0);
            return;
        }

        // Summary: Add / Edit doors button -> Go to Step 2
        var addDoorsBtn = e.target.closest('.sd-summary-btn-add-doors');
        if (addDoorsBtn) {
            sdGoToStep(set, 2);
            return;
        }

        // Summary: Edit individual door button -> Open door design modal
        var editDoorBtn = e.target.closest('.sd-summary-door-edit-btn');
        if (editDoorBtn) {
            var dIdxSummary = parseInt(editDoorBtn.dataset.door, 10);
            if (isNaN(dIdxSummary)) dIdxSummary = 0;
            var curDoorSummary = (st.doorDesigns && st.doorDesigns[dIdxSummary]) ? st.doorDesigns[dIdxSummary] : { design: DESIGNS[0], orientation: 'horizontal', customSplits: null };
            openDoorDesignModal(setId, curDoorSummary.design || DESIGNS[0], [dIdxSummary]);
            return;
        }

        // STEP 1: Select Frame Style Card -> Opens popup modal! (Only in Step 1)
        var frameCard = e.target.closest('.sd-frame-card');
        if (frameCard && currentStep === 1) {
            var fCode = frameCard.dataset.code;
            openFrameModal(setId, fCode);
            return;
        }

        // STEP 2: Click Door Select Zone or Edit Badge on canvas (Only in Step 2)
        var doorZone = e.target.closest('.sd-door-select-zone');
        if (doorZone && currentStep === 2) {
            var warnEl = set.querySelector('.sd-door-warning');
            if (warnEl) {
                warnEl.style.display = 'none';
                if (st._doorWarnTimeout) clearTimeout(st._doorWarnTimeout);
            }
            set.querySelectorAll('.sd-door-select-zone').forEach(function (z) {
                z.classList.remove('sd-door-pulse');
            });

            var doorIdx = parseInt(doorZone.dataset.door, 10);
            if (isNaN(doorIdx)) doorIdx = 0;

            if (!st.selectedDoorIndices) st.selectedDoorIndices = [];

            var isBadge = Boolean(e.target.closest('.sd-door-edit-badge'));

            if (isBadge) {
                // Open modal for all currently selected doors
                var targetDoors = (st.selectedDoorIndices.length > 0) ? st.selectedDoorIndices.slice() : [doorIdx];
                var curDoor = (st.doorDesigns && st.doorDesigns[doorIdx]) ? st.doorDesigns[doorIdx] : { design: DESIGNS[0], orientation: 'horizontal', customSplits: null };
                openDoorDesignModal(setId, curDoor.design, targetDoors);
            } else {
                // Toggle selection for this door
                var dPos = st.selectedDoorIndices.indexOf(doorIdx);
                if (dPos === -1) {
                    st.selectedDoorIndices.push(doorIdx);
                } else {
                    st.selectedDoorIndices.splice(dPos, 1);
                }
                sdRenderDoorDesigns(set);
                sdDrawCanvas(set);
            }
            return;
        }

        // STEP 2: Select Door Design Card -> Apply to selected doors or open modal (Only in Step 2)
        var designCard = e.target.closest('.sd-design-card');
        if (designCard && currentStep === 2) {
            // Must select a door on the canvas first before choosing a layout
            if (!st.selectedDoorIndices || st.selectedDoorIndices.length === 0) {
                var dWarnEl = set.querySelector('.sd-door-warning');
                if (dWarnEl) {
                    dWarnEl.style.display = 'block';
                    if (st._doorWarnTimeout) clearTimeout(st._doorWarnTimeout);
                    st._doorWarnTimeout = setTimeout(function () {
                        dWarnEl.style.display = 'none';
                    }, 4000);
                }

                // Pulse preview doors on canvas to guide user's attention
                set.querySelectorAll('.sd-door-select-zone').forEach(function (z) {
                    z.classList.remove('sd-door-pulse');
                    void z.offsetWidth;
                    z.classList.add('sd-door-pulse');
                });
                return;
            }

            var pCount = parseInt(designCard.dataset.panels, 10);
            var dMatch = DESIGNS.find(function (d) { return d.panels === pCount; }) || DESIGNS[0];
            var targetDoorIndices = st.selectedDoorIndices.slice();

            if (pCount === 1) {
                if (!st.doorDesigns) st.doorDesigns = [];
                targetDoorIndices.forEach(function (dIdx) {
                    st.doorDesigns[dIdx] = {
                        design: dMatch,
                        orientation: 'horizontal',
                        customSplits: null,
                        panels: (st.doorDesigns[dIdx] && st.doorDesigns[dIdx].panels) ? st.doorDesigns[dIdx].panels : []
                    };
                });
                st.doorDesign = dMatch;
                st.selectedDoorIndices = [];
                sdRenderDoorDesigns(set);
                sdDrawCanvas(set);
                sdSaveStateToLocalStorage();
            } else {
                openDoorDesignModal(setId, dMatch, targetDoorIndices);
            }
            return;
        }

        // STEP 3: Click Panel Select Zone on canvas (Only in Step 3)
        var panelZone = e.target.closest('.sd-panel-select-zone');
        if (panelZone && currentStep === 3) {
            var pWarnEl = set.querySelector('.sd-panel-warning');
            if (pWarnEl) {
                pWarnEl.style.display = 'none';
                if (st._panelWarnTimeout) clearTimeout(st._panelWarnTimeout);
            }
            set.querySelectorAll('.sd-panel-select-zone').forEach(function (z) {
                z.classList.remove('sd-door-pulse');
            });

            var doorIdx = parseInt(panelZone.dataset.door, 10);
            var panelIdx = parseInt(panelZone.dataset.panel, 10);
            if (isNaN(doorIdx)) doorIdx = 0;
            if (isNaN(panelIdx)) panelIdx = 0;

            if (!st.selectedPanels) st.selectedPanels = [];

            // Toggle selection for this sub-panel
            var existingIdx = st.selectedPanels.findIndex(function (p) {
                return p.doorIdx === doorIdx && p.panelIdx === panelIdx;
            });
            if (existingIdx === -1) {
                st.selectedPanels.push({ doorIdx: doorIdx, panelIdx: panelIdx });
            } else {
                st.selectedPanels.splice(existingIdx, 1);
            }
            sdRenderPanelDesign(set);
            sdDrawCanvas(set);
            return;
        }

        // STEP 3: Click Panel Design Cards -> Open Modals (Only in Step 3)
        var panelCard = e.target.closest('.sd-panel-card');
        if (panelCard && currentStep === 3) {
            // Must select a panel on the canvas first before choosing material
            if (!st.selectedPanels || st.selectedPanels.length === 0) {
                var pWarnCard = set.querySelector('.sd-panel-warning');
                if (pWarnCard) {
                    pWarnCard.style.display = 'block';
                    if (st._panelWarnTimeout) clearTimeout(st._panelWarnTimeout);
                    st._panelWarnTimeout = setTimeout(function () {
                        pWarnCard.style.display = 'none';
                    }, 4000);
                }

                set.querySelectorAll('.sd-panel-select-zone').forEach(function (z) {
                    z.classList.remove('sd-door-pulse');
                    void z.offsetWidth;
                    z.classList.add('sd-door-pulse');
                });
                return;
            }

            var pType = panelCard.dataset.type;
            if (pType === 'glass') {
                openGlassModal(setId);
            } else {
                openBoardModal(setId);
            }
            return;
        }

    /**
     * Builds standard Sliding Doors Cart Item payload object from configurator state
     */
    function buildSlidingDoorsCartItem(setId) {
        var st = sdSetStates[setId];
        if (!st) return null;

        var w = parseInt(st.width, 10) || 3000;
        var h = parseInt(st.height, 10) || 2000;
        var dc = parseInt(st.doorCount, 10) || 2;

        var overlapMm = 30;
        var doorWidthMm = Math.round((w + (dc - 1) * overlapMm) / dc);
        var doorHeightMm = h - 40; // 40mm top track clearance
        var totalAreaSqm = (w / 1000) * (h / 1000);

        // Frame profile & colour finish
        var frameStyleName = (st.frameStyle && st.frameStyle.name) ? st.frameStyle.name : 'Palermo';
        var frameColorName = (st.frameColor && st.frameColor.name) ? st.frameColor.name : 'Satin Silver';
        var frameFinish = frameStyleName + ' (' + frameColorName + ')';

        var frameBase = (st.frameColor && typeof st.frameColor.basePrice === 'number') ? st.frameColor.basePrice : ((st.frameStyle && typeof st.frameStyle.basePrice === 'number') ? st.frameStyle.basePrice : 0.00);
        var frameSurcharge = (st.frameColor && st.frameColor.surcharge) ? st.frameColor.surcharge : 0.00;
        var totalFrameCost = dc * (frameBase + frameSurcharge);

        // Running Tracks
        var trackCost = (w / 1000) * (PRICING.trackPerMeter || 42.00);
        var trackType = 'Top & Bottom Aluminium Running Tracks (' + w + 'mm)';

        // Dividers, Infill Costs & Door Details
        var dividersCost = 0;
        var infillCost = 0;
        var doorDetailsLines = [];

        for (var di = 0; di < dc; di++) {
            var dObj = (st.doorDesigns && st.doorDesigns[di]) ? st.doorDesigns[di] : { design: st.doorDesign || DESIGNS[0], orientation: st.orientation || 'horizontal', customSplits: null, panels: [] };
            var dDes = dObj.design || DESIGNS[0];
            var dHbar = (dDes && dDes.hBarPrice) ? dDes.hBarPrice : 0;
            dividersCost += dHbar;

            var pCount = dDes.panels || 1;
            var dOrient = dObj.orientation || 'horizontal';
            var dArea = totalAreaSqm / dc;
            var pArea = dArea / pCount;
            var panelsDesc = [];

            for (var pi = 0; pi < pCount; pi++) {
                var pMat = (dObj.panels && dObj.panels[pi]) ? dObj.panels[pi] : null;
                var pName = 'Standard White Panel';
                if (pMat && pMat.type === 'glass' && pMat.item) {
                    var g = pMat.item;
                    infillCost += pArea * (g.pricePerSqm || 30.00);
                    pName = g.name + ' [Glass' + (g.category ? ' - ' + g.category : '') + ']';
                } else if (pMat && pMat.type === 'board' && pMat.item) {
                    var b = pMat.item;
                    infillCost += pArea * (PRICING.mfcBasePerSqm || 28.00);
                    pName = (b.title || b.code || 'Decorative Board') + ' [' + (b.brand || 'Board') + ']';
                } else {
                    infillCost += pArea * (PRICING.mfcBasePerSqm || 28.00);
                    pName = 'Standard White Panel';
                }
                panelsDesc.push(pName);
            }

            var splitsText = '';
            if (dObj.customSplits && dObj.customSplits.length === pCount) {
                splitsText = ' (Splits: ' + dObj.customSplits.join(' / ') + 'mm)';
            }

            var dOrientCap = dOrient ? (dOrient.charAt(0).toUpperCase() + dOrient.slice(1)) : '';

            if (pCount > 1) {
                var doorLayoutInfo = dDes.name + (dOrientCap ? ', ' + dOrientCap : '') + splitsText;
                doorDetailsLines.push('Door ' + (di + 1) + ': ' + doorLayoutInfo);
                for (var pi = 0; pi < panelsDesc.length; pi++) {
                    var panelLetter = String.fromCharCode(65 + pi);
                    doorDetailsLines.push('Door ' + (di + 1) + ' - Panel ' + panelLetter + ': ' + panelsDesc[pi]);
                }
            } else {
                var singleMat = panelsDesc[0] || 'Standard White Panel';
                doorDetailsLines.push('Door ' + (di + 1) + ': ' + singleMat + ' (Single panel' + splitsText + ')');
            }
        }

        // Soft-closers
        var softCloseSummary = [];
        var softCloseDoorsCount = 0;
        if (st.doorSoftClose && Array.isArray(st.doorSoftClose)) {
            st.doorSoftClose.forEach(function(sc, idx) {
                if (sc && (sc.left || sc.right)) {
                    softCloseDoorsCount++;
                    if (sc.left && sc.right) {
                        softCloseSummary.push('Door ' + (idx + 1) + ': Left & Right');
                    } else if (sc.left) {
                        softCloseSummary.push('Door ' + (idx + 1) + ': Left');
                    } else if (sc.right) {
                        softCloseSummary.push('Door ' + (idx + 1) + ': Right');
                    }
                }
            });
        }
        var softCloseUnitPrice = (PRICING.softCloseUnitPrice || 38.50);
        var softCloseCost = softCloseDoorsCount * softCloseUnitPrice;
        var softCloseText = softCloseSummary.length ? softCloseSummary.join(', ') : 'None';

        // Net unit price
        var unitPrice = Math.round((trackCost + totalFrameCost + dividersCost + infillCost + softCloseCost) * 100) / 100;

        // Image
        var image = (st.frameColor && st.frameColor.image) ? st.frameColor.image : ((st.frameStyle && (st.frameStyle.profileImage || st.frameStyle.featuredImage)) ? (st.frameStyle.profileImage || st.frameStyle.featuredImage) : '');

        return {
            setId: setId,
            qty: 1,
            title: 'Custom Sliding Wardrobe Doors (Set ' + setId + ' - ' + dc + ' Doors)',
            openingWidth: w,
            openingHeight: h,
            doorCount: dc,
            doorWidth: doorWidthMm,
            doorHeight: doorHeightMm,
            frameFinish: frameFinish,
            trackType: trackType,
            softClose: softCloseText,
            doorDetails: doorDetailsLines.join('\n'),
            unitPrice: unitPrice,
            image: image
        };
    }

    // STEP 4: Add to Basket / View Cart (Only in Step 4)
    if (e.target.closest('.sd-btn-add-cart') && currentStep === 4) {
        var btn = set.querySelector('.sd-btn-add-cart');
        if (btn && btn.disabled) return;

        var cartUrl = (window.slidingDoorsData && window.slidingDoorsData.cartUrl) || (DATA && DATA.cartUrl) || '/cart/';

        // If already added, clicking opens the cart directly
        if (btn && btn.classList.contains('is-added')) {
            window.location.href = cartUrl;
            return;
        }

        var item = buildSlidingDoorsCartItem(setId);
        if (!item) {
            alert('Please ensure your sliding doors configuration is complete.');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="sd-btn-spinner"></span><span>' + (DATA.i18n ? DATA.i18n.addingToBasket : 'Adding to basket...') + '</span>';
        }

        var ajaxUrl = (window.slidingDoorsData && window.slidingDoorsData.ajaxUrl) ? window.slidingDoorsData.ajaxUrl : (DATA.ajaxUrl || '/wp-admin/admin-ajax.php');
        var ajaxNonce = (window.slidingDoorsData && window.slidingDoorsData.nonce) ? window.slidingDoorsData.nonce : (DATA.nonce || '');

        var formData = new FormData();
        formData.append('action', 'cutlist_add_to_cart');
        formData.append('nonce', ajaxNonce);
        formData.append('data', JSON.stringify({
            clear_cart: false,
            replace_sliding_doors: true,
            slidingDoorsItems: [item]
        }));

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function (response) {
            return response.text().then(function (text) {
                var json;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    console.error('Server returned non-JSON response:', text);
                    throw new Error('Server returned an unexpected response. Please try again.');
                }
                return json;
            });
        })
        .then(function (res) {
            if (res && res.success) {
                if (st) {
                    st.isAddedToCart = true;
                }
                if (btn) {
                    btn.disabled = false;
                    btn.classList.add('is-added');
                    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><span>View Cart</span>';
                }

                // Remove any WooCommerce default injected .added_to_cart links
                set.querySelectorAll('.added_to_cart').forEach(function (el) { el.remove(); });

                // Update WooCommerce cart fragments in header if available
                try {
                    if (window.jQuery && window.jQuery(document.body)) {
                        window.jQuery(document.body).trigger('wc_fragment_refresh');
                        if (res.data) {
                            window.jQuery(document.body).trigger('added_to_cart', [res.data.fragments || {}, res.data.cart_hash || '', false]);
                        }
                    }
                } catch (wcErr) {
                    console.warn('WooCommerce fragment trigger notice:', wcErr);
                }
                set.querySelectorAll('.added_to_cart').forEach(function (el) { el.remove(); });
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('is-added');
                    btn.innerHTML = ICONS.cartIcon + '<span>Add to Basket</span>';
                }
                var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Could not add to basket. Please try again.';
                alert(errMsg);
            }
        })
        .catch(function (err) {
            console.error('Add to cart error:', err);
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('is-added');
                btn.innerHTML = ICONS.cartIcon + '<span>Add to Basket</span>';
            }
            alert((err && err.message) ? err.message : 'Connection error. Please try again.');
        });
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
            st.isAddedToCart = false;
            st.doorOrder = [];
            for (var odi = 0; odi < val; odi++) {
                st.doorOrder.push(odi);
            }
            sdEnableSteps(set, 4);
            sdDrawCanvas(set);
            sdSaveStateToLocalStorage();
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
                    sdSaveStateToLocalStorage();
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
            sdSaveStateToLocalStorage();
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
            st.isAddedToCart = false;
            sdValidateWidth(set, false);
            st.doorOrder = [];
            for (var dwi = 0; dwi < (st.doorCount || 2); dwi++) {
                st.doorOrder.push(dwi);
            }
            set.dataset.switched = '0';
            sdUpdateDoorDropdown(set);
            sdDrawCanvas(set);
            sdDebouncedSaveState();
        }

        if (e.target.classList.contains('sd-height')) {
            st.isAddedToCart = false;
            sdValidateHeight(set, false);
            st.doorOrder = [];
            for (var dhi = 0; dhi < (st.doorCount || 2); dhi++) {
                st.doorOrder.push(dhi);
            }
            set.dataset.switched = '0';
            sdUpdateDoorDropdown(set);
            sdDrawCanvas(set);
            sdDebouncedSaveState();
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
            sdSaveStateToLocalStorage();
        }

        if (e.target.classList.contains('sd-height')) {
            sdValidateHeight(set, true);
            sdUpdateDoorDropdown(set);
            sdSaveStateToLocalStorage();
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
