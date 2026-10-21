document.addEventListener('DOMContentLoaded', function () {
    const contactForm = document.getElementById('contactForm');
    const nameInput = document.getElementById('contactName');
    const emailInput = document.getElementById('contactEmail');
    const phoneInput = document.getElementById('contactPhone');
    const orderIdInput = document.getElementById('contactOrderId');
    const typeInput = document.getElementById('contactType');
    const subjectInput = document.getElementById('contactSubject');
    const messageInput = document.getElementById('contactMessage');
    const agreeCheckbox = document.getElementById('contactAgree');
    const statusEl = document.getElementById('contactFormStatus');
    const counterEl = document.getElementById('contactMessageCounter');

    if (messageInput && counterEl) {
        const updateCounter = () => {
            const currentLength = messageInput.value.length;
            const max = messageInput.getAttribute('maxlength') || 500;
            counterEl.textContent = currentLength + ' / ' + max;
        };

        updateCounter();
        messageInput.addEventListener('input', updateCounter);
    }

    if (!contactForm) {
        return;
    }

    function clearErrors() {
        const errorMessages = contactForm.querySelectorAll('.contact-error-message');
        errorMessages.forEach((el) => el.remove());

        const errorInputs = contactForm.querySelectorAll('.contact-input-error');
        errorInputs.forEach((input) => input.classList.remove('contact-input-error'));

        if (statusEl) {
            statusEl.textContent = '';
            statusEl.className = 'contact-form-status';
        }
    }

    function showFieldError(input, message) {
        if (!input) return;
        const group = input.parentElement;
        if (!group) return;

        input.classList.add('contact-input-error');

        const error = document.createElement('div');
        error.className = 'contact-error-message';
        error.textContent = message;

        if (
            group.lastElementChild &&
            group.lastElementChild.classList &&
            group.lastElementChild.classList.contains('contact-error-message')
        ) {
            group.replaceChild(error, group.lastElementChild);
        } else {
            group.appendChild(error);
        }
    }

    function isValidEmail(value) {
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(value);
    }

    contactForm.addEventListener('submit', function (event) {
        clearErrors();

        let hasError = false;

        const nameValue = nameInput ? nameInput.value.trim() : '';
        if (!nameValue) {
            showFieldError(nameInput, 'Please enter your name.');
            hasError = true;
        } else if (nameValue.length < 3) {
            showFieldError(nameInput, 'Name should be at least 3 characters.');
            hasError = true;
        }

        const emailValue = emailInput ? emailInput.value.trim() : '';
        if (!emailValue) {
            showFieldError(emailInput, 'Please enter your email address.');
            hasError = true;
        } else if (!isValidEmail(emailValue)) {
            showFieldError(emailInput, 'Please enter a valid email address.');
            hasError = true;
        }

        const phoneValue = phoneInput ? phoneInput.value.trim() : '';
        if (!phoneValue) {
            showFieldError(phoneInput, 'Please enter your phone number.');
            hasError = true;
        } else {
            const digits = phoneValue.replace(/[^0-9]/g, '');
            if (digits.length < 7) {
                showFieldError(phoneInput, 'Please enter a valid phone number.');
                hasError = true;
            }
        }

        const orderIdValue = orderIdInput ? orderIdInput.value.trim() : '';
        if (!orderIdValue) {
            showFieldError(orderIdInput, 'Please enter your Order ID.');
            hasError = true;
        } else if (orderIdValue.length < 4) {
            showFieldError(orderIdInput, 'Order ID should be at least 4 characters.');
            hasError = true;
        }

        const typeValue = typeInput ? typeInput.value : '';
        if (!typeValue) {
            showFieldError(typeInput, 'Please select what your query is about.');
            hasError = true;
        }

        const subjectValue = subjectInput ? subjectInput.value.trim() : '';
        if (!subjectValue) {
            showFieldError(subjectInput, 'Please enter a subject.');
            hasError = true;
        } else if (subjectValue.length < 5) {
            showFieldError(subjectInput, 'Subject should be at least 5 characters.');
            hasError = true;
        }

        const messageValue = messageInput ? messageInput.value.trim() : '';
        if (!messageValue) {
            showFieldError(messageInput, 'Please enter your message.');
            hasError = true;
        } else if (messageValue.length < 10) {
            showFieldError(messageInput, 'Message should be at least 10 characters.');
            hasError = true;
        }

        if (!agreeCheckbox || !agreeCheckbox.checked) {
            if (statusEl) {
                statusEl.textContent = 'Please confirm that we can contact you on this email.';
                statusEl.className = 'contact-form-status error';
            }
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
            if (statusEl && !statusEl.textContent) {
                statusEl.textContent = 'Please fix the errors highlighted in red.';
                statusEl.className = 'contact-form-status error';
            }
            return;
        }

        // Form will submit normally to PHP backend
    });
});

