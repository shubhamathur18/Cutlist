    (function () {
        var bar = document.getElementById('cbTopbar');
        if (bar && window.CutlistBasket) {
            var all = CutlistBasket.readAll();
            var hasItems = Object.keys(all).some(function (k) { return (all[k] || []).length > 0; });
            bar.style.display = hasItems ? '' : 'none';
        }
    })();
