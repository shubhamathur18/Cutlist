// These overlays are position:fixed/absolute and expect document-relative
// coordinates, which breaks if a WordPress theme wrapper sets
// position:relative on an ancestor — moving them to be direct children of
// <body> avoids that.
['decorPopup', 'edgePopup', 'machiningOverlay', 'sprayOverlay', 'panelModalOverlay', 'panelSummaryModalOverlay'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) document.body.appendChild(el);
});

let activeDecorInput = null;
const popup = document.getElementById("decorPopup");
const table = document.querySelector(".table-area table");

const edgePopup = document.getElementById("edgePopup");
const edgeTitle = document.getElementById("edgeTitle");
const edgeDimTop = document.getElementById("edgeDimTop");
const edgeDimLeft = document.getElementById("edgeDimLeft");
const edgeDimRight = document.getElementById("edgeDimRight");
const edgeSummaryCode = document.getElementById("edgeSummaryCode");
const edgeSummaryBtn = document.getElementById("edgeSummaryBtn");
const edgeTabs = document.getElementById("edgeTabs");
const edgeFinishOptions = document.getElementById("edgeFinishOptions");
const edgeModeToggle = document.getElementById("edgeModeToggle");

const edgeHighlights = {
    L1: document.getElementById("edgeHighlightL1"),
    L2: document.getElementById("edgeHighlightL2"),
    W1: document.getElementById("edgeHighlightW1"),
    W2: document.getElementById("edgeHighlightW2")
};

const edgeState = new WeakMap();
const TAPE_CODE = "M1";

let activeEdgeRow = null;
let activeEdge = null;

const machiningOverlay = document.getElementById("machiningOverlay");
const machiningDiagram = document.getElementById("machiningDiagram");
const machiningFaceBox = document.getElementById("machiningFaceBox");

let machiningZoom = 1;
// PANEL SUMMARY NAVIGATION

let panelSummaryRows = [];
let panelSummaryCurrentIndex = 0;

// HELPERS

function getDimInputs(row) {

    let dims = row.querySelectorAll("td.small:not(.edging-input) input");

    return { lengthInput: dims[0], widthInput: dims[1] };

}


function toggleInvalid(input) {

    if (!input) return;

    if (!input.disabled && input.value.trim() === "") {

        input.classList.add("invalid");

    } else {

        input.classList.remove("invalid");

    }

}


// A part can't be cut larger than the selected board's own length/width
// (minus the 40mm cutting margin, stored as data-max-length/data-max-width
// when a decor is picked).
function checkMaxDimension(input, row) {
    var isLength = getDimInputs(row).lengthInput === input;
    var max = isLength ? row.dataset.maxLength : row.dataset.maxWidth;
    var label = isLength ? 'length' : 'width';

    hideMaxTooltip(input);

    if (!max) return;

    var value = parseInt(input.value, 10);
    if (!isNaN(value) && value > parseInt(max, 10)) {
        input.classList.add("invalid");
        showMaxTooltip(input, 'Maximum ' + label + ' is ' + max + 'mm');
    }
}

function showMaxTooltip(input, message) {
    var tip = document.createElement("div");
    tip.className = "cutlist-max-tooltip";
    tip.textContent = message;
    input.parentNode.style.position = "relative";
    input.parentNode.appendChild(tip);
}

function hideMaxTooltip(input) {
    var existing = input.parentNode.querySelector(".cutlist-max-tooltip");
    if (existing) existing.remove();
}

function updateEdging(row) {

    let { lengthInput, widthInput } = getDimInputs(row);

    if (!lengthInput || !widthInput) return;

    let length = parseInt(lengthInput.value);
    let width = parseInt(widthInput.value);

    let enableEdging =
        (
            length >= 59 &&
            width >= 60
        )
        ||
        (
            width >= 59 &&
            length >= 60
        );

    row.querySelectorAll(".edging-input input")
        .forEach(edge => {

            edge.disabled = !enableEdging;

            if (!enableEdging) {

                edge.value = "";

            }

        });

    if (!enableEdging) {

        edgeState.delete(row);

    }

}


// EDGE FINISH POPUP

function getEdgeState(row) {

    if (!edgeState.has(row)) {

        edgeState.set(row, { L1: null, L2: null, W1: null, W2: null });

    }

    return edgeState.get(row);

}


function renderEdgePopup() {

    if (!activeEdgeRow || !activeEdge) return;

    let state = getEdgeState(activeEdgeRow);
    let { lengthInput, widthInput } = getDimInputs(activeEdgeRow);
    let decorInput = activeEdgeRow.querySelector(".decor input");

    edgeDimTop.textContent = lengthInput && lengthInput.value ? lengthInput.value : "-";
    edgeDimLeft.textContent = widthInput && widthInput.value ? widthInput.value : "-";
    edgeDimRight.textContent = widthInput && widthInput.value ? widthInput.value : "-";

    let decorText = decorInput && decorInput.value ? decorInput.value : "-";
    edgeSummaryCode.textContent = "M1-22 / " + decorText.split(" - ")[0];

    edgeTabs.querySelectorAll(".edge-tab").forEach(tab => {

        let edge = tab.dataset.edge;

        tab.classList.toggle("active", edge === activeEdge);

        tab.querySelector(".edge-tab-value").textContent =
            state[edge] ? TAPE_CODE : "-";

    });

    Object.keys(edgeHighlights).forEach(edge => {

        edgeHighlights[edge].classList.toggle("active", !!state[edge]);

    });

    edgeFinishOptions.querySelectorAll(".edge-finish-option").forEach(opt => {

        opt.classList.toggle("selected", opt.dataset.finish === state[activeEdge]);

    });

}


function openEdgePopup(row, edge, anchorEl) {

    activeEdgeRow = row;
    activeEdge = edge;

    edgeTitle.textContent = edge;
    edgeSummaryBtn.classList.remove("selected");

    renderEdgePopup();

    let position = anchorEl.getBoundingClientRect();

    edgePopup.style.left = position.left + "px";
    edgePopup.style.top = (position.bottom + window.scrollY) + "px";

    edgePopup.style.display = "block";

}


function closeEdgePopup() {

    if (activeEdgeRow) {

        let state = getEdgeState(activeEdgeRow);

        activeEdgeRow.querySelectorAll(".edging-input").forEach(td => {

            let edge = td.dataset.edge;
            let input = td.querySelector("input");

            if (!input || input.disabled) return;

            input.value = state[edge] ? TAPE_CODE : "";

        });

    }

    edgePopup.style.display = "none";

    activeEdgeRow = null;
    activeEdge = null;

}


// Scales the Machining/Spray diagram grid's centre cell proportionally to
// the row's real length/width, capped to a 320x320 envelope.
function scaleMachiningDiagramPanel(diagramEl, lengthValue, widthValue) {
    if (!diagramEl) return;

    var length = parseFloat(lengthValue);
    var width = parseFloat(widthValue);
    var maxSize = 320;
    var minSize = 90;
    var w = maxSize;
    var h = maxSize;

    if (!isNaN(length) && !isNaN(width) && length > 0 && width > 0) {
        var scale = Math.min(maxSize / length, maxSize / width);
        w = Math.max(minSize, Math.round(length * scale));
        h = Math.max(minSize, Math.round(width * scale));
    }

    diagramEl.style.gridTemplateColumns = "50px " + w + "px 50px 90px";
    diagramEl.style.gridTemplateRows = "50px " + h + "px 50px 50px";
}

// MACHINING DETAILS OVERLAY

function openMachiningOverlay(row) {

    let decorInput = row.querySelector(".decor input");
    let thickSelect = row.querySelector(".thick select");
    let { lengthInput, widthInput } = getDimInputs(row);
    let qtyInput = row.querySelector(".qty input");
    let descInput = row.querySelector(".desc input");

    document.getElementById("mRownum").textContent =
        row.querySelector(".rownum").textContent;

    document.getElementById("mDecor").textContent =
        decorInput && decorInput.value ? decorInput.value : "-";

    document.getElementById("mThick").textContent =
        thickSelect && thickSelect.value ? thickSelect.value : "-";

    document.getElementById("mLength").textContent =
        lengthInput && lengthInput.value ? lengthInput.value : "-";

    document.getElementById("mWidth").textContent =
        widthInput && widthInput.value ? widthInput.value : "-";

    document.getElementById("mQty").textContent =
        qtyInput && qtyInput.value ? qtyInput.value : "-";

    document.getElementById("mDesc").textContent =
        descInput && descInput.value ? descInput.value : "-";

    row.querySelectorAll(".edging-input").forEach(td => {

        let edge = td.dataset.edge;
        let input = td.querySelector("input");
        let target = document.getElementById("m" + edge);

        if (target) target.textContent = input && input.value ? input.value : "-";

    });

    resetMachiningOptionSelect();

    machiningCurrentRow = row;
    loadMachiningAppliedItems(row);
    renderMachiningAppliedList();

    // Must open before the first redrawMachiningCanvas() call — Konva's
    // stage needs actual layout, not display:none, to construct correctly.
    machiningOverlay.classList.add("open");
    redrawMachiningCanvas();
    fitMachiningDiagramToContainer();

}

// The Konva stage is a fixed 500x460 design size, but .machining-canvas'
// on-screen size varies — scale the whole diagram down to fit instead of
// overflowing into a scrollbar. machiningZoom becomes the zoom buttons' new
// baseline rather than always starting back at 1.
function fitMachiningDiagramToContainer() {
    // Scoped to machiningOverlay, not a bare document-wide selector — the
    // Spray overlay reuses the same .machining-canvas class.
    var container = machiningOverlay ? machiningOverlay.querySelector(".machining-canvas") : null;
    if (!container || !machiningDiagram) return;

    var rect = container.getBoundingClientRect();
    var padding = 20;
    var availW = Math.max(100, rect.width - padding * 2);
    var availH = Math.max(100, rect.height - padding * 2);

    machiningZoom = Math.min(1, availW / 500, availH / 460);
    machiningDiagram.style.transform = "translate(-50%, -50%) scale(" + machiningZoom + ")";
}


function closeMachiningOverlay() {

    machiningOverlay.classList.remove("open");

}


function updateMachiningBtn(row) {

    let qtyInput = row.querySelector(".qty input");
    let addBtn = row.querySelector(".machining .add-btn");

    if (!qtyInput || !addBtn) return;

    let qty = parseInt(qtyInput.value);

    addBtn.classList.toggle("visible", !qtyInput.disabled && !isNaN(qty) && qty >= 1);

}


function renumberRows() {

    table.querySelectorAll(":scope > * > tr:not(.header-row):not(.section-row)")
        .forEach((row, i) => {

            row.querySelector(".rownum").textContent = i + 1;

        });

}


function createRow() {

    let template = table.querySelector(":scope > * > tr:not(.header-row):not(.section-row)");
    let row = template.cloneNode(true);

    row.classList.remove("unlocked");

    row.querySelectorAll("input").forEach(input => {

        if (input.type === "checkbox") {

            input.checked = false;
            input.disabled = true;

        } else {

            input.value = "";
            input.disabled = !input.closest(".decor");
            input.classList.remove("invalid");

        }

    });

    row.querySelectorAll("select").forEach(select => {

        select.disabled = true;
        select.value = "";

    });

    row.querySelectorAll(".machining .add-btn, .spray .add-btn").forEach(btn => {

        btn.classList.remove("visible");

    });

    return row;

}


// ADD ROW

document.getElementById("addRowBtn").addEventListener("click", function () {

    (table.querySelector(":scope > tbody") || table).appendChild(createRow());
    renumberRows();

});


// ── CUTTING LIST: INSERT ROW / SECTION HOVER ──────────────────────────

function createSectionRow() {
    var moveSvg = '<svg width="19" height="19" viewBox="0 0 32 32"><path fill="currentColor" d="M4 20h11v6.17l-2.59-2.58L11 25l5 5 5-5-1.41-1.41L17 26.17V20h11v-2H4v2zM11 7l1.41 1.41L15 5.83V12H4v2h24v-2H17V5.83l2.59 2.58L21 7l-5-5-5 5z"></path></svg>';
    var tr = document.createElement('tr');
    tr.className = 'section-row';
    /* 15 cols: # | decor+thick(colspan=2) | length→grain(colspan=11 empty) | actions(⇅ + ×) */
    tr.innerHTML =
        '<td></td>' +
        '<td colspan="2" class="sr-input-cell">' +
        '<input type="text" class="section-name-input" placeholder="Add section name" maxlength="50" title="Give this group of rows a name, like a room or cabinet, so it\'s easy to find on the label when your wood arrives.">' +
        '</td>' +
        '<td colspan="11"></td>' +
        '<td class="actions">' +
        '<div class="actions-inner">' +
        '<span class="icon move" title="Drag to reorder">' + moveSvg + '</span>' +
        '<span class="delete" title="Delete section">×</span>' +
        '</div>' +
        '</td>';
    return tr;
}

var clOverlay = document.createElement('div');
clOverlay.id = 'clInsertOverlay';
clOverlay.innerHTML =
    '<div class="cl-insert-strip top" data-action="section">' +
    '<div class="cl-insert-plus">+</div>' +
    '<span class="cl-insert-rule"></span>' +
    '<span class="cl-insert-label">Add section name</span>' +
    '</div>' +
    '<div class="cl-insert-strip bottom" data-action="row">' +
    '<div class="cl-insert-plus">+</div>' +
    '<span class="cl-insert-rule"></span>' +
    '<span class="cl-insert-label">Insert a row</span>' +
    '</div>';
document.body.appendChild(clOverlay);

var clTargetRow = null;
var clHideTimer = null;

function clShowOverlay(tr) {
    if (clHideTimer) { clearTimeout(clHideTimer); clHideTimer = null; }
    clTargetRow = tr;
    var rowRect = tr.getBoundingClientRect();
    var decorCell = tr.cells[1]; // "Material decor code / name" column
    var cellRect = decorCell ? decorCell.getBoundingClientRect() : rowRect;
    clOverlay.style.top = rowRect.top + 'px';
    clOverlay.style.height = rowRect.height + 'px';
    clOverlay.style.left = cellRect.left + 'px';
    clOverlay.style.width = cellRect.width + 'px';
    clOverlay.style.display = 'block';
}

function clHideOverlay() {
    clOverlay.style.display = 'none';
    clTargetRow = null;
    clHideTimer = null;
}

function clScheduleHide() {
    if (clHideTimer) return;
    clHideTimer = setTimeout(clHideOverlay, 120);
}

table.addEventListener('mouseover', function (e) {
    var tr = e.target.closest('tr');
    if (tr && !tr.classList.contains('header-row') && !tr.classList.contains('section-row') && tr.closest('table') === table) {
        clShowOverlay(tr);
    }
});

table.addEventListener('mouseout', function (e) {
    var fromTr = e.target.closest('tr');
    if (fromTr && !fromTr.classList.contains('header-row') && !fromTr.classList.contains('section-row')) {
        clScheduleHide();
    }
});

clOverlay.addEventListener('mouseenter', function () {
    if (clHideTimer) { clearTimeout(clHideTimer); clHideTimer = null; }
});

clOverlay.addEventListener('mouseleave', function () { clScheduleHide(); });

clOverlay.addEventListener('click', function (e) {
    var strip = e.target.closest('.cl-insert-strip');
    if (!strip || !clTargetRow || !clTargetRow.parentNode) return;
    var parent = clTargetRow.parentNode;
    if (strip.dataset.action === 'section') {
        parent.insertBefore(createSectionRow(), clTargetRow);
    } else {
        parent.insertBefore(createRow(), clTargetRow.nextSibling);
    }
    renumberRows();
    clHideOverlay();
});

window.addEventListener('scroll', clHideOverlay, true);


// FULL SHEETS TABLE

const fsTable = document.getElementById("fsTable");
const fsTableArea = document.getElementById("fsTableArea");

function createFsRow() {
    const template = fsTable.querySelector("tr.fs-row");
    const row = template.cloneNode(true);
    row.classList.remove("unlocked");
    row.querySelectorAll("input,select").forEach(f => {
        f.value = "";
        f.disabled = true;
    });
    row.querySelector(".fs-brand").textContent = "–";
    return row;
}

