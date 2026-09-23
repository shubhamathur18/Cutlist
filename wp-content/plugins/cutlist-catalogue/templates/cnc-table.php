<?php
/**
 * Markup for the [cnc_machining] / [cnc_machining_table] page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = 'cncmachining';
include CUTLIST_CATALOGUE_PATH . 'templates/parts/header-tabs.php';
?>


<div class="container tab-panel active" data-panel="cncmachining" id="cncPanelContainer">
    <div class="cnc-job-card" id="cncJobCard">
        <!-- Header -->
        <div class="cnc-job-header">
            <div class="cnc-job-title-wrap">
                <span class="cnc-job-title-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </span>
                <h2 class="cnc-job-title"><?php esc_html_e('CNC machining job', 'cutlist-catalogue'); ?></h2>
            </div>
            <button type="button" class="cnc-job-collapse-btn" id="cncCollapseBtn" aria-expanded="true">
                <span class="cnc-job-collapse-text"><?php esc_html_e('Collapse', 'cutlist-catalogue'); ?></span>
                <svg class="cnc-job-collapse-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="cnc-job-body" id="cncJobBody">
            <div class="cnc-job-grid">
                <!-- Left Column: Upload drawings -->
                <div class="cnc-job-col">
                    <div class="cnc-col-header">
                        <h3 class="cnc-col-title"><?php esc_html_e('Upload drawings', 'cutlist-catalogue'); ?></h3>
                        <p class="cnc-col-subtitle"><?php esc_html_e('Upload your CNC files (drawings, designs, etc.)', 'cutlist-catalogue'); ?></p>
                    </div>

                    <div class="cnc-dropzone-box" id="cncDropzone">
                        <div class="cnc-upload-cloud-badge" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path>
                                <path d="M12 12v9"></path>
                                <path d="m16 16-4-4-4 4"></path>
                            </svg>
                        </div>
                        <div class="cnc-dropzone-title"><?php esc_html_e('Drag and drop your files here', 'cutlist-catalogue'); ?></div>
                        <div class="cnc-dropzone-or"><?php esc_html_e('or', 'cutlist-catalogue'); ?></div>
                        <button type="button" class="cnc-choose-files-btn" id="cncChooseFilesBtn"><?php esc_html_e('Choose files', 'cutlist-catalogue'); ?></button>
                        <input type="file" id="cncFileInput" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.dwg,.dfx,.dxf" style="display:none">
                    </div>

                    <div class="cnc-uploaded-files-list" id="cncUploadedFilesList"></div>

                    <!-- Meta specs -->
                    <div class="cnc-file-meta-row">
                        <div class="cnc-meta-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <div class="cnc-meta-text">
                                <span class="cnc-meta-label"><?php esc_html_e('Max size per file', 'cutlist-catalogue'); ?></span>
                                <span class="cnc-meta-val">5 MB</span>
                            </div>
                        </div>

                        <div class="cnc-meta-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                                <polyline points="2 12 12 17 22 12"></polyline>
                            </svg>
                            <div class="cnc-meta-text">
                                <span class="cnc-meta-label"><?php esc_html_e('Total limit', 'cutlist-catalogue'); ?></span>
                                <span class="cnc-meta-val">30 MB</span>
                            </div>
                        </div>

                        <div class="cnc-meta-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <div class="cnc-meta-text">
                                <span class="cnc-meta-label"><?php esc_html_e('Supported formats', 'cutlist-catalogue'); ?></span>
                                <span class="cnc-meta-val">JPG, JPEG, PNG, GIF, PDF, DWG, DFX, DXF</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Additional details -->
                <div class="cnc-job-col">
                    <div class="cnc-col-header">
                        <h3 class="cnc-col-title"><?php esc_html_e('Provide additional details for your CNC job', 'cutlist-catalogue'); ?></h3>
                        <p class="cnc-col-subtitle"><?php esc_html_e('Tell us more about your requirements, including material, decor code, and any specific instructions.', 'cutlist-catalogue'); ?></p>
                    </div>

                    <div class="cnc-textarea-wrap">
                        <textarea class="cnc-details-textarea" id="cncDetailsText" maxlength="1000" placeholder="<?php esc_attr_e('Describe in detail your CNC job including material decor code and name', 'cutlist-catalogue'); ?>"></textarea>
                        <div class="cnc-char-count"><span id="cncCharCount">0</span> / 1000 <?php esc_html_e('characters', 'cutlist-catalogue'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="cnc-submit-wrap">
                <button type="button" class="cnc-submit-btn" id="cncSubmitBtn">
                    <span><?php esc_html_e('Submit', 'cutlist-catalogue'); ?></span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </div>
            <div class="cnc-feedback-toast" id="cncFeedbackToast"></div>
        </div>
    </div>
</div>