const container = document.getElementById('container');
const registerBtn = document.getElementById('register');
const loginBtn = document.getElementById('login');

registerBtn.addEventListener('click', () => {
    container.classList.add("active");
});

loginBtn.addEventListener('click', () => {
    container.classList.remove("active");
});

const passwordToggles = document.querySelectorAll('.password-toggle');

passwordToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const targetId = toggle.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            toggle.classList.remove('fa-eye');
            toggle.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            toggle.classList.remove('fa-eye-slash');
            toggle.classList.add('fa-eye');
        }
    });
});

const signupForm = document.getElementById('signupForm');
const loginForm = document.getElementById('loginForm');

function clearErrors(form) {
    if (!form) return;
    const errorMessages = form.querySelectorAll('.error-message');
    errorMessages.forEach((msg) => msg.remove());

    const errorInputs = form.querySelectorAll('.input-error');
    errorInputs.forEach((input) => input.classList.remove('input-error'));
}

function getFieldContainer(input) {
    if (!input) return null;
    const parent = input.parentElement;
    if (parent && parent.classList && parent.classList.contains('password-field')) {
        return parent;
    }
    return input;
}

function showError(input, message) {
    if (!input || !input.form) return;
    const form = input.form;
    const fieldContainer = getFieldContainer(input);
    if (!fieldContainer) return;

    input.classList.add('input-error');

    const error = document.createElement('div');
    error.className = 'error-message';
    error.textContent = message;

    const parent = fieldContainer.parentElement || form;
    parent.insertBefore(error, fieldContainer.nextSibling);
}

function isValidEmail(value) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(value);
}

function isValidFullName(name) {
    const namePattern = /^[A-Z][a-zA-Z\s'-]*$/;
    return namePattern.test(name) && name.trim().length >= 3;
}

function isValidPassword(password) {
    const passwordPattern = /^[A-Z][a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]{5,9}$/;
    return passwordPattern.test(password);
}

if (signupForm) {
    signupForm.addEventListener('submit', (event) => {
        clearErrors(signupForm);

        const nameInput = document.getElementById('signupName');
        const emailInput = document.getElementById('signupEmail');
        const passwordInput = document.getElementById('signupPassword');
        const confirmInput = document.getElementById('signupConfirmPassword');

        let hasError = false;

        if (!nameInput.value.trim()) {
            showError(nameInput, 'Please enter your full name.');
            hasError = true;
        } else if (!isValidFullName(nameInput.value.trim())) {
            showError(nameInput, 'Name must start with capital letter and be at least 3 characters.');
            hasError = true;
        }

        const emailValue = emailInput.value.trim();
        if (!emailValue) {
            showError(emailInput, 'Please enter your email address.');
            hasError = true;
        } else if (!isValidEmail(emailValue)) {
            showError(emailInput, 'Please enter a valid email address.');
            hasError = true;
        }

        const passwordValue = passwordInput.value.trim();
        if (!passwordValue) {
            showError(passwordInput, 'Please enter a password.');
            hasError = true;
        } else if (!isValidPassword(passwordValue)) {
            showError(passwordInput, 'Password must be 6-10 characters, start with capital letter, and contain lowercase letters, numbers or special characters.');
            hasError = true;
        }

        const confirmValue = confirmInput.value.trim();
        if (!confirmValue) {
            showError(confirmInput, 'Please confirm your password.');
            hasError = true;
        } else if (passwordValue && confirmValue !== passwordValue) {
            showError(confirmInput, 'Passwords do not match.');
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
        }
    });
}

if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
        clearErrors(loginForm);

        const emailInput = document.getElementById('loginEmail');
        const passwordInput = document.getElementById('loginPassword');

        let hasError = false;

        if (!emailInput.value.trim()) {
            showError(emailInput, 'Please enter your email or username.');
            hasError = true;
        }

        if (!passwordInput.value.trim()) {
            showError(passwordInput, 'Please enter your password.');
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
        }
    });
}