function renumberFsRows() {
    fsTable.querySelectorAll("tr.fs-row").forEach((row, i) => {
        row.querySelector(".rownum").textContent = i + 1;
    });
}

document.getElementById("addFsRowBtn").addEventListener("click", function () {
    fsTable.appendChild(createFsRow());
    renumberFsRows();
});

fsTable.addEventListener("click", function (e) {

    const decorInput = e.target.closest(".decor input");
    if (decorInput) {
        activeDecorInput = decorInput;
        const pos = decorInput.getBoundingClientRect();
        popup.style.left = pos.left + "px";
        popup.style.top = (pos.bottom + window.scrollY) + "px";
        popup.style.display = "block";
        return;
    }

    const deleteBtn = e.target.closest(".delete");
    if (deleteBtn) {
        deleteBtn.closest("tr").remove();
        renumberFsRows();
        return;
    }

});


// EDGING TAPE TABLE

// Flattened server-side into one row per (tape, matched board) pair — see
// cutlist_proto_get_edge_tape_options().
var etTapes = window.cutlistEdgeTapes || [];

var SVG_ARROW = '<svg height="20" width="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M4.516 7.548c0.436-0.446 1.043-0.481 1.576 0l3.908 3.747 3.908-3.747c0.533-0.481 1.141-0.446 1.574 0 0.436 0.445 0.408 1.197 0 1.615-0.406 0.418-4.695 4.502-4.695 4.502-0.217 0.223-0.502 0.335-0.787 0.335s-0.57-0.112-0.789-0.335c0 0-4.287-4.084-4.695-4.502s-0.436-1.17 0-1.615z" fill="#888"/></svg>';
var SVG_CROSS = '<svg width="16" height="16" viewBox="0 0 16 16"><path d="M2 2l12 12M14 2L2 14" stroke="#cc2222" stroke-width="2.5" stroke-linecap="round"/></svg>';

function etOptionHTML(t, selected) {
    return '<div class="Select2__option' + (selected ? ' Select-option--selected' : '') + '" data-code="' + t.code + '" data-name="' + t.name + '" data-size="' + t.size + '" data-price="' + (t.unitPrice || 0) + '">' +
        '<div class="Select2__option-label"><div class="edgebanding-option">' +
        '<div class="code">' + t.code + '</div>' +
        '<div class="name">' + t.name + '</div>' +
        '<div class="size">' + t.size + '</div>' +
        '</div></div></div>';
}

// Only offer a tape once its matched board is actually on the cutting list.
function etCuttingListDecorCodes() {
    var codes = new Set();
    table.querySelectorAll('tr:not(.header-row) .decor input').forEach(function (input) {
        var code = input.value.split(' - ')[0].trim();
        if (code) codes.add(code);
    });
    return codes;
}

function etVisibleTapes() {
    var codes = etCuttingListDecorCodes();
    if (!codes.size) return [];
    return etTapes.filter(function (t) { return codes.has(t.decorCode); });
}

function etAllOptionsHTML(selectedCode) {
    var visible = etVisibleTapes();
    if (!visible.length) {
        return '<div class="Select2__option Select2__option--empty">Add a board to the cutting list above first</div>';
    }
    return visible.map(function (t) {
        return etOptionHTML(t, t.code === selectedCode);
    }).join('');
}

// A row whose chosen tape no longer matches any selected board is reset
// rather than left pointing at a hidden option.
function etRefreshDropdowns() {
    document.querySelectorAll('#etTbody tr.et-row').forEach(function (row) {
        var sel = row.querySelector('.Select2');
        if (!sel) return;
        var dd = sel.querySelector('.Select2__dropdown');
        var selectedCode = sel.dataset.code || '';
        var stillVisible = selectedCode && etVisibleTapes().some(function (t) { return t.code === selectedCode; });

        if (selectedCode && !stillVisible) {
            delete sel.dataset.code;
            delete sel.dataset.price;
            sel.classList.add('isEmpty');
            var inputSpan = sel.querySelector('.Select2__input');
            if (inputSpan) inputSpan.innerHTML = '';
            var qtyInput = row.querySelector('.et-qty-input');
            if (qtyInput) { qtyInput.value = ''; qtyInput.disabled = true; }
            row.classList.remove('active');
            var priceCell = row.querySelector('.et-unit-price');
            if (priceCell) priceCell.textContent = '–';
            selectedCode = '';
        }

        dd.innerHTML = etAllOptionsHTML(selectedCode);
    });
}

function etBuildRow() {
    var tr = document.createElement('tr');
    tr.className = 'et-row editable';
    tr.innerHTML =
        '<td class="td-index"></td>' +
        '<td colspan="3">' +
        '<div class="Select2 isEmpty Select2--has-arrow">' +
        '<div class="Select2__input-wrapper">' +
        '<span class="Select2__input"></span>' +
        '<span class="Select2__placeholder">Select edging tape</span>' +
        '<span class="Select2__arrow">' + SVG_ARROW + '</span>' +
        '</div>' +
        '<div class="Select2__dropdown">' + etAllOptionsHTML('') + '</div>' +
        '</div>' +
        '</td>' +
        '<td></td>' +
        '<td><input class="et-qty-input" type="text" inputmode="numeric" pattern="[0-9]*" placeholder="Min 5" value="" disabled></td>' +
        '<td class="et-unit-price">&ndash;</td>' +
        '<td class="text-right"><button class="button-remove" type="button" title="Remove row">' + SVG_CROSS + '</button></td>';
    return tr;
}

function etRenumber() {
    document.querySelectorAll('#etTbody tr.et-row').forEach(function (row, i) {
        row.querySelector('.td-index').textContent = i + 1;
    });
}

function etCloseAll(except) {
    document.querySelectorAll('#etTbody .Select2.is-open').forEach(function (sel) {
        if (sel !== except) sel.classList.remove('is-open');
    });
}

document.querySelectorAll('#etTbody .Select2__dropdown').forEach(function (dd) {
    dd.innerHTML = etAllOptionsHTML('');
});

document.getElementById('etTableArea').addEventListener('click', function (e) {

    var wrapper = e.target.closest('.Select2__input-wrapper');
    if (wrapper) {
        var sel = wrapper.closest('.Select2');
        var isOpen = sel.classList.contains('is-open');
        etCloseAll(null);
        if (!isOpen) sel.classList.add('is-open');
        return;
    }

    var opt = e.target.closest('.Select2__option');
    if (opt && !opt.classList.contains('Select2__option--empty')) {
        var sel = opt.closest('.Select2');
        var row = opt.closest('tr.et-row');
        var code = opt.dataset.code;
        var name = opt.dataset.name;
        var size = opt.dataset.size;
        var price = parseFloat(opt.dataset.price) || 0;
        sel.dataset.price = price;
        sel.dataset.code = code;

        var inputSpan = sel.querySelector('.Select2__input');
        inputSpan.innerHTML =
            '<div class="edgebanding-option">' +
            '<div class="code">' + code + '</div>' +
            '<div class="name">' + name + '</div>' +
            '<div class="size">' + size + '</div>' +
            '</div>';

        sel.classList.remove('isEmpty');
        sel.classList.remove('is-open');
        sel.querySelector('.Select2__dropdown').innerHTML = etAllOptionsHTML(code);

        var qtyInput = row.querySelector('.et-qty-input');
        if (qtyInput) { qtyInput.disabled = false; qtyInput.focus(); }
        row.classList.add('active');

        var priceCell = row.querySelector('.et-unit-price');
        if (priceCell) priceCell.textContent = '£' + price.toFixed(2);

        document.getElementById('addEtRowBtn').disabled = false;
        return;
    }

    var removeBtn = e.target.closest('.button-remove');
    if (removeBtn) {
        removeBtn.closest('tr').remove();
        etRenumber();
        return;
    }

});

document.getElementById('addEtRowBtn').addEventListener('click', function () {
    var tbody = document.getElementById('etTbody');
    var row = etBuildRow();
    tbody.appendChild(row);
    etRenumber();
});

document.addEventListener('click', function (e) {
    if (!e.target.closest('#etTableArea .Select2')) {
        etCloseAll(null);
    }
});

// Edging tape is sold with a 5 metre minimum per roll
var ET_MIN_QTY = 5;

document.getElementById('etTableArea').addEventListener('input', function (e) {
    if (!e.target.classList.contains('et-qty-input')) return;
    hideMaxTooltip(e.target);
    var val = parseFloat(e.target.value);
    var isInvalid = !isNaN(val) && val < ET_MIN_QTY;
    e.target.classList.toggle('invalid', isInvalid);
    if (isInvalid) showMaxTooltip(e.target, 'Minimum quantity is ' + ET_MIN_QTY + 'm');
});

document.getElementById('etTableArea').addEventListener('focusout', function (e) {
    if (!e.target.classList.contains('et-qty-input')) return;
    var val = parseFloat(e.target.value);
    if (!isNaN(val) && val < ET_MIN_QTY) {
        e.target.value = ET_MIN_QTY;
        e.target.classList.remove('invalid');
    }
    hideMaxTooltip(e.target);
});


// ==========================================
// UPDATE BASKET BUTTON
// ==========================================

const updateBasketBtn = document.getElementById("updateBasketBtn");
const summarySection = document.getElementById("summarySection");

// Feeds basket.html's PDF summary.
function collectCuttingListItems() {

    var items = [];

    table.querySelectorAll("tr:not(.header-row):not(.section-row)").forEach(function (row) {

        var decorInput = row.querySelector(".decor input");
        if (!decorInput) return;

        var decor = decorInput.value.trim();
        var cells = row.querySelectorAll("td.small input");
        var length = cells[0] ? cells[0].value.trim() : "";
        var width = cells[1] ? cells[1].value.trim() : "";
        var qty = row.querySelector(".qty input") ? row.querySelector(".qty input").value.trim() : "";
        var desc = row.querySelector(".desc input") ? row.querySelector(".desc input").value.trim() : "";
        var thickSelect = row.querySelector(".thick select");
        var thick = thickSelect ? thickSelect.value : "";

        if (!decor && !length && !width && !qty) return;

        var edges = {};
        row.querySelectorAll(".edging-input").forEach(function (cell) {
            var input = cell.querySelector("input");
            edges[cell.dataset.edge] = input ? input.value.trim() : "";
        });

        items.push({
            id: CutlistBasket.makeId("ces"),
            decor: decor,
            thick: thick,
            length: length,
            width: width,
            qty: parseInt(qty, 10) || 1,
            description: desc,
            edgeL1: edges.L1 || "",
            edgeL2: edges.L2 || "",
            edgeW1: edges.W1 || "",
            edgeW2: edges.W2 || "",
            unitPrice: 0
        });

    });

    return items;

}

function collectFullSheetItems() {

    var items = [];

    document.querySelectorAll("#fsTable tr.fs-row").forEach(function (row) {

        var decor = row.querySelector(".decor input").value.trim();
        var lengthInput = row.querySelector(".fs-length input");
        var widthInput = row.querySelector(".fs-width input");
        var qty = parseInt(row.querySelector(".qty input").value, 10) || 0;

        if (!decor || !qty) return;

        items.push({
            decor: decor,
            thick: row.querySelector(".thick select").value,
            length: lengthInput ? lengthInput.value.trim() : "",
            width: widthInput ? widthInput.value.trim() : "",
            brand: row.querySelector(".fs-brand").textContent.trim(),
            qty: qty,
            unitPrice: parseFloat(row.dataset.priceSheet) || 0
        });

    });

    return items;

}

function collectEdgingTapeItems() {

    var items = [];

    document.querySelectorAll("#etTbody tr").forEach(function (row) {

        var sel = row.querySelector(".Select2");
        if (!sel || sel.classList.contains("isEmpty")) return;

        var codeEl = sel.querySelector(".Select2__input .code");
        var nameEl = sel.querySelector(".Select2__input .name");
        var sizeEl = sel.querySelector(".Select2__input .size");
        var qty = parseFloat(row.querySelector(".et-qty-input").value) || ET_MIN_QTY;
        if (qty < ET_MIN_QTY) qty = ET_MIN_QTY;
        var price = parseFloat(sel.dataset.price) || 0;

        items.push({
            code: codeEl ? codeEl.textContent.trim() : "",
            name: nameEl ? nameEl.textContent.trim() : "",
            size: sizeEl ? sizeEl.textContent.trim() : "",
            qty: qty,
            unitPrice: price
        });

    });

    return items;

}

// Demo prices for the prototype summary
var SUMMARY_SHEET_PRICE = 106.10;   // per 2800 x 2070 sheet
var SUMMARY_TAPE_PRICE = 5.57;      // per metre of edging tape

var SHEET_LENGTH = 2800;
var SHEET_WIDTH = 2070;
var SUMMARY_KERF = 25; // saw kerf / trim allowed between the panel and its offcuts, in mm

