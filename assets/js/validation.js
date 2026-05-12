// Email validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Phone validation
function validatePhone(phone) {
    const re = /^[\d\s\-\(\)]+$/;
    return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

// Password strength
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return {
        score: strength,
        label: ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][strength - 1] || 'Very Weak'
    };
}

// Form validation
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const errors = {};
    
    Object.keys(rules).forEach(fieldName => {
        const field = form.elements[fieldName];
        const rule = rules[fieldName];
        const value = field.value.trim();
        
        // Required check
        if (rule.required && !value) {
            errors[fieldName] = `${rule.label || fieldName} is required`;
            isValid = false;
            return;
        }
        
        // Email validation
        if (rule.type === 'email' && value && !validateEmail(value)) {
            errors[fieldName] = 'Please enter a valid email';
            isValid = false;
            return;
        }
        
        // Phone validation
        if (rule.type === 'phone' && value && !validatePhone(value)) {
            errors[fieldName] = 'Please enter a valid phone number';
            isValid = false;
            return;
        }
        
        // Min length
        if (rule.minLength && value.length < rule.minLength) {
            errors[fieldName] = `Minimum ${rule.minLength} characters required`;
            isValid = false;
            return;
        }
        
        // Max length
        if (rule.maxLength && value.length > rule.maxLength) {
            errors[fieldName] = `Maximum ${rule.maxLength} characters allowed`;
            isValid = false;
            return;
        }
        
        // Match field
        if (rule.match) {
            const matchField = form.elements[rule.match];
            if (value !== matchField.value.trim()) {
                errors[fieldName] = `${rule.label} does not match`;
                isValid = false;
                return;
            }
        }
    });
    
    // Display errors
    displayErrors(form, errors);
    
    return isValid;
}

// Display form errors
function displayErrors(form, errors) {
    // Clear previous errors
    form.querySelectorAll('.form-error').forEach(el => el.remove());
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    
    // Display new errors
    Object.keys(errors).forEach(fieldName => {
        const field = form.elements[fieldName];
        if (field) {
            field.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            errorDiv.textContent = errors[fieldName];
            field.parentNode.appendChild(errorDiv);
        }
    });
}

// Real-time validation
function initRealtimeValidation(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    Object.keys(rules).forEach(fieldName => {
        const field = form.elements[fieldName];
        if (field) {
            field.addEventListener('blur', () => {
                validateForm(formId, rules);
            });
        }
    });
}
