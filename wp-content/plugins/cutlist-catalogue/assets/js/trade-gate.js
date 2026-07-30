// Trade-account gate — Furniture Fittings is only available to trading accounts.
// Uses the same demo login flag (localStorage "cutlistAccountType") as the
// checkout pages; regular customers get a hover tooltip and blocked navigation.
(function () {
    var LOCKED_MSG = 'You need a trading account to access this';

    function isTrade() {
        return localStorage.getItem('cutlistAccountType') === 'trade';
    }

    function injectStyles() {
        var css = '' +
            '.trade-locked { position: relative; cursor: not-allowed; opacity: 0.55; }' +
            '.trade-locked:hover::after {' +
            '  content: attr(data-locked-msg);' +
            '  position: absolute; top: 100%; left: 50%; transform: translateX(-50%);' +
            '  margin-top: 8px; padding: 7px 14px; background: #333; color: #fff;' +
            '  font-size: 12px; line-height: 1.4; white-space: nowrap;' +
            '  border-radius: 3px; z-index: 10001; pointer-events: none;' +
            '}' +
            '.trade-locked:hover::before {' +
            '  content: ""; position: absolute; top: 100%; left: 50%;' +
            '  transform: translateX(-50%); margin-top: 2px;' +
            '  border: 6px solid transparent; border-bottom-color: #333;' +
            '  z-index: 10001; pointer-events: none;' +
            '}' +
            '.trade-gate-overlay {' +
            '  position: fixed; inset: 0; background: #fff; z-index: 99999;' +
            '  display: flex; align-items: center; justify-content: center;' +
            '  font-family: Arial, Helvetica, sans-serif;' +
            '}' +
            '.trade-gate-inner { text-align: center; max-width: 460px; padding: 20px; }' +
            '.trade-gate-inner h2 { font-size: 26px; font-weight: 400; color: #1a2a3a; margin: 0 0 12px; }' +
            '.trade-gate-inner p { font-size: 14px; color: #667; line-height: 1.7; margin: 0 0 28px; }' +
            '.trade-gate-inner a, .trade-gate-inner button {' +
            '  display: inline-block; height: 44px; line-height: 44px; padding: 0 22px;' +
            '  font-size: 14px; font-family: inherit; text-decoration: none; cursor: pointer; margin: 0 5px;' +
            '}' +
            '.trade-gate-back { background: #fff; border: 1px solid #5da344; color: #5da344; }' +
            '.trade-gate-switch { background: #5da344; border: 1px solid #5da344; color: #fff; }' +
            '.trade-demo-bar {' +
            '  position: fixed; bottom: 14px; left: 14px; z-index: 100000;' +
            '  display: flex; align-items: center; gap: 8px;' +
            '  background: #fff; border: 1px solid #d5d5d5; border-radius: 4px;' +
            '  box-shadow: 0 2px 8px rgba(0,0,0,0.12); padding: 8px 12px;' +
            '  font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #888;' +
            '}' +
            '.trade-demo-bar select {' +
            '  height: 28px; border: 1px solid #d5d5d5; font-size: 12px;' +
            '  font-family: inherit; color: #333; padding: 0 6px; cursor: pointer;' +
            '}';
        var style = document.createElement('style');
        style.textContent = css;
        document.head.appendChild(style);
    }

    function lockLinks() {
        document.querySelectorAll('a[href$="furniture-fittings.html"]').forEach(function (link) {
            link.classList.add('trade-locked');
            link.setAttribute('data-locked-msg', LOCKED_MSG);
            link.addEventListener('click', function (e) {
                e.preventDefault();
            });
        });
    }

    function gatePage() {
        var overlay = document.createElement('div');
        overlay.className = 'trade-gate-overlay';
        overlay.innerHTML = '' +
            '<div class="trade-gate-inner">' +
            '<h2>Trading account required</h2>' +
            '<p>' + LOCKED_MSG + '. Furniture Fittings are available to trade customers only. ' +
            'If you have a trading account, please log in to continue.</p>' +
            '<a class="trade-gate-back" href="cut-edge-spray.html">Back to Cut, Edge &amp; Spray</a>' +
            '<button class="trade-gate-switch" type="button" id="tradeGateSwitch">Log in as trader (demo)</button>' +
            '</div>';
        document.body.appendChild(overlay);
        document.getElementById('tradeGateSwitch').addEventListener('click', function () {
            localStorage.setItem('cutlistAccountType', 'trade');
            location.reload();
        });
    }

    injectStyles();

    if (isTrade()) return;

    var onFittingsPage = /furniture-fittings\.html$/i.test(location.pathname);

    if (onFittingsPage) {
        gatePage();
    } else {
        lockLinks();
    }
})();