function summaryMoney(n) {
    return "£" + n.toLocaleString("en-GB", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Packs as many copies of one panel size as fit into a grid on a single
// sheet (to scale); whatever doesn't fit is drawn as labelled offcut.
function cutPlanBoxHTML(panelLength, panelWidth, qty, sheetLength, sheetWidth) {

    sheetLength = sheetLength || SHEET_LENGTH;
    sheetWidth = sheetWidth || SHEET_WIDTH;
    qty = Math.max(1, qty || 1);

    // Clamp the panel to the sheet so a bad/oversized entry can't break the layout
    var panelL = Math.min(Math.max(panelLength, 0), sheetLength);
    var panelW = Math.min(Math.max(panelWidth, 0), sheetWidth);

    var colPitch = panelL + SUMMARY_KERF;
    var rowPitch = panelW + SUMMARY_KERF;
    var perRow = Math.max(1, Math.floor((sheetLength + SUMMARY_KERF) / colPitch));
    var perCol = Math.max(1, Math.floor((sheetWidth + SUMMARY_KERF) / rowPitch));
    var maxPerSheet = perRow * perCol;
    var count = Math.min(qty, maxPerSheet);
    var rows = Math.ceil(count / perRow);
    // Row-major fill means every row except a possible last one uses all perRow columns;
    // the grid's actual width is only as wide as the fullest row needs, not the sheet's capacity.
    var gridWidthCols = rows > 1 ? perRow : count;

    var gridWidth = gridWidthCols * panelL + (gridWidthCols - 1) * SUMMARY_KERF;
    var gridHeight = rows * panelW + (rows - 1) * SUMMARY_KERF;

    function pct(mm, of) { return (mm / of) * 100; }
    function rect(xMm, yMm, wMm, hMm) {
        return "left:" + pct(xMm, sheetLength) + "%;top:" + pct(yMm, sheetWidth) + "%;" +
            "width:" + pct(wMm, sheetLength) + "%;height:" + pct(hMm, sheetWidth) + "%";
    }

    var html = "<div class=\"box\" style=\"aspect-ratio:" + sheetLength + "/" + sheetWidth + "\">";

    for (var i = 0; i < count; i++) {
        var col = i % perRow;
        var row = Math.floor(i / perRow);
        html += "<div class=\"panel\" style=\"" + rect(col * colPitch, row * rowPitch, panelL, panelW) + "\">" +
            (i === 0 ? "<span class=\"panel-dim-chip\">" + panelL + " x " + panelW + "mm</span>" : "") +
            "</div>";
    }

    // If there's more than one row, a partial last row leaves empty cells inside
    // the grid's own width (as opposed to the sheet's spare capacity) - still offcut
    var lastRowCount = count - (rows - 1) * perRow;
    if (rows > 1 && lastRowCount < perRow) {
        var emptyW = (perRow - lastRowCount) * panelL + (perRow - lastRowCount - 1) * SUMMARY_KERF;
        html += "<div class=\"offcut\" style=\"" + rect(lastRowCount * colPitch, (rows - 1) * rowPitch, emptyW, panelW) + "\">" +
            "Offcut<br>" + Math.round(emptyW) + " x " + panelW +
            "</div>";
    }

    // Right offcut: whatever's left of the sheet width, running its full height
    var rightW = sheetLength - gridWidth - SUMMARY_KERF;
    if (rightW > 0) {
        html += "<div class=\"offcut\" style=\"" + rect(gridWidth + SUMMARY_KERF, 0, rightW, sheetWidth) + "\">" +
            "Offcut<br>" + rightW + " x " + sheetWidth +
            "</div>";
    }

    // Bottom offcut: below the grid, only as wide as the grid itself
    var bottomH = sheetWidth - gridHeight - SUMMARY_KERF;
    if (bottomH > 0) {
        html += "<div class=\"offcut\" style=\"" + rect(0, gridHeight + SUMMARY_KERF, gridWidth, bottomH) + "\">" +
            "Offcut<br>" + gridWidth + " x " + bottomH +
            "</div>";
    }

    html += "</div>";

    return html;

}

function summaryCardHTML(title, price, headerExtra, bodyHTML, open) {
    return "" +
        "<div class=\"summary-card" + (open ? " open" : "") + "\">" +
        "<div class=\"summary-header\">" +
        "<div class=\"summary-header-left\"><span>" + title + "</span><strong>" + price + "</strong></div>" +
        "<div class=\"summary-header-right\">" +
        (headerExtra ? "<span>" + headerExtra + "</span>" : "") +
        "<button class=\"summary-toggle\" type=\"button\">Details <span class=\"summary-arrow\">&#9660;</span></button>" +
        "</div></div>" +
        "<div class=\"summary-body" + (open ? " is-open" : "") + "\">" + bodyHTML + "</div>" +
        "</div>";
}

function buildSummaryHTML(cutItems, fsItems, etItems) {

    var html = "<h2 class=\"summary-title\">Cut, edge &amp; spray summary</h2>";
    var grandTotal = 0;
    var EMPTY_BODY = "<p class=\"summary-note\">No items added yet.</p>";

    // ---- Sheets to be cut: one cutting plan per decor + thickness ----
    var sheetsTotal = 0;
    var sheetsBody = EMPTY_BODY;
    var planNo = 0;

    if (cutItems.length) {

        var groups = {};
        cutItems.forEach(function (item) {
            var key = item.decor + "|" + item.thick;
            if (!groups[key]) groups[key] = { decor: item.decor, thick: item.thick, area: 0, bestItem: null, bestArea: 0 };
            var l = parseFloat(item.length) || 0;
            var w = parseFloat(item.width) || 0;
            groups[key].area += l * w * item.qty;
            // Track the single largest panel in the group to draw the cutting plan to scale
            if (l * w > groups[key].bestArea) {
                groups[key].bestArea = l * w;
                groups[key].bestItem = { length: l, width: w, qty: item.qty };
            }
        });

        var totalSheets = 0;
        var plansHTML = Object.keys(groups).map(function (key) {
            var g = groups[key];
            planNo++;
            // Rough demo estimate: panel area + 25% waste, at least one sheet
            var sheets = Math.max(1, Math.ceil((g.area * 1.25) / (SHEET_LENGTH * SHEET_WIDTH)));
            totalSheets += sheets;
            var boxHTML = g.bestItem
                ? cutPlanBoxHTML(g.bestItem.length, g.bestItem.width, g.bestItem.qty, SHEET_LENGTH, SHEET_WIDTH)
                : "<div class=\"box\" style=\"aspect-ratio:" + SHEET_LENGTH + "/" + SHEET_WIDTH + "\"></div>";
            return "" +
                "<div class=\"plan\">" +
                "<div>Plan " + planNo + "</div>" +
                boxHTML +
                "<p>" + g.decor + (g.thick ? "<br>" + SHEET_LENGTH + " &times; " + SHEET_WIDTH + " &times; " + g.thick + "mm" : "") + "</p>" +
                "<div class=\"sheet\"><strong>x" + sheets + "</strong><span>sheets</span></div>" +
                "</div>";
        }).join("");

        var sheetPrice = g.unitPrice || SUMMARY_SHEET_PRICE;
        sheetsTotal += sheets * sheetPrice;
        grandTotal += sheetsTotal;

        sheetsBody =
            "<h3>Select offcuts</h3>" +
            "<p class=\"summary-note\">If there are any offcuts, you can mark them here to be included with your order free of charge.</p>" +
            "<div class=\"plans\">" + plansHTML + "</div>";

    }

    if (cutItems.length) {
        html += summaryCardHTML(
            "Sheets to be cut",
            summaryMoney(sheetsTotal),
            planNo ? "Cutting plans x" + planNo : "",
            sheetsBody,
            true
        );
    }

    // ---- Full sheets ----
    var fsTotal = 0;
    var fsBody = EMPTY_BODY;

    if (fsItems.length) {

        var fsRows = fsItems.map(function (item) {
            var price = item.unitPrice || SUMMARY_SHEET_PRICE;
            var line = item.qty * price;
            fsTotal += line;
            return "<tr><td>" + item.qty + "</td><td>" + item.decor + "</td>" +
                "<td>" + (item.length && item.width ? item.length + " &times; " + item.width + (item.thick ? " &times; " + item.thick : "") + "mm" : "-") + "</td>" +
                "<td>" + (item.brand || "-") + "</td>" +
                "<td>" + summaryMoney(price) + "</td><td>" + summaryMoney(line) + "</td></tr>";
        }).join("");

        grandTotal += fsTotal;

        fsBody =
            "<table><tr><th>Qty</th><th>Decor</th><th>Size</th><th>Brand</th><th>Unit price</th><th>Line total</th></tr>" + fsRows + "</table>" +
            "<div class=\"total\"><strong>This section: " + summaryMoney(fsTotal) + "</strong><div>With VAT: " + summaryMoney(fsTotal * 1.2) + "</div></div>";

    }

    if (fsItems.length) {
        html += summaryCardHTML("Full sheets", summaryMoney(fsTotal), "", fsBody, false);
    }

    // ---- Edging tape ----
    var etTotal = 0;
    var etBody = EMPTY_BODY;

    if (etItems.length) {

        var etRows = etItems.map(function (item) {
            var price = item.unitPrice || SUMMARY_TAPE_PRICE;
            var line = item.qty * price;
            etTotal += line;
            return "<tr><td>" + item.qty + "</td><td>" + item.code + "</td><td>" + item.name + "</td>" +
                "<td>" + item.size + "</td><td>" + summaryMoney(price) + "</td><td>" + summaryMoney(line) + "</td></tr>";
        }).join("");

        grandTotal += etTotal;

        etBody =
            "<table><tr><th>Qty [m]</th><th>Product code</th><th>Product name</th><th>Tape size</th><th>Unit price</th><th>Line total</th></tr>" + etRows + "</table>" +
            "<div class=\"total\"><strong>This section: " + summaryMoney(etTotal) + "</strong><div>With VAT: " + summaryMoney(etTotal * 1.2) + "</div></div>";

    }

    if (etItems.length) {
        html += summaryCardHTML("Edging tape", summaryMoney(etTotal), "", etBody, false);
    }

    if (!cutItems.length && !fsItems.length && !etItems.length) {
        html += "<p class=\"summary-note\">Nothing to summarise yet — add panels, full sheets or edging tape above.</p>";
    } else {
        html += "<div class=\"grand\">" +
            "<div class=\"price\">Total: " + summaryMoney(grandTotal) + "</div>" +
            "<div class=\"vat\">With VAT: " + summaryMoney(grandTotal * 1.2) + "</div>" +
            "</div>";
    }

    return html;

}

// The summary is rebuilt from scratch on every basket update, so its
// Details toggles are wired via delegation instead of per-button listeners.
summarySection.addEventListener("click", function (e) {

    var btn = e.target.closest(".summary-toggle");
    if (!btn) return;

    var card = btn.closest(".summary-card");
    var body = card.querySelector(".summary-body");

    card.classList.toggle("open");
    body.classList.toggle("is-open");

});

updateBasketBtn.addEventListener("click", function () {

    var cutItems = collectCuttingListItems();

    if (window.CutlistBasket) {
        CutlistBasket.setCategory("cut-edge-spray", cutItems);
        var bar = document.getElementById("cbTopbar");
        if (bar) bar.style.display = "";
    }

    summarySection.innerHTML = buildSummaryHTML(cutItems, collectFullSheetItems(), collectEdgingTapeItems());
    summarySection.style.display = "block";
    summarySection.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });

    updateBasketBtn.disabled = true;

});

function markDirty() {
    updateBasketBtn.disabled = false;
}
table.addEventListener('input', markDirty);

document.getElementById('decorPopup').addEventListener('click', function (e) {
    if (e.target.closest('.product-row')) markDirty();
});

document.getElementById('fsTable').addEventListener('input', markDirty);

document.getElementById('etTableArea').addEventListener('click', function (e) {
    if (e.target.closest('.Select2__option')) markDirty();
});
document.getElementById('etTableArea').addEventListener('input', markDirty);

document.getElementById('addRowBtn').addEventListener('click', markDirty, true);
document.getElementById('addFsRowBtn').addEventListener('click', markDirty, true);
document.getElementById('addEtRowBtn').addEventListener('click', markDirty, true);


// SECTION COLLAPSE / EXPAND  (full-width — entire section-title is the click target)

document.querySelectorAll(".section-title").forEach(function (sectionTitle) {

    sectionTitle.addEventListener("click", function (e) {

        if (e.target.closest("button, a, input, select")) return;

        let toggle = sectionTitle.querySelector(".toggle");
        if (!toggle) return;

        let arrow = toggle.querySelector(".arrow");
        let label = toggle.querySelector(".toggle-label");

        let collapsing = !arrow.classList.contains("down");

        arrow.classList.toggle("down", collapsing);
        label.textContent = collapsing ? "Expand" : "Collapse";

        let content = sectionTitle.nextElementSibling;

        if (content && !content.classList.contains("section-title")) {

            content.style.display = collapsing ? "none" : "";

        }

    });

});

var hdrAddRowBtn = document.getElementById("hdrAddRowBtn");
if (hdrAddRowBtn) hdrAddRowBtn.addEventListener("click", function () {
    document.getElementById("addRowBtn").click();
});





// TABLE CLICK DELEGATION (open popup / delete row)

table.addEventListener("click", function (e) {

    let edgeInput = e.target.closest(".edging-input input");

    if (edgeInput && !edgeInput.disabled) {

        let td = edgeInput.closest(".edging-input");
        let row = td.closest("tr");

        openEdgePopup(row, td.dataset.edge, edgeInput);

        return;

    }


    let decorInput = e.target.closest(".decor input");

    if (decorInput) {

        activeDecorInput = decorInput;

        let position = decorInput.getBoundingClientRect();

        popup.style.left = position.left + "px";
        popup.style.top = (position.bottom + window.scrollY) + "px";

        popup.style.display = "block";

        return;

    }


    let deleteBtn = e.target.closest(".delete");

    if (deleteBtn) {

        deleteBtn.closest("tr").remove();

        renumberRows();
        etRefreshDropdowns();

        return;

    }


    let panelSummaryBtn = e.target.closest(".actions .icon.view");

    if (panelSummaryBtn) {

        openPanelSummaryModal(panelSummaryBtn.closest("tr"));

        return;

    }


    let machiningBtn = e.target.closest(".machining .add-btn");

    if (machiningBtn) {

        openMachiningOverlay(machiningBtn.closest("tr"));

        return;

    }


    let sprayAddBtn = e.target.closest(".spray .add-btn");

    if (sprayAddBtn) {

        openSprayOverlay(sprayAddBtn.closest("tr"));

        return;

    }

});




// TABLE INPUT DELEGATION (edging + required-field validation)

table.addEventListener("input", function (e) {

    let row = e.target.closest("tr");

    if (!row || row.classList.contains("header-row")) return;


    if (e.target.matches("td.small:not(.edging-input) input")) {

        toggleInvalid(e.target);
        updateEdging(row);
        checkMaxDimension(e.target, row);

    }


    if (e.target.matches(".qty input")) {

        toggleInvalid(e.target);
        updateMachiningBtn(row);

    }


    if (e.target.matches(".decor input")) {

        etRefreshDropdowns();

    }

});




// SUPPLIER SWITCH

document.querySelectorAll(".supplier-tab")
    .forEach(tab => {


        tab.addEventListener("click", function () {


            document.querySelectorAll(".supplier-tab")
                .forEach(t => t.classList.remove("active"));


            this.classList.add("active");


            document.querySelectorAll(".product-list")
                .forEach(p => p.classList.remove("active"));


            document
                .getElementById(this.dataset.tab)
                .classList.add("active");


        });


    });




// SELECT PRODUCT

document.querySelectorAll(".product-row")
    .forEach(row => {


        row.addEventListener("click", function (e) {

            // "More info" is handled separately — don't select product
            if (e.target.closest('.more')) return;

            let code = this.children[0].innerText;

            let name = this.children[1].innerText;


            activeDecorInput.value =
                code + " - " + name;



            let currentRow =
                activeDecorInput.closest("tr");

            // Save selected decor image for Panel Summary
            var img = this.querySelector("img");

            if (img) {
                currentRow.dataset.image = img.src;
            } else {
                currentRow.dataset.image = "";
            }

            console.log("Selected image:", img ? img.src : "No Image");
            currentRow.classList.add("unlocked");

            // Thick options come from the real board's thickness(es)
            // (wp-admin Board Details), not a fixed static list.
            var selectedBoard = window.cutlistPmProducts && window.cutlistPmProducts[code];
            var thickSelectEl = currentRow.querySelector(".thick select");
            if (thickSelectEl && selectedBoard && selectedBoard.thicknesses && selectedBoard.thicknesses.length) {
                thickSelectEl.innerHTML = selectedBoard.thicknesses.map(function (t) {
                    return '<option value="' + t + '">' + t + '</option>';
                }).join('');
                thickSelectEl.value = selectedBoard.thicknesses[0];
            }

            // Largest part cuttable from this sheet (length/width minus
            // 40mm), stored so the input handler can validate as-you-type.
            if (selectedBoard && selectedBoard.length && selectedBoard.width) {
                currentRow.dataset.maxLength = selectedBoard.length - 40;
                currentRow.dataset.maxWidth = selectedBoard.width - 40;
            } else {
                delete currentRow.dataset.maxLength;
                delete currentRow.dataset.maxWidth;
            }



            // Every field unlocks except edging — that stays locked until
            // an edge finish is actually picked via the Edge popup.
            currentRow.querySelectorAll("input,select")
                .forEach(field => {

                    if (
                        field.closest(".edging-input")
                    ) {

                        field.disabled = true;

                    }
                    else {

                        field.disabled = false;

                    }


                });


            if (currentRow.classList.contains("fs-row")) {

                const brand = this.dataset.brand || "";
                const size = this.dataset.size || "";   // e.g. "2800x2050"

                const brandCell = currentRow.querySelector(".fs-brand");
                if (brandCell) brandCell.textContent = brand || "–";

                if (size) {
                    const parts = size.split("x");
                    const lenIn = currentRow.querySelector(".fs-length input");
                    const widIn = currentRow.querySelector(".fs-width input");
                    if (lenIn) lenIn.value = parts[0] || "";
                    if (widIn) widIn.value = parts[1] || "";
                }

                if (selectedBoard && selectedBoard.price_sheet) {
                    var priceSheet = parseFloat((selectedBoard.price_sheet || '').replace(/[^0-9.]/g, ''));
                    if (!isNaN(priceSheet)) {
                        currentRow.dataset.priceSheet = priceSheet;
                    } else {
                        delete currentRow.dataset.priceSheet;
                    }
                } else {
                    delete currentRow.dataset.priceSheet;
                }

            }


            // Which boards offer grain matching / spray finishing is set per
            // board in wp-admin (Board Details → Finishing), not hardcoded
            // to specific decor codes.
            var grainCheckbox = currentRow.querySelector(".grain input");
            if (grainCheckbox) {
                var isGrainDecor = !!(selectedBoard && selectedBoard.grainMatch);
                currentRow.classList.toggle("grain-allowed", isGrainDecor);
                grainCheckbox.disabled = !isGrainDecor;
                if (!isGrainDecor && grainCheckbox.checked) {
                    grainCheckbox.checked = false;
                }
                updateGrainSection();
            }

            var sprayBtn = currentRow.querySelector(".spray .add-btn");
            if (sprayBtn) {
                sprayBtn.classList.toggle("visible", !!(selectedBoard && selectedBoard.sprayFinishing));
            }

            let { lengthInput, widthInput } = getDimInputs(currentRow);
            let qtyInput = currentRow.querySelector(".qty input");

            [lengthInput, widthInput, qtyInput].forEach(toggleInvalid);

            // Cutting list row got a board — edging tape options may change.
            if (!currentRow.classList.contains("fs-row")) etRefreshDropdowns();

            popup.style.display = "none";


        });


    });




