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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dropzone = document.getElementById('cncDropzone');
    var fileInput = document.getElementById('cncFileInput');
    var chooseBtn = document.getElementById('cncChooseFilesBtn');
    var filesList = document.getElementById('cncUploadedFilesList');
    var textarea = document.getElementById('cncDetailsText');
    var charCount = document.getElementById('cncCharCount');
    var collapseBtn = document.getElementById('cncCollapseBtn');
    var jobCard = document.getElementById('cncJobCard');
    var collapseText = collapseBtn ? collapseBtn.querySelector('.cnc-job-collapse-text') : null;
    var submitBtn = document.getElementById('cncSubmitBtn');
    var feedbackToast = document.getElementById('cncFeedbackToast');

    var uploadedFiles = [];
    var MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    var MAX_TOTAL_SIZE = 30 * 1024 * 1024; // 30 MB
    var autoSaveTimer = null;

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(saveDraft, 300);
    }

    function saveDraft() {
        try {
            if (typeof localStorage === 'undefined') return;
            var details = textarea ? textarea.value.trim() : '';
            var isCollapsed = jobCard ? jobCard.classList.contains('is-collapsed') : false;
            var hasData = details.length > 0 || uploadedFiles.length > 0;

            if (!hasData) {
                localStorage.removeItem('cnc_job_draft');
                return;
            }

            var serializedFiles = uploadedFiles.map(function(f) {
                return {
                    name: f.name,
                    size: f.size,
                    type: f.type || '',
                    dataUrl: f.dataUrl || ''
                };
            });

            var draftData = {
                version: 1,
                timestamp: Date.now(),
                details: textarea ? textarea.value : '',
                isCollapsed: isCollapsed,
                files: serializedFiles
            };

            try {
                localStorage.setItem('cnc_job_draft', JSON.stringify(draftData));
            } catch (quotaErr) {
                // Fallback: save without bulky dataUrl if localStorage limit is reached
                var leanDraft = {
                    version: 1,
                    timestamp: Date.now(),
                    details: textarea ? textarea.value : '',
                    isCollapsed: isCollapsed,
                    files: uploadedFiles.map(function(f) {
                        return { name: f.name, size: f.size, type: f.type || '', dataUrl: '' };
                    })
                };
                localStorage.setItem('cnc_job_draft', JSON.stringify(leanDraft));
            }
        } catch (e) {
            console.warn('Could not save CNC draft:', e);
        }
    }

    function restoreDraft() {
        try {
            if (typeof localStorage === 'undefined') return false;
            var rawDraft = localStorage.getItem('cnc_job_draft');
            if (!rawDraft) return false;

            var draft = JSON.parse(rawDraft);
            if (!draft) return false;

            var restoredAny = false;

            // Restore details text
            if (typeof draft.details === 'string' && draft.details.length > 0) {
                if (textarea) {
                    textarea.value = draft.details;
                    if (charCount) charCount.textContent = draft.details.length;
                    restoredAny = true;
                }
            }

            // Restore files
            if (Array.isArray(draft.files) && draft.files.length > 0) {
                uploadedFiles = draft.files.slice();
                renderFileList();
                restoredAny = true;
            }

            // Restore collapse state
            if (draft.isCollapsed && jobCard && collapseBtn) {
                jobCard.classList.add('is-collapsed');
                collapseBtn.setAttribute('aria-expanded', 'false');
                if (collapseText) {
                    collapseText.textContent = '<?php echo esc_js(__('Expand', 'cutlist-catalogue')); ?>';
                }
            }

            if (restoredAny) {
                showDraftRestoredToast();
                return true;
            }
            return false;
        } catch (err) {
            console.error('Error restoring CNC draft:', err);
            return false;
        }
    }

    function showDraftRestoredToast() {
        var existing = document.getElementById('cutlistDraftToast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'cutlistDraftToast';
        toast.className = 'cutlist-draft-toast';
        toast.innerHTML =
            '<div class="cutlist-draft-toast__icon">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>' +
            '</div>' +
            '<div class="cutlist-draft-toast__content">' +
            '<strong><?php echo esc_js(__('Previous CNC job restored', 'cutlist-catalogue')); ?></strong>' +
            '<span><?php echo esc_js(__('Your saved drawings and job instructions have been reloaded.', 'cutlist-catalogue')); ?></span>' +
            '</div>' +
            '<button type="button" class="cutlist-draft-toast__close" aria-label="<?php echo esc_js(__('Dismiss', 'cutlist-catalogue')); ?>">&times;</button>';

        document.body.appendChild(toast);

        var timer = setTimeout(function() {
            if (toast.parentNode) {
                toast.classList.add('fade-out');
                setTimeout(function() { if (toast.parentNode) toast.remove(); }, 400);
            }
        }, 5000);

        var closeBtn = toast.querySelector('.cutlist-draft-toast__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                clearTimeout(timer);
                toast.classList.add('fade-out');
                setTimeout(function() { if (toast.parentNode) toast.remove(); }, 300);
            });
        }
    }

    // Collapse / Expand toggle
    if (collapseBtn && jobCard) {
        collapseBtn.addEventListener('click', function() {
            var isCollapsed = jobCard.classList.toggle('is-collapsed');
            collapseBtn.setAttribute('aria-expanded', !isCollapsed);
            if (collapseText) {
                collapseText.textContent = isCollapsed ? '<?php echo esc_js(__('Expand', 'cutlist-catalogue')); ?>' : '<?php echo esc_js(__('Collapse', 'cutlist-catalogue')); ?>';
            }
            scheduleAutoSave();
        });
    }

    // Character counter & auto-save on input
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            scheduleAutoSave();
        });
    }

    // File choose button
    if (chooseBtn && fileInput) {
        chooseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.click();
        });
    }

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        ['dragenter', 'dragover'].forEach(function(eventName) {
            dropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(function(eventName) {
            dropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragover');
            }, false);
        });

        dropzone.addEventListener('drop', function(e) {
            var dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                handleFiles(dt.files);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length) {
                handleFiles(this.files);
            }
        });
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes < 1024) return (bytes || 0) + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function handleFiles(files) {
        showFeedback('', '');
        var totalSize = uploadedFiles.reduce(function(acc, f) { return acc + (f.size || 0); }, 0);

        for (var i = 0; i < files.length; i++) {
            (function(file) {
                if (file.size > MAX_FILE_SIZE) {
                    showFeedback('error', '<?php echo esc_js(__('File "', 'cutlist-catalogue')); ?>' + file.name + '<?php echo esc_js(__(' exceeds the 5 MB limit.', 'cutlist-catalogue')); ?>');
                    return;
                }
                if (totalSize + file.size > MAX_TOTAL_SIZE) {
                    showFeedback('error', '<?php echo esc_js(__('Total upload size exceeds the 30 MB limit.', 'cutlist-catalogue')); ?>');
                    return;
                }

                // Check if already in list
                var alreadyExists = uploadedFiles.some(function(f) {
                    return f.name === file.name && f.size === file.size;
                });

                if (!alreadyExists) {
                    var fileEntry = {
                        name: file.name,
                        size: file.size,
                        type: file.type || '',
                        dataUrl: '',
                        fileObj: file
                    };
                    uploadedFiles.push(fileEntry);
                    totalSize += file.size;

                    // Asynchronously read dataUrl for persistence
                    if (typeof FileReader !== 'undefined') {
                        var reader = new FileReader();
                        reader.onload = function(evt) {
                            fileEntry.dataUrl = evt.target.result || '';
                            scheduleAutoSave();
                        };
                        reader.readAsDataURL(file);
                    }
                }
            })(files[i]);
        }
        renderFileList();
        scheduleAutoSave();
    }

    function renderFileList() {
        if (!filesList) return;
        filesList.innerHTML = '';

        uploadedFiles.forEach(function(file, index) {
            var chip = document.createElement('div');
            chip.className = 'cnc-file-chip';

            var nameSpan = document.createElement('span');
            nameSpan.className = 'cnc-file-chip-name';
            nameSpan.textContent = file.name + ' (' + formatFileSize(file.size) + ')';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'cnc-file-chip-remove';
            removeBtn.innerHTML = '&times;';
            removeBtn.setAttribute('aria-label', 'Remove ' + file.name);
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                uploadedFiles.splice(index, 1);
                renderFileList();
                scheduleAutoSave();
            });

            chip.appendChild(nameSpan);
            chip.appendChild(removeBtn);
            filesList.appendChild(chip);
        });
    }

    function showFeedback(type, message) {
        if (!feedbackToast) return;
        feedbackToast.className = 'cnc-feedback-toast';
        if (type && message) {
            feedbackToast.classList.add(type === 'success' ? 'is-success' : 'is-error');
            feedbackToast.textContent = message;
        } else {
            feedbackToast.textContent = '';
        }
    }

    // Submit handler
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var details = textarea ? textarea.value.trim() : '';

            if (uploadedFiles.length === 0 && !details) {
                showFeedback('error', '<?php echo esc_js(__('Please upload at least one drawing file or provide details for your CNC job.', 'cutlist-catalogue')); ?>');
                return;
            }

            // Provide visual feedback
            showFeedback('success', '<?php echo esc_js(__('Thank you! Your CNC machining job details have been submitted.', 'cutlist-catalogue')); ?>');
            
            // Clear saved draft once submitted
            localStorage.removeItem('cnc_job_draft');
        });
    }

    // Unload handlers to guarantee save
    window.addEventListener('beforeunload', saveDraft);
    window.addEventListener('pagehide', saveDraft);

    // Initial restoration
    restoreDraft();
});
</script>