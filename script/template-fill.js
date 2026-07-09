/*
$(document).ready(function() {
    // Check form completion status
    checkFormStatus();
    
    // Remove form card click handler - only button clicks are allowed
    
    function checkFormStatus() {
        const templateId = getCurrentTemplateId();
        const user = getCurrentUser();
        
        if (!templateId || !user) return;
        
        // Check if there's an existing submission for this template
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
                    updateFormStatus(response.submission.formData);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking submission status:', error);
            }
        });
    }
    
    function updateFormStatus(formData) {
        if (!formData) return;
        
        const formDataObj = typeof formData === 'string' ? JSON.parse(formData) : formData;
        
        // Update status for each form type
        Object.keys(formDataObj).forEach(formType => {
            const formData = formDataObj[formType];
            const $statusElement = $(`[data-form-type="${formType}"] .form-status`);
            
            if (formData && Object.keys(formData).length > 0) {
                $statusElement.text('Completed').removeClass('in-progress').addClass('completed');
            } else {
                $statusElement.text('In Progress').removeClass('completed').addClass('in-progress');
            }
        });
        
        // Trigger event for progress update
        $(document).trigger('formStatusUpdated');
    }
    
    function getCurrentUser() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('user');
    }
    
    function getCurrentTemplateId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('templateId');
    }
    
    // Remove hover effects from form cards since they're not clickable
    
    // Add progress indicator
    function updateProgress() {
        const totalForms = $('.form-card').length;
        const completedForms = $('.form-status.completed').length;
        const progressPercentage = totalForms > 0 ? (completedForms / totalForms) * 100 : 0;
        
        // Create or update progress bar
        let $progressBar = $('.template-progress');
        if ($progressBar.length === 0) {
            $('.template-info').after(`
                <div class="template-progress">
                    <div class="progress-info">
                        <span class="progress-text">Progress: ${completedForms}/${totalForms} forms completed</span>
                        <span class="progress-percentage">${Math.round(progressPercentage)}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                    </div>
                </div>
            `);
        } else {
            $progressBar.find('.progress-text').text(`Progress: ${completedForms}/${totalForms} forms completed`);
            $progressBar.find('.progress-percentage').text(`${Math.round(progressPercentage)}%`);
            $progressBar.find('.progress-fill').css('width', `${progressPercentage}%`);
        }
    }
    
    // Update progress when page loads
    updateProgress();
    
    // Update progress when form status changes
    $(document).on('formStatusUpdated', function() {
        updateProgress();
    });
    
    // Handle submit all forms
    $('#submit-all-forms').on('click', function() {
        const templateId = getCurrentTemplateId();
        const user = getCurrentUser();
        
        if (confirm('Are you sure you want to submit all forms? This action cannot be undone.')) {
            $.ajax({
                url: 'api/submit_all_forms.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    templateId: templateId,
                    user: user
                }),
                success: function(response) {
                    if (response.success) {
                        alert('All forms submitted successfully!');
                        window.location.href = `index.php?view=submissions&user=${user}&query=`;
                    } else {
                        alert('Error: ' + (response.error || 'Failed to submit forms'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error submitting forms:', error);
                    alert('Error submitting forms. Please try again.');
                }
            });
        }
    });
    
    // Update submit all button state
    function updateSubmitAllButton() {
        const totalForms = $('.form-card').length;
        const completedForms = $('.form-status.completed').length;
        const $submitBtn = $('#submit-all-forms');
        
        if (completedForms === totalForms && totalForms > 0) {
            $submitBtn.prop('disabled', false);
        } else {
            $submitBtn.prop('disabled', true);
        }
    }
    
    // Update submit all button when progress updates
    $(document).on('formStatusUpdated', function() {
        updateSubmitAllButton();
    });
    
    // Initial update
    updateSubmitAllButton();

    // Persist submission title in localStorage and preload
    const templateId = getCurrentTemplateId();
    const titleKey = `submissionTitle:${templateId}`;
    const $titleInput = $('#submission-title');
    if ($titleInput.length) {
        const savedTitle = localStorage.getItem(titleKey) || '';
        if (savedTitle) {
            $titleInput.val(savedTitle);
        }
        $titleInput.on('input', function(){
            localStorage.setItem(titleKey, $(this).val());
        });
    }
});
*/