// GRAIN MATCHING DETAILS SECTION
// Shown while at least one cutting-list row has Grain match ticked.

var grainSection = document.getElementById("grainMatchSection");

function updateGrainSection() {
    var anyChecked = table.querySelector(".grain input:checked");
    grainSection.style.display = anyChecked ? "" : "none";
}

table.addEventListener("change", function (e) {
    if (e.target.closest(".grain")) updateGrainSection();
});

// Deleting a row can remove the last ticked checkbox
table.addEventListener("click", function (e) {
    if (e.target.closest(".delete")) setTimeout(updateGrainSection);
});

document.getElementById("grainAddFiles").addEventListener("click", function () {
    document.getElementById("grainFileInput").click();
});

function listGrainFiles(files) {
    document.getElementById("grainFileList").innerHTML =
        Array.prototype.map.call(files, function (f) {
            return "<div>&#10003; " + f.name + "</div>";
        }).join("");
}

document.getElementById("grainFileInput").addEventListener("change", function () {
    listGrainFiles(this.files);
});

var grainDropzone = document.getElementById("grainDropzone");

grainDropzone.addEventListener("dragover", function (e) {
    e.preventDefault();
    grainDropzone.classList.add("dragover");
});

grainDropzone.addEventListener("dragleave", function () {
    grainDropzone.classList.remove("dragover");
});

grainDropzone.addEventListener("drop", function (e) {
    e.preventDefault();
    grainDropzone.classList.remove("dragover");
    if (e.dataTransfer.files.length) listGrainFiles(e.dataTransfer.files);
});


// PANEL MODAL (More info)

// Rendered server-side from the real Board catalogue, not a mock object.
var pmProducts = window.cutlistPmProducts || {};

var pmSlideIdx = 0;
var pmSlideCount = 0;

function pmShowSlide(idx) {
    var slides = document.querySelectorAll('#pmSlides .pm-gallery-slide');
    var thumbs = document.querySelectorAll('#pmThumbs .pm-gallery-thumb');
    if (!slides.length) return;
    pmSlideIdx = (idx + slides.length) % slides.length;
    slides.forEach(function (s, i) { s.classList.toggle('active', i === pmSlideIdx); });
    thumbs.forEach(function (t, i) { t.classList.toggle('active', i === pmSlideIdx); });
}

var SVG_PDF = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="9" y2="17"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="15" y1="15" x2="15" y2="17"/></svg>';
var SVG_EXTERNAL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';

function openPanelModal(code) {
    var p = pmProducts[code];
    if (!p) {
        p = {
            fullCode: code, title: code, name: '',
            length: '–', width: '–', material: '–',
            desc: 'No description available.', bside: '', chars: '',
            thicknesses: [18], slides: [{ label: 'Decor', bg: '#ccc' }],
            price_sheet: '–', price_cut: '–',
            machining: { cut_to_size: false, edgebanding: false, cnc: false },
            downloads: [], manufacturer_url: ''
        };
    }

    document.getElementById('pmProductCode').textContent = 'Product code: ' + p.fullCode;

    var slidesEl = document.getElementById('pmSlides');
    slidesEl.innerHTML = p.slides.map(function (s, i) {
        return '<div class="pm-gallery-slide' + (i === 0 ? ' active' : '') + '">' +
            '<div class="pm-gallery-img" style="background:' + s.bg + '">' +
            '<div class="pm-gallery-label">' + s.label + '</div>' +
            '</div>' +
            '</div>';
    }).join('');

    var thumbsEl = document.getElementById('pmThumbs');
    thumbsEl.innerHTML = p.slides.map(function (s, i) {
        return '<div class="pm-gallery-thumb' + (i === 0 ? ' active' : '') + '" data-idx="' + i + '">' +
            '<div class="pm-gallery-thumb-img" style="background:' + s.bg + '"></div>' +
            '</div>';
    }).join('');
    pmSlideIdx = 0;
    pmSlideCount = p.slides.length;
    var showNav = pmSlideCount > 1;
    document.getElementById('pmPrev').style.display = showNav ? '' : 'none';
    document.getElementById('pmNext').style.display = showNav ? '' : 'none';

    document.getElementById('pmTitle').textContent = p.title;
    document.getElementById('pmName').textContent = p.name;

    // Thickness shown here is always the first of the board's real options.
    var firstThick = p.thicknesses ? p.thicknesses[0] : '–';
    var sizeBody = document.querySelector('#pmSizeTable tbody');
    sizeBody.innerHTML =
        '<tr><td class="left">Length:</td><td class="right">' + p.length + ' mm</td></tr>' +
        '<tr><td class="left">Width:</td><td class="right">' + p.width + ' mm</td></tr>' +
        '<tr><td class="left">Thickness:</td><td class="right">' + firstThick + ' mm</td></tr>';

    document.getElementById('pmDesc').innerHTML = p.desc.replace(/\n/g, '<br>');

    var bsideSection = document.getElementById('pmBsideSection');
    if (p.bside) {
        bsideSection.style.display = '';
        document.getElementById('pmBside').innerHTML = p.bside.replace(/\n/g, '<br>');
    } else {
        bsideSection.style.display = 'none';
    }

    document.getElementById('pmChars').innerHTML = p.chars.replace(/\n/g, '<br>');

    // Real per-board flags (wp-admin Board Details), not a mock object.
    var machining = p.machining || {};
    document.getElementById('pmMachCut').textContent = machining.cut_to_size ? 'Yes' : 'No';
    document.getElementById('pmMachEdge').textContent = machining.edgebanding ? 'Yes' : 'No';
    document.getElementById('pmMachCnc').textContent = machining.cnc ? 'Yes' : 'No';

    // One link per real uploaded file, plus the manufacturer link only if
    // a URL was actually set.
    var dlEl = document.getElementById('pmDownloads');
    var dlHtml = (p.downloads || []).map(function (d) {
        return '<a class="pm-download-link" href="' + d.url + '" target="_blank" rel="noopener">' + SVG_PDF + (d.label || 'Download') + '</a>';
    }).join('');
    if (p.manufacturer_url) {
        dlHtml += '<a class="pm-download-link" href="' + p.manufacturer_url + '" target="_blank" rel="noopener">' + SVG_EXTERNAL + "Manufacturer's product page</a>";
    }
    dlEl.innerHTML = dlHtml;

    var thickEl = document.getElementById('pmThicknesses');
    thickEl.innerHTML = (p.thicknesses || [18]).map(function (t, i) {
        var id = 'pmThick_' + code + '_' + t;
        return '<div class="pm-thickness-option">' +
            '<input type="radio" name="pmThickness_' + code + '" value="' + t + '"' + (i === 0 ? ' checked' : '') + ' id="' + id + '">' +
            '<label for="' + id + '">' + t + '</label>' +
            '</div>';
    }).join('');

    var pricingBody = document.querySelector('.pricing-levels__table tbody');
    pricingBody.innerHTML =
        '<tr><td class="pricing-levels__label">Full sheet price</td><td class="price-val">' + (p.price_sheet || '–') + '</td></tr>' +
        '<tr><td class="pricing-levels__label">Sheet price with cutting (up to 20 pieces per sheet)</td><td class="price-val">' + (p.price_cut || '–') + '</td></tr>';

    // reset tab to Description
    document.querySelectorAll('.SimpleTabs__tab-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.tab === 'desc');
    });
    document.getElementById('pmTabDesc').classList.add('active');
    document.getElementById('pmTabFaq').classList.remove('active');

    // open overlay
    document.getElementById('panelModalOverlay').classList.add('is-open');
}

// gallery prev/next
document.getElementById('pmPrev').addEventListener('click', function () { pmShowSlide(pmSlideIdx - 1); });
document.getElementById('pmNext').addEventListener('click', function () { pmShowSlide(pmSlideIdx + 1); });

// thumbnail clicks (delegated)
document.getElementById('pmThumbs').addEventListener('click', function (e) {
    var thumb = e.target.closest('.pm-gallery-thumb');
    if (!thumb) return;
    pmShowSlide(parseInt(thumb.dataset.idx, 10));
});

// tab switching
document.querySelector('.panel-reveal').addEventListener('click', function (e) {
    var btn = e.target.closest('.SimpleTabs__tab-btn');
    if (!btn) return;
    var tab = btn.dataset.tab;
    document.querySelectorAll('.SimpleTabs__tab-btn').forEach(function (b) { b.classList.toggle('active', b === btn); });
    document.getElementById('pmTabDesc').classList.toggle('active', tab === 'desc');
    document.getElementById('pmTabFaq').classList.toggle('active', tab === 'faq');
});

// close panel modal
document.getElementById('panelModalClose').addEventListener('click', function () {
    document.getElementById('panelModalOverlay').classList.remove('is-open');
});
document.getElementById('panelModalOverlay').addEventListener('click', function (e) {
    if (!e.target.closest('.panel-reveal')) {
        document.getElementById('panelModalOverlay').classList.remove('is-open');
    }
});

// "More info" click inside decor popup — use delegation on popup container
document.getElementById('decorPopup').addEventListener('click', function (e) {
    var moreSpan = e.target.closest('.more');
    if (!moreSpan) return;
    e.stopPropagation();
    var row = moreSpan.closest('.product-row');
    if (!row) return;
    var code = row.children[0].innerText.trim();
    openPanelModal(code);
});


let panelSummaryCurrentData = null;

function panelSummaryContextValue(field) {
    if (!field) return '-';
    var value = field.value;
    return value && value.trim() ? value.trim() : '-';
}

function populatePanelSummaryContextBar(row) {
    document.getElementById('psRownum').textContent =
        row.querySelector('.rownum') ? row.querySelector('.rownum').textContent : '-';
    document.getElementById('psDecor').textContent =
        panelSummaryContextValue(row.querySelector('.decor input'));
    document.getElementById('psThick').textContent =
        panelSummaryContextValue(row.querySelector('.thick select'));
    var dims = getDimInputs(row);
    document.getElementById('psLength').textContent = panelSummaryContextValue(dims.lengthInput);
    document.getElementById('psWidth').textContent = panelSummaryContextValue(dims.widthInput);
    document.getElementById('psQty').textContent =
        panelSummaryContextValue(row.querySelector('.qty input'));
    document.getElementById('psDesc').textContent =
        panelSummaryContextValue(row.querySelector('.desc input'));
    row.querySelectorAll('.edging-input').forEach(function (td) {
        var target = document.getElementById('ps' + td.dataset.edge);
        if (target) target.textContent = panelSummaryContextValue(td.querySelector('input'));
    });
}

function updatePanelSummaryChrome(data) {
    var grainArrow = document.getElementById('panelSummaryGrainArrow');
    if (grainArrow) {
        grainArrow.style.visibility = data.grain === 'Yes' ? 'visible' : 'hidden';
        grainArrow.innerHTML = String(data.grainDirection).toLowerCase() === 'vertical' ? '&#8597;' : '&#8596;';
    }
    var faceBox = document.getElementById('panelSummaryFaceBox');
    if (faceBox) faceBox.innerHTML = '<div>FRONT</div><div>FACE</div>';
}

function updatePanelSummaryNav() {
    var nav = document.getElementById('panelSummaryNav');
    var prevBtn = document.getElementById('panelSummaryPrev');
    var nextBtn = document.getElementById('panelSummaryNext');
    // Only worth showing Previous/Next once there's more than one
    // panel (row with a board actually picked) to move between.
    if (nav) nav.style.display = panelSummaryRows.length > 1 ? 'flex' : 'none';
    if (prevBtn) prevBtn.disabled = panelSummaryCurrentIndex <= 0;
    if (nextBtn) nextBtn.disabled = panelSummaryCurrentIndex >= panelSummaryRows.length - 1;
}

function renderPanelSummaryDrawing() {
    document.getElementById('panelSummaryDrawingContainer').innerHTML =
        buildPanelSummaryDrawing(panelSummaryCurrentData);
    panelSummaryInitZoom();
}

function openPanelSummaryModal(row) {

    // Only rows that actually have a board picked count as a
    // "panel" to navigate between — blank/unfilled cutting-list
    // rows don't get a Previous/Next stop.
    panelSummaryRows = Array.from(
        table.querySelectorAll("tr")
    ).filter(function (r) {

        if (r.classList.contains("header-row") || r.classList.contains("section-row")) return false;

        var decorInput = r.querySelector(".decor input");

        return !!(decorInput && decorInput.value.trim());

    });

    panelSummaryCurrentIndex =
        panelSummaryRows.indexOf(row);

    var overlay = document.getElementById('panelSummaryModalOverlay');

    overlay._row = row;
    populatePanelSummaryContextBar(row);

    panelSummaryCurrentData = getPanelSummaryData(row);
    renderPanelSummaryDrawing();
    updatePanelSummaryChrome(panelSummaryCurrentData);
    updatePanelSummaryNav();

    document.getElementById("panelSummarySidebar").innerHTML =
        buildPanelSummaryInfo(panelSummaryCurrentData);

    overlay.classList.add('is-open');
}


function panelSummaryValue(field) {
    if (!field) return '-';
    var value = field.value;
    return value && value.trim() ? value.trim() : '-';
}

function panelSummaryEdgeValue(row, edge) {
    var input = row ? row.querySelector('.edging-input[data-edge="' + edge + '"] input') : null;
    return panelSummaryValue(input);
}

var PANEL_SUMMARY_EDGE_COLOURS = {
    none: '#999',
    M1: '#3f8fd1',
    M2: '#48a868',
    ABS: '#f0a23a',
    PVC: '#8b64c7',
    Laser: '#d94a4a',
    unknown: '#2f7fb8'
};

