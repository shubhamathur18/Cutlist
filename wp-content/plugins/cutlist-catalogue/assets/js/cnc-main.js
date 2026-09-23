/**
 * CNC Machining Frontend Interactions
 * Handles file dropzone, file list management, localStorage draft save/restore,
 * character counting, and form interactions on the CNC Machining page.
 */
document.addEventListener('DOMContentLoaded', function () {
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

    var i18n = (typeof cncI18n !== 'undefined') ? cncI18n : {
        expand: 'Expand',
        collapse: 'Collapse',
        toastTitle: 'Previous CNC job restored',
        toastDesc: 'Your saved drawings and job instructions have been reloaded.',
        dismiss: 'Dismiss',
        fileLimitError: 'File exceeds the 5 MB limit.',
        totalLimitError: 'Total upload size exceeds the 30 MB limit.',
        emptyError: 'Please upload at least one drawing file or provide details for your CNC job.',
        submitSuccess: 'Thank you! Your CNC machining job details have been submitted.'
    };

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

            var serializedFiles = uploadedFiles.map(function (f) {
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
                    files: uploadedFiles.map(function (f) {
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
                    collapseText.textContent = i18n.expand;
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
            '<strong>' + i18n.toastTitle + '</strong>' +
            '<span>' + i18n.toastDesc + '</span>' +
            '</div>' +
            '<button type="button" class="cutlist-draft-toast__close" aria-label="' + i18n.dismiss + '">&times;</button>';

        document.body.appendChild(toast);

        var timer = setTimeout(function () {
            if (toast.parentNode) {
                toast.classList.add('fade-out');
                setTimeout(function () { if (toast.parentNode) toast.remove(); }, 400);
            }
        }, 5000);

        var closeBtn = toast.querySelector('.cutlist-draft-toast__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                clearTimeout(timer);
                toast.classList.add('fade-out');
                setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
            });
        }
    }

    // Collapse / Expand toggle
    if (collapseBtn && jobCard) {
        collapseBtn.addEventListener('click', function () {
            var isCollapsed = jobCard.classList.toggle('is-collapsed');
            collapseBtn.setAttribute('aria-expanded', !isCollapsed);
            if (collapseText) {
                collapseText.textContent = isCollapsed ? i18n.expand : i18n.collapse;
            }
            scheduleAutoSave();
        });
    }

    // Character counter & auto-save on input
    if (textarea && charCount) {
        textarea.addEventListener('input', function () {
            charCount.textContent = this.value.length;
            scheduleAutoSave();
        });
    }

    // File choose button
    if (chooseBtn && fileInput) {
        chooseBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fileInput.click();
        });
    }

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragover');
            }, false);
        });

        dropzone.addEventListener('drop', function (e) {
            var dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                handleFiles(dt.files);
            }
        });

        fileInput.addEventListener('change', function () {
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
        var totalSize = uploadedFiles.reduce(function (acc, f) { return acc + (f.size || 0); }, 0);

        for (var i = 0; i < files.length; i++) {
            (function (file) {
                if (file.size > MAX_FILE_SIZE) {
                    showFeedback('error', 'File "' + file.name + '" ' + i18n.fileLimitError);
                    return;
                }
                if (totalSize + file.size > MAX_TOTAL_SIZE) {
                    showFeedback('error', i18n.totalLimitError);
                    return;
                }

                // Check if already in list
                var alreadyExists = uploadedFiles.some(function (f) {
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
                        reader.onload = function (evt) {
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

        uploadedFiles.forEach(function (file, index) {
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
            removeBtn.addEventListener('click', function (e) {
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
        submitBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var details = textarea ? textarea.value.trim() : '';

            if (uploadedFiles.length === 0 && !details) {
                showFeedback('error', i18n.emptyError);
                return;
            }

            // Provide visual feedback
            showFeedback('success', i18n.submitSuccess);

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
