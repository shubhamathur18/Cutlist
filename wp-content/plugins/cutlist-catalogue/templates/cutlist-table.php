<?php
/**
 * Markup for the [cutlist_table] "Cut, Edge & Spray" page.
 *
 * Included (not file_get_contents()'d) from cutlist_table's shortcode
 * callback in includes/cutting-list-shortcode.php, so $boards is already
 * in scope here. Ported from the Cutlist Proto (cut-edge-spray.html)
 * prototype — structure/classes/ids are kept as close to the prototype as
 * possible so the existing JS (proto-main.js) needs no changes; the only
 * change from the prototype is wrapping the static copy in translation
 * functions so future copy/language changes are a one-line edit instead
 * of a find-and-replace across a static HTML asset.
 */

if (!defined('ABSPATH')) {
	exit;
}
?>
    <div class="cb-topbar" id="cbTopbar" style="display:none">
        <a href="basket.html" class="cb-topbar-btn cb-topbar-view"><?php esc_html_e('View basket', 'cutlist-catalogue'); ?></a>
        <a href="checkout/address/index.html" class="cb-topbar-btn cb-topbar-checkout"><?php esc_html_e('Checkout', 'cutlist-catalogue'); ?></a>
    </div>
    <div class="header">
        <div class="tabs">
            <a href="cut-edge-spray.html" class="tab active"><div><?php esc_html_e('Cut, Edge & Spray', 'cutlist-catalogue'); ?></div><strong>&pound;0.00</strong></a>
            <a href="#" class="tab disabled" aria-disabled="true" title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>"><div><?php esc_html_e('CNC Machining', 'cutlist-catalogue'); ?></div><strong>&pound;0.00</strong></a>
            <a href="#" class="tab disabled" aria-disabled="true" title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>"><div><?php esc_html_e('Sliding Doors', 'cutlist-catalogue'); ?></div><strong>&pound;0.00</strong></a>
            <a href="#" class="tab disabled" aria-disabled="true" title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>"><div><?php esc_html_e('Hinged Doors', 'cutlist-catalogue'); ?></div><strong>&pound;0.00</strong></a>
            <a href="#" class="tab disabled" aria-disabled="true" title="<?php esc_attr_e('Not built yet', 'cutlist-catalogue'); ?>"><div><?php esc_html_e('Furniture Fittings', 'cutlist-catalogue'); ?></div><strong>&pound;0.00</strong></a>
        </div>
    </div>
    <div class="container tab-panel active" data-panel="cutspray">
        <div class="section-title">
            <h2><?php esc_html_e('Cutting list', 'cutlist-catalogue'); ?></h2>
            <div class="toggle">
                <span class="toggle-label"><?php esc_html_e('Collapse', 'cutlist-catalogue'); ?></span>
                <span class="arrow"></span>
            </div>
        </div>
        <div class="table-area">
            <table>
                <tr class="header-row">
                    <th>#</th>
                    <th><?php esc_html_e('Material decor code / name', 'cutlist-catalogue'); ?></th>
                    <th><?php esc_html_e('Thick', 'cutlist-catalogue'); ?><br>[mm]</th>
                    <th><?php esc_html_e('Length', 'cutlist-catalogue'); ?><br>[mm]</th>
                    <th><?php esc_html_e('Width', 'cutlist-catalogue'); ?><br>[mm]</th>
                    <th><?php esc_html_e('Qty', 'cutlist-catalogue'); ?></th>
                    <th><?php esc_html_e('Part description', 'cutlist-catalogue'); ?></th>
                    <th colspan="4" class="edging">
                        <?php esc_html_e('Edgebanding details', 'cutlist-catalogue'); ?>
                        <br><br>
                        <table style="width:100%;border-spacing:20px 0">
                            <tr>
                                <td>L1</td>
                                <td>L2</td>
                                <td>W1</td>
                                <td>W2</td>
                            </tr>
                        </table>
                    </th>
                    <th><?php esc_html_e('Additional', 'cutlist-catalogue'); ?><br><?php esc_html_e('machining', 'cutlist-catalogue'); ?></th>
                    <th><?php esc_html_e('Spray', 'cutlist-catalogue'); ?><br><?php esc_html_e('finishing', 'cutlist-catalogue'); ?></th>
                    <th><?php esc_html_e('Grain', 'cutlist-catalogue'); ?><br><?php esc_html_e('match', 'cutlist-catalogue'); ?></th>
                    <th class="text-right"><?php esc_html_e('Actions', 'cutlist-catalogue'); ?></th>
                </tr>
                <!-- ROW -->
                <tr>
                    <td class="rownum">1</td>
                    <td class="decor">
                        <input placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>">
                    </td>
                    <td class="small thick">
                        <select disabled>
                            <option value=""><?php esc_html_e('Select', 'cutlist-catalogue'); ?></option>
                            <option value="19">19</option>
                            <option value="38">38</option>
                        </select>
                    </td>
                    <td class="small"><input></td>
                    <td class="small"><input></td>
                    <td class="qty"><input></td>
                    <td class="desc"><input></td>
                    <td class="small edging-input" data-edge="L1">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="L2">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="W1">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="W2">
                        <input disabled readonly>
                    </td>
                    <td class="machining">
                        <button class="add-btn" type="button"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                    </td>
                    <td class="spray">
                        <button class="add-btn" type="button"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                    </td>
                    <td class="grain">
                        <input type="checkbox" disabled>
                    </td>
                    <td class="actions">
                        <div class="actions-inner">
                            <span class="icon move" title="<?php esc_attr_e('Move row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 32 32">
                                    <path fill="currentColor"
                                        d="M4 20h11v6.17l-2.59-2.58L11 25l5 5 5-5-1.41-1.41L17 26.17V20h11v-2H4v2zM11 7l1.41 1.41L15 5.83V12H4v2h24v-2H17V5.83l2.59 2.58L21 7l-5-5-5 5z">
                                    </path>
                                </svg>
                            </span>
                            <span class="icon edit" title="<?php esc_attr_e('Edit row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 1000 1000">
                                    <path fill="currentColor"
                                        d="M231.1 915L87 770.9l626-626L857.1 289l-626 626zm-80.5-144.1l80.5 80.5L793.5 289 713 208.5 150.6 770.9zM64 938l22.3-156.2 133.9 133.8L64 938zm54.1-60.8L117 885l7.8-1.1-6.7-6.7zM845.5 272.5l-116-116L816 70l116 116.1-86.5 86.4zm-52.4-116l52.4 52.4 22.9-22.9-52.4-52.4-22.9 22.9z">
                                    </path>
                                </svg>
                            </span>
                            <span class="icon view" title="<?php esc_attr_e('View row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round">
                                    <circle cx="10.5" cy="10.5" r="6.5"></circle>
                                    <line x1="21" y1="21" x2="15.5" y2="15.5"></line>
                                </svg>
                            </span>
                            <span class="delete" title="<?php esc_attr_e('Delete row', 'cutlist-catalogue'); ?>">×</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="rownum">2</td>
                    <td class="decor">
                        <input placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>">
                    </td>
                    <td class="small thick">
                        <select disabled>
                            <option value=""><?php esc_html_e('Select', 'cutlist-catalogue'); ?></option>
                            <option value="19">19</option>
                            <option value="38">38</option>
                        </select>
                    </td>
                    <td class="small"><input></td>
                    <td class="small"><input></td>
                    <td class="qty"><input></td>
                    <td class="desc"><input></td>
                    <td class="small edging-input" data-edge="L1">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="L2">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="W1">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="W2">
                        <input disabled readonly>
                    </td>
                    <td class="machining">
                        <button class="add-btn" type="button"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                    </td>
                    <td class="spray">
                        <button class="add-btn" type="button"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                    </td>
                    <td class="grain">
                        <input type="checkbox" disabled>
                    </td>
                    <td class="actions">
                        <div class="actions-inner">
                            <span class="icon move" title="<?php esc_attr_e('Move row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 32 32">
                                    <path fill="currentColor"
                                        d="M4 20h11v6.17l-2.59-2.58L11 25l5 5 5-5-1.41-1.41L17 26.17V20h11v-2H4v2zM11 7l1.41 1.41L15 5.83V12H4v2h24v-2H17V5.83l2.59 2.58L21 7l-5-5-5 5z">
                                    </path>
                                </svg>
                            </span>
                            <span class="icon edit" title="<?php esc_attr_e('Edit row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 1000 1000">
                                    <path fill="currentColor"
                                        d="M231.1 915L87 770.9l626-626L857.1 289l-626 626zm-80.5-144.1l80.5 80.5L793.5 289 713 208.5 150.6 770.9zM64 938l22.3-156.2 133.9 133.8L64 938zm54.1-60.8L117 885l7.8-1.1-6.7-6.7zM845.5 272.5l-116-116L816 70l116 116.1-86.5 86.4zm-52.4-116l52.4 52.4 22.9-22.9-52.4-52.4-22.9 22.9z">
                                    </path>
                                </svg>
                            </span>
                            <span class="icon view" title="<?php esc_attr_e('View row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round">
                                    <circle cx="10.5" cy="10.5" r="6.5"></circle>
                                    <line x1="21" y1="21" x2="15.5" y2="15.5"></line>
                                </svg>
                            </span>
                            <span class="delete" title="<?php esc_attr_e('Delete row', 'cutlist-catalogue'); ?>">×</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="rownum">3</td>
                    <td class="decor">
                        <input placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>">
                    </td>
                    <td class="small thick">
                        <select disabled>
                            <option value=""><?php esc_html_e('Select', 'cutlist-catalogue'); ?></option>
                            <option value="19">19</option>
                            <option value="38">38</option>
                        </select>
                    </td>
                    <td class="small"><input></td>
                    <td class="small"><input></td>
                    <td class="qty"><input></td>
                    <td class="desc"><input></td>
                    <td class="small edging-input" data-edge="L1">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="L2">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="W1">
                        <input disabled readonly>
                    </td>
                    <td class="small edging-input" data-edge="W2">
                        <input disabled readonly>
                    </td>
                    <td class="machining">
                        <button class="add-btn" type="button"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                    </td>
                    <td class="spray">
                        <button class="add-btn" type="button"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                    </td>
                    <td class="grain">
                        <input type="checkbox" disabled>
                    </td>
                    <td class="actions">
                        <div class="actions-inner">
                            <span class="icon move" title="<?php esc_attr_e('Move row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 32 32">
                                    <path fill="currentColor"
                                        d="M4 20h11v6.17l-2.59-2.58L11 25l5 5 5-5-1.41-1.41L17 26.17V20h11v-2H4v2zM11 7l1.41 1.41L15 5.83V12H4v2h24v-2H17V5.83l2.59 2.58L21 7l-5-5-5 5z">
                                    </path>
                                </svg>
                            </span>
                            <span class="icon edit" title="<?php esc_attr_e('Edit row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 1000 1000">
                                    <path fill="currentColor"
                                        d="M231.1 915L87 770.9l626-626L857.1 289l-626 626zm-80.5-144.1l80.5 80.5L793.5 289 713 208.5 150.6 770.9zM64 938l22.3-156.2 133.9 133.8L64 938zm54.1-60.8L117 885l7.8-1.1-6.7-6.7zM845.5 272.5l-116-116L816 70l116 116.1-86.5 86.4zm-52.4-116l52.4 52.4 22.9-22.9-52.4-52.4-22.9 22.9z">
                                    </path>
                                </svg>
                            </span>
                            <span class="icon view" title="<?php esc_attr_e('View row', 'cutlist-catalogue'); ?>">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round">
                                    <circle cx="10.5" cy="10.5" r="6.5"></circle>
                                    <line x1="21" y1="21" x2="15.5" y2="15.5"></line>
                                </svg>
                            </span>
                            <span class="delete" title="<?php esc_attr_e('Delete row', 'cutlist-catalogue'); ?>">×</span>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="btn-area">
                <button id="addRowBtn"><?php esc_html_e('Add more rows', 'cutlist-catalogue'); ?></button>
                <button style="width:257px">
                    <?php esc_html_e('Upload cutting list', 'cutlist-catalogue'); ?>
                </button>
                <button style="width:257px" disabled>
                    <?php esc_html_e('Download cutting list', 'cutlist-catalogue'); ?>
                </button>
            </div>
        </div>

        <!-- GRAIN MATCHING DETAILS — shown when a grain-match checkbox is ticked -->
        <div class="grain-details" id="grainMatchSection" style="display:none;">
            <h2><?php esc_html_e('Grain matching details', 'cutlist-catalogue'); ?></h2>
            <div class="grain-notice">
                <?php esc_html_e('A drawing detailing grain matching cluster is required. Please upload it below before proceeding to the checkout.', 'cutlist-catalogue'); ?><br>
                <?php esc_html_e('Mark the panels with "Grain matching" with the letters as A, B or C and so on. Provide a description of how the grain matching should be done.', 'cutlist-catalogue'); ?>
            </div>
            <div class="grain-cols">
                <div class="grain-col">
                    <h3><?php esc_html_e('Grain matching files', 'cutlist-catalogue'); ?></h3>
                    <div class="grain-dropzone" id="grainDropzone">
                        <?php esc_html_e('Drag and drop your files here, or click below to select files to upload.', 'cutlist-catalogue'); ?><br>
                        <?php esc_html_e('Maximum size: 5 MB per file 30 MB total', 'cutlist-catalogue'); ?><br>
                        <?php esc_html_e('Supported formats: JPEG, PNG, GIF, PDF', 'cutlist-catalogue'); ?>
                        <br>
                        <a id="grainAddFiles"><?php esc_html_e('Add your files', 'cutlist-catalogue'); ?></a>
                        <input type="file" id="grainFileInput" multiple accept=".jpg,.jpeg,.png,.gif,.pdf" style="display:none;">
                        <div class="grain-file-list" id="grainFileList"></div>
                    </div>
                </div>
                <div class="grain-col">
                    <h3><?php esc_html_e('Grain matching information', 'cutlist-catalogue'); ?></h3>
                    <textarea class="grain-info-textarea" id="grainInfoText" placeholder="<?php esc_attr_e('Type here', 'cutlist-catalogue'); ?>"></textarea>
                </div>
                <div class="grain-col">
                    <h3><?php esc_html_e('Help', 'cutlist-catalogue'); ?></h3>
                    <div class="grain-help-box">
                        <div class="grain-direction"><?php esc_html_e('grain direction', 'cutlist-catalogue'); ?></div>
                        <div class="grain-diagram">
                            <div class="grain-cell big">A</div>
                            <div class="grain-mid">
                                <div class="grain-cell">B</div>
                                <div class="grain-cell">B</div>
                                <div class="grain-cell">B</div>
                            </div>
                            <div class="grain-cell big">A</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="closed">
            <div class="section-title">
                <h2><?php esc_html_e('Full sheets', 'cutlist-catalogue'); ?></h2>
                <div class="toggle">
                    <span class="toggle-label"><?php esc_html_e('Collapse', 'cutlist-catalogue'); ?></span>
                    <span class="arrow"></span>
                </div>
            </div>
            <div class="table-area" id="fsTableArea">
                <table id="fsTable">
                    <tr class="header-row">
                        <th>#</th>
                        <th><?php esc_html_e('Material decor code / name', 'cutlist-catalogue'); ?></th>
                        <th><?php esc_html_e('Thick', 'cutlist-catalogue'); ?><br>[mm]</th>
                        <th><?php esc_html_e('Length', 'cutlist-catalogue'); ?><br>[mm]</th>
                        <th><?php esc_html_e('Width', 'cutlist-catalogue'); ?><br>[mm]</th>
                        <th><?php esc_html_e('Brand', 'cutlist-catalogue'); ?></th>
                        <th><?php esc_html_e('Qty', 'cutlist-catalogue'); ?></th>
                        <th class="text-right"><?php esc_html_e('Actions', 'cutlist-catalogue'); ?></th>
                    </tr>
                    <tr class="fs-row">
                        <td class="rownum">1</td>
                        <td class="decor"><input placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>"></td>
                        <td class="small thick">
                            <select disabled>
                                <option value="">–</option>
                                <option value="8">8</option>
                                <option value="12">12</option>
                                <option value="16">16</option>
                                <option value="18">18</option>
                                <option value="25">25</option>
                                <option value="38">38</option>
                            </select>
                        </td>
                        <td class="small fs-length"><input disabled placeholder="0"></td>
                        <td class="small fs-width"><input disabled placeholder="0"></td>
                        <td class="fs-brand">–</td>
                        <td class="qty"><input disabled></td>
                        <td class="actions">
                            <div class="actions-inner">
                                <span class="delete" title="<?php esc_attr_e('Delete row', 'cutlist-catalogue'); ?>">×</span>
                            </div>
                        </td>
                    </tr>
                    <tr class="fs-row">
                        <td class="rownum">2</td>
                        <td class="decor"><input placeholder="<?php esc_attr_e('Enter decor code or name', 'cutlist-catalogue'); ?>"></td>
                        <td class="small thick">
                            <select disabled>
                                <option value="">–</option>
                                <option value="8">8</option>
                                <option value="12">12</option>
                                <option value="16">16</option>
                                <option value="18">18</option>
                                <option value="25">25</option>
                                <option value="38">38</option>
                            </select>
                        </td>
                        <td class="small fs-length"><input disabled placeholder="0"></td>
                        <td class="small fs-width"><input disabled placeholder="0"></td>
                        <td class="fs-brand">–</td>
                        <td class="qty"><input disabled></td>
                        <td class="actions">
                            <div class="actions-inner">
                                <span class="delete" title="<?php esc_attr_e('Delete row', 'cutlist-catalogue'); ?>">×</span>
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="btn-area">
                    <button id="addFsRowBtn"><?php esc_html_e('Add a row', 'cutlist-catalogue'); ?></button>
                </div>
            </div>
            <div class="section-title">
                <h2><?php esc_html_e('Edging tape', 'cutlist-catalogue'); ?></h2>
                <div class="toggle">
                    <span class="toggle-label"><?php esc_html_e('Expand', 'cutlist-catalogue'); ?></span>
                    <span class="arrow down"></span>
                </div>
            </div>
            <div class="table-area" id="etTableArea" style="display:none">
                <table id="etTable">
                    <colgroup>
                        <col class="col-index">
                        <col class="col-tape">
                        <col class="col-name">
                        <col class="col-size">
                        <col class="col-space-1">
                        <col class="col-qty-wide">
                        <col class="col-space-2">
                        <col class="col-qty-wide">
                        <col class="col-actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="th-index">#</th>
                            <th class="text-left"><?php esc_html_e('Edging decor code', 'cutlist-catalogue'); ?></th>
                            <th class="text-left"><?php esc_html_e('Product name – decor name', 'cutlist-catalogue'); ?></th>
                            <th class="text-left"><?php esc_html_e('Size', 'cutlist-catalogue'); ?><br>[mm]</th>
                            <th class="text-left"></th>
                            <th class="text-left"><?php esc_html_e('Qty', 'cutlist-catalogue'); ?><br>[m]</th>
                            <th class="text-left"><?php esc_html_e('Unit price', 'cutlist-catalogue'); ?></th>
                            <th class="text-right th-actions"><?php esc_html_e('Actions', 'cutlist-catalogue'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="etTbody">
                        <tr class="et-row editable">
                            <td class="td-index">1</td>
                            <td colspan="3">
                                <div class="Select2 isEmpty Select2--has-arrow">
                                    <div class="Select2__input-wrapper">
                                        <span class="Select2__input"></span>
                                        <span class="Select2__placeholder"><?php esc_html_e('Select edging tape', 'cutlist-catalogue'); ?></span>
                                        <span class="Select2__arrow">
                                            <svg height="20" width="20" viewBox="0 0 20 20" aria-hidden="true"
                                                focusable="false">
                                                <path
                                                    d="M4.516 7.548c0.436-0.446 1.043-0.481 1.576 0l3.908 3.747 3.908-3.747c0.533-0.481 1.141-0.446 1.574 0 0.436 0.445 0.408 1.197 0 1.615-0.406 0.418-4.695 4.502-4.695 4.502-0.217 0.223-0.502 0.335-0.787 0.335s-0.57-0.112-0.789-0.335c0 0-4.287-4.084-4.695-4.502s-0.436-1.17 0-1.615z"
                                                    fill="#888" />
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="Select2__dropdown"></div>
                                </div>
                            </td>
                            <td></td>
                            <td><input class="et-qty-input" type="text" inputmode="numeric" pattern="[0-9]*" placeholder="<?php esc_attr_e('Min 5', 'cutlist-catalogue'); ?>" value=""
                                    disabled></td>
                            <td class="et-unit-price">&ndash;</td>
                            <td class="text-right">
                                <button class="button-remove" type="button" title="<?php esc_attr_e('Remove row', 'cutlist-catalogue'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M2 2l12 12M14 2L2 14" stroke="#cc2222" stroke-width="2.5"
                                            stroke-linecap="round" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr class="et-row editable">
                            <td class="td-index">2</td>
                            <td colspan="3">
                                <div class="Select2 isEmpty Select2--has-arrow">
                                    <div class="Select2__input-wrapper">
                                        <span class="Select2__input"></span>
                                        <span class="Select2__placeholder"><?php esc_html_e('Select edging tape', 'cutlist-catalogue'); ?></span>
                                        <span class="Select2__arrow">
                                            <svg height="20" width="20" viewBox="0 0 20 20" aria-hidden="true"
                                                focusable="false">
                                                <path
                                                    d="M4.516 7.548c0.436-0.446 1.043-0.481 1.576 0l3.908 3.747 3.908-3.747c0.533-0.481 1.141-0.446 1.574 0 0.436 0.445 0.408 1.197 0 1.615-0.406 0.418-4.695 4.502-4.695 4.502-0.217 0.223-0.502 0.335-0.787 0.335s-0.57-0.112-0.789-0.335c0 0-4.287-4.084-4.695-4.502s-0.436-1.17 0-1.615z"
                                                    fill="#888" />
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="Select2__dropdown"></div>
                                </div>
                            </td>
                            <td></td>
                            <td><input class="et-qty-input" type="text" inputmode="numeric" pattern="[0-9]*" placeholder="<?php esc_attr_e('Min 5', 'cutlist-catalogue'); ?>" value=""
                                    disabled></td>
                            <td class="et-unit-price">&ndash;</td>
                            <td class="text-right">
                                <button class="button-remove" type="button" title="<?php esc_attr_e('Remove row', 'cutlist-catalogue'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M2 2l12 12M14 2L2 14" stroke="#cc2222" stroke-width="2.5"
                                            stroke-linecap="round" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-left"></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="relative table-bottom-buttons" style="margin-top:10px">
                    <button id="addEtRowBtn" style="width:215px" disabled><?php esc_html_e('Add a row', 'cutlist-catalogue'); ?></button>
                </div>
            </div>
        </div>
        <!-- Update basket showbox -->
        <div class="update-basket-showbox" id="updateBasketShowbox">
            <div class="update-basket-inner">
                <h2><?php esc_html_e('Please update your basket', 'cutlist-catalogue'); ?></h2>
                <p><?php esc_html_e('Click the button below', 'cutlist-catalogue'); ?></p>
                <button id="updateBasketBtn" disabled><?php esc_html_e('Update basket', 'cutlist-catalogue'); ?></button>
            </div>
        </div>
        <!-- =========================================
CUT, EDGE & SPRAY SUMMARY
========================================= -->

<section class="summary-section" id="summarySection" style="display:none;">
<h2 class="summary-title"><?php esc_html_e('Cut, edge & spray summary', 'cutlist-catalogue'); ?></h2>

<div class="summary-card">
<div class="summary-header">
<div class="summary-header-left"><span><?php esc_html_e('Sheets to be cut', 'cutlist-catalogue'); ?></span><strong>£848.80</strong></div>
<div class="summary-header-right">
    <span><?php esc_html_e('Cutting plans x2', 'cutlist-catalogue'); ?></span>

    <button class="summary-toggle" type="button">
        <?php esc_html_e('Details', 'cutlist-catalogue'); ?>
        <span class="summary-arrow">▼</span>
    </button>
</div>
</div>
<div class="summary-body  is-open">
<h3><?php esc_html_e('Select offcuts', 'cutlist-catalogue'); ?></h3>
<p class="summary-note"><?php esc_html_e('If there are any offcuts, you can mark them here to be included with your order free of charge.', 'cutlist-catalogue'); ?></p>
<div class="plans">
<div class="plan">
<div>Plan 1</div>
<div class="box"><div class="panel"></div><div class="offcut"><?php esc_html_e('Offcut', 'cutlist-catalogue'); ?></div></div>
<p>H1227-TM12-19 Brown Abano Ash<br>2800 × 2070 × 19mm</p>
<div class="sheet"><strong>x2</strong><span><?php esc_html_e('sheets', 'cutlist-catalogue'); ?></span></div>
</div>
<div class="plan">
<div>Plan 2</div>
<div class="box"><div class="panel"></div><div class="offcut"><?php esc_html_e('Offcut', 'cutlist-catalogue'); ?></div></div>
<p>H1227-TM12-19 Brown Abano Ash<br>2800 × 2070 × 19mm</p>
<div class="sheet"><strong>x2</strong><span><?php esc_html_e('sheets', 'cutlist-catalogue'); ?></span></div>
</div>
</div>
</div>
</div>

<div class="summary-card">
<div class="summary-header"><div class="summary-header-left"><span><?php esc_html_e('Edgebanding', 'cutlist-catalogue'); ?></span><strong>£89.12</strong></div>
<div class="summary-header-right">

    <button class="summary-toggle" type="button">
        <?php esc_html_e('Details', 'cutlist-catalogue'); ?>
        <span class="summary-arrow">▼</span>
    </button>

</div>
</div>
<div class="summary-body">
<table><tr><th><?php esc_html_e('Qty', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Product code', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Product name', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Tape size', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Unit price', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Line total', 'cutlist-catalogue'); ?></th></tr>
<tr><td>16</td><td>M1-42</td><td>Matt ABS edging - Brown Abano Ash</td><td>42x1mm</td><td>£5.57</td><td>£89.12</td></tr></table>
<div class="total"><strong><?php echo esc_html__('This section:', 'cutlist-catalogue') . ' £89.12'; ?></strong><div><?php echo esc_html__('With VAT:', 'cutlist-catalogue') . ' £106.94'; ?></div></div>
</div></div>

<div class="summary-card">
<div class="summary-header"><div class="summary-header-left"><span><?php esc_html_e('Additional services', 'cutlist-catalogue'); ?></span><strong>£115.50</strong></div>
<div class="summary-header-right">

    <button class="summary-toggle" type="button">
        <?php esc_html_e('Details', 'cutlist-catalogue'); ?>
        <span class="summary-arrow">▼</span>
    </button>

</div>
</div>
<div class="summary-body">
<table><tr><th><?php esc_html_e('Qty', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Service code', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Service', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Description', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Unit price', 'cutlist-catalogue'); ?></th><th><?php esc_html_e('Line total', 'cutlist-catalogue'); ?></th></tr>
<tr><td>2</td><td>BND-FS</td><td>Bonding</td><td>Bonding of two panels</td><td>£57.75</td><td>£115.50</td></tr></table>
<div class="total"><strong><?php echo esc_html__('This section:', 'cutlist-catalogue') . ' £115.50'; ?></strong><div><?php echo esc_html__('With VAT:', 'cutlist-catalogue') . ' £138.60'; ?></div></div>
</div></div>

<div class="grand">
<div class="price"><?php echo esc_html__('Total:', 'cutlist-catalogue') . ' £1,053.42'; ?></div>
<div class="vat"><?php echo esc_html__('With VAT:', 'cutlist-catalogue') . ' £1,264.10'; ?></div>
</div>

</div>


    </div>
    <!-- PANEL MODAL (More info) -->
    <div class="panel-modal-overlay" id="panelModalOverlay">
        <div class="panel-reveal">
            <button class="panel-close" id="panelModalClose" type="button" aria-label="<?php esc_attr_e('Close', 'cutlist-catalogue'); ?>">
                <svg viewBox="0 0 50 50">
                    <polygon fill="currentColor" points="42.7,46.2 4,7.5 8,3.6 46.6,42.2" />
                    <polygon fill="currentColor" points="8,46.2 4,42.2 42.7,3.6 46.6,7.5" />
                </svg>
            </button>
            <div class="panel-modal-inner">
                <!-- LEFT -->
                <div class="column-gallery">
                    <div class="panel-product-code" id="pmProductCode"><?php echo esc_html__('Product code:', 'cutlist-catalogue') . ' –'; ?></div>
                    <div class="pm-gallery">
                        <div class="pm-gallery-slides" id="pmSlides"></div>
                        <button class="pm-gallery-nav pm-gallery-nav-prev" id="pmPrev" type="button">&#8249;</button>
                        <button class="pm-gallery-nav pm-gallery-nav-next" id="pmNext" type="button">&#8250;</button>
                    </div>
                    <div class="pm-gallery-thumbs" id="pmThumbs"></div>
                    <div class="panel-title" id="pmTitle"></div>
                    <div class="panel-name" id="pmName"></div>
                    <p class="panel-modal__label"><?php esc_html_e('Product size', 'cutlist-catalogue'); ?></p>
                    <table class="panel-table" id="pmSizeTable">
                        <tbody></tbody>
                    </table>
                    <p class="panel-modal__label"><?php esc_html_e('Product machining', 'cutlist-catalogue'); ?></p>
                    <table class="panel-table">
                        <tbody>
                            <tr>
                                <td class="left"><?php esc_html_e('Cut to size:', 'cutlist-catalogue'); ?></td>
                                <td class="right" id="pmMachCut">–</td>
                            </tr>
                            <tr>
                                <td class="left"><?php esc_html_e('Edgebanding:', 'cutlist-catalogue'); ?></td>
                                <td class="right" id="pmMachEdge">–</td>
                            </tr>
                            <tr>
                                <td class="left"><?php esc_html_e('CNC machining:', 'cutlist-catalogue'); ?></td>
                                <td class="right" id="pmMachCnc">–</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- RIGHT -->
                <div class="column-description">
                    <div class="SimpleTabs__tabs">
                        <button class="SimpleTabs__tab-btn active" data-tab="desc" type="button"><?php esc_html_e('Description', 'cutlist-catalogue'); ?></button>
                        <button class="SimpleTabs__tab-btn" data-tab="faq" type="button"><?php esc_html_e('FAQ', 'cutlist-catalogue'); ?></button>
                    </div>
                    <div class="SimpleTabs__tab-content">
                        <div class="active" id="pmTabDesc">
                            <p class="panel-modal__label" style="margin-top:0"><?php esc_html_e('Product description', 'cutlist-catalogue'); ?></p>
                            <div class="panel-modal__product-description" id="pmDesc"></div>
                            <div id="pmBsideSection">
                                <p class="panel-modal__label"><?php esc_html_e('B side description', 'cutlist-catalogue'); ?></p>
                                <div class="panel-modal__product-description" id="pmBside"></div>
                            </div>
                            <p class="panel-modal__label"><?php esc_html_e('Product characteristics', 'cutlist-catalogue'); ?></p>
                            <div class="panel-modal__product-description" id="pmChars"></div>
                            <div class="pm-downloads" id="pmDownloads"></div>
                        </div>
                        <div id="pmTabFaq">
                            <p class="panel-modal__label" style="margin-top:0"><?php esc_html_e('FAQ', 'cutlist-catalogue'); ?></p>
                            <div class="panel-modal__product-description"><?php esc_html_e('No FAQ available for this product.', 'cutlist-catalogue'); ?></div>
                        </div>
                    </div>
                    <p class="pm-thickness-label"><?php esc_html_e('Available thicknesses [mm]:', 'cutlist-catalogue'); ?></p>
                    <div class="pm-thickness-options" id="pmThicknesses"></div>
                    <table class="pricing-levels__table">
                        <tbody>
                            <tr>
                                <td class="pricing-levels__label"><?php esc_html_e('Full sheet price', 'cutlist-catalogue'); ?></td>
                                <td class="price-val">–</td>
                            </tr>
                            <tr>
                                <td class="pricing-levels__label"><?php esc_html_e('Sheet price with cutting (up to 20 pieces per sheet)', 'cutlist-catalogue'); ?>
                                </td>
                                <td class="price-val">–</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- PANEL SUMMARY MODAL -->
    <div class="panel-modal-overlay panel-summary-overlay" id="panelSummaryModalOverlay">
        <div class="panel-reveal panel-summary-modal">
            <div class="panel-summary-context-bar">
                <span class="ps-ctx-field ps-ctx-rownum" id="psRownum">1</span>
                <span class="ps-ctx-field ps-ctx-decor" id="psDecor">-</span>
                <span class="ps-ctx-field ps-ctx-small" id="psThick">-</span>
                <span class="ps-ctx-field ps-ctx-small" id="psLength">-</span>
                <span class="ps-ctx-field ps-ctx-small" id="psWidth">-</span>
                <span class="ps-ctx-field ps-ctx-small" id="psQty">-</span>
                <span class="ps-ctx-field ps-ctx-desc" id="psDesc">-</span>
                <span class="ps-ctx-field ps-ctx-edge" id="psL1">-</span>
                <span class="ps-ctx-field ps-ctx-edge" id="psL2">-</span>
                <span class="ps-ctx-field ps-ctx-edge" id="psW1">-</span>
                <span class="ps-ctx-field ps-ctx-edge" id="psW2">-</span>
                <span class="panel-summary-context-title"><?php esc_html_e('Panel summary', 'cutlist-catalogue'); ?></span>
                <button class="panel-summary-context-close" id="panelSummaryModalClose" type="button" aria-label="<?php esc_attr_e('Close', 'cutlist-catalogue'); ?>">&times;</button>
            </div>
            <div class="panel-summary-body">

                <div class="panel-summary-left">

                    <div class="panel-summary-svg-wrap" id="panelSummaryDrawingContainer"></div>

                    <div class="panel-summary-grain">
                        <div class="panel-summary-grain-label"><?php esc_html_e('Grain direction', 'cutlist-catalogue'); ?></div>
                        <div class="panel-summary-grain-box" id="panelSummaryGrainBox">
                            <span class="edge-grain-arrow" id="panelSummaryGrainArrow">&#8596;</span>
                        </div>
                    </div>

                    <div class="panel-summary-zoom">
                        <button class="zoom-btn" type="button" id="panelZoomIn">+</button>
                        <button class="zoom-btn" type="button" id="panelZoomOut">&minus;</button>
                    </div>

                    <div class="panel-summary-shows">
                        <div class="panel-summary-shows-label"><?php esc_html_e('Panel shows', 'cutlist-catalogue'); ?></div>
                        <div class="panel-summary-face-box" id="panelSummaryFaceBox">
                            <div>FRONT</div>
                            <div>FACE</div>
                        </div>
                        <button type="button" class="panel-summary-rotate-btn" id="panelSummaryRotateBtn"><?php esc_html_e('Rotate', 'cutlist-catalogue'); ?></button>
                    </div>

                </div>

                <div class="panel-summary-right">
                    <div id="panelSummarySidebar"></div>
                    <div class="panel-summary-nav" id="panelSummaryNav" style="display:none">
                        <button type="button" class="panel-summary-nav-btn panel-summary-nav-prev" id="panelSummaryPrev">&lsaquo; <?php esc_html_e('Previous panel', 'cutlist-catalogue'); ?></button>
                        <button type="button" class="panel-summary-nav-btn panel-summary-nav-next" id="panelSummaryNext"><?php esc_html_e('Next panel', 'cutlist-catalogue'); ?> &rsaquo;</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="decor-popup" id="decorPopup">
        <?php echo cutlist_proto_render_decor_popup_inner($boards); ?>
    </div>
    <div class="edge-popup" id="edgePopup">
        <div class="edge-popup-header">
            <span id="edgeTitle">L1</span>
            <span class="edge-popup-close" id="edgePopupClose">&times;</span>
        </div>
        <div class="edge-diagram">
            <div class="edge-dim-top" id="edgeDimTop">200</div>
            <div class="edge-diagram-frame">
                <span class="edge-highlight top" id="edgeHighlightL1"></span>
                <span class="edge-highlight bottom" id="edgeHighlightL2"></span>
                <span class="edge-highlight left" id="edgeHighlightW1"></span>
                <span class="edge-highlight right" id="edgeHighlightW2"></span>
                <div class="edge-diagram-row">
                    <span class="edge-dim-side" id="edgeDimLeft">200</span>
                    <div class="edge-diagram-box">
                        <span class="edge-grain-arrow">&#8596;</span>
                    </div>
                    <span class="edge-dim-side" id="edgeDimRight">200</span>
                </div>
            </div>
            <div class="edge-grain-caption"><?php esc_html_e('Length oriented grain', 'cutlist-catalogue'); ?></div>
        </div>
        <div class="edge-bottom-label">L2</div>
        <div class="edge-tabs" id="edgeTabs">
            <div class="edge-tab" data-edge="L1">
                <div>L1</div>
                <div class="edge-tab-value">-</div>
            </div>
            <div class="edge-tab" data-edge="L2">
                <div>L2</div>
                <div class="edge-tab-value">-</div>
            </div>
            <div class="edge-tab" data-edge="W1">
                <div>W1</div>
                <div class="edge-tab-value">-</div>
            </div>
            <div class="edge-tab" data-edge="W2">
                <div>W2</div>
                <div class="edge-tab-value">-</div>
            </div>
        </div>
        <button type="button" class="edge-summary" id="edgeSummaryBtn">
            <div class="edge-summary-code" id="edgeSummaryCode">M1-22 / H1227-TM12</div>
            <div class="edge-summary-desc">1mm Matt ABS edging</div>
        </button>
        <div class="edge-finish-options" id="edgeFinishOptions">
            <div class="edge-finish-option" data-finish="radius">
                <div class="edge-finish-icon radius"></div>
                <div><?php esc_html_e('Radius', 'cutlist-catalogue'); ?><br><?php esc_html_e('edge finish', 'cutlist-catalogue'); ?></div>
            </div>
            <div class="edge-finish-option" data-finish="square">
                <div class="edge-finish-icon square"></div>
                <div><?php esc_html_e('Square', 'cutlist-catalogue'); ?><br><?php esc_html_e('edge finish', 'cutlist-catalogue'); ?></div>
            </div>
        </div>
        <a href="https://cworkshop.co.uk/services/panel-edgebanding/" target="_blank" rel="noopener noreferrer"
            class="edge-finishing-link"><?php esc_html_e('Edge finishing options explained', 'cutlist-catalogue'); ?> &#8599;</a>
        <div class="edge-mode-toggle" id="edgeModeToggle">
            <button type="button" class="mode-btn active" data-mode="standard"><?php esc_html_e('Standard', 'cutlist-catalogue'); ?></button>
            <button type="button" class="mode-btn" data-mode="expert"><?php esc_html_e('Expert', 'cutlist-catalogue'); ?></button>
        </div>
    </div>
    <div class="machining-overlay" id="machiningOverlay">
        <div class="machining-modal" id="machiningModal">
            <div class="machining-context-bar">
                <span class="ctx-field ctx-rownum" id="mRownum">1</span>
                <span class="ctx-field ctx-decor" id="mDecor">-</span>
                <span class="ctx-field ctx-small" id="mThick">-</span>
                <span class="ctx-field ctx-small" id="mLength">-</span>
                <span class="ctx-field ctx-small" id="mWidth">-</span>
                <span class="ctx-field ctx-small" id="mQty">-</span>
                <span class="ctx-field ctx-desc" id="mDesc">-</span>
                <span class="ctx-field ctx-edge" id="mL1">-</span>
                <span class="ctx-field ctx-edge" id="mL2">-</span>
                <span class="ctx-field ctx-edge" id="mW1">-</span>
                <span class="ctx-field ctx-edge" id="mW2">-</span>
                <span class="machining-context-title"><?php esc_html_e('Machining details', 'cutlist-catalogue'); ?></span>
                <span class="machining-context-close" id="machiningClose">&times;</span>
            </div>
            <div class="machining-body">
                <div class="machining-sidebar">
                    <div class="machining-select-wrap" id="machiningSelectWrap">
                        <div class="machining-select-trigger" id="machiningSelectTrigger" role="button" tabindex="0">
                            <span id="machiningSelectPlaceholder"><?php esc_html_e('Select machining option', 'cutlist-catalogue'); ?></span>
                            <span class="machining-select-arrow">
                                <svg height="14" width="14" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                                    <path d="M4.516 7.548c0.436-0.446 1.043-0.481 1.576 0l3.908 3.747 3.908-3.747c0.533-0.481 1.141-0.446 1.574 0 0.436 0.445 0.408 1.197 0 1.615-0.406 0.418-4.695 4.502-4.695 4.502-0.217 0.223-0.502 0.335-0.787 0.335s-0.57-0.112-0.789-0.335c0 0-4.287-4.084-4.695-4.502s-0.436-1.17 0-1.615z" fill="#888" />
                                </svg>
                            </span>
                        </div>
                        <div class="machining-selected-row" id="machiningSelectedRow" style="display:none">
                            <div class="machining-selected-value" id="machiningSelectedValue" role="button" tabindex="0"></div>
                            <button type="button" class="machining-add-btn" id="machiningAddBtn"><?php esc_html_e('Add', 'cutlist-catalogue'); ?></button>
                        </div>
                        <div class="machining-option-dropdown" id="machiningOptionDropdown">
                            <div class="machining-option-group">
                                <div class="machining-option-header"><?php esc_html_e('Panel shaping', 'cutlist-catalogue'); ?></div>
                                <div class="machining-option-item" data-option="angled-cut"><?php esc_html_e('Angled cut', 'cutlist-catalogue'); ?></div>
                            </div>
                            <div class="machining-option-group">
                                <div class="machining-option-header"><?php esc_html_e('Surface shaping', 'cutlist-catalogue'); ?></div>
                                <div class="machining-option-item" data-option="groove"><?php esc_html_e('Groove', 'cutlist-catalogue'); ?></div>
                                <div class="machining-option-item disabled" data-option="j-handle"><?php esc_html_e('J handle', 'cutlist-catalogue'); ?></div>
                            </div>
                            <div class="machining-option-group">
                                <div class="machining-option-header"><?php esc_html_e('Hinge holes', 'cutlist-catalogue'); ?></div>
                                <div class="machining-option-item" data-option="blum-screw-on">Blum 35mm Screw-On</div>
                                <div class="machining-option-item" data-option="blum-inserta">Blum 35mm INSERTA</div>
                            </div>
                            <div class="machining-option-group">
                                <div class="machining-option-header"><?php esc_html_e('Shelf holes', 'cutlist-catalogue'); ?></div>
                                <div class="machining-option-item" data-option="hole-5mm">5mm &#8960; <?php esc_html_e('diameter hole', 'cutlist-catalogue'); ?></div>
                                <div class="machining-option-item" data-option="hole-7-5mm">7.5mm &#8960; <?php esc_html_e('diameter hole', 'cutlist-catalogue'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="machining-applied-list" id="machiningAppliedList"></div>
                </div>
                <div class="machining-canvas">
                    <div class="machining-grain">
                        <div class="machining-grain-label"><?php esc_html_e('Grain direction', 'cutlist-catalogue'); ?></div>
                        <div class="machining-grain-box">
                            <span class="edge-grain-arrow">&#8596;</span>
                        </div>
                    </div>
                    <div class="machining-diagram" id="machiningDiagram">
                        <div id="machiningKonvaStage" style="width:500px;height:460px;"></div>
                    </div>
                    <div class="machining-panel-shows">
                        <div class="machining-grain-label"><?php esc_html_e('Panel shows', 'cutlist-catalogue'); ?></div>
                        <div class="machining-face-box" id="machiningFaceBox">
                            <div class="machining-face-inner">
                                <div class="machining-face-side front"><div>FRONT</div><div>FACE</div></div>
                                <div class="machining-face-side back"><div>BACK</div><div>FACE</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="machining-zoom">
                        <button type="button" id="machiningZoomIn">+</button>
                        <button type="button" id="machiningZoomOut">-</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SPRAY FINISHING OVERLAY (reuses the machining modal styles) -->
    <div class="machining-overlay" id="sprayOverlay">
        <div class="machining-modal" id="sprayModal">
            <div class="machining-context-bar">
                <span class="ctx-field ctx-rownum" id="sRownum">1</span>
                <span class="ctx-field ctx-decor" id="sDecor">-</span>
                <span class="ctx-field ctx-small" id="sThick">-</span>
                <span class="ctx-field ctx-small" id="sLength">-</span>
                <span class="ctx-field ctx-small" id="sWidth">-</span>
                <span class="ctx-field ctx-small" id="sQty">-</span>
                <span class="ctx-field ctx-desc" id="sDesc">-</span>
                <span class="ctx-field ctx-edge" id="sL1">-</span>
                <span class="ctx-field ctx-edge" id="sL2">-</span>
                <span class="ctx-field ctx-edge" id="sW1">-</span>
                <span class="ctx-field ctx-edge" id="sW2">-</span>
                <span class="machining-context-title"><?php esc_html_e('Spray finishing details', 'cutlist-catalogue'); ?></span>
                <span class="machining-context-close" id="sprayClose">&times;</span>
            </div>
            <div class="machining-body">
                <div class="machining-sidebar spray-sidebar">
                    <select class="machining-select" id="spraySelect" style="color:#333;">
                        <option value=""><?php esc_html_e('Select spray finishing option', 'cutlist-catalogue'); ?></option>
                        <?php foreach ($spray_finishes as $finish) : ?>
                            <option value="<?php echo esc_attr($finish['slug']); ?>"><?php echo esc_html($finish['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="spraySidebarBody"></div>
                    <div class="spray-total-row">
                        <span><?php esc_html_e('Total panel spray price:', 'cutlist-catalogue'); ?></span>
                        <strong id="sprayTotal">&pound;0.00</strong>
                    </div>
                </div>
                <div class="machining-canvas">
                    <div class="machining-grain">
                        <div class="machining-grain-label"><?php esc_html_e('Spray area', 'cutlist-catalogue'); ?></div>
                        <div class="spray-area-box">
                            <div class="spray-area-value" id="sprayAreaValue">-</div>
                            <div>SQ. M.</div>
                        </div>
                    </div>
                    <div class="machining-diagram" id="sprayDiagram">
                        <div class="machining-edge-label edge-top">L1</div>
                        <div class="machining-edge-label edge-left">W1</div>
                        <div class="machining-panel" id="sprayPanel"></div>
                        <div class="machining-edge-label edge-right">W2</div>
                        <div class="machining-edge-label edge-bottom">L2</div>
                        <div class="machining-dim-line vertical">
                            <span class="machining-dim-value" id="sDimWidth">2000 mm</span>
                        </div>
                        <div class="machining-dim-line horizontal">
                            <span class="machining-dim-value" id="sDimLength">2000 mm</span>
                        </div>
                    </div>
                    <div class="machining-panel-shows">
                        <div class="machining-grain-label"><?php esc_html_e('Panel shows', 'cutlist-catalogue'); ?></div>
                        <div class="machining-face-box" id="sprayFaceBox">
                            <div>FRONT</div>
                            <div>FACE</div>
                        </div>
                        <button type="button" class="spray-rotate-btn" id="sprayRotate"><?php esc_html_e('Rotate', 'cutlist-catalogue'); ?></button>
                    </div>
                    <div class="machining-zoom">
                        <button type="button" id="sprayZoomIn">+</button>
                        <button type="button" id="sprayZoomOut">-</button>
                    </div>
                    <button type="button" class="spray-save-btn" id="spraySave"><?php esc_html_e('Save', 'cutlist-catalogue'); ?></button>
                </div>
            </div>
        </div>
    </div>