function panelSummaryEscape(value) {
    return String(value == null ? '-' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getPanelSummaryData(row) {
    var dims = row ? getDimInputs(row) : {};
    var grainInput = row ? row.querySelector('.grain input') : null;
    return {
        decor: panelSummaryValue(row ? row.querySelector('.decor input') : null),
        thickness: panelSummaryValue(row ? row.querySelector('.thick select') : null),
        length: panelSummaryValue(dims.lengthInput),
        width: panelSummaryValue(dims.widthInput),
        qty: panelSummaryValue(row ? row.querySelector('.qty input') : null),
        desc: panelSummaryValue(row ? row.querySelector('.desc input') : null),

        // NEW
        image: row ? (row.dataset.image || "") : "",

        grain: grainInput && grainInput.checked ? 'Yes' : '-',
        grainDirection: row && row.dataset.grainDirection ? row.dataset.grainDirection : 'horizontal',

        L1: panelSummaryEdgeValue(row, 'L1'),
        L2: panelSummaryEdgeValue(row, 'L2'),
        W1: panelSummaryEdgeValue(row, 'W1'),
        W2: panelSummaryEdgeValue(row, 'W2'),

        machining: getPanelSummaryMachining(row)
    };
}

function panelSummaryEdgeBadge(key, value, cx, cy) {
    var type = panelSummaryEdgeType(value);
    var active = type !== 'none';
    var colour = active ? panelSummaryEdgeColour(type) : '#999';
    return '<circle class="panel-edge-badge' + (active ? ' active' : '') + '" cx="' + cx + '" cy="' + cy + '" r="12" style="stroke:' + colour + '"></circle>' +
        '<text class="panel-edge-badge-label" x="' + cx + '" y="' + (cy + 3) + '" text-anchor="middle">' + key + '</text>';
}

// Drawn to scale from the row's real length/width, not a fixed square
// like the Machining/Spray overlays use.
function buildPanelSummaryDrawing(data) {
    var length = parseFloat(data.length);
    var width = parseFloat(data.width);
    var maxW = 230;
    var maxH = 170;
    var rectW = 190;
    var rectH = 120;
    if (!isNaN(length) && !isNaN(width) && length > 0 && width > 0) {
        var scale = Math.min(maxW / length, maxH / width);
        rectW = Math.max(70, Math.round(length * scale));
        rectH = Math.max(45, Math.round(width * scale));
    }
    var x = 50;
    var y = 30;
    var right = x + rectW;
    var bottom = y + rectH;
    var midX = x + rectW / 2;
    var midY = y + rectH / 2;
    var viewW = right + 65;
    var viewH = bottom + 65;

    var badges =
        panelSummaryEdgeBadge('L1', data.L1, midX, y) +
        panelSummaryEdgeBadge('L2', data.L2, midX, bottom) +
        panelSummaryEdgeBadge('W1', data.W1, x, midY) +
        panelSummaryEdgeBadge('W2', data.W2, right, midY);

    var lengthDimY = bottom + 26;
    var widthDimX = right + 26;

    return `
<svg class="panel-summary-drawing"
     viewBox="0 0 ${viewW} ${viewH}"
     preserveAspectRatio="xMidYMid meet"
     width="100%" height="100%"
     aria-label="Panel drawing">

    <rect class="panel-board" x="${x}" y="${y}" width="${rectW}" height="${rectH}"></rect>

    ${badges}

    <!-- LENGTH DIMENSION (bottom) -->
    <line x1="${x}" y1="${lengthDimY}" x2="${right}" y2="${lengthDimY}" stroke="#777" stroke-width="1"/>
    <line x1="${x}" y1="${bottom}" x2="${x}" y2="${lengthDimY}" stroke="#777" stroke-width="1"/>
    <line x1="${right}" y1="${bottom}" x2="${right}" y2="${lengthDimY}" stroke="#777" stroke-width="1"/>
    <polygon points="${x},${lengthDimY} ${x + 6},${lengthDimY - 3} ${x + 6},${lengthDimY + 3}" fill="#777"/>
    <polygon points="${right},${lengthDimY} ${right - 6},${lengthDimY - 3} ${right - 6},${lengthDimY + 3}" fill="#777"/>
    <text x="${midX}" y="${lengthDimY + 15}" text-anchor="middle" font-size="10" fill="#444" font-weight="600">${panelSummaryEscape(data.length)} mm</text>

    <!-- WIDTH DIMENSION (right) -->
    <line x1="${widthDimX}" y1="${y}" x2="${widthDimX}" y2="${bottom}" stroke="#777" stroke-width="1"/>
    <line x1="${right}" y1="${y}" x2="${widthDimX}" y2="${y}" stroke="#777" stroke-width="1"/>
    <line x1="${right}" y1="${bottom}" x2="${widthDimX}" y2="${bottom}" stroke="#777" stroke-width="1"/>
    <polygon points="${widthDimX},${y} ${widthDimX - 3},${y + 6} ${widthDimX + 3},${y + 6}" fill="#777"/>
    <polygon points="${widthDimX},${bottom} ${widthDimX - 3},${bottom - 6} ${widthDimX + 3},${bottom - 6}" fill="#777"/>
    <text transform="rotate(-90 ${widthDimX + 15} ${midY})" x="${widthDimX + 15}" y="${midY}" text-anchor="middle" font-size="10" fill="#444" font-weight="600">${panelSummaryEscape(data.width)} mm</text>

</svg>`;
}

function panelSummaryEdgeType(value) {
    if (!value || value === '-') return 'none';
    var text = String(value).trim();
    if (!text) return 'none';
    if (/^M1\b|^M1-/i.test(text)) return 'M1';
    if (/^M2\b|^M2-/i.test(text)) return 'M2';
    if (/ABS/i.test(text)) return 'ABS';
    if (/PVC/i.test(text)) return 'PVC';
    if (/Laser/i.test(text)) return 'Laser';
    return text.split(/[\s/-]+/)[0] || 'unknown';
}

function panelSummaryEdgeColour(type) {
    return PANEL_SUMMARY_EDGE_COLOURS[type] || PANEL_SUMMARY_EDGE_COLOURS.unknown;
}

function getPanelSummaryMachining(row) {
    if (!row) return [];
    var raw = row.dataset.machiningData || row.dataset.machining || '';
    if (!raw) return [];
    try {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) return parsed;
        return parsed.items && Array.isArray(parsed.items) ? parsed.items : [];
    } catch (err) {
        return raw.split(',').map(function (item) {
            var parts = item.split(':');
            return {
                type: (parts[0] || '').trim(),
                x: parseFloat(parts[1]),
                y: parseFloat(parts[2]),
                w: parseFloat(parts[3]),
                h: parseFloat(parts[4])
            };
        }).filter(function (item) {
            return item.type;
        });
    }
}

// "No edging applied" when every edge is untaped, otherwise a short
// list of which edges have tape and what code they're matched to.
function buildPanelSummaryEdgingText(data) {
    var edges = [
        { key: 'L1', value: data.L1 },
        { key: 'L2', value: data.L2 },
        { key: 'W1', value: data.W1 },
        { key: 'W2', value: data.W2 }
    ].filter(function (edge) { return panelSummaryEdgeType(edge.value) !== 'none'; });

    if (!edges.length) return 'No edging applied';

    return edges.map(function (edge) {
        return edge.key + ': ' + panelSummaryEscape(edge.value);
    }).join(', ');
}

// Reads row.dataset.machiningData, written by saveMachiningAppliedItems().
function buildPanelSummaryMachiningText(data) {
    if (!data.machining || !data.machining.length) return 'No additional machining';

    return data.machining.map(function (item) {
        var type = panelSummaryEscape(item.type || item.kind || 'Machining');
        var side = item.side ? ' on ' + panelSummaryEscape(item.side) : '';

        // Angled cut (see saveMachiningAppliedItems()) sends a ready-made
        // description instead of the w/h/length triplet below.
        if (item.detail) {
            return '<div class="panel-summary-machining-item">' + type + side + '<br>' + panelSummaryEscape(item.detail) + '</div>';
        }

        var dims = [];
        if (item.w) dims.push(panelSummaryEscape(item.w) + 'mm wide');
        if (item.h) dims.push(panelSummaryEscape(item.h) + 'mm deep');
        if (item.length) dims.push(panelSummaryEscape(item.length) + 'mm long');
        var dimsText = dims.length ? '<br>' + dims.join(', ') : '';
        return '<div class="panel-summary-machining-item">' + type + side + dimsText + '</div>';
    }).join('');
}

function buildPanelSummaryInfo(data) {
    var code = String(data.decor || '').split(' - ')[0].trim();
    var product = (window.cutlistPmProducts && window.cutlistPmProducts[code]) || null;

    var brand = product && product.brand ? product.brand : '';
    var name = product && product.name ? product.name : (data.decor === '-' ? '' : data.decor);
    var swatchStyle = product && product.slides && product.slides[0] && product.slides[0].bg
        ? product.slides[0].bg
        : '#ccc';

    var size =
        panelSummaryEscape(data.length) +
        ' x ' +
        panelSummaryEscape(data.width) +
        ' x ' +
        panelSummaryEscape(data.thickness) +
        ' mm';

    var brandLine = (brand ? panelSummaryEscape(brand) + ' ' : '') + panelSummaryEscape(code || '-');

    return `
<div class="panel-summary-size">${size}</div>

<div class="panel-summary-swatch" style="background:${swatchStyle}"></div>

<div class="panel-summary-brand">${brandLine}</div>
<div class="panel-summary-decor-name">${panelSummaryEscape(name || '-')}</div>

<div class="panel-summary-qty">&times;${panelSummaryEscape(data.qty)}</div>

<div class="panel-summary-section">
    <h4>Edging details</h4>
    <p>${buildPanelSummaryEdgingText(data)}</p>
</div>

<div class="panel-summary-section">
    <h4>Surface shaping summary</h4>
    <p>${buildPanelSummaryMachiningText(data)}</p>
</div>
`;
}

let panelZoom = 1;

function panelSummaryInitZoom() {

    panelZoom = 1;

    const svg = document.querySelector(".panel-summary-drawing");

    if (!svg) return;

    svg.style.transformOrigin = "center center";
    svg.style.transform = "scale(1)";

    const zoomInBtn = document.getElementById("panelZoomIn");
    const zoomOutBtn = document.getElementById("panelZoomOut");

    if (!zoomInBtn || !zoomOutBtn) return;

    zoomInBtn.onclick = function () {

        panelZoom = Math.min(panelZoom + 0.1, 3);

        svg.style.transform = "scale(" + panelZoom + ")";
    };

    zoomOutBtn.onclick = function () {

        panelZoom = Math.max(panelZoom - 0.1, 0.5);

        svg.style.transform = "scale(" + panelZoom + ")";
    };

}
function panelSummaryPrevious() {

    if (panelSummaryCurrentIndex <= 0)
        return;

    panelSummaryCurrentIndex--;

    openPanelSummaryModal(
        panelSummaryRows[panelSummaryCurrentIndex]
    );

}

function panelSummaryNext() {

    if (panelSummaryCurrentIndex >= panelSummaryRows.length - 1)
        return;

    panelSummaryCurrentIndex++;

    openPanelSummaryModal(
        panelSummaryRows[panelSummaryCurrentIndex]
    );

}
function closePanelSummaryModal() {
    var overlay = document.getElementById('panelSummaryModalOverlay');
    overlay.classList.remove('is-open');
    overlay._row = null;
}
const panelPrevBtn =
    document.getElementById("panelSummaryPrev");

const panelNextBtn =
    document.getElementById("panelSummaryNext");

if (panelPrevBtn) {

    panelPrevBtn.onclick = panelSummaryPrevious;

}

if (panelNextBtn) {

    panelNextBtn.onclick = panelSummaryNext;

}
document.getElementById('panelSummaryModalClose').addEventListener('click', closePanelSummaryModal);
document.getElementById('panelSummaryModalOverlay').addEventListener('click', function (e) {
    if (!e.target.closest('.panel-summary-modal')) {
        closePanelSummaryModal();
    }
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closePanelSummaryModal();
    }
});

var panelSummaryFaceBox = document.getElementById('panelSummaryFaceBox');
if (panelSummaryFaceBox) {
    panelSummaryFaceBox.addEventListener('click', function () {
        var isFront = panelSummaryFaceBox.textContent.includes('FRONT');
        panelSummaryFaceBox.innerHTML = isFront
            ? '<div>BACK</div><div>FACE</div>'
            : '<div>FRONT</div><div>FACE</div>';
    });
}

var panelSummaryRotateBtn = document.getElementById('panelSummaryRotateBtn');
if (panelSummaryRotateBtn) {
    panelSummaryRotateBtn.addEventListener('click', function () {
        if (!panelSummaryCurrentData) return;
        var swap = panelSummaryCurrentData.length;
        panelSummaryCurrentData.length = panelSummaryCurrentData.width;
        panelSummaryCurrentData.width = swap;
        renderPanelSummaryDrawing();
    });
}


// EDGE POPUP INTERACTIONS

edgeTabs.addEventListener("click", function (e) {

    let tab = e.target.closest(".edge-tab");

    if (!tab || !activeEdgeRow) return;

    let edge = tab.dataset.edge;

    activeEdge = edge;

    if (edgeSummaryBtn.classList.contains("selected")) {

        let state = getEdgeState(activeEdgeRow);

        state[edge] = state[edge] ? null : "radius";

    }

    renderEdgePopup();

});


edgeSummaryBtn.addEventListener("click", function () {

    edgeSummaryBtn.classList.toggle("selected");

});


edgeFinishOptions.addEventListener("click", function (e) {

    let opt = e.target.closest(".edge-finish-option");

    if (!opt || !activeEdgeRow || !activeEdge) return;

    let state = getEdgeState(activeEdgeRow);

    state[activeEdge] = opt.dataset.finish;

    renderEdgePopup();

});


edgeModeToggle.addEventListener("click", function (e) {

    let btn = e.target.closest(".mode-btn");

    if (!btn) return;

    edgeModeToggle.querySelectorAll(".mode-btn")
        .forEach(b => b.classList.remove("active"));

    btn.classList.add("active");

});


document.getElementById("edgePopupClose")
    .addEventListener("click", closeEdgePopup);




// MACHINING OVERLAY INTERACTIONS

document.getElementById("machiningClose")
    .addEventListener("click", closeMachiningOverlay);


machiningOverlay.addEventListener("click", function (e) {

    // Must be a direct-target check (e.target === machiningOverlay), not
    // e.target.closest(".machining-modal"): several buttons in the
    // applied-items list re-render its innerHTML synchronously on click,
    // detaching the clicked element before this event finishes bubbling —
    // closest() on a detached node can't find .machining-modal and wrongly
    // closes the overlay. e.target itself stays a stable reference.
    if (e.target === machiningOverlay) {

        closeMachiningOverlay();

    }

});


// MACHINING OPTION DROPDOWN
// Closed by default showing a "Select machining option" trigger; opens on
// click, and picking an item swaps the trigger for a selected-value row
// with an Add button next to it.

var machiningSelectWrap = document.getElementById("machiningSelectWrap");
var machiningSelectTrigger = document.getElementById("machiningSelectTrigger");
var machiningSelectedRow = document.getElementById("machiningSelectedRow");
var machiningSelectedValue = document.getElementById("machiningSelectedValue");
var machiningOptionDropdown = document.getElementById("machiningOptionDropdown");
var machiningAddBtn = document.getElementById("machiningAddBtn");

function resetMachiningOptionSelect() {
    if (!machiningSelectWrap) return;
    machiningSelectWrap.classList.remove("open");
    machiningSelectTrigger.style.display = "flex";
    machiningSelectedRow.style.display = "none";
    machiningSelectedValue.textContent = "";
    document.querySelectorAll(".machining-option-item")
        .forEach(function (el) { el.classList.remove("selected"); });
}

function openMachiningOptionDropdown() {
    machiningSelectWrap.classList.add("open");
}

function closeMachiningOptionDropdown() {
    machiningSelectWrap.classList.remove("open");
}

if (machiningSelectWrap) {

    machiningSelectTrigger.addEventListener("click", function () {
        machiningSelectWrap.classList.toggle("open");
    });

    // Selected-value row stays clickable so a different option can
    // be picked without closing the whole Machining modal.
    machiningSelectedValue.addEventListener("click", openMachiningOptionDropdown);

    machiningOptionDropdown.addEventListener("click", function (e) {

        let item = e.target.closest(".machining-option-item");

        if (!item || item.classList.contains("disabled")) return;

        document.querySelectorAll(".machining-option-item")
            .forEach(function (el) { el.classList.remove("selected"); });
        item.classList.add("selected");

        machiningSelectedValue.textContent = item.textContent.trim();
        machiningSelectTrigger.style.display = "none";
        machiningSelectedRow.style.display = "flex";
        closeMachiningOptionDropdown();

    });

    document.addEventListener("click", function (e) {
        if (!machiningSelectWrap.contains(e.target)) {
            closeMachiningOptionDropdown();
        }
    });

}

// APPLIED MACHINING OPTIONS
// "Add" pushes the picked option onto a per-row list rendered as a chip.
// "Angled cut" gets a full detail panel (corner, offsets, edging, finish,
// A/B view); other options just get a plain removable chip. Saving an
// Angled cut writes it to row.dataset.machiningData, which also feeds
// Panel Summary's "Surface shaping summary".

var machiningCurrentRow = null;
var machiningAppliedItems = [];
var machiningAppliedList = document.getElementById("machiningAppliedList");

var MACHINING_CORNERS = ["L1-W1", "L1-W2", "L2-W1", "L2-W2"];

function machiningCornerLabels(corner) {
    var parts = String(corner || "L1-W1").split("-");
    var lEdge = parts[0];
    var wEdge = parts[1];
    return {
        h: "From " + (wEdge === "W1" ? "W2" : "W1"),
        v: "From " + (lEdge === "L1" ? "L2" : "L1")
    };
}

function loadMachiningAppliedItems(row) {
    var raw = row.dataset.machiningApplied || "";
    try {
        machiningAppliedItems = raw ? JSON.parse(raw) : [];
    } catch (err) {
        machiningAppliedItems = [];
    }
}

// Konva-based technical drawing for the Machining panel. Shapes are created
// once (initMachiningStage) and updated in place on every redraw, which is
// what makes the hLabel/vLabel position callouts smoothly draggable.
//
// maxW/maxH are equal so an equal length/width board renders as a true
// square. Stacking order outward from the board edge is: callout (badge
// -10px) -> badge (badgeOffset) -> ruler+text (rulerOffset). Each gap must
// clear the previous element's own footprint or they visibly collide.
var MACHINING_CANVAS_CFG = { x: 120, y: 120, maxW: 220, maxH: 220, badgeOffset: 62, rulerOffset: 88 };

