// Shared basket storage — a thin localStorage wrapper so every product page
// (Cut, Edge & Spray / CNC Machining / Sliding Doors / Hinged Doors / Furniture Fittings)
// and the basket.html page can read/write the same cart, since navigation between
// pages here is a full page load, not an SPA.
(function (global) {
    var STORAGE_KEY = 'cutlistBasket';

    function readAll() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function writeAll(data) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function getCategory(key) {
        var all = readAll();
        return all[key] || [];
    }

    function setCategory(key, items) {
        var all = readAll();
        all[key] = items;
        writeAll(all);
    }

    function addItem(key, item) {
        var items = getCategory(key);
        items.push(item);
        setCategory(key, items);
        return item;
    }

    function removeItem(key, id) {
        setCategory(key, getCategory(key).filter(function (it) { return it.id !== id; }));
    }

    function updateItem(key, id, patch) {
        var items = getCategory(key);
        var item = items.filter(function (it) { return it.id === id; })[0];
        if (!item) return null;
        Object.keys(patch).forEach(function (k) { item[k] = patch[k]; });
        setCategory(key, items);
        return item;
    }

    function categoryTotal(items) {
        return items.reduce(function (sum, it) { return sum + it.unitPrice * it.qty; }, 0);
    }

    function getGrandTotal() {
        var all = readAll();
        return Object.keys(all).reduce(function (sum, k) { return sum + categoryTotal(all[k] || []); }, 0);
    }

    function makeId(prefix) {
        return prefix + '_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
    }

    global.CutlistBasket = {
        STORAGE_KEY: STORAGE_KEY,
        readAll: readAll,
        getCategory: getCategory,
        setCategory: setCategory,
        addItem: addItem,
        removeItem: removeItem,
        updateItem: updateItem,
        categoryTotal: categoryTotal,
        getGrandTotal: getGrandTotal,
        makeId: makeId
    };
})(window);
