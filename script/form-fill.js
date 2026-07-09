/*
$(document).ready(function() {
    let currentSubmissionId = null;

    // Handle form submission
    $('#form-fill-form').on('submit', function(e) {
        e.preventDefault();
        submitForm('submit');
    });

    // Handle save draft
    $('#save-draft').on('click', function() {
        submitForm('save');
    });

    function submitForm(action) {
        const nonFileData = collectFormData();

        // determine if there's anything to send (either non-file answers or at least one selected file)
        let hasContent = Object.keys(nonFileData).length > 0;
        $('#form-fill-form input[type=file]').each(function(){
            if(this.files && this.files.length > 0){
                hasContent = true;
            }
        });

        if (!hasContent) {
            if (action === 'submit') {
                alert('Please fill in at least one field before submitting.');
            }
            return;
        }

        // build FormData so that file inputs are included automatically
        const formElement = document.getElementById('form-fill-form');
        const data = new FormData(formElement);

        // overwrite formData field with our cleaned JSON
        data.set('formData', JSON.stringify(nonFileData));
        data.set('submissionTitle', getSubmissionTitle());
        data.set('user', getCurrentUser());
        data.set('action', action);

        // Show loading state
        const submitBtn = action === 'submit' ? $('.submit-form-btn') : $('#save-draft');
        const originalText = submitBtn.text();
        submitBtn.text('Saving...').prop('disabled', true);

        $.ajax({
            url: 'api/save_form_data.php',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    currentSubmissionId = response.submissionId;

                    if (action === 'submit') {
                        alert('Form submitted successfully!');
                        // Redirect back to template fill page
                        const templateId = $('input[name="templateId"]').val();
                        const user = getCurrentUser();
                        window.location.href = `index.php?view=fillTemplate&user=${user}&templateId=${templateId}`;
                    } else {
                        alert('Draft saved successfully!');
                    }
                } else {
                    alert('Error: ' + (response.error || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                let errorMessage = 'An error occurred while saving the form.';

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }

                alert('Error: ' + errorMessage);
            },
            complete: function() {
                // Reset button state
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    }

    function collectFormData() {
        const formData = {};

        // Collect all form inputs except file inputs (they go in FormData separately)
        $('#form-fill-form input, #form-fill-form textarea, #form-fill-form select').each(function() {
            const $input = $(this);
            let name = $input.attr('name');

            // Skip hidden fields and fields without names
            if (!name || name === 'templateId' || name === 'formId' || name === 'formType') {
                return;
            }

            // normalise name (remove [] from multiple file inputs)
            const cleanName = name.replace(/\[\]$/,'');

            if ($input.attr('type') === 'file') {
                // preserve existing file path(s) if there are no new files
                const existing = $input.data('existing');
                if (existing) {
                    formData[cleanName] = existing;
                }
                return; // do not process further
            }

            if ($input.attr('type') === 'checkbox') {
                // Handle checkboxes - collect all checked values
                if (!formData[cleanName]) {
                    formData[cleanName] = [];
                }
                if ($input.is(':checked')) {
                    formData[cleanName].push($input.val());
                }
            } else if ($input.attr('type') === 'radio') {
                // Handle radio buttons - only collect if checked
                if ($input.is(':checked')) {
                    formData[cleanName] = $input.val();
                }
            } else {
                // Handle text, textarea, select, number, date inputs
                const value = $input.val().trim();
                if (value !== '') {
                    formData[cleanName] = value;
                }
            }
        });

        return formData;
    }

    function getCurrentUser() {
        // Extract user from URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('user');
    }

    function getSubmissionTitle() {
        const urlParams = new URLSearchParams(window.location.search);
        const templateId = urlParams.get('templateId');
        const key = `submissionTitle:${templateId}`;
        return localStorage.getItem(key) || '';
    }

    // Auto-save functionality (save draft every 30 seconds)
    let autoSaveTimer;

    function startAutoSave() {
        autoSaveTimer = setInterval(function() {
            // simply delegate to submitForm; it already checks for content and handles files
            submitForm('save');
        }, 30000); // 30 seconds
    }

    function stopAutoSave() {
        if (autoSaveTimer) {
            clearInterval(autoSaveTimer);
        }
    }

    // Start auto-save when page loads
    startAutoSave();

    // Stop auto-save when leaving page
    $(window).on('beforeunload', function() {
        stopAutoSave();
    });

    // Form validation
    $('#form-fill-form').on('input change', function() {
        validateForm();
    });

    function validateForm() {
        let isValid = true;
        const requiredFields = $('#form-fill-form [required]');

        requiredFields.each(function() {
            const $field = $(this);
            const value = $field.val().trim();

            if (value === '') {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });

        // Update submit button state
        $('.submit-form-btn').prop('disabled', !isValid);

        return isValid;
    }

    // Initialize form validation
    validateForm();

    // Load existing form data if available
    loadExistingFormData();

    function loadExistingFormData() {
        const templateId = $('input[name="templateId"]').val();
        const formType = $('input[name="formType"]').val();
        const user = getCurrentUser();

        $.ajax({
            url: 'api/check_submission_status.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                templateId: templateId,
                user: user
            }),
            success: function(response) {
                if (response.success && response.submission) {
                    const formData = JSON.parse(response.submission.formData);
                    if (formData && formData[formType]) {
                        populateFormFields(formData[formType]);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading existing form data:', error);
            }
        });
    }

    function populateFormFields(formData) {
        Object.keys(formData).forEach(questionId => {
            const value = formData[questionId];
            const nameSelector = questionId.replace(/\[\]$/,'');
            const $field = $(`[name="${nameSelector}"]`);

            if ($field.length > 0) {
                const type = $field.attr('type');
                if (type === 'checkbox') {
                    // Handle checkboxes - check all values in array
                    if (Array.isArray(value)) {
                        value.forEach(val => {
                            $(`[name="${nameSelector}"][value="${val}"]`).prop('checked', true);
                        });
                    }
                } else if (type === 'radio') {
                    // Handle radio buttons
                    $(`[name="${nameSelector}"][value="${value}"]`).prop('checked', true);
                } else if (type === 'file') {
                    // cannot set file value for security; show existing path/preview
                    if (value) {
                        // convert windows paths to relative uploads URL
                        const normalizePath = p => {
                            let v = p.replace(/\\/g,'/');
                            if (/^[A-Za-z]:\//.test(v)) {
                                return 'uploads/' + v.split('/').pop();
                            }
                            if (v.indexOf('/uploads/') !== -1) {
                                return v.substring(v.indexOf('/uploads/')).replace(/^\//, '');
                            }
                            return v;
                        };
                        $field.data('existing', value);
                        const previewId = '#preview-' + nameSelector.replace(/[^a-z0-9]/gi,'-');
                        let html = '';
                        if (Array.isArray(value)) {
                            value.forEach(p => {
                                html += `<img src="${normalizePath(p)}" class="file-preview-img" />`;
                            });
                        } else {
                            html = `<img src="${normalizePath(value)}" class="file-preview-img" />`;
                        }
                        $(previewId).html(html);
                    }
                } else {
                    // Handle text, textarea, select, number, date inputs
                    $field.val(value);
                }
            }
        });

        // Re-validate form after populating
        validateForm();
    }
});
*/