// Fallback inset (px), used only when an angled-cut item has no offsetH/
// offsetV yet. The real 0..length-1/width-1 range is enforced in mm space
// elsewhere, not by clamping this pixel value — a fixed pixel epsilon
// distorts disproportionately on a large board with a small px/mm ratio.
var MACHINING_MIN_INSET = 2;

var machiningStage = null;
var machiningLayer = null;
var machiningShapes = null;
var machiningLastGeometry = null;

function buildMachiningBadge(label) {
    var group = new Konva.Group();
    group.add(new Konva.Circle({ radius: 16, fill: "#dceafd", stroke: "#999", strokeWidth: 1 }));
    group.add(new Konva.Text({
        text: label, fontSize: 12, fontFamily: "Arial, sans-serif", fill: "#666",
        width: 32, height: 32, offsetX: 16, offsetY: 16,
        align: "center", verticalAlign: "middle"
    }));
    return group;
}

// A dimension ruler: a single centered-label span normally, or split at the
// cut position into two independently labeled segments when the Angled cut
// lands on this edge (see splitAt in updateMachiningDimLine).
function buildMachiningDimLine() {
    var group = new Konva.Group();
    var line = new Konva.Line({ stroke: "#777", strokeWidth: 1 });
    var tickStart = new Konva.Line({ stroke: "#777", strokeWidth: 1 });
    var tickEnd = new Konva.Line({ stroke: "#777", strokeWidth: 1 });
    var tickMid = new Konva.Line({ stroke: "#777", strokeWidth: 1, visible: false });
    var textA = new Konva.Text({ fontSize: 11, fontStyle: "bold", fontFamily: "Arial, sans-serif", fill: "#444" });
    var textB = new Konva.Text({ fontSize: 11, fontStyle: "bold", fontFamily: "Arial, sans-serif", fill: "#444", visible: false });
    group.add(line, tickStart, tickEnd, tickMid, textA, textB);
    return { group: group, line: line, tickStart: tickStart, tickEnd: tickEnd, tickMid: tickMid, textA: textA, textB: textB };
}

function positionMachiningDimText(textNode, x, y, vertical) {
    textNode.rotation(vertical ? -90 : 0);
    textNode.offsetX(textNode.width() / 2);
    textNode.offsetY(textNode.height() / 2);
    textNode.position({ x: x, y: y });
}

// sign: +1 on the ruler's default side (bottom/right), -1 when flipped to
// the cut's side (top/left) — "further out" is the opposite direction
// there, so the text offset must flip too or it lands back toward the board.
function updateMachiningDimLine(dl, x1, y1, x2, y2, vertical, splitAt, labelA, labelB, sign) {
    dl.line.points([x1, y1, x2, y2]);
    var tick = 5;
    function setTick(shape, tx, ty) {
        if (vertical) shape.points([tx - tick, ty, tx + tick, ty]);
        else shape.points([tx, ty - tick, tx, ty + tick]);
    }
    setTick(dl.tickStart, x1, y1);
    setTick(dl.tickEnd, x2, y2);

    var textOffset = 18 * sign;

    if (splitAt == null) {
        dl.tickMid.visible(false);
        dl.textB.visible(false);
        dl.textA.text(labelA);
        positionMachiningDimText(dl.textA, vertical ? x1 + textOffset : (x1 + x2) / 2, vertical ? (y1 + y2) / 2 : y1 + textOffset, vertical);
        return;
    }

    var splitX = vertical ? x1 : splitAt;
    var splitY = vertical ? splitAt : y1;
    setTick(dl.tickMid, splitX, splitY);
    dl.tickMid.visible(true);

    dl.textA.text(labelA);
    dl.textB.text(labelB);
    dl.textB.visible(true);
    if (vertical) {
        positionMachiningDimText(dl.textA, x1 + textOffset, (y1 + splitY) / 2, true);
        positionMachiningDimText(dl.textB, x1 + textOffset, (splitY + y2) / 2, true);
    } else {
        positionMachiningDimText(dl.textA, (x1 + splitX) / 2, y1 + textOffset, false);
        positionMachiningDimText(dl.textB, (splitX + x2) / 2, y1 + textOffset, false);
    }
}

// A small clickable callout showing the live cut position on each edge —
// clicking opens promptMachiningPositionEdit() to type it directly instead
// of only dragging.
function buildMachiningPositionLabel() {
    var group = new Konva.Group({ visible: false });
    var bg = new Konva.Rect({ fill: "#fff", stroke: "#2b78c8", strokeWidth: 1 });
    var arrow = new Konva.RegularPolygon({ sides: 3, radius: 4, fill: "#2b78c8" });
    var text = new Konva.Text({ fontSize: 11, fontFamily: "Arial, sans-serif", fill: "#2b78c8", padding: 4 });
    group.add(bg, arrow, text);
    group.on("mouseenter", function () { machiningStage.container().style.cursor = "pointer"; });
    group.on("mouseleave", function () { machiningStage.container().style.cursor = "default"; });
    return { group: group, bg: bg, arrow: arrow, text: text };
}

// vertical: true for the width-axis label (points right, left of the cut
// point); false for the length-axis label (points down, above it).
function updateMachiningPositionLabel(lbl, value, px, py, vertical) {
    lbl.text.text(Math.round(value) + "");
    var w = lbl.text.width();
    var h = lbl.text.height();
    lbl.bg.size({ width: w, height: h });

    if (vertical) {
        lbl.bg.position({ x: px - w - 10, y: py - h / 2 });
        lbl.text.position({ x: px - w - 10, y: py - h / 2 });
        lbl.arrow.rotation(90);
        lbl.arrow.position({ x: px - 4, y: py });
    } else {
        lbl.bg.position({ x: px - w / 2, y: py - h - 10 });
        lbl.text.position({ x: px - w / 2, y: py - h - 10 });
        lbl.arrow.rotation(180);
        lbl.arrow.position({ x: px, y: py - 4 });
    }
    lbl.group.visible(true);
}

// Konva has no native text editing, so a real <input> is floated over the
// clicked label, scaled by stage-declared-size vs on-screen CSS size so it
// stays correct under the +/- zoom (a CSS transform on #machiningDiagram,
// not stage.scale()).
function promptMachiningPositionEdit(shape, currentValue, min, max, onCommit) {
    var containerRect = machiningStage.container().getBoundingClientRect();
    var cssScale = containerRect.width / machiningStage.width();
    var box = shape.getClientRect();

    var input = document.createElement("input");
    input.type = "text";
    input.setAttribute("data-min", min);
    input.setAttribute("data-max", max);
    input.value = isNaN(currentValue) ? "" : Math.round(currentValue);
    input.style.position = "fixed";
    input.style.left = (containerRect.left + box.x * cssScale) + "px";
    input.style.top = (containerRect.top + box.y * cssScale) + "px";
    input.style.width = Math.max(50, box.width * cssScale) + "px";
    input.style.height = Math.max(20, box.height * cssScale) + "px";
    input.style.zIndex = "100000";
    input.style.fontSize = "12px";
    input.style.border = "2px solid #2b78c8";
    input.style.padding = "1px 3px";
    input.style.boxSizing = "border-box";
    document.body.appendChild(input);
    input.focus();
    input.select();

    var settled = false;
    function commit() {
        if (settled) return;
        settled = true;
        var val = parseFloat(input.value);
        if (document.body.contains(input)) document.body.removeChild(input);
        if (!isNaN(val)) onCommit(Math.min(max, Math.max(min, val)));
    }
    function cancel() {
        if (settled) return;
        settled = true;
        if (document.body.contains(input)) document.body.removeChild(input);
    }
    input.addEventListener("blur", commit);
    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") input.blur();
        if (e.key === "Escape") cancel();
    });
}

function initMachiningStage() {
    if (machiningStage || typeof Konva === "undefined") return;

    machiningStage = new Konva.Stage({ container: "machiningKonvaStage", width: 500, height: 460 });
    machiningLayer = new Konva.Layer();
    machiningStage.add(machiningLayer);

    machiningShapes = {
        panel: new Konva.Rect({ fill: "#fff", stroke: "#5da344", strokeWidth: 2 }),
        badgeL1: buildMachiningBadge("L1"),
        badgeL2: buildMachiningBadge("L2"),
        badgeW1: buildMachiningBadge("W1"),
        badgeW2: buildMachiningBadge("W2"),
        dimLength: buildMachiningDimLine(),
        dimWidth: buildMachiningDimLine(),
        // strokeWidth 6 (wider than the panel's 2px border) so the fill also
        // covers the old border's outer half along the cut's edge stubs.
        notchFill: new Konva.Line({ closed: true, fill: "#dceafd", stroke: "#dceafd", strokeWidth: 6, visible: false }),
        notchLine: new Konva.Line({ stroke: "#5da344", strokeWidth: 2, visible: false }),
        // Rotated to the cut's real angle, resized every redraw in
        // updateMachiningNotch(). Not draggable — the position-callout
        // arrows below handle that.
        handle: new Konva.Rect({
            fill: "#fff", stroke: "#2b78c8", strokeWidth: 1.5,
            visible: false
        }),
        cutLengthLabel: new Konva.Text({
            fontSize: 10, fontFamily: "Arial, sans-serif", fill: "#2b78c8", visible: false
        }),
        hLabel: buildMachiningPositionLabel(),
        vLabel: buildMachiningPositionLabel()
    };

    machiningLayer.add(machiningShapes.panel);
    machiningLayer.add(machiningShapes.badgeL1, machiningShapes.badgeL2, machiningShapes.badgeW1, machiningShapes.badgeW2);
    machiningLayer.add(machiningShapes.dimLength.group, machiningShapes.dimWidth.group);
    machiningLayer.add(machiningShapes.notchFill, machiningShapes.notchLine, machiningShapes.handle, machiningShapes.cutLengthLabel);
    machiningLayer.add(machiningShapes.hLabel.group, machiningShapes.vLabel.group);

    // Click either callout (box or arrow, no movement) to type the cut
    // position directly — commits immediately, same as a drag's dragend,
    // rather than waiting for the sidebar's Save button.
    machiningShapes.hLabel.group.on("click tap", function () {
        var item = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0];
        if (!item) return;
        var dims = machiningCurrentDims();
        promptMachiningPositionEdit(machiningShapes.hLabel.group, parseFloat(item.offsetH), 0, isNaN(dims.length) ? 9998 : dims.length - 1, function (val) {
            item.offsetH = val;
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    });
    machiningShapes.vLabel.group.on("click tap", function () {
        var item = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0];
        if (!item) return;
        var dims = machiningCurrentDims();
        promptMachiningPositionEdit(machiningShapes.vLabel.group, parseFloat(item.offsetV), 0, isNaN(dims.width) ? 9998 : dims.width - 1, function (val) {
            item.offsetV = val;
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    });

    // Dragging each callout's arrow along its own edge (not the diagonal
    // bar) adjusts the cut position; dragBoundFunc reads live geometry each
    // move so it stays correct as the row/corner/panel size changes.
    machiningShapes.hLabel.arrow.draggable(true);
    machiningShapes.hLabel.arrow.dragBoundFunc(function (pos) {
        var geo = machiningLastGeometry;
        if (!geo || geo.cornerCy == null) return pos;
        return { x: Math.max(geo.x, Math.min(geo.right, pos.x)), y: geo.cornerCy - 6 };
    });
    machiningShapes.hLabel.arrow.on("dragmove", function () {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0];
        if (!geo || !item || !(geo.length > 0)) return;

        // dragBoundFunc already clamps the arrow's x to [geo.x, geo.right],
        // so the mm clamp below is enough to enforce 0..length-1.
        var insetH = geo.dirX * (machiningShapes.hLabel.arrow.x() - geo.cornerCx);
        var nearH = insetH * (geo.length / geo.rectW);
        item.offsetH = Math.max(0, Math.min(geo.length - 1, Math.round(geo.length - nearH)));

        updateMachiningNotch(item, geo);
        machiningLayer.batchDraw();
    });
    machiningShapes.hLabel.arrow.on("dragend", function () {
        saveMachiningAppliedItems();
        renderMachiningAppliedList();
    });

    machiningShapes.vLabel.arrow.draggable(true);
    machiningShapes.vLabel.arrow.dragBoundFunc(function (pos) {
        var geo = machiningLastGeometry;
        if (!geo || geo.cornerCx == null) return pos;
        return { x: geo.cornerCx - 6, y: Math.max(geo.y, Math.min(geo.bottom, pos.y)) };
    });
    machiningShapes.vLabel.arrow.on("dragmove", function () {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0];
        if (!geo || !item || !(geo.width > 0)) return;

        // Same reasoning as the hLabel handler above, for the width axis.
        var insetV = geo.dirY * (machiningShapes.vLabel.arrow.y() - geo.cornerCy);
        var nearV = insetV * (geo.width / geo.rectH);
        item.offsetV = Math.max(0, Math.min(geo.width - 1, Math.round(geo.width - nearV)));

        updateMachiningNotch(item, geo);
        machiningLayer.batchDraw();
    });
    machiningShapes.vLabel.arrow.on("dragend", function () {
        saveMachiningAppliedItems();
        renderMachiningAppliedList();
    });
}

