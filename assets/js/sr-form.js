jQuery(function($) {
    'use strict';
    
    let currentStep = 1;
    const totalSteps = 5;
    let formData = {};
    
    // Initialize form
    function initForm() {
        updateStepIndicators();
        bindEvents();
        toggleContactFields();
    }
    
    // Bind all event handlers
    function bindEvents() {
        // Navigation buttons
        $('.btn-next').on('click', handleNext);
        $('.btn-prev').on('click', handlePrev);
        $('#saveData').on('click', handleSaveStep4);
        $('#serviceRequestForm').on('submit', handleFinalSubmit);
        
        // Anonymous checkbox
        $('#anonymous').on('change', toggleContactFields);
        
        // PIN confirmation validation
        $('#pin_confirm').on('input', validatePinMatch);
        
        // Real-time validation
        $('input[required], textarea[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Email validation
        $('input[type="email"]').on('blur', function() {
            validateEmail($(this));
        });
    }
    
    // Handle next button click
    function handleNext() {
        if (validateCurrentStep()) {
            collectStepData();
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
                updateStepIndicators();
                
                // If moving to review step, populate review data
                if (currentStep === 5) {
                    populateReviewData();
                }
            }
        }
    }
    
    // Handle previous button click
    function handlePrev() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
            updateStepIndicators();
        }
    }
    
    // Show specific step
    function showStep(step) {
        $('.sr-step-content').removeClass('active');
        $(`.sr-step-content[data-step="${step}"]`).addClass('active');
    }
    
    // Update step indicators
    function updateStepIndicators() {
        $('.sr-step').each(function(index) {
            const stepNum = index + 1;
            $(this).removeClass('active completed');
            
            if (stepNum < currentStep) {
                $(this).addClass('completed');
            } else if (stepNum === currentStep) {
                $(this).addClass('active');
            }
        });
    }
    
    // Validate current step
    function validateCurrentStep() {
        let isValid = true;
        const currentStepElement = $(`.sr-step-content[data-step="${currentStep}"]`);
        
        // Clear previous errors
        currentStepElement.find('.sr-error').removeClass('show').text('');
        currentStepElement.find('input, textarea, select').removeClass('error');
        
        // Validate required fields
        currentStepElement.find('input[required], textarea[required], select[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        // Step-specific validations
        switch (currentStep) {
            case 1:
                // Description validation
                const description = $('#description').val().trim();
                // if (description.length < 10) {
                //     showError('description', 'Please provide a more detailed description (at least 10 characters)');
                //     isValid = false;
                // }
                break;
                
            case 2:
                // Location validation - at least general location is required
                if (!$('#location_general').val().trim()) {
                    showError('location_general', 'General location is required');
                    isValid = false;
                }
                
                // ZIP code format validation
                const zipcode = $('#zipcode').val().trim();
                if (zipcode && !/^\d{5}(-\d{4})?$/.test(zipcode)) {
                    showError('zipcode', 'Please enter a valid ZIP code (12345 or 12345-6789)');
                    isValid = false;
                }
                break;
                
            case 3:
                // Contact validation - only if not anonymous
                if (!$('#anonymous').is(':checked')) {
                    const email = $('#email_contact').val().trim();
                    if (email && !isValidEmail(email)) {
                        showError('email_contact', 'Please enter a valid email address');
                        isValid = false;
                    }
                }
                break;
                
            case 4:
                // PIN validation
                const pin = $('#pin').val();
                const pinConfirm = $('#pin_confirm').val();
                
                if (!/^\d{4,6}$/.test(pin)) {
                    showError('pin', 'PIN must be 4-6 digits only');
                    isValid = false;
                }
                
                if (pin !== pinConfirm) {
                    showError('pin_confirm', 'PINs do not match');
                    isValid = false;
                }
                break;
                
            case 5:
                // Review email validation
                const reviewEmail = $('#review_email').val().trim();
                if (!reviewEmail) {
                    showError('review_email', 'Confirmation email is required');
                    isValid = false;
                } else if (!isValidEmail(reviewEmail)) {
                    showError('review_email', 'Please enter a valid email address');
                    isValid = false;
                }
                break;
        }
        
        return isValid;
    }
    
    // Validate individual field
    function validateField($field) {
        const value = $field.val().trim();
        const fieldName = $field.attr('name');
        let isValid = true;
        
        if ($field.prop('required') && !value) {
            showError(fieldName, 'This field is required');
            isValid = false;
        }
        
        if (isValid) {
            hideError(fieldName);
        }
        
        return isValid;
    }
    
    // Validate email format
    function validateEmail($field) {
        const email = $field.val().trim();
        const fieldName = $field.attr('name');
        
        if (email && !isValidEmail(email)) {
            showError(fieldName, 'Please enter a valid email address');
            return false;
        }
        
        hideError(fieldName);
        return true;
    }
    
    // Check if email is valid
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Validate PIN match
    function validatePinMatch() {
        const pin = $('#pin').val();
        const pinConfirm = $('#pin_confirm').val();
        
        if (pinConfirm && pin !== pinConfirm) {
            showError('pin_confirm', 'PINs do not match');
            return false;
        }
        
        hideError('pin_confirm');
        return true;
    }
    
    // Show error message
    function showError(fieldName, message) {
        $(`#${fieldName}`).addClass('error');
        $(`#${fieldName}-error`).text(message).addClass('show');
    }
    
    // Hide error message
    function hideError(fieldName) {
        $(`#${fieldName}`).removeClass('error');
        $(`#${fieldName}-error`).removeClass('show').text('');
    }
    
    // Toggle contact fields based on anonymous checkbox
    function toggleContactFields() {
        const isAnonymous = $('#anonymous').is(':checked');
        const $contactFields = $('#contact-fields');
        
        if (isAnonymous) {
            $contactFields.slideUp();
            $contactFields.find('input').prop('required', false);
        } else {
            $contactFields.slideDown();
            // Make name required if not anonymous
            $('#name').prop('required', true);
        }
    }
    
    // Collect current step data
    function collectStepData() {
        const currentStepElement = $(`.sr-step-content[data-step="${currentStep}"]`);
        
        currentStepElement.find('input, textarea, select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            let value = $field.val();
            
            if ($field.attr('type') === 'checkbox') {
                value = $field.is(':checked') ? 1 : 0;
            }
            
            if (name) {
                formData[name] = value;
            }
        });
    }
    
    // Populate review data
    function populateReviewData() {
        let reviewHtml = '';
        
        // Description
        if (formData.description) {
            reviewHtml += `
                <div class="review-item">
                    <div class="review-label">Service Request Description:</div>
                    <div class="review-value">${escapeHtml(formData.description)}</div>
                </div>
            `;
        }
        
        // Location
        reviewHtml += '<div class="review-item"><div class="review-label">Location Information:</div><div class="review-value">';
        if (formData.location_general) reviewHtml += `General Location: ${escapeHtml(formData.location_general)}<br>`;
        if (formData.location_intersection) reviewHtml += `Intersection: ${escapeHtml(formData.location_intersection)}<br>`;
        if (formData.address) reviewHtml += `Address: ${escapeHtml(formData.address)}<br>`;
        if (formData.city || formData.state || formData.zipcode) {
            reviewHtml += `City/State/ZIP: ${escapeHtml(formData.city || '')} ${escapeHtml(formData.state || '')} ${escapeHtml(formData.zipcode || '')}`;
        }
        reviewHtml += '</div></div>';
        
        // Contact
        if (formData.anonymous == 1) {
            reviewHtml += `
                <div class="review-item">
                    <div class="review-label">Contact Information:</div>
                    <div class="review-value">Anonymous Request</div>
                </div>
            `;
        } else {
            reviewHtml += '<div class="review-item"><div class="review-label">Contact Information:</div><div class="review-value">';
            if (formData.name) reviewHtml += `Name: ${escapeHtml(formData.name)}<br>`;
            if (formData.street || formData.city_contact || formData.state_contact || formData.zip_contact) {
                reviewHtml += `Address: ${escapeHtml(formData.street || '')} ${escapeHtml(formData.city_contact || '')} ${escapeHtml(formData.state_contact || '')} ${escapeHtml(formData.zip_contact || '')}<br>`;
            }
            if (formData.phone) reviewHtml += `Phone: ${escapeHtml(formData.phone)}<br>`;
            if (formData.email_contact) reviewHtml += `Email: ${escapeHtml(formData.email_contact)}`;
            reviewHtml += '</div></div>';
        }
        
        $('#review-data').html(reviewHtml);
    }
    
    // Handle Save & Continue for step 4
    function handleSaveStep4(e) {
        e.preventDefault();
        
        if (!validateCurrentStep()) {
            return;
        }
        
        collectStepData();
        setLoading(true);
        
        $.post(SR_Ajax.ajax_url, {
            action: 'sr_save_step4',
            security: SR_Ajax.nonce,
            formData: formData
        })
        .done(function(response) {
            if (response.success) {
                currentStep++;
                showStep(currentStep);
                updateStepIndicators();
                populateReviewData();
            } else {
                alert('Error saving data: ' + (response.data || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error. Please try again.');
        })
        .always(function() {
            setLoading(false);
        });
    }
    
    // Handle final form submission
    function handleFinalSubmit(e) {
        e.preventDefault();
        
        if (!validateCurrentStep()) {
            return;
        }
        
        collectStepData();
        setLoading(true);
        
        $.post(SR_Ajax.ajax_url, {
            action: 'sr_submit_form',
            security: SR_Ajax.nonce,
            formData: formData
        })
        .done(function(response) {
            if (response.success) {
                $('#sr-confirmation').html(`
                    <div class="sr-confirm-box">
                        <h3>${response.data.message}</h3>
                        <p><strong>Confirmation Number:</strong> ${response.data.confirmation}</p>
                        <p><strong>Your PIN:</strong> ${response.data.pin}</p>
                        <p>Please save these details for your records.</p>
                    </div>
                `).show();
                $('#serviceRequestForm').hide();
            } else {
                alert('Error submitting request: ' + (response.data || 'Unknown error'));
            }
        })
        .fail(function() {
            alert('Network error. Please try again.');
        })
        .always(function() {
            setLoading(false);
        });
    }
    
    // Set loading state
    function setLoading(loading) {
        if (loading) {
            $('.sr-stepper').addClass('sr-loading');
        } else {
            $('.sr-stepper').removeClass('sr-loading');
        }
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Initialize the form when document is ready
    initForm();
});
