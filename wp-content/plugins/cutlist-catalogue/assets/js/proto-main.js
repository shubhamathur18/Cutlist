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
const edgeDimTop = document.getElementById("edgeDimTop");
const edgeDimLeft = document.getElementById("edgeDimLeft");
const edgeDiagramCard = document.getElementById("edgeDiagramCard");
const edgeSummaryCode = document.getElementById("edgeSummaryCode");
const edgeSummaryDesc = document.getElementById("edgeSummaryDesc");
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

// The edging tape matched to a row's decor, from the Edge Tape CPT — was a
// hardcoded "M1" before. A row whose decor has no matched tape gets null,
// and the popup shows that rather than inventing a code.
function edgeTapeForRow(row) {
    if (!row || !window.cutlistEdgeTapes) return null;
    const decorInput = row.querySelector(".decor input");
    const decorCode = decorInput && decorInput.value
        ? decorInput.value.split(" - ")[0].trim()
        : "";
    if (!decorCode) return null;
    return window.cutlistEdgeTapes
        .filter(t => t.decorCode === decorCode)[0] || null;
}

// "M1-42 / H1227-TM12" -> "M1": just the tape family, dropping both the
// matched decor and the size suffix. The edge tabs and the narrow
// cutting-list edge inputs only have room for that much.
function edgeTapeShortCode(tape) {
    if (!tape) return "";
    return String(tape.code).split("/")[0].split("-")[0].trim();
}

// "22mm x 1mm" + "Matt ABS edging - Brown Abano Ash" -> "1mm Matt ABS
// edging" — thickness plus product name, matching the reference design.
function edgeTapeDescription(tape) {
    if (!tape) return "";
    const thickness = String(tape.size || "").split(/\s*x\s*/i)[1] || "";
    const product = String(tape.name || "").split(" - ")[0].trim();
    return (thickness ? thickness + " " : "") + product;
}

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

    // Draw the panel to shape. Length runs along the L1/L2 edges
    // (horizontal), width along W1/W2 (vertical), so the drawn box has the
    // real board's proportions rather than a fixed rectangle.
    //
    // Both sides are sized here rather than via CSS aspect-ratio: the box
    // has to stay inside the popup AND stay big enough for the dimension
    // text, and those two limits pull in opposite directions. Start from
    // the full width, and if that makes it too tall, drive from the height
    // instead and let the width shrink — that keeps the ratio exact for
    // everything up to roughly 1:2.5 either way, instead of silently
    // flattening tall panels into squares.
    let lengthMm = parseFloat(lengthInput && lengthInput.value);
    let widthMm = parseFloat(widthInput && widthInput.value);

    if (lengthMm > 0 && widthMm > 0) {
        const MAX_W = 196, MIN_W = 132, MAX_H = 236, MIN_H = 92;
        let ratio = lengthMm / widthMm;          // drawn width ÷ drawn height
        let w = MAX_W;
        let h = w / ratio;

        if (h > MAX_H) {
            h = MAX_H;
            w = Math.max(MIN_W, h * ratio);
        } else if (h < MIN_H) {
            h = MIN_H;
            w = Math.min(MAX_W, h * ratio);
        }

        edgeDiagramCard.style.width = Math.round(w) + "px";
        edgeDiagramCard.style.height = Math.round(h) + "px";
    } else {
        edgeDiagramCard.style.width = "";
        edgeDiagramCard.style.height = "";
    }

    // Highlight whichever edge is being edited — L1 is the header, L2 the
    // caption under the diagram, W1/W2 either side of it.
    edgePopup.querySelectorAll(".edge-face-label").forEach(label => {
        label.classList.toggle("active", label.dataset.face === activeEdge);
    });

    let tape = edgeTapeForRow(activeEdgeRow);
    let shortCode = edgeTapeShortCode(tape);

    edgeSummaryCode.textContent = tape ? tape.code : "–";
    edgeSummaryDesc.textContent = tape
        ? edgeTapeDescription(tape)
        : "No matching edging tape";
    edgeSummaryBtn.disabled = !tape;

    edgeTabs.querySelectorAll(".edge-tab").forEach(tab => {

        let edge = tab.dataset.edge;

        tab.classList.toggle("active", edge === activeEdge);

        tab.querySelector(".edge-tab-value").textContent =
            state[edge] ? shortCode : "-";

    });

    Object.keys(edgeHighlights).forEach(edge => {

        edgeHighlights[edge].classList.toggle("active", !!state[edge]);

    });

    // The tape button reads as selected (bordered) whenever the edge being
    // edited actually has tape on it, so switching tabs shows each edge's
    // own state rather than resetting to unselected.
    let tapeApplied = !!state[activeEdge];
    edgeSummaryBtn.classList.toggle("selected", tapeApplied);

    // A finish only becomes selectable once the tape has been applied to
    // this edge — before that there's nothing to finish, so both options
    // stay disabled regardless of what the tape supports. After that, only
    // the finishes the tape is available in (Edge Tape CPT) are usable.
    let allowed = machiningTapeFinishes(tape ? tape.code : "");

    edgeFinishOptions.querySelectorAll(".edge-finish-option").forEach(opt => {

        let supported = !!tape && allowed[opt.dataset.finish] !== false;
        let usable = tapeApplied && supported;

        opt.classList.toggle("disabled", !usable);
        opt.setAttribute("aria-disabled", usable ? "false" : "true");
        opt.title = supported
            ? (tapeApplied ? "" : "Select the edging tape above first")
            : "This edging tape isn't available with a " + opt.dataset.finish + " edge finish";

        opt.classList.toggle("selected", usable && opt.dataset.finish === state[activeEdge]);

    });

}


function openEdgePopup(row, edge, anchorEl) {

    activeEdgeRow = row;
    activeEdge = edge;

    // The summary's selected state is derived from the edge's own data in
    // renderEdgePopup(), so it must not be cleared here — doing so showed
    // an already-taped edge as untaped every time the popup reopened.
    renderEdgePopup();

    // Show it before measuring: the height isn't fixed — the panel drawing
    // is sized per row — so it can only be read once the popup is laid out.
    // Hidden rather than transparent so the pre-positioned frame never
    // flashes at the old location.
    edgePopup.style.visibility = "hidden";
    edgePopup.style.display = "block";

    let anchor = anchorEl.getBoundingClientRect();
    // Centre against the whole cell, not just the input inside it.
    let cell = (anchorEl.closest(".edging-input") || anchorEl).getBoundingClientRect();
    let popupHeight = edgePopup.offsetHeight;

    let top = cell.top + window.scrollY + (cell.height / 2) - (popupHeight / 2);

    // Centring pushes the popup above the fold for rows near the top of
    // the page, so keep it within the viewport — preferring the top edge
    // when the popup is taller than the window.
    let minTop = window.scrollY + 8;
    let maxTop = window.scrollY + window.innerHeight - popupHeight - 8;
    top = maxTop > minTop ? Math.min(Math.max(top, minTop), maxTop) : minTop;

    edgePopup.style.left = anchor.left + "px";
    edgePopup.style.top = top + "px";
    edgePopup.style.visibility = "";

}