function updateMachiningNotch(angledCut, geo) {
    // The length/width rulers default to the L2 (bottom) / W2 (right) side
    // — same as before this edge became cut-aware — and only move to
    // whichever side the cut actually landed on.
    geo.cornerL = "L2";
    geo.cornerW = "W2";
    geo.lengthAtTop = false;
    geo.splitLenAt = null;
    geo.splitWidAt = null;

    if (!angledCut || !angledCut.corner) {
        machiningShapes.notchFill.visible(false);
        machiningShapes.notchLine.visible(false);
        machiningShapes.handle.visible(false);
        machiningShapes.cutLengthLabel.visible(false);
        machiningShapes.hLabel.group.visible(false);
        machiningShapes.vLabel.group.visible(false);
        return;
    }

    // "B side" swaps which length badge renders at the top (see
    // redrawMachiningCanvas) — XOR'd below so the cut stays next to
    // whichever badge it actually belongs to.
    var flipLength = angledCut.view === "B";

    var cornerL = angledCut.corner.split("-")[0]; // "L1" | "L2"
    var cornerW = angledCut.corner.split("-")[1]; // "W1" | "W2"
    var lengthAtTop = (cornerL === "L1") !== flipLength;
    var cornerCx = cornerW === "W1" ? geo.x : geo.right;
    var cornerCy = lengthAtTop ? geo.y : geo.bottom;
    var dirX = cornerW === "W1" ? 1 : -1; // which way is "into the board" from this corner
    var dirY = lengthAtTop ? 1 : -1;

    geo.cornerCx = cornerCx;
    geo.cornerCy = cornerCy;
    geo.dirX = dirX;
    geo.dirY = dirY;
    geo.cornerL = cornerL;
    geo.cornerW = cornerW;
    geo.lengthAtTop = lengthAtTop;

    // Near-edge distance = total minus the "From {far edge}" offset, scaled
    // to the panel rect's px/mm ratio.
    var offsetH = parseFloat(angledCut.offsetH);
    var offsetV = parseFloat(angledCut.offsetV);
    var nearH = (!isNaN(offsetH) && geo.length > 0) ? Math.max(0, geo.length - offsetH) : null;
    var nearV = (!isNaN(offsetV) && geo.width > 0) ? Math.max(0, geo.width - offsetV) : null;
    // offsetH/offsetV are already clamped to their valid mm range elsewhere,
    // so converting straight to px (no extra pixel-space clamp) is what lets
    // the rendered position reach both ends of that range on any board size.
    var insetH = nearH != null ? nearH * (geo.rectW / geo.length) : MACHINING_MIN_INSET;
    var insetV = nearV != null ? nearV * (geo.rectH / geo.width) : MACHINING_MIN_INSET;

    var ptOnLEdge = { x: cornerCx + dirX * insetH, y: cornerCy };
    var ptOnWEdge = { x: cornerCx, y: cornerCy + dirY * insetV };

    geo.splitLenAt = ptOnLEdge.x;
    geo.splitWidAt = ptOnWEdge.y;

    // The corner is painted over with the canvas background colour so it
    // reads as material actually cut away, not just a line on an intact panel.
    machiningShapes.notchFill.points([cornerCx, cornerCy, ptOnLEdge.x, ptOnLEdge.y, ptOnWEdge.x, ptOnWEdge.y]);
    machiningShapes.notchFill.visible(true);

    // The handle band below is the visible cut edge, so the plain
    // notchLine stroke stays hidden to avoid doubling up.
    machiningShapes.notchLine.points([ptOnLEdge.x, ptOnLEdge.y, ptOnWEdge.x, ptOnWEdge.y]);
    machiningShapes.notchLine.visible(false);

    // Spans the entire cut line (not just its midpoint), rotated to the
    // cut's real angle — the two insets aren't always equal.
    var dxPx = ptOnWEdge.x - ptOnLEdge.x;
    var dyPx = ptOnWEdge.y - ptOnLEdge.y;
    var cutPixLen = Math.sqrt(dxPx * dxPx + dyPx * dyPx);
    var angleDeg = Math.atan2(dyPx, dxPx) * 180 / Math.PI;
    var midX = (ptOnLEdge.x + ptOnWEdge.x) / 2;
    var midY = (ptOnLEdge.y + ptOnWEdge.y) / 2;

    if (!machiningShapes.handle.isDragging()) {
        machiningShapes.handle.position({ x: midX, y: midY });
    }
    var barLen = Math.max(16, cutPixLen);
    machiningShapes.handle.width(barLen);
    machiningShapes.handle.height(8);
    machiningShapes.handle.offsetX(barLen / 2);
    machiningShapes.handle.offsetY(4);
    machiningShapes.handle.rotation(angleDeg);
    machiningShapes.handle.visible(true);

    // Pythagoras on the real mm distances, not the pixel line — stays
    // correct even if rectW/rectH end up at different px/mm ratios.
    var nearH_mm = nearH != null ? nearH : (geo.length > 0 ? insetH * (geo.length / geo.rectW) : insetH);
    var nearV_mm = nearV != null ? nearV : (geo.width > 0 ? insetV * (geo.width / geo.rectH) : insetV);
    var cutLengthMm = Math.round(Math.sqrt(nearH_mm * nearH_mm + nearV_mm * nearV_mm));
    machiningShapes.cutLengthLabel.text(cutLengthMm + "");
    machiningShapes.cutLengthLabel.position({ x: midX + dirX * 14, y: midY + dirY * 26 });
    machiningShapes.cutLengthLabel.visible(true);

    // Shows the saved offset once one exists, else the position the
    // default/dragged inset currently represents.
    var displayH = !isNaN(offsetH) ? offsetH : (geo.length > 0 ? geo.length - insetH * (geo.length / geo.rectW) : insetH);
    var displayV = !isNaN(offsetV) ? offsetV : (geo.width > 0 ? geo.width - insetV * (geo.width / geo.rectH) : insetV);
    updateMachiningPositionLabel(machiningShapes.hLabel, displayH, ptOnLEdge.x, ptOnLEdge.y, false);
    updateMachiningPositionLabel(machiningShapes.vLabel, displayV, ptOnWEdge.x, ptOnWEdge.y, true);
}

function redrawMachiningCanvas() {
    initMachiningStage();
    if (!machiningStage) return;

    var lengthRaw = document.getElementById("mLength") ? document.getElementById("mLength").textContent : "-";
    var widthRaw = document.getElementById("mWidth") ? document.getElementById("mWidth").textContent : "-";
    var angledItem = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0] || null;

    var length = parseFloat(lengthRaw);
    var width = parseFloat(widthRaw);
    var cfg = MACHINING_CANVAS_CFG;
    var rectW = 190;
    var rectH = 120;
    if (!isNaN(length) && !isNaN(width) && length > 0 && width > 0) {
        var scale = Math.min(cfg.maxW / length, cfg.maxH / width);
        rectW = Math.max(70, Math.round(length * scale));
        rectH = Math.max(45, Math.round(width * scale));
    }

    var x = cfg.x;
    var y = cfg.y;
    var right = x + rectW;
    var bottom = y + rectH;
    var midX = x + rectW / 2;
    var midY = y + rectH / 2;

    machiningShapes.panel.position({ x: x, y: y });
    machiningShapes.panel.size({ width: rectW, height: rectH });

    // "A side" (default) shows L1 at the top; "B side" swaps L1/L2 — kept in
    // sync with the item's A/B toggle and the "Panel shows" face box below.
    var flipLength = !!(angledItem && angledItem.view === "B");

    // Badge centres sit cfg.badgeOffset outside the board edge, not on the
    // border line itself.
    machiningShapes.badgeL1.position({ x: midX, y: flipLength ? bottom + cfg.badgeOffset : y - cfg.badgeOffset });
    machiningShapes.badgeL2.position({ x: midX, y: flipLength ? y - cfg.badgeOffset : bottom + cfg.badgeOffset });
    machiningShapes.badgeW1.position({ x: x - cfg.badgeOffset, y: midY });
    machiningShapes.badgeW2.position({ x: right + cfg.badgeOffset, y: midY });

    if (machiningFaceBox) {
        machiningFaceBox.classList.toggle("flipped", flipLength);
    }

    machiningLastGeometry = { x: x, y: y, right: right, bottom: bottom, rectW: rectW, rectH: rectH, length: length, width: width };
    var geo = machiningLastGeometry;

    // Notch first — decides which side/position the rulers below split at.
    updateMachiningNotch(angledItem, geo);

    var lengthRulerY = geo.lengthAtTop ? (y - cfg.rulerOffset) : (bottom + cfg.rulerOffset);
    var widthRulerX = geo.cornerW === "W1" ? (x - cfg.rulerOffset) : (right + cfg.rulerOffset);

    var lengthLabelA = "-";
    var lengthLabelB = null;
    if (geo.splitLenAt != null && !isNaN(length)) {
        var segA = Math.round((geo.splitLenAt - x) * (length / rectW));
        lengthLabelA = segA + " mm";
        lengthLabelB = Math.max(0, length - segA) + " mm";
    } else if (!isNaN(length)) {
        lengthLabelA = length + " mm";
    }

    var widthLabelA = "-";
    var widthLabelB = null;
    if (geo.splitWidAt != null && !isNaN(width)) {
        var segC = Math.round((geo.splitWidAt - y) * (width / rectH));
        widthLabelA = segC + " mm";
        widthLabelB = Math.max(0, width - segC) + " mm";
    } else if (!isNaN(width)) {
        widthLabelA = width + " mm";
    }

    var lengthSign = geo.lengthAtTop ? -1 : 1;
    var widthSign = geo.cornerW === "W1" ? -1 : 1;
    updateMachiningDimLine(machiningShapes.dimLength, x, lengthRulerY, right, lengthRulerY, false, geo.splitLenAt, lengthLabelA, lengthLabelB, lengthSign);
    updateMachiningDimLine(machiningShapes.dimWidth, widthRulerX, y, widthRulerX, bottom, true, geo.splitWidAt, widthLabelA, widthLabelB, widthSign);

    machiningLayer.batchDraw();
}

function saveMachiningAppliedItems() {
    if (!machiningCurrentRow) return;

    machiningCurrentRow.dataset.machiningApplied = JSON.stringify(machiningAppliedItems);

    // Feed into Panel Summary's "Surface shaping summary" — see
    // buildPanelSummaryMachiningText() in the Panel Summary section, which
    // already reads row.dataset.machiningData expecting this shape.
    var summaryItems = machiningAppliedItems
        .filter(function (item) { return item.option === "angled-cut"; })
        .map(function (item) {
            var labels = machiningCornerLabels(item.corner);
            return {
                type: "Angled cut",
                side: item.corner,
                detail: labels.h + " " + (item.offsetH || "-") + "mm, " + labels.v + " " + (item.offsetV || "-") + "mm"
            };
        });
    machiningCurrentRow.dataset.machiningData = JSON.stringify(summaryItems);

    redrawMachiningCanvas();
}

function machiningCurrentDims() {
    var lengthRaw = document.getElementById("mLength") ? document.getElementById("mLength").textContent : "-";
    var widthRaw = document.getElementById("mWidth") ? document.getElementById("mWidth").textContent : "-";
    return { length: parseFloat(lengthRaw), width: parseFloat(widthRaw) };
}

function machiningTapesForCurrentRow() {
    if (!machiningCurrentRow || !window.cutlistEdgeTapes) return [];
    var decorInput = machiningCurrentRow.querySelector(".decor input");
    var code = decorInput && decorInput.value ? decorInput.value.split(" - ")[0].trim() : "";
    if (!code) return [];
    return window.cutlistEdgeTapes.filter(function (t) { return t.decorCode === code; });
}

function buildMachiningEdgingOptionsHTML(tapes, selectedCode) {
    if (!tapes.length) {
        return '<div class="Select2__option Select2__option--empty">No edging tape matched to this board</div>';
    }
    return tapes.map(function (t) {
        var selected = t.code === selectedCode;
        return '<div class="Select2__option' + (selected ? ' Select-option--selected' : '') + '" data-code="' + t.code + '">' +
            '<div class="Select2__option-label"><div class="edgebanding-option">' +
            '<div class="code">' + t.code + '</div>' +
            '<div class="name">' + t.name + '</div>' +
            '</div></div></div>';
    }).join("");
}

function buildMachiningPreviewSVG(item) {
    var isBack = item.view === "B";
    var cornerDot = {
        "L1-W1": [30, 20], "L1-W2": [110, 20],
        "L2-W1": [30, 80], "L2-W2": [110, 80]
    }[item.corner] || [30, 20];
    return '<svg viewBox="0 0 140 100" class="machining-preview-svg">' +
        '<polygon class="preview-panel" points="25,20 115,20 125,85 35,85"></polygon>' +
        '<text class="preview-label" x="70" y="56" text-anchor="middle">' + (isBack ? "B SIDE" : "A SIDE") + "</text>" +
        '<text x="65" y="14" text-anchor="middle">L1</text>' +
        '<text x="65" y="97" text-anchor="middle">L2</text>' +
        '<text x="14" y="55">W1</text>' +
        '<text x="122" y="55">W2</text>' +
        '<circle cx="' + cornerDot[0] + '" cy="' + cornerDot[1] + '" r="4" fill="#d94a4a"></circle>' +
        "</svg>";
}

function buildMachiningAppliedItemHTML(item, index) {

    if (item.option !== "angled-cut") {
        return '<div class="machining-applied-item" data-index="' + index + '">' +
            '<div class="machining-applied-chip">' +
            '<span class="machining-applied-chip-label">' + panelSummaryEscape(item.label) + '</span>' +
            '<button type="button" class="machining-applied-remove" aria-label="Remove">&times;</button>' +
            "</div></div>";
    }

    var labels = machiningCornerLabels(item.corner);
    var tapes = machiningTapesForCurrentRow();
    var dims = machiningCurrentDims();
    var maxH = !isNaN(dims.length) ? dims.length - 1 : 9998;
    var maxV = !isNaN(dims.width) ? dims.width - 1 : 9998;

    var cornerOptionsHTML = MACHINING_CORNERS.map(function (corner) {
        return '<label class="machining-corner-option">' +
            '<input type="radio" name="machiningCorner' + index + '" value="' + corner + '"' + (item.corner === corner ? " checked" : "") + ">" +
            "<span>" + corner + "</span></label>";
    }).join("");

    return "" +
        '<div class="machining-applied-item" data-index="' + index + '">' +
        '<div class="machining-applied-chip">' +
        '<span class="machining-applied-chip-label">Angled cut on ' + item.corner + '</span>' +
        '<button type="button" class="machining-applied-remove" aria-label="Remove">&times;</button>' +
        "</div>" +
        '<div class="machining-applied-detail">' +
        '<div class="machining-detail-label">Angle cut on:</div>' +
        '<div class="machining-corner-picker">' + cornerOptionsHTML + "</div>" +
        '<div class="machining-offset-row">' +
        '<div class="machining-offset-field"><label>' + labels.h + "</label>" +
        '<input type="text" class="machining-offset-input" data-field="offsetH" data-min="0" data-max="' + maxH + '" value="' + (item.offsetH || "") + '"></div>' +
        '<div class="machining-offset-field"><label>' + labels.v + "</label>" +
        '<input type="text" class="machining-offset-input" data-field="offsetV" data-min="0" data-max="' + maxV + '" value="' + (item.offsetV || "") + '"></div>' +
        "</div>" +
        '<div class="machining-detail-label">Edging</div>' +
        '<div class="Select2' + (item.edgeTapeCode ? "" : " isEmpty") + ' Select2--has-arrow machining-edging-select">' +
        '<div class="Select2__input-wrapper">' +
        '<span class="Select2__input">' + (item.edgeTapeCode ? panelSummaryEscape(item.edgeTapeName || item.edgeTapeCode) : "") + "</span>" +
        '<span class="Select2__placeholder">Add edging tape to angled edge</span>' +
        '<span class="Select2__arrow">' + SVG_ARROW + "</span>" +
        "</div>" +
        '<div class="Select2__dropdown">' + buildMachiningEdgingOptionsHTML(tapes, item.edgeTapeCode) + "</div>" +
        "</div>" +
        '<div class="machining-toggle-row" data-role="finish"' + (item.edgeTapeCode ? "" : ' style="display:none"') + ">" +
        '<button type="button" class="machining-toggle-btn' + (item.finish === "radius" ? " selected" : "") + '" data-finish="radius">Radius<br><small>edge finish</small></button>' +
        '<button type="button" class="machining-toggle-btn' + (item.finish === "square" ? " selected" : "") + '" data-finish="square">Square<br><small>edge finish</small></button>' +
        "</div>" +
        '<div class="machining-detail-label">View</div>' +
        '<div class="machining-toggle-row" data-role="view">' +
        '<button type="button" class="machining-toggle-btn' + (item.view !== "B" ? " selected" : "") + '" data-view="A">A side<br><small>Front face</small></button>' +
        '<button type="button" class="machining-toggle-btn' + (item.view === "B" ? " selected" : "") + '" data-view="B">B side<br><small>Back face</small></button>' +
        "</div>" +
        '<div class="machining-preview-box">' + buildMachiningPreviewSVG(item) + "</div>" +
        '<button type="button" class="machining-save-btn">Save</button>' +
        "</div>" +
        "</div>";
}

function renderMachiningAppliedList() {
    if (!machiningAppliedList) return;
    machiningAppliedList.innerHTML = machiningAppliedItems.map(function (item, index) {
        return buildMachiningAppliedItemHTML(item, index);
    }).join("");
}

if (machiningAddBtn) {

    machiningAddBtn.addEventListener("click", function () {

        var selectedItem = document.querySelector(".machining-option-item.selected");
        if (!selectedItem) return;

        var optionKey = selectedItem.dataset.option;

        if (optionKey === "angled-cut") {
            machiningAppliedItems.push({
                option: "angled-cut",
                corner: "L1-W1",
                offsetH: "",
                offsetV: "",
                edgeTapeCode: "",
                edgeTapeName: "",
                finish: "",
                view: "A"
            });
        } else {
            machiningAppliedItems.push({
                option: optionKey,
                label: selectedItem.textContent.trim()
            });
        }

        renderMachiningAppliedList();
        saveMachiningAppliedItems();
        resetMachiningOptionSelect();

    });

}

if (machiningAppliedList) {

    machiningAppliedList.addEventListener("click", function (e) {

        var itemEl = e.target.closest(".machining-applied-item");
        if (!itemEl) return;

        var index = parseInt(itemEl.dataset.index, 10);
        var item = machiningAppliedItems[index];
        if (!item) return;

        if (e.target.closest(".machining-applied-remove")) {
            machiningAppliedItems.splice(index, 1);
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
            return;
        }

        if (e.target.closest(".machining-applied-chip")) {
            itemEl.classList.toggle("collapsed");
            return;
        }

        var edgingWrapper = e.target.closest(".machining-edging-select");
        if (edgingWrapper) {

            var inputWrapper = e.target.closest(".Select2__input-wrapper");
            if (inputWrapper) {
                var wasOpen = edgingWrapper.classList.contains("is-open");
                document.querySelectorAll(".machining-edging-select.is-open")
                    .forEach(function (el) { el.classList.remove("is-open"); });
                if (!wasOpen) edgingWrapper.classList.add("is-open");
                return;
            }

            var option = e.target.closest(".Select2__option");
            if (option && !option.classList.contains("Select2__option--empty")) {
                item.edgeTapeCode = option.dataset.code;
                var tape = machiningTapesForCurrentRow()
                    .filter(function (t) { return t.code === option.dataset.code; })[0];
                item.edgeTapeName = tape ? tape.name : "";
                renderMachiningAppliedList();
                saveMachiningAppliedItems();
                return;
            }

        }

        var finishBtn = e.target.closest(".machining-toggle-btn[data-finish]");
        if (finishBtn) {
            item.finish = finishBtn.dataset.finish;
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
            return;
        }

        var viewBtn = e.target.closest(".machining-toggle-btn[data-view]");
        if (viewBtn) {
            item.view = viewBtn.dataset.view;
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
            return;
        }

        if (e.target.closest(".machining-save-btn")) {
            itemEl.classList.add("collapsed");
            saveMachiningAppliedItems();
            return;
        }

    });

    machiningAppliedList.addEventListener("change", function (e) {

        var itemEl = e.target.closest(".machining-applied-item");
        if (!itemEl) return;

        var index = parseInt(itemEl.dataset.index, 10);
        var item = machiningAppliedItems[index];
        if (!item) return;

        if (e.target.matches('input[type="radio"][name^="machiningCorner"]')) {
            item.corner = e.target.value;
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
        }

    });

    // Only updates the in-memory item, not the DOM — re-rendering on every
    // keystroke would steal focus mid-typing. Persisted on Save instead.
    machiningAppliedList.addEventListener("input", function (e) {

        var itemEl = e.target.closest(".machining-applied-item");
        if (!itemEl) return;

        var index = parseInt(itemEl.dataset.index, 10);
        var item = machiningAppliedItems[index];
        if (!item) return;

        var field = e.target.dataset.field;
        if (field === "offsetH" || field === "offsetV") {
            item[field] = e.target.value;
        }

    });

    // Clamp into data-min/data-max on blur and redraw so the canvas
    // reflects the corrected value immediately.
    machiningAppliedList.addEventListener("focusout", function (e) {

        if (!e.target.classList.contains("machining-offset-input")) return;

        var itemEl = e.target.closest(".machining-applied-item");
        if (!itemEl) return;

        var index = parseInt(itemEl.dataset.index, 10);
        var item = machiningAppliedItems[index];
        if (!item) return;

        var min = parseFloat(e.target.dataset.min);
        var max = parseFloat(e.target.dataset.max);
        var val = parseFloat(e.target.value);

        if (isNaN(val)) return;

        var clamped = Math.min(max, Math.max(min, val));
        if (clamped !== val) e.target.value = clamped;

        var field = e.target.dataset.field;
        item[field] = clamped;

        redrawMachiningCanvas();

    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".machining-edging-select")) {
            document.querySelectorAll(".machining-edging-select.is-open")
                .forEach(function (el) { el.classList.remove("is-open"); });
        }
    });

}


machiningFaceBox.addEventListener("click", function () {

    machiningFaceBox.classList.toggle("flipped");

});


document.getElementById("machiningZoomIn")
    .addEventListener("click", function () {

        machiningZoom = Math.min(machiningZoom + 0.1, 1.8);

        machiningDiagram.style.transform =
            "translate(-50%, -50%) scale(" + machiningZoom + ")";

    });


document.getElementById("machiningZoomOut")
    .addEventListener("click", function () {

        machiningZoom = Math.max(machiningZoom - 0.1, 0.6);

        machiningDiagram.style.transform =
            "translate(-50%, -50%) scale(" + machiningZoom + ")";

    });


// SPRAY FINISHING OVERLAY

var sprayOverlay = document.getElementById("sprayOverlay");
var sprayDiagram = document.getElementById("sprayDiagram");
var sprayFaceBox = document.getElementById("sprayFaceBox");
var sprayZoom = 1;

function openSprayOverlay(row) {

    var decorInput = row.querySelector(".decor input");
    var thickSelect = row.querySelector(".thick select");
    var dims = getDimInputs(row);
    var qtyInput = row.querySelector(".qty input");
    var descInput = row.querySelector(".desc input");

    document.getElementById("sRownum").textContent = row.querySelector(".rownum").textContent;
    document.getElementById("sDecor").textContent = decorInput && decorInput.value ? decorInput.value : "-";
    document.getElementById("sThick").textContent = thickSelect && thickSelect.value ? thickSelect.value : "-";
    document.getElementById("sLength").textContent = dims.lengthInput && dims.lengthInput.value ? dims.lengthInput.value : "-";
    document.getElementById("sWidth").textContent = dims.widthInput && dims.widthInput.value ? dims.widthInput.value : "-";
    document.getElementById("sQty").textContent = qtyInput && qtyInput.value ? qtyInput.value : "-";
    document.getElementById("sDesc").textContent = descInput && descInput.value ? descInput.value : "-";

    row.querySelectorAll(".edging-input").forEach(function (td) {
        var input = td.querySelector("input");
        var target = document.getElementById("s" + td.dataset.edge);
        if (target) target.textContent = input && input.value ? input.value : "-";
    });

    document.getElementById("sDimLength").textContent =
        dims.lengthInput && dims.lengthInput.value ? dims.lengthInput.value + " mm" : "-";
    document.getElementById("sDimWidth").textContent =
        dims.widthInput && dims.widthInput.value ? dims.widthInput.value + " mm" : "-";

    scaleMachiningDiagramPanel(sprayDiagram, dims.lengthInput && dims.lengthInput.value, dims.widthInput && dims.widthInput.value);

    sprayState = { option: null, sides: { A: true, B: false }, finish: 0, bOnly: false, brand: "", colour: "" };
    renderSpraySidebar();

    sprayZoom = 1;
    sprayDiagram.style.transform = "translate(-50%, -50%) scale(1)";

    sprayOverlay.classList.add("open");

}

function closeSprayOverlay() {

    sprayOverlay.classList.remove("open");

}

document.getElementById("sprayClose").addEventListener("click", closeSprayOverlay);

sprayOverlay.addEventListener("click", function (e) {

    if (e.target === sprayOverlay) closeSprayOverlay();

});

sprayFaceBox.addEventListener("click", function () {

    var isFront = sprayFaceBox.textContent.includes("FRONT");

    sprayFaceBox.innerHTML = isFront
        ? "<div>BACK</div><div>FACE</div>"
        : "<div>FRONT</div><div>FACE</div>";

});

document.getElementById("sprayZoomIn").addEventListener("click", function () {

    sprayZoom = Math.min(sprayZoom + 0.1, 1.8);
    sprayDiagram.style.transform = "translate(-50%, -50%) scale(" + sprayZoom + ")";

});

document.getElementById("sprayZoomOut").addEventListener("click", function () {

    sprayZoom = Math.max(sprayZoom - 0.1, 0.6);
    sprayDiagram.style.transform = "translate(-50%, -50%) scale(" + sprayZoom + ")";

});


// SPRAY FINISHING OPTIONS — from the Spray Finish CPT (wp-admin), not
// hardcoded. window.cutlistSprayFinishes is keyed by slug here to rebuild
// the shape this used to be hardcoded as, so renderSpraySidebar() etc. below
// need no changes.

var SPRAY_OPTIONS = {};
(window.cutlistSprayFinishes || []).forEach(function (f) {
    SPRAY_OPTIONS[f.slug] = {
        label: f.label,
        panelFill: f.panelFill,
        finishes: f.finishes,
        paintFields: f.paintFields,
        bOption: f.bOption
    };
});

var SPRAY_BRANDS = ["Farrow & Ball", "Dulux", "Little Greene", "RAL Classic"];

var sprayState = null;

// Single-side panel area in sq.m., from the values shown in the context bar
function sprayPanelArea() {
    var l = parseFloat(document.getElementById("sLength").textContent) || 0;
    var w = parseFloat(document.getElementById("sWidth").textContent) || 0;
    return (l * w) / 1000000;
}

function sprayMoney(n) {
    return "£" + n.toFixed(2);
}

function renderSpraySidebar() {

    var sel = document.getElementById("spraySelect");
    var body = document.getElementById("spraySidebarBody");

    if (!sprayState.option) {
        sel.disabled = false;
        sel.value = "";
        body.innerHTML = "";
        updateSprayVisuals();
        return;
    }

    sel.disabled = true;
    sel.value = "";

    var cfg = SPRAY_OPTIONS[sprayState.option];

    var html = "" +
        "<div class=\"spray-pill-row\">" +
        "<div class=\"spray-pill\">" + cfg.label + "</div>" +
        "<button type=\"button\" class=\"spray-pill-remove\" title=\"Remove\">&#10005;</button>" +
        "</div>" +
        "<div class=\"spray-label\">Select panel sides for spraying</div>" +
        "<div class=\"spray-sides\">" +
        "<div class=\"spray-side\">" +
        "<button type=\"button\" class=\"spray-side-btn" + (sprayState.sides.A ? " selected" : "") + "\" data-side=\"A\">A side<small>Front face</small></button>" +
        "<div class=\"spray-side-note\">and edgebanded edges</div>" +
        "</div>" +
        "<div class=\"spray-side\">" +
        "<button type=\"button\" class=\"spray-side-btn" + (sprayState.sides.B ? " selected" : "") + "\" data-side=\"B\">B side<small>Back face</small></button>" +
        "</div>" +
        "</div>" +
        "<div class=\"spray-label\">Select finish</div>" +
        "<div class=\"spray-finish-cards\">" +
        cfg.finishes.map(function (f, i) {
            return "<button type=\"button\" class=\"spray-finish-card" + (sprayState.finish === i ? " selected" : "") + "\" data-finish=\"" + i + "\">" +
                "<div class=\"spray-finish-title\">" + f.title + "</div>" +
                (f.sub ? "<div class=\"spray-finish-sub\">" + f.sub + "</div>" : "") +
                "<div class=\"spray-finish-price\">" + sprayMoney(f.price) + "</div>" +
                "<div class=\"spray-finish-unit\">sq.m.</div>" +
                "</button>";
        }).join("") +
        "</div>";

    if (cfg.paintFields) {
        html += "" +
            "<div class=\"spray-fields\">" +
            "<div class=\"spray-field\">" +
            "<label>Paint brand</label>" +
            "<select id=\"sprayBrand\"><option value=\"\">Select brand</option>" +
            SPRAY_BRANDS.map(function (b) {
                return "<option" + (sprayState.brand === b ? " selected" : "") + ">" + b + "</option>";
            }).join("") +
            "</select>" +
            "</div>" +
            "<div class=\"spray-field\">" +
            "<label>Colour</label>" +
            "<input type=\"text\" id=\"sprayColour\" placeholder=\"Type colour name\" value=\"" + sprayState.colour + "\"" + (sprayState.brand ? "" : " disabled") + ">" +
            "</div>" +
            "</div>";
    }

    if (cfg.bOption) {
        html += "" +
            "<div class=\"spray-boption\">" +
            "<span>" + cfg.bOption.text + "<br>Price: " + sprayMoney(cfg.bOption.price) + " sq.m.</span>" +
            "<input type=\"checkbox\" id=\"sprayBOnly\"" + (sprayState.bOnly ? " checked" : "") + ">" +
            "</div>";
    }

    body.innerHTML = html;
    updateSprayVisuals();

}

function updateSprayVisuals() {

    var areaEl = document.getElementById("sprayAreaValue");
    var totalEl = document.getElementById("sprayTotal");
    var panel = document.getElementById("sprayPanel");

    if (!sprayState || !sprayState.option) {
        areaEl.textContent = "-";
        totalEl.textContent = sprayMoney(0);
        panel.style.background = "";
        return;
    }

    var cfg = SPRAY_OPTIONS[sprayState.option];
    if (!cfg) {
        // No matching finish (removed in wp-admin, or a stale cached slug)
        // — fail visibly instead of throwing on cfg.finishes below, which
        // would silently freeze the total.
        areaEl.textContent = "-";
        totalEl.textContent = sprayMoney(0);
        panel.style.background = "";
        return;
    }
    var panelArea = sprayPanelArea();
    var sides = (sprayState.sides.A ? 1 : 0) + (sprayState.sides.B ? 1 : 0);
    var area = panelArea * sides;

    var total = area * cfg.finishes[sprayState.finish].price;
    if (cfg.bOption && sprayState.bOnly) total += panelArea * cfg.bOption.price;

    areaEl.textContent = area ? area.toFixed(2) : "-";
    // A real "£0.00" (finish picked, area genuinely 0) reads as a broken
    // price rather than "the row has no Length/Width yet" — show "-" for
    // that case instead of a misleading zero-pound total.
    totalEl.textContent = area ? sprayMoney(total) : "-";
    panel.style.background = cfg.panelFill;

}

document.querySelector(".spray-sidebar").addEventListener("change", function (e) {

    if (e.target.id === "spraySelect" && e.target.value) {
        sprayState = { option: e.target.value, sides: { A: true, B: false }, finish: 0, bOnly: false, brand: "", colour: "" };
        renderSpraySidebar();
    }

    if (e.target.id === "sprayBrand") {
        sprayState.brand = e.target.value;
        document.getElementById("sprayColour").disabled = !sprayState.brand;
    }

    if (e.target.id === "sprayBOnly") {
        sprayState.bOnly = e.target.checked;
        updateSprayVisuals();
    }

});

document.querySelector(".spray-sidebar").addEventListener("input", function (e) {

    if (e.target.id === "sprayColour") sprayState.colour = e.target.value;

});

document.querySelector(".spray-sidebar").addEventListener("click", function (e) {

    if (e.target.closest(".spray-pill-remove")) {
        sprayState = { option: null, sides: { A: true, B: false }, finish: 0, bOnly: false, brand: "", colour: "" };
        renderSpraySidebar();
        return;
    }

    var sideBtn = e.target.closest(".spray-side-btn");
    if (sideBtn) {
        var side = sideBtn.dataset.side;
        sprayState.sides[side] = !sprayState.sides[side];
        sideBtn.classList.toggle("selected", sprayState.sides[side]);
        updateSprayVisuals();
        return;
    }

    var finishCard = e.target.closest(".spray-finish-card");
    if (finishCard) {
        sprayState.finish = parseInt(finishCard.dataset.finish, 10);
        document.querySelectorAll(".spray-finish-card").forEach(function (c) {
            c.classList.remove("selected");
        });
        finishCard.classList.add("selected");
        updateSprayVisuals();
        return;
    }

});

document.getElementById("sprayRotate").addEventListener("click", function () {

    var dimW = document.getElementById("sDimWidth");
    var dimL = document.getElementById("sDimLength");
    var tmp = dimW.textContent;
    dimW.textContent = dimL.textContent;
    dimL.textContent = tmp;

});

document.getElementById("spraySave").addEventListener("click", closeSprayOverlay);




// CLOSE OUTSIDE

document.addEventListener("click", function (e) {


    if (
        !popup.contains(e.target)
        &&
        !e.target.closest(".decor input")
    ) {

        popup.style.display = "none";

    }


    if (
        edgePopup.style.display === "block"
        &&
        !edgePopup.contains(e.target)
        &&
        !e.target.closest(".edging-input input")
    ) {

        closeEdgePopup();

    }


});




// INITIAL DISABLE

document.querySelectorAll(".decor")
    .forEach(decorTd => {


        let row = decorTd.closest("tr");


        row.querySelectorAll("input,select")
            .forEach(field => {


                if (!field.closest(".decor")) {

                    field.disabled = true;

                }


            });


    });
/* ==========================================
   SUMMARY ACCORDION
========================================== */

document.querySelectorAll(".summary-toggle").forEach(function (btn) {

    btn.addEventListener("click", function () {

        const card = this.closest(".summary-card");

        const body = card.querySelector(".summary-body");

        card.classList.toggle("open");

        body.classList.toggle("is-open");

    });

});