function closeEdgePopup() {

    if (activeEdgeRow) {

        let state = getEdgeState(activeEdgeRow);
        let shortCode = edgeTapeShortCode(edgeTapeForRow(activeEdgeRow));

        activeEdgeRow.querySelectorAll(".edging-input").forEach(td => {

            let edge = td.dataset.edge;
            let input = td.querySelector("input");

            if (!input || input.disabled) return;

            input.value = state[edge] ? shortCode : "";

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

    // Availability depends on the row (decor, capabilities, thickness), so
    // the dropdown is rebuilt per row rather than once at load.
    renderMachiningOptionDropdown();
    pruneDisallowedMachiningItems();

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
        '<td class="text-right"><span class="delete" title="Remove row">&times;</span></td>';
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

    // Now a span.delete, matching the cutting list's × — this listener is
    // bound to #etTableArea, so it can't collide with the cutting list's
    // own .delete handler (bound to that table).
    var removeBtn = e.target.closest('.delete');
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

        // The insert-row / add-section overlay is a body-level element
        // positioned over whichever row is hovered, so removing that row
        // doesn't hide it — and no mouseout fires for an element that no
        // longer exists, leaving it stranded until some other row is
        // hovered. Hide it explicitly instead.
        clHideOverlay();

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

// Rows are lettered A, B, C... in the order their Grain match checkbox was
// actually ticked (not table position) — so the letter tells you "this was
// the Nth board I picked for matching", matching the grain direction
// diagram's A/B roles for the reader. grainCheckedOrder tracks that click
// order; unchecking or deleting a row re-letters the rest with no gaps.
var grainCheckedOrder = [];

function grainLetterFor(index) {
    var n = index + 1;
    var letters = "";
    while (n > 0) {
        var rem = (n - 1) % 26;
        letters = String.fromCharCode(65 + rem) + letters;
        n = Math.floor((n - 1) / 26);
    }
    return letters;
}

function updateGrainLetters() {
    grainCheckedOrder = grainCheckedOrder.filter(function (row) {
        return row.isConnected && row.querySelector(".grain input").checked;
    });

    table.querySelectorAll(".grain input:checked").forEach(function (cb) {
        var row = cb.closest("tr");
        if (grainCheckedOrder.indexOf(row) === -1) grainCheckedOrder.push(row);
    });

    table.querySelectorAll(".grain-letter").forEach(function (badge) {
        badge.textContent = "";
        badge.classList.remove("visible");
    });

    grainCheckedOrder.forEach(function (row, i) {
        var badge = row.querySelector(".grain-letter");
        if (!badge) return;
        badge.textContent = grainLetterFor(i);
        badge.classList.add("visible");
    });
}

table.addEventListener("change", function (e) {
    if (e.target.closest(".grain")) {
        updateGrainSection();
        updateGrainLetters();
    }
});

// Deleting a row can remove the last ticked checkbox
table.addEventListener("click", function (e) {
    if (e.target.closest(".delete")) {
        setTimeout(function () {
            updateGrainSection();
            updateGrainLetters();
        });
    }
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

    // Switching tabs only changes which edge you're editing. It used to
    // also apply/remove tape when the summary button carried a "selected"
    // class, but that class now means "this edge has tape" — so reusing it
    // as a mode flag would toggle a different edge than the one clicked.
    activeEdge = tab.dataset.edge;

    renderEdgePopup();

});


// Applies (or removes) the tape on the edge currently being edited. It has
// to re-render rather than just toggle a class: the finish options are
// gated on whether tape is applied, so they'd otherwise stay disabled until
// something else redrew the popup.
edgeSummaryBtn.addEventListener("click", function () {

    if (!activeEdgeRow || !activeEdge || edgeSummaryBtn.disabled) return;

    let state = getEdgeState(activeEdgeRow);

    if (state[activeEdge]) {

        state[activeEdge] = null;

    } else {

        // Tape always carries a finish, so applying it picks the first one
        // this tape is actually available in.
        let tape = edgeTapeForRow(activeEdgeRow);
        let allowed = machiningTapeFinishes(tape ? tape.code : "");
        state[activeEdge] = allowed.radius ? "radius" : (allowed.square ? "square" : null);

    }

    renderEdgePopup();

});


edgeFinishOptions.addEventListener("click", function (e) {

    let opt = e.target.closest(".edge-finish-option");

    if (!opt || !activeEdgeRow || !activeEdge) return;

    // These are divs, not buttons, so a "disabled" one still receives
    // clicks — it has to be rejected here.
    if (opt.classList.contains("disabled")) return;

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
    // e.target.closest(".machining-modal") — several buttons inside the
    // applied-items list (view/finish toggles, edging options) call
    // renderMachiningAppliedList() synchronously on click, which replaces
    // the list's innerHTML and detaches the clicked element from the DOM
    // *before* this click event finishes bubbling up here. closest() on a
    // detached node can't walk back up to .machining-modal and wrongly
    // finds no match, closing the overlay on a click that was actually
    // inside it. e.target itself stays a stable reference regardless of
    // later DOM mutations, so comparing it directly is immune to that.
    if (e.target === machiningOverlay) {

        closeMachiningOverlay();

    }

});


// MACHINING OPTION DROPDOWN
// The sidebar's option list (Panel shaping / Surface shaping / ...)
// now behaves as an actual dropdown: closed by default showing a
// "Select machining option" trigger, opens on click, and picking an
// item swaps the trigger for a selected-value row with an Add
// button next to it (wiring up what "Add" actually does is a later
// step).

var machiningSelectWrap = document.getElementById("machiningSelectWrap");
var machiningSelectTrigger = document.getElementById("machiningSelectTrigger");
var machiningSelectedRow = document.getElementById("machiningSelectedRow");
var machiningSelectedValue = document.getElementById("machiningSelectedValue");
var machiningOptionDropdown = document.getElementById("machiningOptionDropdown");
var machiningAddBtn = document.getElementById("machiningAddBtn");

// MACHINING OPTIONS — Machining Option CPT (wp-admin), not hardcoded.
// window.cutlistMachiningOptions (see cutlist_format_machining_option() in
// rest-endpoints.php) replaces what used to be a static list of
// .machining-option-item divs in the template. Everything downstream still
// reads data-option, so the click/selection code is unchanged.
var MACHINING_OPTIONS = window.cutlistMachiningOptions || [];

function machiningOptionBySlug(slug) {
    return MACHINING_OPTIONS.filter(function (o) { return o.slug === slug; })[0] || null;
}

// The board behind a row, looked up by decor code — same lookup the grain
// checkbox and edging picker already use.
function machiningBoardForRow(row) {
    if (!row || !window.cutlistPmProducts) return null;
    var decorInput = row.querySelector(".decor input");
    var code = decorInput && decorInput.value
        ? decorInput.value.split(" - ")[0].trim()
        : "";
    return code ? (window.cutlistPmProducts[code] || null) : null;
}

// Why an option can't be used on this row, or "" if it can. Checked in the
// order a person would explain it: switched off entirely, then not for this
// decor. Returning the reason (not just a boolean) is what lets the
// dropdown say why rather than just greying out.
//
// Capability and min/max thickness rules were dropped from the CPT for now;
// re-adding them means restoring those fields and adding a branch here.
function machiningOptionBlockedReason(opt, row) {
    if (!opt) return "";

    if (opt.available === false) {
        return "Not currently available";
    }

    var board = machiningBoardForRow(row);

    if (board && (board.machiningExcluded || []).indexOf(opt.slug) !== -1) {
        return "Not available for this decor";
    }

    return "";
}

function renderMachiningOptionDropdown() {
    if (!machiningOptionDropdown) return;

    if (!MACHINING_OPTIONS.length) {
        machiningOptionDropdown.innerHTML =
            '<div class="machining-option-group"><div class="machining-option-item disabled">' +
            'No machining options yet — add one in wp-admin under Machining Options.' +
            "</div></div>";
        return;
    }

    // Group in first-seen order so the CPT's menu_order drives both which
    // group comes first and the order within it, without a second sort key.
    var groups = [];
    var byGroup = {};
    MACHINING_OPTIONS.forEach(function (opt) {
        var name = opt.group || "";
        if (!byGroup[name]) {
            byGroup[name] = [];
            groups.push(name);
        }
        byGroup[name].push(opt);
    });

    machiningOptionDropdown.innerHTML = groups.map(function (name) {
        var header = name
            ? '<div class="machining-option-header">' + panelSummaryEscape(name) + "</div>"
            : "";
        var items = byGroup[name].map(function (opt) {
            // Greyed with the specific reason rather than hidden, so it's
            // clear the service exists but doesn't apply to this panel.
            var reason = machiningOptionBlockedReason(opt, machiningCurrentRow);
            return '<div class="machining-option-item' + (reason ? " disabled" : "") + '"' +
                ' data-option="' + panelSummaryEscape(opt.slug) + '"' +
                (reason ? ' title="' + panelSummaryEscape(reason) + '"' : "") +
                ">" + panelSummaryEscape(opt.label) + "</div>";
        }).join("");
        return '<div class="machining-option-group">' + header + items + "</div>";
    }).join("");
}

renderMachiningOptionDropdown();

// Drops applied machining the row can no longer take — after a decor or
// thickness change, an item saved earlier may now be disallowed, and
// without this it would stay on the row and reach the order. Mirrors what
// the grain checkbox already does when a decor stops supporting it.
// Returns true if anything was removed.
function pruneDisallowedMachiningItems() {
    if (!machiningCurrentRow || !machiningAppliedItems.length) return false;

    var kept = machiningAppliedItems.filter(function (item) {
        var opt = machiningOptionBySlug(item.option);
        // An item whose option no longer exists in the catalogue is left
        // alone: removing it would silently discard a saved order line
        // just because someone deleted the option in wp-admin.
        if (!opt) return true;
        return !machiningOptionBlockedReason(opt, machiningCurrentRow);
    });

    if (kept.length === machiningAppliedItems.length) return false;

    machiningAppliedItems = kept;
    saveMachiningAppliedItems();
    return true;
}

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

var MACHINING_GROOVE_MAX_WIDTH_MM = 50;
var MACHINING_GROOVE_DEPTH_MARGIN_MM = 3;

// The groove's two ends touch whichever edges it *doesn't* run parallel to
// — a groove "along L1-L2" runs left-right, so its ends touch W1/W2, and
// its position is measured from L1/L2 instead; a groove "along W1-W2" is
// the reverse.
function machiningGrooveLabels(edge) {
    if (edge === "W1-W2") {
        return { end1: "From L1", end2: "From L2", distEdges: ["W1", "W2"] };
    }
    return { end1: "From W1", end2: "From W2", distEdges: ["L1", "L2"] };
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
var MACHINING_CANVAS_CFG = { x: 120, y: 120, maxW: 160, maxH: 160, badgeOffset: 62, rulerOffset: 88 };

// Fallback inset (px), used only when an angled-cut item has no offsetH/
// offsetV yet. The real 0..length-1/width-1 range is enforced in mm space
// elsewhere, not by clamping this pixel value — a fixed pixel epsilon
// distorts disproportionately on a large board with a small px/mm ratio.
var MACHINING_MIN_INSET = 2;

// A new angled cut starts this far in from each edge of its corner, giving
// a 141mm cut (100 x sqrt(2)) — a real, visible chamfer to adjust from,
// rather than the near-zero one an unset offset would draw.
var MACHINING_DEFAULT_CUT_LEG_MM = 100;

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
    var text = new Konva.Text({ fontSize: 11, fontFamily: "Arial, sans-serif", fill: "#2b78c8", padding: 10 });
    group.add(bg, arrow, text);
    group.on("mouseenter", function () { machiningStage.container().style.cursor = "pointer"; });
    group.on("mouseleave", function () { machiningStage.container().style.cursor = "default"; });
    return { group: group, bg: bg, arrow: arrow, text: text };
}

// vertical: true for the width-axis label (points right, left of the cut
// point); false for the length-axis label (points down, above it). flip
// mirrors the box to the opposite side (right/below instead of left/above)
// — needed for groove endpoints, which sit at the L2/W2 edge instead of
// the L1/W1 edge angled-cut callouts always use.
function updateMachiningPositionLabel(lbl, value, px, py, vertical, flip) {
    lbl.text.text(Math.round(value) + "");
    var w = lbl.text.width();
    var h = lbl.text.height();
    lbl.bg.size({ width: w, height: h });

    if (vertical) {
        if (flip) {
            lbl.bg.position({ x: px + 10, y: py - h / 2 });
            lbl.text.position({ x: px + 10, y: py - h / 2 });
            lbl.arrow.rotation(270);
            lbl.arrow.position({ x: px + 4, y: py });
        } else {
            lbl.bg.position({ x: px - w - 10, y: py - h / 2 });
            lbl.text.position({ x: px - w - 10, y: py - h / 2 });
            lbl.arrow.rotation(90);
            lbl.arrow.position({ x: px - 4, y: py });
        }
    } else {
        if (flip) {
            lbl.bg.position({ x: px - w / 2, y: py + 10 });
            lbl.text.position({ x: px - w / 2, y: py + 10 });
            lbl.arrow.rotation(0);
            lbl.arrow.position({ x: px, y: py + 4 });
        } else {
            lbl.bg.position({ x: px - w / 2, y: py - h - 10 });
            lbl.text.position({ x: px - w / 2, y: py - h - 10 });
            lbl.arrow.rotation(180);    
            lbl.arrow.position({ x: px, y: py - 4 });
        }
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
        // A closed polygon, not a Rect — an angled cut removes that corner
        // from the panel's own outline (see updateMachiningNotch), so the
        // border is one continuous stroke that already includes the
        // diagonal. Nothing is painted over an intact rectangle.
        panel: new Konva.Line({ closed: true, fill: "#fff", stroke: "#5da344", strokeWidth: 2 }),
        badgeL1: buildMachiningBadge("L1"),
        badgeL2: buildMachiningBadge("L2"),
        badgeW1: buildMachiningBadge("W1"),
        badgeW2: buildMachiningBadge("W2"),
        dimLength: buildMachiningDimLine(),
        dimWidth: buildMachiningDimLine(),
        // Dimension band for the cut, lying just inside the board with its
        // outer long edge sitting on the cut itself — so the panel's own
        // diagonal border doubles as the band's fourth side. Positioned in
        // updateMachiningNotch(), with cutLengthLabel centred inside it.
        cutBand: new Konva.Rect({
            fill: "#fff", stroke: "#2b78c8", strokeWidth: 1, height: 16,
            offsetY: 8, visible: false
        }),
        cutLengthLabel: new Konva.Text({
            fontSize: 10, fontFamily: "Arial, sans-serif", fill: "#2b78c8", visible: false
        }),
        hLabel: buildMachiningPositionLabel(),
        vLabel: buildMachiningPositionLabel(),
        // Groove cut — a straight slot, not a corner notch, so it gets its
        // own bar + labels instead of reusing the angled-cut notch shapes.
        grooveBar: new Konva.Rect({ fill: "#c9c9c9", stroke: "#888", strokeWidth: 1, visible: false }),
        grooveLengthLabel: new Konva.Text({
            fontSize: 10, fontFamily: "Arial, sans-serif", fill: "#2b78c8", visible: false
        }),
        grooveEnd1Label: buildMachiningPositionLabel(),
        grooveEnd2Label: buildMachiningPositionLabel(),
        grooveDistLabel: buildMachiningPositionLabel(),
        // Hinge-hole markers, gap dimension line and per-hole position
        // callouts. The hole count varies per row (2 up to ~19), so unlike
        // the fixed shapes above this is a plain group that
        // updateMachiningHinge() empties and rebuilds each redraw rather
        // than a fixed set of shapes updated in place.
        hingeGroup: new Konva.Group(),
        // Shelf-pin rows — same rebuild-per-redraw approach as hingeGroup,
        // for the same reason: the hole count varies with clusters,
        // positions and the two rows.
        shelfGroup: new Konva.Group()
    };

    machiningLayer.add(machiningShapes.panel);
    machiningLayer.add(machiningShapes.badgeL1, machiningShapes.badgeL2, machiningShapes.badgeW1, machiningShapes.badgeW2);
    machiningLayer.add(machiningShapes.dimLength.group, machiningShapes.dimWidth.group);
    // Band first so its value label draws on top of it.
    machiningLayer.add(machiningShapes.cutBand, machiningShapes.cutLengthLabel);
    machiningLayer.add(machiningShapes.hLabel.group, machiningShapes.vLabel.group);
    machiningLayer.add(machiningShapes.grooveBar, machiningShapes.grooveLengthLabel);
    machiningLayer.add(machiningShapes.grooveEnd1Label.group, machiningShapes.grooveEnd2Label.group, machiningShapes.grooveDistLabel.group);
    machiningLayer.add(machiningShapes.hingeGroup);
    machiningLayer.add(machiningShapes.shelfGroup);

    // Click a groove callout to type its value directly, same pattern as
    // the angled-cut hLabel/vLabel callouts above — no drag support for
    // groove yet, typed editing only.
    machiningShapes.grooveEnd1Label.group.on("click tap", function () {
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!item) return;
        var dims = machiningCurrentDims();
        var runMax = item.edge === "W1-W2"
            ? (isNaN(dims.width) ? 9998 : dims.width - 1)
            : (isNaN(dims.length) ? 9998 : dims.length - 1);
        promptMachiningPositionEdit(machiningShapes.grooveEnd1Label.group, parseFloat(item.end1), 0, runMax, function (val) {
            item.end1 = val;
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    });
    machiningShapes.grooveEnd2Label.group.on("click tap", function () {
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!item) return;
        var dims = machiningCurrentDims();
        var runMax = item.edge === "W1-W2"
            ? (isNaN(dims.width) ? 9998 : dims.width - 1)
            : (isNaN(dims.length) ? 9998 : dims.length - 1);
        promptMachiningPositionEdit(machiningShapes.grooveEnd2Label.group, parseFloat(item.end2), 0, runMax, function (val) {
            item.end2 = val;
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    });
    machiningShapes.grooveDistLabel.group.on("click tap", function () {
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!item) return;
        var dims = machiningCurrentDims();
        var distMax = item.edge === "W1-W2"
            ? (isNaN(dims.length) ? 9998 : dims.length - 1)
            : (isNaN(dims.width) ? 9998 : dims.width - 1);
        promptMachiningPositionEdit(machiningShapes.grooveDistLabel.group, parseFloat(item.distance), 0, distMax, function (val) {
            item.distance = val;
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    });

    // Dragging the endpoint arrows moves along the board edge they're
    // anchored to (horizontal for a L1-L2 groove, vertical for W1-W2);
    // dragBoundFunc re-checks the item's current edge on every move since
    // it can change (via the edge toggle) while the overlay is open.
    machiningShapes.grooveEnd1Label.arrow.draggable(true);
    machiningShapes.grooveEnd1Label.arrow.dragBoundFunc(function (pos) {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!geo || !item) return pos;
        if (item.edge === "W1-W2") {
            return { x: geo.x - 4, y: Math.max(geo.y, Math.min(geo.bottom, pos.y)) };
        }
        return { x: Math.max(geo.x, Math.min(geo.right, pos.x)), y: geo.bottom + 4 };
    });
    machiningShapes.grooveEnd1Label.arrow.on("dragmove", function () {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!geo || !item) return;
        var isVertical = item.edge === "W1-W2";
        var runTotal = isVertical ? geo.width : geo.length;
        var runPx = isVertical ? geo.rectH : geo.rectW;
        if (!(runTotal > 0)) return;
        // A vertical groove's end1 is measured from L1, which sits at the
        // bottom of the canvas in the B-side view.
        var px = isVertical
            ? machiningPxFromL1(geo, machiningShapes.grooveEnd1Label.arrow.y(), item.view === "B")
            : machiningShapes.grooveEnd1Label.arrow.x() - geo.x;
        item.end1 = Math.max(0, Math.min(runTotal - 1, Math.round(px * (runTotal / runPx))));
        updateMachiningGroove(item, geo);
        machiningLayer.batchDraw();
    });
    machiningShapes.grooveEnd1Label.arrow.on("dragend", function () {
        saveMachiningAppliedItems();
        renderMachiningAppliedList();
    });

    machiningShapes.grooveEnd2Label.arrow.draggable(true);
    machiningShapes.grooveEnd2Label.arrow.dragBoundFunc(function (pos) {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!geo || !item) return pos;
        if (item.edge === "W1-W2") {
            return { x: geo.x - 4, y: Math.max(geo.y, Math.min(geo.bottom, pos.y)) };
        }
        return { x: Math.max(geo.x, Math.min(geo.right, pos.x)), y: geo.bottom + 4 };
    });
    machiningShapes.grooveEnd2Label.arrow.on("dragmove", function () {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!geo || !item) return;
        var isVertical = item.edge === "W1-W2";
        var runTotal = isVertical ? geo.width : geo.length;
        var runPx = isVertical ? geo.rectH : geo.rectW;
        if (!(runTotal > 0)) return;
        // Measured from the *far* edge (W2/L2), unlike end1 above — and L2
        // is the one that moves to the top in the B-side view.
        var pxFromFar = isVertical
            ? machiningPxFromL2(geo, machiningShapes.grooveEnd2Label.arrow.y(), item.view === "B")
            : geo.right - machiningShapes.grooveEnd2Label.arrow.x();
        item.end2 = Math.max(0, Math.min(runTotal - 1, Math.round(pxFromFar * (runTotal / runPx))));
        updateMachiningGroove(item, geo);
        machiningLayer.batchDraw();
    });
    machiningShapes.grooveEnd2Label.arrow.on("dragend", function () {
        saveMachiningAppliedItems();
        renderMachiningAppliedList();
    });

    // The distance arrow moves along the *opposite* axis from the
    // endpoints — vertical for a L1-L2 groove (it's measuring how far up/
    // down the groove sits), horizontal for W1-W2.
    machiningShapes.grooveDistLabel.arrow.draggable(true);
    machiningShapes.grooveDistLabel.arrow.dragBoundFunc(function (pos) {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!geo || !item) return pos;
        if (item.edge === "W1-W2") {
            return { x: Math.max(geo.x, Math.min(geo.right, pos.x)), y: geo.y - 4 };
        }
        return { x: geo.x - 4, y: Math.max(geo.y, Math.min(geo.bottom, pos.y)) };
    });
    machiningShapes.grooveDistLabel.arrow.on("dragmove", function () {
        var geo = machiningLastGeometry;
        var item = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0];
        if (!geo || !item) return;
        var isVertical = item.edge === "W1-W2";
        var crossTotal = isVertical ? geo.length : geo.width;
        var crossPx = isVertical ? geo.rectW : geo.rectH;
        if (!(crossTotal > 0)) return;
        var mm;
        if (isVertical) {
            var xPos = machiningShapes.grooveDistLabel.arrow.x();
            mm = item.distanceEdge === "W2" ? (geo.right - xPos) * (crossTotal / crossPx) : (xPos - geo.x) * (crossTotal / crossPx);
        } else {
            // Measured off L1/L2, so it follows the B-side flip.
            var yPos = machiningShapes.grooveDistLabel.arrow.y();
            var flipped = item.view === "B";
            var pxFromEdge = item.distanceEdge === "L2"
                ? machiningPxFromL2(geo, yPos, flipped)
                : machiningPxFromL1(geo, yPos, flipped);
            mm = pxFromEdge * (crossTotal / crossPx);
        }
        item.distance = Math.max(0, Math.min(crossTotal - 1, Math.round(mm)));
        updateMachiningGroove(item, geo);
        machiningLayer.batchDraw();
    });
    machiningShapes.grooveDistLabel.arrow.on("dragend", function () {
        saveMachiningAppliedItems();
        renderMachiningAppliedList();
    });

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

    // The cut length isn't stored — it's derived from the two offsets — so
    // typing a new one scales both legs by the same factor. That keeps the
    // cut's current angle and just slides it further in or out, which is
    // the only reading of "make the cut 500 long" that has one answer: any
    // other split of the two legs would give the same length at a
    // different angle.
    function promptMachiningCutLengthEdit() {
        var item = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0];
        if (!item) return;

        var dims = machiningCurrentDims();
        if (isNaN(dims.length) || isNaN(dims.width) || dims.length <= 0 || dims.width <= 0) return;

        // Distance from the cut corner along each edge — an unset offset
        // counts as 0 so a half-filled item still has a usable angle.
        var offH = parseFloat(item.offsetH);
        var offV = parseFloat(item.offsetV);
        var nearH = isNaN(offH) ? 0 : Math.max(0, dims.length - offH);
        var nearV = isNaN(offV) ? 0 : Math.max(0, dims.width - offV);
        var curLen = Math.sqrt(nearH * nearH + nearV * nearV);

        // Cap at the point where whichever leg is proportionally longest
        // would run past the end of its own edge.
        var maxLen;
        if (curLen > 0) {
            maxLen = Math.floor(curLen * Math.min(
                nearH > 0 ? dims.length / nearH : Infinity,
                nearV > 0 ? dims.width / nearV : Infinity
            ));
        } else {
            maxLen = Math.floor(Math.sqrt(dims.length * dims.length + dims.width * dims.width));
        }

        promptMachiningPositionEdit(machiningShapes.cutLengthLabel, Math.round(curLen), 1, maxLen, function (val) {
            var newH, newV;
            if (curLen > 0) {
                var scale = val / curLen;
                newH = nearH * scale;
                newV = nearV * scale;
            } else {
                // No angle established yet, so split evenly (45 degrees).
                newH = newV = val / Math.SQRT2;
            }
            item.offsetH = Math.max(0, Math.min(dims.length - 1, Math.round(dims.length - newH)));
            item.offsetV = Math.max(0, Math.min(dims.width - 1, Math.round(dims.width - newV)));
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    }

    // The band is the click target as well as the number — the rotated
    // 10px text on its own is a fiddly thing to hit.
    [machiningShapes.cutLengthLabel, machiningShapes.cutBand].forEach(function (shape) {
        shape.on("click tap", promptMachiningCutLengthEdit);
        shape.on("mouseenter", function () { machiningStage.container().style.cursor = "pointer"; });
        shape.on("mouseleave", function () { machiningStage.container().style.cursor = "default"; });
    });

    // Dragging each callout's arrow along its own edge (not the diagonal
    // bar) adjusts the cut position; dragBoundFunc reads live geometry each
    // move so it stays correct as the row/corner/panel size changes.
    machiningShapes.hLabel.arrow.draggable(true);
    machiningShapes.hLabel.arrow.dragBoundFunc(function (pos) {
        var geo = machiningLastGeometry;
        if (!geo || geo.cornerCy == null) return pos;
        // -dirY so the arrow rides the outside of the edge (above a top
        // edge, below a bottom one), matching where the callout renders.
        return { x: Math.max(geo.x, Math.min(geo.right, pos.x)), y: geo.cornerCy - 6 * geo.dirY };
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
        // -dirX for the same reason as hLabel above: left of a left edge,
        // right of a right one.
        return { x: geo.cornerCx - 6 * geo.dirX, y: Math.max(geo.y, Math.min(geo.bottom, pos.y)) };
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

// Builds the panel outline with the cut corner removed: whichever of the
// rect's 4 corners sits at (cornerCx, cornerCy) is dropped and replaced by
// the two chamfer endpoints, making the diagonal a real edge of the
// polygon. Works for any corner, not just top-left.
function machiningCutPanelPoints(geo, cornerCx, cornerCy, ptOnLEdge, ptOnWEdge) {
    // Clockwise from top-left, so the polygon winds consistently.
    var corners = [
        [geo.x, geo.y],          // top-left
        [geo.right, geo.y],      // top-right
        [geo.right, geo.bottom], // bottom-right
        [geo.x, geo.bottom]      // bottom-left
    ];
    var cutIndex = -1;
    for (var i = 0; i < corners.length; i++) {
        if (corners[i][0] === cornerCx && corners[i][1] === cornerCy) { cutIndex = i; break; }
    }
    if (cutIndex === -1) {
        return [geo.x, geo.y, geo.right, geo.y, geo.right, geo.bottom, geo.x, geo.bottom];
    }

    // Walking clockwise, the edge arriving at top-left/bottom-right is the
    // vertical one, so its chamfer endpoint (the point on the W edge) comes
    // first; at top-right/bottom-left the arriving edge is horizontal.
    var arrivesVertical = (cutIndex === 0 || cutIndex === 2);
    var first = arrivesVertical ? ptOnWEdge : ptOnLEdge;
    var second = arrivesVertical ? ptOnLEdge : ptOnWEdge;

    var points = [];
    corners.forEach(function (corner, idx) {
        if (idx === cutIndex) {
            points.push(first.x, first.y, second.x, second.y);
        } else {
            points.push(corner[0], corner[1]);
        }
    });
    return points;
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
        // No cut — the panel outline is the plain full rectangle.
        machiningShapes.panel.points([geo.x, geo.y, geo.right, geo.y, geo.right, geo.bottom, geo.x, geo.bottom]);
        machiningShapes.cutBand.visible(false);
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

    // The cut corner is removed from the panel's own outline, so the
    // diagonal IS one of the polygon's edges — drawn by the same single
    // border stroke as the straight sides. Nothing is painted over an
    // intact rectangle, so there's no leftover square corner, no patch of
    // mismatched fill, and no seam where the diagonal meets the edges.
    machiningShapes.panel.points(machiningCutPanelPoints(geo, cornerCx, cornerCy, ptOnLEdge, ptOnWEdge));

    // angleDeg/midX/midY describe the cut line for the band + label below.
    var dxPx = ptOnWEdge.x - ptOnLEdge.x;
    var dyPx = ptOnWEdge.y - ptOnLEdge.y;
    var cutPixLen = Math.sqrt(dxPx * dxPx + dyPx * dyPx) || 1;
    var angleDeg = Math.atan2(dyPx, dxPx) * 180 / Math.PI;
    var midX = (ptOnLEdge.x + ptOnWEdge.x) / 2;
    var midY = (ptOnLEdge.y + ptOnWEdge.y) / 2;

    // Unit normal to the cut, flipped to point away from the cut-off
    // corner — i.e. into the remaining board. Which screen direction that
    // is differs per corner, so it's derived rather than hardcoded.
    var normX = -dyPx / cutPixLen;
    var normY = dxPx / cutPixLen;
    if (normX * (midX - cornerCx) + normY * (midY - cornerCy) < 0) {
        normX = -normX;
        normY = -normY;
    }

    // Pythagoras on the real mm distances, not the pixel line — stays
    // correct even if rectW/rectH end up at different px/mm ratios.
    var nearH_mm = nearH != null ? nearH : (geo.length > 0 ? insetH * (geo.length / geo.rectW) : insetH);
    var nearV_mm = nearV != null ? nearV : (geo.width > 0 ? insetV * (geo.width / geo.rectH) : insetV);
    var cutLengthMm = Math.round(Math.sqrt(nearH_mm * nearH_mm + nearV_mm * nearV_mm));
    machiningShapes.cutLengthLabel.text(cutLengthMm + "");
    // Centre the label on its own text box so rotating it pivots in place
    // instead of swinging around its top-left corner.
    var labelW = machiningShapes.cutLengthLabel.width();
    var labelH = machiningShapes.cutLengthLabel.height();
    machiningShapes.cutLengthLabel.offsetX(labelW / 2);
    machiningShapes.cutLengthLabel.offsetY(labelH / 2);

    // Band spans the full cut, but never shrinks below what the value
    // inside it needs — a short cut is only a few px long on screen, and a
    // band clamped to that would be a sliver with the digits spilling out
    // either end instead of a clean box.
    var bandH = Math.max(16, labelH + 6);
    var bandW = Math.max(cutPixLen, labelW + 10);
    machiningShapes.cutBand.height(bandH);
    machiningShapes.cutBand.offsetY(bandH / 2);
    machiningShapes.cutBand.width(bandW);
    machiningShapes.cutBand.offsetX(bandW / 2);
    // Pushed half its own thickness inward, so its outer long edge lies on
    // the cut — the panel's green diagonal border then reads as the band's
    // fourth side, and the band sits wholly inside the board rather than
    // straddling the edge.
    machiningShapes.cutBand.rotation(angleDeg);
    machiningShapes.cutBand.position({ x: midX + normX * bandH / 2, y: midY + normY * bandH / 2 });
    machiningShapes.cutBand.visible(true);
    // Same line angle as the handle, but folded back into -90..90 so the
    // digits never render upside-down (a line and its 180°-rotated self
    // are the same line, so this doesn't change what it's aligned to).
    var labelAngle = angleDeg;
    if (labelAngle > 90) labelAngle -= 180;
    else if (labelAngle < -90) labelAngle += 180;
    machiningShapes.cutLengthLabel.rotation(labelAngle);
    // Same centre as the band, so the value sits inside it.
    machiningShapes.cutLengthLabel.position({ x: midX + normX * bandH / 2, y: midY + normY * bandH / 2 });
    machiningShapes.cutLengthLabel.visible(true);

    // Shows the saved offset once one exists, else the position the
    // default/dragged inset currently represents.
    var displayH = !isNaN(offsetH) ? offsetH : (geo.length > 0 ? geo.length - insetH * (geo.length / geo.rectW) : insetH);
    var displayV = !isNaN(offsetV) ? offsetV : (geo.width > 0 ? geo.width - insetV * (geo.width / geo.rectH) : insetV);
    // Each callout goes on the far side of the edge it's anchored to, so it
    // always sits outside the board: above a top edge but below a bottom
    // one, left of a left edge but right of a right one. dirY/dirX point
    // *into* the board from the cut corner, so flipping on the negative
    // case puts the box on the outside for all four corners.
    updateMachiningPositionLabel(machiningShapes.hLabel, displayH, ptOnLEdge.x, ptOnLEdge.y, false, dirY < 0);
    updateMachiningPositionLabel(machiningShapes.vLabel, displayV, ptOnWEdge.x, ptOnWEdge.y, true, dirX < 0);
}

// Viewing the B side shows the panel flipped top-to-bottom, so L1 and L2
// swap ends of the canvas rect (see the badge placement in
// redrawMachiningCanvas). Anything measured *from* L1 or L2 therefore has
// to be read from the opposite end in that view — these four helpers are
// the single place that flip is applied, so the drawing code below and the
// drag handlers in initMachiningStage can't disagree about which end is
// which. The W1/W2 axis is untouched by a top-to-bottom flip.
function machiningYFromL1(geo, px, flipped) {
    return flipped ? geo.bottom - px : geo.y + px;
}
function machiningYFromL2(geo, px, flipped) {
    return flipped ? geo.y + px : geo.bottom - px;
}
function machiningPxFromL1(geo, yPos, flipped) {
    return flipped ? geo.bottom - yPos : yPos - geo.y;
}
function machiningPxFromL2(geo, yPos, flipped) {
    return flipped ? yPos - geo.y : geo.bottom - yPos;
}

// A groove is a straight slot running parallel to one pair of edges (L1-L2
// = horizontal, W1-W2 = vertical), inset from the other pair's edges by
// end1/end2, and offset from whichever edge distanceEdge names.
function updateMachiningGroove(grooveItem, geo) {
    var shapes = machiningShapes;

    if (!grooveItem) {
        shapes.grooveBar.visible(false);
        shapes.grooveLengthLabel.visible(false);
        shapes.grooveEnd1Label.group.visible(false);
        shapes.grooveEnd2Label.group.visible(false);
        shapes.grooveDistLabel.group.visible(false);
        return;
    }

    var isVertical = grooveItem.edge === "W1-W2";
    var runTotal = isVertical ? geo.width : geo.length;
    var crossTotal = isVertical ? geo.length : geo.width;
    var runPx = isVertical ? geo.rectH : geo.rectW;
    var crossPx = isVertical ? geo.rectW : geo.rectH;

    var end1Mm = parseFloat(grooveItem.end1);
    if (isNaN(end1Mm)) end1Mm = 0;
    var end2Mm = parseFloat(grooveItem.end2);
    if (isNaN(end2Mm)) end2Mm = 0;
    var distMm = parseFloat(grooveItem.distance);
    if (isNaN(distMm)) distMm = crossTotal / 2;
    var widthMm = parseFloat(grooveItem.width);
    if (isNaN(widthMm) || widthMm <= 0) widthMm = 20;

    if (runTotal > 0) {
        end1Mm = Math.max(0, Math.min(runTotal - 1, end1Mm));
        end2Mm = Math.max(0, Math.min(runTotal - 1, end2Mm));
    }
    if (crossTotal > 0) distMm = Math.max(0, Math.min(crossTotal - 1, distMm));

    var end1Px = runTotal > 0 ? end1Mm * (runPx / runTotal) : 0;
    var end2Px = runTotal > 0 ? end2Mm * (runPx / runTotal) : 0;
    var distPx = crossTotal > 0 ? distMm * (crossPx / crossTotal) : crossPx / 2;
    var thicknessPx = crossTotal > 0 ? Math.max(4, widthMm * (crossPx / crossTotal)) : 8;

    var barRect, end1Pt, end2Pt, distPt;

    var flipped = grooveItem.view === "B";

    if (!isVertical) {
        // Runs along the length; ends are measured from W1/W2, which a
        // top-to-bottom flip doesn't move. Only the L1/L2 distance flips.
        var barX1 = geo.x + end1Px;
        var barX2 = geo.right - end2Px;
        var barYCenter = grooveItem.distanceEdge === "L2"
            ? machiningYFromL2(geo, distPx, flipped)
            : machiningYFromL1(geo, distPx, flipped);
        barRect = { x: barX1, y: barYCenter - thicknessPx / 2, width: Math.max(0, barX2 - barX1), height: thicknessPx };
        end1Pt = { x: barX1, y: geo.bottom };
        end2Pt = { x: barX2, y: geo.bottom };
        distPt = { x: geo.x, y: barYCenter };
    } else {
        // Runs across the width, so it's the two ends that flip; the
        // W1/W2 distance is unaffected. Flipping swaps which end is
        // nearer the top, so the bar is built from min/max rather than
        // assuming end1 comes first.
        var yEnd1 = machiningYFromL1(geo, end1Px, flipped);
        var yEnd2 = machiningYFromL2(geo, end2Px, flipped);
        var barTop = Math.min(yEnd1, yEnd2);
        var barBottom = Math.max(yEnd1, yEnd2);
        var barXCenter = grooveItem.distanceEdge === "W2" ? (geo.right - distPx) : (geo.x + distPx);
        barRect = { x: barXCenter - thicknessPx / 2, y: barTop, width: thicknessPx, height: Math.max(0, barBottom - barTop) };
        end1Pt = { x: geo.x, y: yEnd1 };
        end2Pt = { x: geo.x, y: yEnd2 };
        distPt = { x: barXCenter, y: geo.y };
    }

    shapes.grooveBar.position({ x: barRect.x, y: barRect.y });
    shapes.grooveBar.size({ width: barRect.width, height: barRect.height });
    shapes.grooveBar.visible(true);

    var runLenMm = Math.max(0, Math.round(runTotal - end1Mm - end2Mm));
    shapes.grooveLengthLabel.text(runLenMm + "");
    if (!isVertical) {
        shapes.grooveLengthLabel.offsetX(shapes.grooveLengthLabel.width() / 2);
        shapes.grooveLengthLabel.offsetY(0);
        shapes.grooveLengthLabel.position({ x: barRect.x + barRect.width / 2, y: barRect.y + barRect.height + 6 });
    } else {
        shapes.grooveLengthLabel.offsetX(0);
        shapes.grooveLengthLabel.offsetY(shapes.grooveLengthLabel.height() / 2);
        shapes.grooveLengthLabel.position({ x: barRect.x + barRect.width + 6, y: barRect.y + barRect.height / 2 });
    }
    shapes.grooveLengthLabel.visible(true);

    // end1/end2 sit at the L2/W2-side edge (bottom/left), so they're
    // flipped to the opposite side from the angled-cut callouts' default.
    updateMachiningPositionLabel(shapes.grooveEnd1Label, end1Mm, end1Pt.x, end1Pt.y, isVertical, !isVertical);
    updateMachiningPositionLabel(shapes.grooveEnd2Label, end2Mm, end2Pt.x, end2Pt.y, isVertical, !isVertical);
    updateMachiningPositionLabel(shapes.grooveDistLabel, distMm, distPt.x, distPt.y, !isVertical, false);
}

// Draws the hinge markers, the gap dimension line between them, and a
// position callout per hole (box + arrow, reusing the same widget the
// angled-cut/groove callouts use) — the canvas equivalent of the size-driven
// hole count in the sidebar. Rebuilds hingeGroup's children from scratch
// each call rather than updating fixed shapes in place, since the hole
// count (and so the shape count) changes with the panel size.
var HINGE_ACCENT = "#2b78c8";
var HINGE_HOLE_R = 3.5;
var HINGE_HOLE_INSET = 8;      // hole marker, clear of the panel border
var HINGE_BRACKET_INSET = 26;  // gap bracket's run, inside the panel face
var HINGE_GAP_TEXT_GAP = 8;    // gap number, further inside than the run
var HINGE_TIP_GAP = 3;         // pointer tip, just clear of the edge
var HINGE_TRI_H = 11;
var HINGE_TRI_HALF_W = 8;
var HINGE_BOX_GAP = 1;

// Places every hinge shape for a given set of mm positions. Split out from
// the shape *creation* below so a drag can re-run just this — rebuilding
// the group mid-drag would destroy the very circle Konva is dragging.
function machiningHingeLayout(refs, ctx, positions) {
    // A point posMm along the drilled edge, offsetPx outward from it —
    // negative offsetPx reaches back onto the panel face.
    function pt(posMm, offsetPx) {
        var moving = ctx.originCoord + posMm * ctx.pxPerMm;
        var fixed = ctx.fixedCoord + ctx.outSign * offsetPx;
        return ctx.isVertical ? { x: fixed, y: moving } : { x: moving, y: fixed };
    }

    // A point offsetAlongPx further along the edge from `p` — used for the
    // pointer's base corners, which straddle the edge axis rather than the
    // outward axis.
    function alongFrom(p, offsetAlongPx) {
        return ctx.isVertical
            ? { x: p.x, y: p.y + offsetAlongPx }
            : { x: p.x + offsetAlongPx, y: p.y };
    }

    var last = positions.length - 1;
    var runA = pt(positions[0], -HINGE_BRACKET_INSET);
    var runB = pt(positions[last], -HINGE_BRACKET_INSET);
    refs.run.points([runA.x, runA.y, runB.x, runB.y]);

    positions.forEach(function (mm, i) {
        var legTop = pt(mm, -HINGE_BRACKET_INSET);
        var hole = pt(mm, -HINGE_HOLE_INSET);
        refs.legs[i].points([legTop.x, legTop.y, hole.x, hole.y]);
        refs.holes[i].position(hole);

        var tip = pt(mm, HINGE_TIP_GAP);
        var base = pt(mm, HINGE_TIP_GAP + HINGE_TRI_H);
        var b1 = alongFrom(base, -HINGE_TRI_HALF_W);
        var b2 = alongFrom(base, HINGE_TRI_HALF_W);
        refs.pointers[i].points([tip.x, tip.y, b1.x, b1.y, b2.x, b2.y]);

        var txt = refs.boxTexts[i];
        txt.text(String(Math.round(mm)));
        var bw = txt.width();
        var bh = txt.height();
        var near = pt(mm, HINGE_TIP_GAP + HINGE_TRI_H + HINGE_BOX_GAP);
        // `near` is the box's edge-facing side; step back by the full box
        // depth on whichever edge grows toward the panel.
        var bx = ctx.isVertical ? (ctx.outSign > 0 ? near.x : near.x - bw) : near.x - bw / 2;
        var by = ctx.isVertical ? near.y - bh / 2 : (ctx.outSign > 0 ? near.y : near.y - bh);
        refs.boxes[i].position({ x: bx, y: by });
        refs.boxes[i].size({ width: bw, height: bh });
        txt.position({ x: bx, y: by });
    });

    for (var g = 0; g < last; g++) {
        var gapText = refs.gapTexts[g];
        gapText.text(String(Math.round(positions[g + 1] - positions[g])));
        gapText.offsetX(gapText.width() / 2);
        gapText.offsetY(gapText.height() / 2);
        gapText.position(pt((positions[g] + positions[g + 1]) / 2,
            -(HINGE_BRACKET_INSET + HINGE_GAP_TEXT_GAP)));
    }
}

// Makes each hole marker draggable along its edge, fenced by
// machiningHingeBounds(). The clamp is applied twice by design:
// dragBoundFunc stops the marker at the limit so the pointer can never
// drag it somewhere illegal, and dragmove re-clamps the mm value it
// derives, so a rounding step at the boundary can't slip past the rule.
function machiningHingeBindInteractions(refs, ctx, positions, item, edgeLengthMm) {
    // Single commit path for both drag and typed edits, so a position can
    // only ever reach the item through the same clamp.
    function commit(index, mm) {
        positions[index] = machiningHingeClamp(positions, index, Math.round(mm), edgeLengthMm);
        item.positions = positions.slice();
        machiningHingeLayout(refs, ctx, positions);
        machiningLayer.batchDraw();
    }

    refs.holes.forEach(function (holeShape, index) {
        holeShape.draggable(true);

        holeShape.on("mouseenter", function () {
            machiningStage.container().style.cursor = ctx.isVertical ? "ns-resize" : "ew-resize";
        });
        holeShape.on("mouseleave", function () {
            machiningStage.container().style.cursor = "default";
        });

        holeShape.dragBoundFunc(function (pos) {
            var b = machiningHingeBounds(positions, index, edgeLengthMm);
            var lockedFixed = ctx.fixedCoord + ctx.outSign * -HINGE_HOLE_INSET;
            var moving = ctx.isVertical ? pos.y : pos.x;
            moving = Math.max(ctx.originCoord + b.lo * ctx.pxPerMm,
                Math.min(ctx.originCoord + b.hi * ctx.pxPerMm, moving));
            return ctx.isVertical
                ? { x: lockedFixed, y: moving }
                : { x: moving, y: lockedFixed };
        });

        holeShape.on("dragmove", function () {
            var moving = ctx.isVertical ? holeShape.y() : holeShape.x();
            commit(index, (moving - ctx.originCoord) / ctx.pxPerMm);
        });

        holeShape.on("dragend", function () {
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
    });

    // Clicking a position callout types the value instead of dragging for
    // it — the precise route to the same place, with the same limits. The
    // box and its number are separate nodes, so both carry the handler.
    refs.boxes.forEach(function (box, index) {
        [box, refs.boxTexts[index]].forEach(function (node) {
            node.on("mouseenter", function () {
                machiningStage.container().style.cursor = "pointer";
            });
            node.on("mouseleave", function () {
                machiningStage.container().style.cursor = "default";
            });
            node.on("click tap", function () {
                var b = machiningHingeBounds(positions, index, edgeLengthMm);
                promptMachiningPositionEdit(box, positions[index], b.lo, b.hi, function (val) {
                    commit(index, val);
                    saveMachiningAppliedItems();
                    renderMachiningAppliedList();
                });
            });
        });
    });
}

function updateMachiningHinge(hingeItem, geo) {
    var group = machiningShapes.hingeGroup;
    group.destroyChildren();

    if (!hingeItem) {
        group.visible(false);
        return;
    }

    var dims = machiningCurrentDims();
    var edge = hingeItem.edge || "L1";
    var isVertical = (edge === "W1" || edge === "W2");
    var edgeLengthMm = isVertical ? dims.width : dims.length;
    var positions = machiningHingeResolvedPositions(hingeItem, edgeLengthMm);

    if (!positions.length || !(geo.rectW > 0) || !(geo.rectH > 0)) {
        group.visible(false);
        return;
    }

    // Where the drilled edge sits, and which direction is "outward" (away
    // from the panel face) from it — everything is placed relative to those
    // two, so one code path serves all four edges.
    var ctx = {
        isVertical: isVertical,
        fixedCoord: edge === "L1" ? geo.y : edge === "L2" ? geo.bottom
            : edge === "W1" ? geo.x : geo.right,
        originCoord: isVertical ? geo.y : geo.x,
        pxPerMm: isVertical ? (geo.rectH / dims.width) : (geo.rectW / dims.length),
        outSign: (edge === "L1" || edge === "W1") ? -1 : 1
    };

    // Gap dimension: a bracket on the panel face — one run spanning the
    // outer holes with a leg dropping to every hole, so each gap number
    // reads against the two holes it measures between. Position callouts
    // sit outside: a solid pointer aimed at the hole, box behind it.
    var refs = {
        run: new Konva.Line({ stroke: HINGE_ACCENT, strokeWidth: 1.5 }),
        legs: [], holes: [], pointers: [], boxes: [], boxTexts: [], gapTexts: []
    };
    group.add(refs.run);

    positions.forEach(function () {
        var leg = new Konva.Line({ stroke: HINGE_ACCENT, strokeWidth: 1.5 });
        var pointer = new Konva.Line({ closed: true, fill: HINGE_ACCENT });
        var box = new Konva.Rect({ fill: "#fff", stroke: HINGE_ACCENT, strokeWidth: 1 });
        var boxText = new Konva.Text({
            fontSize: 11, fontFamily: "Arial, sans-serif", fill: "#222", padding: 6
        });
        // Marker added last so it stays above its own leg while dragging.
        var hole = new Konva.Circle({
            radius: HINGE_HOLE_R, fill: "#9a9a9a", stroke: "#5f5f5f", strokeWidth: 1
        });
        group.add(leg, pointer, box, boxText, hole);

        refs.legs.push(leg);
        refs.pointers.push(pointer);
        refs.boxes.push(box);
        refs.boxTexts.push(boxText);
        refs.holes.push(hole);
    });

    for (var g = 0; g < positions.length - 1; g++) {
        var gapText = new Konva.Text({
            fontSize: 11, fontStyle: "bold", fontFamily: "Arial, sans-serif",
            fill: HINGE_ACCENT, rotation: isVertical ? -90 : 0
        });
        group.add(gapText);
        refs.gapTexts.push(gapText);
    }

    machiningHingeLayout(refs, ctx, positions);
    machiningHingeBindInteractions(refs, ctx, positions, hingeItem, edgeLengthMm);

    group.visible(true);
}

// Run-axis mm of every hole, both clusters and positions flattened. The
// first cluster begins at `start` and the last ends the same distance in
// from the far end, with the rest spread evenly between — the same
// symmetric-ends spacing the hinge holes use.
// The numbers every shelf calculation starts from, parsed and floored once
// so the callers below stay readable. `span` is the width of one cluster,
// first hole to last.
function machiningShelfGeom(item) {
    var positions = Math.max(1, Math.round(machiningShelfNum(item.positions, MACHINING_SHELF_DEFAULT_POSITIONS)));
    var step = Math.max(MACHINING_SHELF_MIN_HOLE_GAP_MM,
        machiningShelfNum(item.step, MACHINING_SHELF_DEFAULT_STEP_MM));
    var clusters = Math.max(1, Math.round(machiningShelfNum(item.clusters, MACHINING_SHELF_DEFAULT_CLUSTERS)));
    return { positions: positions, step: step, clusters: clusters, span: (positions - 1) * step };
}

// The legal span for one cluster's start, given where its neighbours are.
// Same shape as machiningHingeBounds(): the end rules and the between-
// clusters rule are combined per cluster, so moving any one of them can
// never push another into an illegal spot.
function machiningShelfClusterBounds(starts, index, span, runMm) {
    var last = starts.length - 1;

    var lo = index === 0
        ? MACHINING_SHELF_MIN_END_MM
        : starts[index - 1] + span + MACHINING_SHELF_MIN_CLUSTER_GAP_MM;
    var hi = index === last
        ? runMm - MACHINING_SHELF_MIN_END_MM - span
        : starts[index + 1] - MACHINING_SHELF_MIN_CLUSTER_GAP_MM - span;

    // A run too short for this many clusters leaves no legal span — pin to
    // the lower bound rather than let one cross its neighbour.
    if (hi < lo) hi = lo;
    return { lo: lo, hi: hi };
}

function machiningShelfClampCluster(starts, index, valueMm, span, runMm) {
    var b = machiningShelfClusterBounds(starts, index, span, runMm);
    return Math.min(b.hi, Math.max(b.lo, valueMm));
}

function machiningShelfClusterStartsValid(starts, span, runMm) {
    return starts.every(function (v, i) {
        if (typeof v !== "number" || !isFinite(v)) return false;
        var b = machiningShelfClusterBounds(starts, i, span, runMm);
        return v >= b.lo - 0.5 && v <= b.hi + 0.5;
    });
}

// Shortest run that can hold N clusters: the end margin at both ends, each
// cluster's own width, and the clear gap between every neighbouring pair.
function machiningShelfMinRunFor(clusters, span) {
    return 2 * MACHINING_SHELF_MIN_END_MM + clusters * span +
        (clusters - 1) * MACHINING_SHELF_MIN_CLUSTER_GAP_MM;
}

// How many clusters the run can hold at all, ignoring where they currently
// sit — this drives the count on offer in the panel.
function machiningShelfMaxClusters(item, runMm) {
    var g = machiningShelfGeom(item);
    var pitch = g.span + MACHINING_SHELF_MIN_CLUSTER_GAP_MM;
    var usable = runMm - 2 * MACHINING_SHELF_MIN_END_MM - g.span;
    if (!(usable >= 0) || !(pitch > 0)) return 1;
    return Math.max(1, Math.min(MACHINING_SHELF_MAX_CLUSTERS, 1 + Math.floor(usable / pitch)));
}

// Set default cluster positions with proper spacing. Used for 5mm Ø diameter hole,
// Blum 35mm Screw-On, and Blum 35mm INSERTA.
function machiningShelfDefaultClusterStarts(item, runMm) {
    var g = machiningShelfGeom(item);
    var clusters = Math.min(g.clusters, machiningShelfMaxClusters(item, runMm));

    // The 100mm preferred start only applies when the run is long enough
    // for it — on a short run it would push the cluster's far hole inside
    // the end margin, so it gives way to the margin itself.
    var lastAllowed = runMm - MACHINING_SHELF_MIN_END_MM - g.span;
    var first = Math.max(MACHINING_SHELF_MIN_END_MM,
        Math.min(MACHINING_SHELF_DEFAULT_START_MM, lastAllowed));
    var last = Math.max(first, Math.min(lastAllowed, runMm - first - g.span));

    // With the ends fixed, the remaining clusters have to fit between them
    // at the minimum gap; pull the first end in if they don't.
    if (clusters > 1 && (last - first) / (clusters - 1) < g.span + MACHINING_SHELF_MIN_CLUSTER_GAP_MM) {
        first = MACHINING_SHELF_MIN_END_MM;
        last = Math.max(first, lastAllowed);
    }

    var out = [];
    for (var c = 0; c < clusters; c++) {
        out.push(clusters === 1 ? first : first + c * (last - first) / (clusters - 1));
    }
    return out;
}

// Keep the user's dragged cluster positions if they are still valid.
// If the settings or panel size make them invalid, use the default positions instead.
function machiningShelfResolvedClusterStarts(item, runMm) {
    var g = machiningShelfGeom(item);
    var clusters = Math.min(g.clusters, machiningShelfMaxClusters(item, runMm));

    // No legal arrangement exists on a run this short.
    if (!(runMm > 0) || g.span > runMm ||
        !(runMm >= machiningShelfMinRunFor(clusters, g.span))) {
        return [];
    }

    var stored = item.clusterStarts;
    if (Array.isArray(stored) && stored.length === clusters &&
        machiningShelfClusterStartsValid(stored, g.span, runMm)) {
        return stored.slice();
    }
    return machiningShelfDefaultClusterStarts(item, runMm);
}

function machiningShelfHolePositions(item, runMm) {
    var g = machiningShelfGeom(item);
    var out = [];
    machiningShelfResolvedClusterStarts(item, runMm).forEach(function (base) {
        for (var i = 0; i < g.positions; i++) out.push(base + i * g.step);
    });
    return out;
}

// Draw the holes, spacing, cluster positions and drag controls for each cluster.
// Used for 5mm Ø diameter hole, Blum 35mm Screw-On, and Blum 35mm INSERTA.
function updateMachiningShelf(shelfItem, geo) {
    var group = machiningShapes.shelfGroup;
    group.destroyChildren();

    if (!shelfItem) {
        group.visible(false);
        return;
    }

    var dims = machiningCurrentDims();
    var alongLength = (shelfItem.edge || "L1-L2") !== "W1-W2";
    var runMm = alongLength ? dims.length : dims.width;
    var crossMm = alongLength ? dims.width : dims.length;

    var g = machiningShelfGeom(shelfItem);
    var starts = machiningShelfResolvedClusterStarts(shelfItem, runMm);
    var holes = machiningShelfHolePositions(shelfItem, runMm);
    if (!holes.length || !(runMm > 0) || !(crossMm > 0) ||
        !(geo.rectW > 0) || !(geo.rectH > 0)) {
        group.visible(false);
        return;
    }

    var runOrigin = alongLength ? geo.x : geo.y;
    var runPxPerMm = alongLength ? (geo.rectW / dims.length) : (geo.rectH / dims.width);
    var crossPxPerMm = alongLength ? (geo.rectH / dims.width) : (geo.rectW / dims.length);
    // Row one is inset from the near edge (L1 / W1), row two from the far
    // one (L2 / W2), so they close in from opposite sides.
    var crossNear = alongLength ? geo.y : geo.x;
    var crossFar = alongLength ? geo.bottom : geo.right;
    var row1Px = crossNear + machiningShelfNum(shelfItem.row1, MACHINING_SHELF_DEFAULT_ROW_MM) * crossPxPerMm;
    var row2Px = crossFar - machiningShelfNum(shelfItem.row2, MACHINING_SHELF_DEFAULT_ROW_MM) * crossPxPerMm;

    // A point at runMm along the run, on the given absolute cross pixel.
    function pt(mm, crossPx) {
        var along = runOrigin + mm * runPxPerMm;
        return alongLength ? { x: along, y: crossPx } : { x: crossPx, y: along };
    }

    // Both rows of pin holes.
    [row1Px, row2Px].forEach(function (crossPx) {
        holes.forEach(function (mm) {
            var p = pt(mm, crossPx);
            group.add(new Konva.Circle({
                x: p.x, y: p.y, radius: 2.2,
                fill: "#9a9a9a", stroke: "#5f5f5f16", strokeWidth: 0
            }));
        });
    });

    // Gap dimensions along row two: one segment per clear run between
    // consecutive clusters, then a final one from the last hole to the far
    // end. The line sits just inside row two with a leg dropping to each
    // hole it measures from, the same bracket idiom as the hinge holes.
    var BRACKET_INSET = 14;
    var bracketCross = row2Px - BRACKET_INSET;

    function addBracket(fromMm, toMm) {
        var a = pt(fromMm, bracketCross);
        var b = pt(toMm, bracketCross);
        group.add(new Konva.Line({
            points: [a.x, a.y, b.x, b.y], stroke: HINGE_ACCENT, strokeWidth: 1.5
        }));
        [[a, fromMm], [b, toMm]].forEach(function (pair) {
            var leg = pt(pair[1], row2Px);
            group.add(new Konva.Line({
                points: [pair[0].x, pair[0].y, leg.x, leg.y],
                stroke: HINGE_ACCENT, strokeWidth: 1.5
            }));
        });

        var text = new Konva.Text({
            text: String(Math.round(toMm - fromMm)), fontSize: 11, fontStyle: "bold",
            fontFamily: "Arial, sans-serif", fill: HINGE_ACCENT,
            rotation: alongLength ? 0 : -90
        });
        text.offsetX(text.width() / 2);
        text.offsetY(text.height() / 2);
        var mid = pt((fromMm + toMm) / 2, bracketCross);
        text.position({
            x: mid.x - (alongLength ? 0 : 9),
            y: mid.y - (alongLength ? 9 : 0)
        });
        group.add(text);
    }

    starts.forEach(function (startMm, i) {
        var clusterEnd = startMm + g.span;
        // Between this cluster and the next, or from the last one to the
        // far end of the run.
        addBracket(clusterEnd, i < starts.length - 1 ? starts[i + 1] : runMm);
    });

    // One position callout per cluster, outside the panel past row two's
    // edge — pointer aimed at the cluster's first hole, box behind it.
    var calloutEdge = crossFar;
    function outward(mm, offsetPx) {
        var along = runOrigin + mm * runPxPerMm;
        var cross = calloutEdge + offsetPx;
        return alongLength ? { x: along, y: cross } : { x: cross, y: along };
    }
    function alongFrom(p, d) {
        return alongLength ? { x: p.x + d, y: p.y } : { x: p.x, y: p.y + d };
    }

    function commitCluster(index, mm) {
        starts[index] = machiningShelfClampCluster(starts, index, Math.round(mm), g.span, runMm);
        shelfItem.clusterStarts = starts.slice();
        updateMachiningShelf(shelfItem, geo);
        machiningLayer.batchDraw();
    }

    starts.forEach(function (startMm, index) {
        var tip = outward(startMm, HINGE_TIP_GAP);
        var base = outward(startMm, HINGE_TIP_GAP + HINGE_TRI_H);
        group.add(new Konva.Line({
            points: [tip.x, tip.y,
                alongFrom(base, -HINGE_TRI_HALF_W).x, alongFrom(base, -HINGE_TRI_HALF_W).y,
                alongFrom(base, HINGE_TRI_HALF_W).x, alongFrom(base, HINGE_TRI_HALF_W).y],
            closed: true, fill: HINGE_ACCENT
        }));

        var text = new Konva.Text({
            text: String(Math.round(startMm)), fontSize: 11,
            fontFamily: "Arial, sans-serif", fill: "#222", padding: 6
        });
        var bw = text.width();
        var bh = text.height();
        var near = outward(startMm, HINGE_TIP_GAP + HINGE_TRI_H + HINGE_BOX_GAP);
        var bx = alongLength ? near.x - bw / 2 : near.x;
        var by = alongLength ? near.y : near.y - bh / 2;
        var box = new Konva.Rect({
            x: bx, y: by, width: bw, height: bh,
            fill: "#fff", stroke: HINGE_ACCENT, strokeWidth: 1
        });
        text.position({ x: bx, y: by });
        group.add(box, text);

        var bounds = machiningShelfClusterBounds(starts, index, g.span, runMm);

        // Invisible grab area over the cluster's first hole — the hole dots
        // are only 2.2px, too small to be a comfortable drag target.
        var first = pt(startMm, row2Px);
        var handle = new Konva.Circle({
            x: first.x, y: first.y, radius: 7, fill: "transparent", draggable: true
        });
        handle.dragBoundFunc(function (pos) {
            var along = alongLength ? pos.x : pos.y;
            along = Math.max(runOrigin + bounds.lo * runPxPerMm,
                Math.min(runOrigin + bounds.hi * runPxPerMm, along));
            return alongLength ? { x: along, y: first.y } : { x: first.x, y: along };
        });
        handle.on("mouseenter", function () {
            machiningStage.container().style.cursor = alongLength ? "ew-resize" : "ns-resize";
        });
        handle.on("mouseleave", function () {
            machiningStage.container().style.cursor = "default";
        });
        handle.on("dragmove", function () {
            var along = alongLength ? handle.x() : handle.y();
            commitCluster(index, (along - runOrigin) / runPxPerMm);
        });
        handle.on("dragend", function () {
            saveMachiningAppliedItems();
            renderMachiningAppliedList();
        });
        group.add(handle);

        [box, text].forEach(function (node) {
            node.on("mouseenter", function () {
                machiningStage.container().style.cursor = "pointer";
            });
            node.on("mouseleave", function () {
                machiningStage.container().style.cursor = "default";
            });
            node.on("click tap", function () {
                promptMachiningPositionEdit(box, startMm, bounds.lo, bounds.hi, function (val) {
                    commitCluster(index, val);
                    saveMachiningAppliedItems();
                    renderMachiningAppliedList();
                });
            });
        });
    });

    group.visible(true);
}

// Redraw the board and all selected machining options on the canvas.
// Used for all machining options.
function redrawMachiningCanvas() {
    initMachiningStage();
    if (!machiningStage) return;

    var lengthRaw = document.getElementById("mLength") ? document.getElementById("mLength").textContent : "-";
    var widthRaw = document.getElementById("mWidth") ? document.getElementById("mWidth").textContent : "-";
    var angledItem = machiningAppliedItems.filter(function (i) { return i.option === "angled-cut"; })[0] || null;
    var grooveItem = machiningAppliedItems.filter(function (i) { return i.option === "groove"; })[0] || null;
    // Matched on behaviour, not slug — any option set to "Hinge holes" in
    // wp-admin gets drawn here, the same lookup buildMachiningAppliedItemHTML()
    // already uses to pick its detail panel.
    var hingeItem = machiningAppliedItems.filter(function (i) {
        var opt = machiningOptionBySlug(i.option);
        return opt && opt.behaviour === "hinge-holes";
    })[0] || null;
    var shelfItem = machiningAppliedItems.filter(function (i) {
        var opt = machiningOptionBySlug(i.option);
        return opt && opt.behaviour === "shelf-holes";
    })[0] || null;

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

    // The panel's outline points are set by updateMachiningNotch() below —
    // full rectangle when there's no cut, corner-removed polygon when
    // there is. It's called on every redraw and during callout drags, so
    // the outline stays in sync with the cut in both cases.

    // "A side" (default) shows L1 at the top; "B side" swaps L1/L2 — kept in
    // sync with whichever item's A/B toggle and the "Panel shows" face box
    // below (angled-cut and groove share the same view concept).
    var flipLength = !!((angledItem && angledItem.view === "B") || (grooveItem && grooveItem.view === "B") || (hingeItem && hingeItem.view === "B"));

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
    updateMachiningGroove(grooveItem, geo);
    updateMachiningHinge(hingeItem, geo);
    updateMachiningShelf(shelfItem, geo);

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
        .filter(function (item) { return item.option === "angled-cut" || item.option === "groove"; })
        .map(function (item) {
            if (item.option === "groove") {
                var glabels = machiningGrooveLabels(item.edge);
                return {
                    type: "Groove cut",
                    side: item.edge,
                    detail: (item.width || "-") + "mm wide x " + (item.depth || "-") + "mm deep, " +
                        glabels.end1 + " " + (item.end1 || "-") + "mm, " + glabels.end2 + " " + (item.end2 || "-") + "mm, " +
                        "From " + (item.distanceEdge || "-") + " " + (item.distance || "-") + "mm"
                };
            }
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

// Which edge finishes the chosen tape supports — the "Radius edge finish" /
// "Square edge finish" toggles on the Edge Tape CPT. Only an explicit false
// disables one: an unselected or unrecognised tape leaves both available,
// matching the REST layer, which reports true for tapes saved before those
// fields existed rather than silently offering nothing.
function machiningTapeFinishes(code) {
    var both = { radius: true, square: true };
    if (!code) return both;
    var tape = (window.cutlistEdgeTapes || []).filter(function (t) { return t.code === code; })[0];
    if (!tape) return both;
    return {
        radius: tape.radiusEdgeFinish !== false,
        square: tape.squareEdgeFinish !== false
    };
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

function buildGroovePreviewSVG(item) {
    var isBack = item.view === "B";
    var isVertical = item.edge === "W1-W2";
    var grooveLine = isVertical
        ? '<line x1="95" y1="28" x2="99" y2="78" stroke="#2b78c8" stroke-width="3" stroke-linecap="round"></line>'
        : '<line x1="42" y1="66" x2="108" y2="70" stroke="#2b78c8" stroke-width="3" stroke-linecap="round"></line>';
    return '<svg viewBox="0 0 140 100" class="machining-preview-svg">' +
        '<polygon class="preview-panel" points="25,20 115,20 125,85 35,85"></polygon>' +
        grooveLine +
        '<text class="preview-label" x="70" y="45" text-anchor="middle">' + (isBack ? "B SIDE" : "A SIDE") + "</text>" +
        '<text x="65" y="14" text-anchor="middle">L1</text>' +
        '<text x="65" y="97" text-anchor="middle">L2</text>' +
        '<text x="14" y="55">W1</text>' +
        '<text x="122" y="55">W2</text>' +
        "</svg>";
}

// HINGE HOLES (Blum 35mm Screw-On / INSERTA)
//
// A panel must be at least this square in both directions before hinges
// can be drilled at all.
var MACHINING_HINGE_MIN_PANEL_MM = 150;

// Hinge count steps with the length of the edge being drilled:
//   150-399 -> 2, 400-499 -> 3, 500-599 -> 4, 600-699 -> 5, ...
// i.e. 2 up to 399, then one more per whole 100mm from 400 up. Returns 0
// when the edge is too short to take hinges at all.
function machiningHingeHoleCount(edgeLengthMm) {
    if (!(edgeLengthMm >= MACHINING_HINGE_MIN_PANEL_MM)) return 0;
    if (edgeLengthMm < 400) return 2;
    return 3 + Math.floor((edgeLengthMm - 400) / 100);
}

// The count to *start* a new item on, which is not the maximum. The rule
// above is a ceiling — what the edge can physically take at the 100mm
// minimum spacing — so a 1000mm edge allows 9, and defaulting to that
// looks nothing like a real door. This aims at roughly one hinge per
// 500mm, which lands on the counts a fitter would actually specify:
// 1000mm -> 3, 2000mm -> 5. The select still offers the full range up to
// the ceiling for anyone who wants more.
function machiningHingeDefaultHoleCount(edgeLengthMm) {
    var max = machiningHingeHoleCount(edgeLengthMm);
    if (max < 2) return 0;
    return Math.max(2, Math.min(max, 1 + Math.ceil(edgeLengthMm / 500)));
}

// Hinges run along the edge being drilled, so an L1/L2 edge is measured
// along the panel's length and a W1/W2 edge along its width.
function machiningHingeEdgeLength(edge, dims) {
    return (edge === "W1" || edge === "W2") ? dims.width : dims.length;
}

// How far the first/last hinge sits in from each end of the edge.
var MACHINING_HINGE_END_OFFSET_MM = 100;

// Evenly spaces N holes along an edge of length L, O in from each end:
//   gap = (L - 2*O) / (N - 1)
//   position(k) = O + (k - 1) * gap,  k = 1..N
// e.g. L=1000, N=3, O=100 -> gap 400, positions [100, 500, 900] — the
// worked example this was specified against. Returns [] for N < 2 (no
// gap to divide) or a length too short for the offsets to fit.
function machiningHingePositions(edgeLengthMm, holeCount, offsetMm) {
    offsetMm = offsetMm == null ? MACHINING_HINGE_END_OFFSET_MM : offsetMm;
    if (!(holeCount >= 2) || !(edgeLengthMm > offsetMm * 2)) return [];

    var gap = (edgeLengthMm - 2 * offsetMm) / (holeCount - 1);
    var positions = [];
    for (var k = 1; k <= holeCount; k++) {
        positions.push(offsetMm + (k - 1) * gap);
    }
    return positions;
}

// Drag limits, all in mm along the drilled edge.
var MACHINING_HINGE_MIN_END_MM = 50;   // closest an end hole may sit to its end
var MACHINING_HINGE_MAX_END_MM = 300;  // furthest an end hole may sit from it
var MACHINING_HINGE_MIN_GAP_MM = 100;  // closest two adjacent holes may sit

// The legal mm span for one hole, given where its neighbours currently are.
//
// The end rules (50..300 from the near end) and the spacing rule (>=100mm
// between adjacent holes) are combined here rather than applied separately,
// because they overlap at the ends: dragging the first hole right must also
// respect the second hole's own 100mm clearance, or moving one hole would
// silently invalidate its neighbour. Expressing every hole's limits in terms
// of its neighbours keeps the whole set valid no matter which one moves.
function machiningHingeBounds(positions, index, edgeLengthMm) {
    var last = positions.length - 1;

    var lo = index === 0
        ? MACHINING_HINGE_MIN_END_MM
        : positions[index - 1] + MACHINING_HINGE_MIN_GAP_MM;
    var hi = index === last
        ? edgeLengthMm - MACHINING_HINGE_MIN_END_MM
        : positions[index + 1] - MACHINING_HINGE_MIN_GAP_MM;

    if (index === 0) hi = Math.min(hi, MACHINING_HINGE_MAX_END_MM);
    if (index === last) lo = Math.max(lo, edgeLengthMm - MACHINING_HINGE_MAX_END_MM);

    // An edge too short for this many holes can leave no legal span at all
    // — pin to the lower bound rather than let the hole cross its neighbour.
    if (hi < lo) hi = lo;
    return { lo: lo, hi: hi };
}

function machiningHingeClamp(positions, index, valueMm, edgeLengthMm) {
    var b = machiningHingeBounds(positions, index, edgeLengthMm);
    return Math.min(b.hi, Math.max(b.lo, valueMm));
}

// Shortest edge that can hold N holes at all: a 50mm minimum at each end
// plus a 100mm minimum between every adjacent pair.
function machiningHingeMinEdgeFor(holeCount) {
    return 2 * MACHINING_HINGE_MIN_END_MM + MACHINING_HINGE_MIN_GAP_MM * (holeCount - 1);
}

// The nominal 100mm end offset only fits on an edge long enough for it —
// below roughly 300mm, two holes 100mm in from each end would sit almost
// on top of each other (a 201mm edge gave [100, 101]). Pulling the offset
// in toward the 50mm end minimum keeps the 100mm gap intact instead.
function machiningHingeDefaultOffset(edgeLengthMm, holeCount) {
    var widest = (edgeLengthMm - MACHINING_HINGE_MIN_GAP_MM * (holeCount - 1)) / 2;
    return Math.max(MACHINING_HINGE_MIN_END_MM,
        Math.min(MACHINING_HINGE_END_OFFSET_MM, widest));
}

// Tolerance absorbs the rounding done when a drag commits whole mm.
function machiningHingePositionsValid(positions, edgeLengthMm) {
    return positions.every(function (v, i) {
        if (typeof v !== "number" || !isFinite(v)) return false;
        var b = machiningHingeBounds(positions, i, edgeLengthMm);
        return v >= b.lo - 0.5 && v <= b.hi + 0.5;
    });
}

// Hole positions are the evenly-spaced defaults until the user drags one,
// after which the dragged set is stored on the item. Anything that changes
// the edge being drilled or the hole count leaves those stored positions
// meaningless, so they're re-validated here and silently dropped back to
// the computed spacing rather than needing every such handler to clear them.
function machiningHingeResolvedPositions(item, edgeLengthMm) {
    var holeCount = Math.max(2, Number(item.holes) || 2);
    var stored = item.positions;

    // No legal arrangement exists on an edge this short, so draw nothing
    // rather than a layout that breaks its own rules.
    if (!(edgeLengthMm >= machiningHingeMinEdgeFor(holeCount))) return [];

    if (Array.isArray(stored) && stored.length === holeCount &&
        machiningHingePositionsValid(stored, edgeLengthMm)) {
        return stored.slice();
    }
    return machiningHingePositions(edgeLengthMm, holeCount,
        machiningHingeDefaultOffset(edgeLengthMm, holeCount));
}

function buildHingeHolesDetailHTML(item, index, label) {
    var dims = machiningCurrentDims();
    var edgeLength = machiningHingeEdgeLength(item.edge, dims);
    var maxHoles = machiningHingeHoleCount(edgeLength);

    var edgesHTML = ["L1", "L2", "W1", "W2"].map(function (edge) {
        return '<label class="machining-hinge-edge">' +
            '<input type="radio" name="machiningHingeEdge' + index + '" value="' + edge + '"' +
            (item.edge === edge ? " checked" : "") + ">" +
            "<span>" + edge + "</span></label>";
    }).join("");

    // The panel's own size decides how many hinges fit, so the choices are
    // built from it rather than being a fixed list.
    var countHTML;
    if (maxHoles < 2) {
        countHTML = '<div class="machining-hinge-note">' +
            (isNaN(edgeLength)
                ? "Enter the panel's length and width first."
                : "This edge is " + Math.round(edgeLength) + "mm — hinges need at least " +
                  MACHINING_HINGE_MIN_PANEL_MM + "mm.") +
            "</div>";
    } else {
        var opts = "";
        for (var n = 2; n <= maxHoles; n++) {
            opts += '<option value="' + n + '"' + (Number(item.holes) === n ? " selected" : "") + ">" + n + "</option>";
        }
        countHTML = '<select class="machining-select machining-hinge-count">' + opts + "</select>";
    }

    return "" +
        '<div class="machining-applied-item machining-applied-item--hinge" data-index="' + index + '">' +
        '<div class="machining-applied-chip">' +
        '<span class="machining-applied-chip-label">' + panelSummaryEscape(label) + " on " + item.edge + "</span>" +
        '<button type="button" class="machining-applied-remove" aria-label="Remove">&times;</button>' +
        "</div>" +
        '<div class="machining-applied-detail">' +
        '<div class="machining-hinge-row">' +
        '<div class="machining-detail-label">Holes drilled along edge:</div>' +
        '<div class="machining-hinge-edges">' + edgesHTML + "</div>" +
        "</div>" +
        '<div class="machining-hinge-row">' +
        '<div class="machining-detail-label">Number of hinge holes:</div>' +
        countHTML +
        "</div>" +
        '<div class="machining-hinge-row machining-hinge-view-row">' +
        '<div class="machining-detail-label">Holes drilled on</div>' +
        '<div class="machining-toggle-row" data-role="hinge-view">' +
        // Hinge cups are bored into the back of the panel, so A side is
        // shown for orientation but never selectable.
        '<button type="button" class="machining-toggle-btn" data-view="A" disabled ' +
        'title="Hinge holes are drilled on the back face">A side<br><small>Front face</small></button>' +
        '<button type="button" class="machining-toggle-btn selected" data-view="B">B side<br><small>Back face</small></button>' +
        "</div>" +
        "</div>" +
        '<div class="machining-preview-box">' + buildHingePreviewSVG(item) + "</div>" +
        '<button type="button" class="machining-save-btn">Save</button>' +
        "</div>" +
        "</div>";
}

function buildHingePreviewSVG(item) {
    var edge = item.edge || "L1";
    // Dots sit along whichever edge is selected, on the B (back) face.
    var dots = { L1: [], L2: [], W1: [], W2: [] };
    var n = Math.max(2, Number(item.holes) || 2);
    for (var i = 0; i < n; i++) {
        var t = (i + 1) / (n + 1);
        dots.L1.push([25 + 90 * t, 21]);
        dots.L2.push([35 + 90 * t, 84]);
        dots.W1.push([26 + 10 * t, 20 + 65 * t]);
        dots.W2.push([115 + 10 * t, 20 + 65 * t]);
    }
    var marks = (dots[edge] || []).map(function (p) {
        return '<circle cx="' + p[0].toFixed(1) + '" cy="' + p[1].toFixed(1) + '" r="2.6" fill="#2b78c8"></circle>';
    }).join("");

    return '<svg viewBox="0 0 140 100" class="machining-preview-svg">' +
        '<polygon class="preview-panel" points="25,20 115,20 125,85 35,85"></polygon>' +
        '<text class="preview-label" x="75" y="56" text-anchor="middle">B SIDE</text>' +
        '<text x="65" y="14" text-anchor="middle">L1</text>' +
        '<text x="70" y="97" text-anchor="middle">L2</text>' +
        '<text x="14" y="55">W1</text>' +
        '<text x="122" y="55">W2</text>' +
        marks +
        "</svg>";
}

// SHELF HOLES (5mm / 7.5mm diameter shelf-pin rows)
//
// Two rows of pin holes run parallel to the chosen edge pair, one inset
// from each edge of that pair. Each row carries N clusters, and a cluster
// is `positions` holes spaced `step` mm apart — 32mm being the System 32
// standard the default follows.
var MACHINING_SHELF_DEFAULT_ROW_MM = 50;
// Where the first cluster starts along the run. Not a panel field — each
// cluster's start is set by dragging its marker or typing into its canvas
// callout, and this is only the value a fresh layout begins from.
var MACHINING_SHELF_DEFAULT_START_MM = 100;
var MACHINING_SHELF_DEFAULT_STEP_MM = 32;
var MACHINING_SHELF_DEFAULT_POSITIONS = 2;
var MACHINING_SHELF_DEFAULT_CLUSTERS = 1;
var MACHINING_SHELF_MAX_CLUSTERS = 12;

// Drilling limits, all in mm. MIN_END and MIN_SIDE fence the pattern away
// from the panel's edges (along the run and across it respectively);
// MIN_HOLE_GAP is the closest two holes inside one cluster may sit, which
// is the floor on Step; MIN_CLUSTER_GAP is the clear board one cluster
// must leave the next.
var MACHINING_SHELF_HOLE_DEPTH_MM = 8;
var MACHINING_SHELF_MIN_END_MM = 50;
var MACHINING_SHELF_MIN_SIDE_MM = 20;
var MACHINING_SHELF_MIN_HOLE_GAP_MM = 25;
var MACHINING_SHELF_MIN_CLUSTER_GAP_MM = 75;
var MACHINING_SHELF_MAX_POSITIONS = 50;
var MACHINING_SHELF_MAX_STEP_MM = 500;

// An "L1-L2" pair means the rows run along the panel's length and their
// inset is measured across its width from L1/L2; "W1-W2" is the reverse.
// Mirrors machiningGrooveLabels()'s convention for the same edge pairs.
function machiningShelfAxes(edge, dims) {
    if (edge === "W1-W2") {
        return {
            run: dims.width, cross: dims.length,
            row1Label: "Row one from W1", row2Label: "Row two from W2"
        };
    }
    return {
        run: dims.length, cross: dims.width,
        row1Label: "Row one from L1", row2Label: "Row two from L2"
    };
}

function machiningShelfNum(value, fallback) {
    var n = parseFloat(value);
    return isFinite(n) ? n : fallback;
}

function buildShelfHolesDetailHTML(item, index, label) {
    var dims = machiningCurrentDims();
    var axes = machiningShelfAxes(item.edge, dims);
    var crossMax = !isNaN(axes.cross) ? axes.cross - 1 : 9998;
    var runMax = !isNaN(axes.run) ? axes.run - 1 : 9998;

    var edgesHTML = ["L1-L2", "W1-W2"].map(function (pair) {
        return '<label class="machining-shelf-edge">' +
            '<input type="radio" name="machiningShelfEdge' + index + '" value="' + pair + '"' +
            (item.edge === pair ? " checked" : "") + ">" +
            "<span>" + pair + "</span></label>";
    }).join("");

    // Only offer counts the run can actually take at the 25mm minimum
    // between clusters — same approach as the hinge count, which is built
    // from what the edge allows rather than a fixed list.
    var maxClusters = isNaN(axes.run) ? 1 : machiningShelfMaxClusters(item, axes.run);
    var clusterOpts = "";
    for (var c = 1; c <= maxClusters; c++) {
        clusterOpts += '<option value="' + c + '"' +
            (Number(item.clusters) === c ? " selected" : "") + ">" + c + "</option>";
    }

    function field(fieldName, fieldLabel, value, min, max) {
        return '<div class="machining-offset-field"><label>' + fieldLabel + "</label>" +
            '<input type="text" class="machining-offset-input" data-field="' + fieldName + '"' +
            ' data-min="' + min + '" data-max="' + max + '" value="' + value + '"></div>';
    }

    // A cluster can be configured so it can't be drilled at all — wider
    // than the panel, or too many clusters for the run. Say why rather
    // than leaving a blank canvas.
    var geom = machiningShelfGeom(item);
    var minRun = machiningShelfMinRunFor(Math.min(geom.clusters, maxClusters), geom.span);
    var warning = "";
    if (!isNaN(axes.run)) {
        if (geom.span > axes.run) {
            warning = "A cluster of " + geom.positions + " at " + Math.round(geom.step) +
                "mm spans " + Math.round(geom.span) + "mm, wider than this " +
                Math.round(axes.run) + "mm run. Reduce the step or the number of positions.";
        } else if (axes.run < minRun) {
            warning = "This " + Math.round(axes.run) + "mm run is too short for " +
                Math.min(geom.clusters, maxClusters) + " cluster(s) — " +
                Math.round(minRun) + "mm needed at these limits.";
        }
    }

    var noteHTML = warning
        ? '<div class="machining-hinge-note">' + warning + "</div>"
        : '<div class="machining-groove-note">Notes:<br>' +
          "Hole depth: " + MACHINING_SHELF_HOLE_DEPTH_MM + "mm<br>" +
          "Min distance from end: " + MACHINING_SHELF_MIN_END_MM + "mm<br>" +
          "Min distance from side: " + MACHINING_SHELF_MIN_SIDE_MM + "mm<br>" +
          "Min distance between holes: " + MACHINING_SHELF_MIN_HOLE_GAP_MM + "mm<br>" +
          "Min distance between clusters: " + MACHINING_SHELF_MIN_CLUSTER_GAP_MM + "mm" +
          "</div>";

    return "" +
        '<div class="machining-applied-item machining-applied-item--shelf" data-index="' + index + '">' +
        '<div class="machining-applied-chip">' +
        '<span class="machining-applied-chip-label">' + panelSummaryEscape(label) + " along " + item.edge + "</span>" +
        '<button type="button" class="machining-applied-remove" aria-label="Remove">&times;</button>' +
        "</div>" +
        '<div class="machining-applied-detail">' +

        '<div class="machining-shelf-row">' +
        '<div class="machining-detail-label">Holes drilled along edge:</div>' +
        '<div class="machining-shelf-edges">' + edgesHTML + "</div>" +
        "</div>" +

        '<div class="machining-shelf-row">' +
        '<div class="machining-detail-label">Distance:</div>' +
        '<div class="machining-offset-row">' +
        field("row1", axes.row1Label, item.row1 == null ? "" : item.row1, MACHINING_SHELF_MIN_SIDE_MM, crossMax) +
        field("row2", axes.row2Label, item.row2 == null ? "" : item.row2, MACHINING_SHELF_MIN_SIDE_MM, crossMax) +
        "</div>" +
        "</div>" +

        '<div class="machining-shelf-row">' +
        '<div class="machining-detail-label">Cluster size:</div>' +
        '<div class="machining-offset-row">' +
        field("step", "Step", item.step == null ? "" : item.step, MACHINING_SHELF_MIN_HOLE_GAP_MM, MACHINING_SHELF_MAX_STEP_MM) +
        field("positions", "Positions", item.positions == null ? "" : item.positions, 1, MACHINING_SHELF_MAX_POSITIONS) +
        "</div>" +
        "</div>" +

        '<div class="machining-shelf-row">' +
        '<div class="machining-detail-label">Number of clusters:</div>' +
        '<select class="machining-select machining-shelf-clusters">' + clusterOpts + "</select>" +
        "</div>" +

        '<div class="machining-detail-label machining-shelf-view-label">Holes drilled on:</div>' +
        '<div class="machining-toggle-row" data-role="shelf-view">' +
        '<button type="button" class="machining-toggle-btn' + (item.view === "A" ? " selected" : "") +
        '" data-view="A">A side<br><small>Front face</small></button>' +
        '<button type="button" class="machining-toggle-btn' + (item.view === "B" ? " selected" : "") +
        '" data-view="B">B side<br><small>Back face</small></button>' +
        '<button type="button" class="machining-toggle-btn' + (item.view === "AB" ? " selected" : "") +
        '" data-view="AB">Both<br><small>Front &amp; Back</small></button>' +
        "</div>" +

        '<div class="machining-preview-box">' + buildShelfPreviewSVG(item) + "</div>" +
        noteHTML +
        '<button type="button" class="machining-save-btn">Save</button>' +
        "</div>" +
        "</div>";
}

function buildShelfPreviewSVG(item) {
    var faceText = item.view === "A" ? "A SIDE" : item.view === "AB" ? "A + B" : "B SIDE";
    var alongLength = item.edge !== "W1-W2";

    // Two rows of dots on the isometric face, one per configured row, laid
    // out along whichever edge pair is selected.
    var n = Math.max(1, Math.min(6, Math.round(machiningShelfNum(item.positions, 2))));
    var marks = "";
    for (var r = 0; r < 2; r++) {
        for (var i = 0; i < n; i++) {
            var t = (i + 1) / (n + 1);
            var cx, cy;
            if (alongLength) {
                cx = 30 + 80 * t + 8 * (r ? 1 : 0);
                cy = 34 + 38 * r;
            } else {
                cx = 40 + 62 * r;
                cy = 28 + 50 * t;
            }
            marks += '<circle cx="' + cx.toFixed(1) + '" cy="' + cy.toFixed(1) +
                '" r="2.2" fill="#2b78c8"></circle>';
        }
    }

    return '<svg viewBox="0 0 140 100" class="machining-preview-svg">' +
        '<polygon class="preview-panel" points="25,20 115,20 125,85 35,85"></polygon>' +
        '<text class="preview-label" x="75" y="60" text-anchor="middle">' + faceText + "</text>" +
        '<text x="65" y="14" text-anchor="middle">L1</text>' +
        '<text x="70" y="97" text-anchor="middle">L2</text>' +
        '<text x="14" y="55">W1</text>' +
        '<text x="122" y="55">W2</text>' +
        marks +
        "</svg>";
}

function buildGrooveDetailHTML(item, index) {
    var dims = machiningCurrentDims();
    var isVertical = item.edge === "W1-W2";
    var runMax = isVertical
        ? (!isNaN(dims.width) ? dims.width - 1 : 9998)
        : (!isNaN(dims.length) ? dims.length - 1 : 9998);
    var distMax = isVertical
        ? (!isNaN(dims.length) ? dims.length - 1 : 9998)
        : (!isNaN(dims.width) ? dims.width - 1 : 9998);
    var labels = machiningGrooveLabels(item.edge);

    var thickEl = document.getElementById("mThick");
    var thickness = thickEl ? parseFloat(thickEl.textContent) : NaN;
    var maxDepth = !isNaN(thickness) ? Math.max(1, thickness - MACHINING_GROOVE_DEPTH_MARGIN_MM) : 9998;

    var distEdgeOptionsHTML = labels.distEdges.map(function (edge) {
        return '<option value="' + edge + '"' + (item.distanceEdge === edge ? " selected" : "") + '>From ' + edge + "</option>";
    }).join("");

    return "" +
        '<div class="machining-applied-item" data-index="' + index + '">' +
        '<div class="machining-applied-chip">' +
        '<span class="machining-applied-chip-label">Groove cut along ' + item.edge + '</span>' +
        '<button type="button" class="machining-applied-remove" aria-label="Remove">&times;</button>' +
        "</div>" +
        '<div class="machining-applied-detail">' +
        '<div class="machining-detail-label">Groove cut along edge:</div>' +
        '<div class="machining-toggle-row" data-role="groove-edge">' +
        '<button type="button" class="machining-toggle-btn' + (item.edge !== "W1-W2" ? " selected" : "") + '" data-edge="L1-L2">L1-L2</button>' +
        '<button type="button" class="machining-toggle-btn' + (item.edge === "W1-W2" ? " selected" : "") + '" data-edge="W1-W2">W1-W2</button>' +
        "</div>" +
        '<div class="machining-detail-label">Size:</div>' +
        '<div class="machining-offset-row">' +
        '<div class="machining-offset-field"><label>Width</label>' +
        '<input type="text" class="machining-offset-input" data-field="width" data-min="1" data-max="' + MACHINING_GROOVE_MAX_WIDTH_MM + '" value="' + (item.width || "") + '"></div>' +
        '<div class="machining-offset-field"><label>Depth</label>' +
        '<input type="text" class="machining-offset-input" data-field="depth" data-min="1" data-max="' + maxDepth + '" value="' + (item.depth || "") + '"></div>' +
        "</div>" +
        '<div class="machining-detail-label">End points:</div>' +
        '<div class="machining-offset-row">' +
        '<div class="machining-offset-field"><label>' + labels.end1 + '</label>' +
        '<input type="text" class="machining-offset-input" data-field="end1" data-min="0" data-max="' + runMax + '" value="' + (item.end1 || "") + '"></div>' +
        '<div class="machining-offset-field"><label>' + labels.end2 + '</label>' +
        '<input type="text" class="machining-offset-input" data-field="end2" data-min="0" data-max="' + runMax + '" value="' + (item.end2 || "") + '"></div>' +
        "</div>" +
        '<div class="machining-detail-label">Distance:</div>' +
        '<div class="machining-offset-row">' +
        '<div class="machining-offset-field"><label>Specify edge</label>' +
        '<select class="machining-select machining-groove-distance-edge">' + distEdgeOptionsHTML + "</select></div>" +
        '<div class="machining-offset-field"><label>Edge to groove</label>' +
        '<input type="text" class="machining-offset-input" data-field="distance" data-min="0" data-max="' + distMax + '" value="' + (item.distance || "") + '"></div>' +
        "</div>" +
        '<div class="machining-detail-label">Groove cut on:</div>' +
        '<div class="machining-toggle-row" data-role="view">' +
        '<button type="button" class="machining-toggle-btn' + (item.view !== "B" ? " selected" : "") + '" data-view="A">A side<br><small>Front face</small></button>' +
        '<button type="button" class="machining-toggle-btn' + (item.view === "B" ? " selected" : "") + '" data-view="B">B side<br><small>Back face</small></button>' +
        "</div>" +
        '<div class="machining-preview-box">' + buildGroovePreviewSVG(item) + "</div>" +
        '<div class="machining-groove-note">Notes: Max groove depth: ' + maxDepth + "mm</div>" +
        '<button type="button" class="machining-save-btn">Save</button>' +
        "</div>" +
        "</div>";
}

function buildMachiningAppliedItemHTML(item, index) {

    if (item.option === "groove") {
        return buildGrooveDetailHTML(item, index);
    }

    // Dispatched on the CPT's behaviour rather than the slug, so any option
    // set to "Hinge holes" in wp-admin gets this panel — not just the two
    // Blum ones seeded with it.
    var itemOpt = machiningOptionBySlug(item.option);
    if (itemOpt && itemOpt.behaviour === "hinge-holes") {
        return buildHingeHolesDetailHTML(item, index, itemOpt.label || item.label || "Hinge holes");
    }

    if (itemOpt && itemOpt.behaviour === "shelf-holes") {
        return buildShelfHolesDetailHTML(item, index, itemOpt.label || item.label || "Shelf holes");
    }

    if (item.option !== "angled-cut") {
        return '<div class="machining-applied-item" data-index="' + index + '">' +
            '<div class="machining-applied-chip">' +
            '<span class="machining-applied-chip-label">' + panelSummaryEscape(item.label) + '</span>' +
            '<button type="button" class="machining-applied-remove" aria-label="Remove">&times;</button>' +
            "</div></div>";
    }

    var labels = machiningCornerLabels(item.corner);
    var tapes = machiningTapesForCurrentRow();
    var allowedFinishes = machiningTapeFinishes(item.edgeTapeCode);
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
        '<button type="button" class="machining-toggle-btn' + (item.finish === "radius" ? " selected" : "") + '"' +
        (allowedFinishes.radius ? "" : ' disabled title="This edging tape isn\'t available with a radius edge finish"') +
        ' data-finish="radius">Radius<br><small>edge finish</small></button>' +
        '<button type="button" class="machining-toggle-btn' + (item.finish === "square" ? " selected" : "") + '"' +
        (allowedFinishes.square ? "" : ' disabled title="This edging tape isn\'t available with a square edge finish"') +
        ' data-finish="square">Square<br><small>edge finish</small></button>' +
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

        // Which settings panel/drawing to use comes from the CPT's
        // behaviour field, so an option added in wp-admin can reuse an
        // existing behaviour under its own name. Falls back to the slug
        // for anything not in the catalogue.
        var optionDef = machiningOptionBySlug(optionKey);
        var behaviour = optionDef ? optionDef.behaviour : optionKey;

        // The dropdown item is already greyed, but this is a delegated
        // handler on a div — nothing stops a click reaching it.
        if (machiningOptionBlockedReason(optionDef, machiningCurrentRow)) return;

        if (behaviour === "angled-cut") {
            // offsetH/offsetV are measured from the *far* edge, so the
            // default leg is subtracted from the board's own dimension.
            // Left blank when that dimension isn't filled in yet — there's
            // nothing to subtract from, and the cut is drawn at
            // MACHINING_MIN_INSET until the row has real numbers.
            var addDims = machiningCurrentDims();
            var defaultOffsetH = (!isNaN(addDims.length) && addDims.length > MACHINING_DEFAULT_CUT_LEG_MM)
                ? Math.round(addDims.length - MACHINING_DEFAULT_CUT_LEG_MM) : "";
            var defaultOffsetV = (!isNaN(addDims.width) && addDims.width > MACHINING_DEFAULT_CUT_LEG_MM)
                ? Math.round(addDims.width - MACHINING_DEFAULT_CUT_LEG_MM) : "";
            machiningAppliedItems.push({
                option: "angled-cut",
                corner: "L1-W1",
                offsetH: defaultOffsetH,
                offsetV: defaultOffsetV,
                edgeTapeCode: "",
                edgeTapeName: "",
                finish: "",
                view: "A"
            });
        } else if (behaviour === "groove") {
            machiningAppliedItems.push({
                option: "groove",
                edge: "L1-L2",
                width: "",
                depth: "",
                end1: "",
                end2: "",
                distanceEdge: "L1",
                distance: "",
                view: "A"
            });
        } else if (behaviour === "hinge-holes") {
            // Start at the recommended count for the edge, not the maximum
            // it could take — the select then lets it be changed either way.
            var hingeDims = machiningCurrentDims();
            var hingeDefault = machiningHingeDefaultHoleCount(machiningHingeEdgeLength("L1", hingeDims));
            machiningAppliedItems.push({
                option: optionKey,
                label: selectedItem.textContent.trim(),
                edge: "L1",
                holes: hingeDefault >= 2 ? hingeDefault : 2,
                // Hinge cups go into the back face; A side isn't selectable.
                view: "B"
            });
        } else if (behaviour === "shelf-holes") {
            machiningAppliedItems.push({
                option: optionKey,
                label: selectedItem.textContent.trim(),
                edge: "L1-L2",
                row1: MACHINING_SHELF_DEFAULT_ROW_MM,
                row2: MACHINING_SHELF_DEFAULT_ROW_MM,
                step: MACHINING_SHELF_DEFAULT_STEP_MM,
                positions: MACHINING_SHELF_DEFAULT_POSITIONS,
                clusters: MACHINING_SHELF_DEFAULT_CLUSTERS,
                // Shelf pins are bored into the inner face, but unlike hinge
                // cups either face — or both — is a legitimate choice.
                view: "B"
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

                // Switching tape can outlaw the finish already chosen —
                // drop it rather than leave a selected-but-disabled button
                // that would also be saved to the order.
                var nowAllowed = machiningTapeFinishes(item.edgeTapeCode);
                if (!nowAllowed[item.finish]) item.finish = "";

                renderMachiningAppliedList();
                saveMachiningAppliedItems();
                return;
            }

        }

        var finishBtn = e.target.closest(".machining-toggle-btn[data-finish]");
        if (finishBtn) {
            // Browsers don't fire click on a disabled button, but this
            // listener is delegated — so guard rather than rely on that.
            if (finishBtn.disabled) return;
            item.finish = finishBtn.dataset.finish;
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
            return;
        }

        var edgeBtn = e.target.closest(".machining-toggle-btn[data-edge]");
        if (edgeBtn) {
            item.edge = edgeBtn.dataset.edge;
            // Switching axis changes which edges the end points/distance are
            // measured from, and their valid max — re-render picks that up.
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

        if (e.target.classList.contains("machining-groove-distance-edge")) {
            item.distanceEdge = e.target.value;
            saveMachiningAppliedItems();
        }

        if (e.target.matches('input[type="radio"][name^="machiningHingeEdge"]')) {
            item.edge = e.target.value;
            // Switching L1/L2 <-> W1/W2 measures a different side of the
            // panel, so the allowed hole count changes with it — clamp
            // rather than keep a count the new edge can't take.
            var maxHoles = machiningHingeHoleCount(
                machiningHingeEdgeLength(item.edge, machiningCurrentDims())
            );
            if (maxHoles >= 2) {
                item.holes = Math.min(Number(item.holes) || 2, maxHoles);
            }
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
        }

        if (e.target.classList.contains("machining-hinge-count")) {
            item.holes = parseInt(e.target.value, 10) || 2;
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
        }

        if (e.target.matches('input[type="radio"][name^="machiningShelfEdge"]')) {
            item.edge = e.target.value;
            // The row insets are measured across the other axis now, so
            // their labels and max change — re-render picks both up.
            renderMachiningAppliedList();
            saveMachiningAppliedItems();
        }

        if (e.target.classList.contains("machining-shelf-clusters")) {
            item.clusters = parseInt(e.target.value, 10) || 1;
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
        if (field === "offsetH" || field === "offsetV" ||
            field === "width" || field === "depth" || field === "end1" || field === "end2" || field === "distance" ||
            field === "row1" || field === "row2" || field === "step" || field === "positions") {
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


// SPRAY FINISHING OPTIONS — Spray Finish CPT (wp-admin), not hardcoded.
// window.cutlistSprayFinishes (see cutlist_format_spray_finish() in
// rest-endpoints.php) is keyed by slug here to rebuild the exact shape this
// file used to hardcode, so renderSpraySidebar() etc. below need no changes.

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
        // sprayState.option doesn't match any known finish — e.g. it was 
        // removed in wp-admin after this overlay was opened, or (during
        // testing) a stale cached proto-main.js still has an old slug.
        // Fail visibly instead of throwing on cfg.finishes below, which
        // would silently freeze the total at whatever it last showed.
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
