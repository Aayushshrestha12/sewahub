document.addEventListener('DOMContentLoaded', function () {
    // Select all login type options
    const options = document.querySelectorAll('.type-option');

    // Add click event to each option
    options.forEach(option => {
        option.addEventListener('click', function () {
            // Remove active class from all
            options.forEach(o => o.classList.remove('active'));

            // Add active class to clicked one
            this.classList.add('active');

            // Select the radio button inside
            this.querySelector('input[type="radio"]').checked = true;
        });
    });

// Get modal and buttons
const authModal = document.getElementById('authModal');
const closeBtn = authModal.querySelector('.close-btn');

const userForm = document.getElementById('userForm');
const vendorForm = document.getElementById('vendorForm');
const userLoginForm = document.getElementById('userLoginForm');
const vendorLoginForm = document.getElementById('vendorLoginForm');
const adminLoginForm = document.getElementById('adminLoginForm');

// Open buttons
const openLoginBtn = document.querySelectorAll('.open-login');
const openUserBtn = document.querySelectorAll('.open-user');
const openVendorBtn = document.querySelectorAll('.open-vendor');

// Helper: hide all forms
function hideAllForms() {
    userForm.style.display = 'none';
    vendorForm.style.display = 'none';
    userLoginForm.style.display = 'none';
    vendorLoginForm.style.display = 'none';
    adminLoginForm.style.display = 'none';
}

// Show modal with specific form
function showForm(formType) {
    hideAllForms();
    authModal.style.display = 'block';

    switch (formType) {
        case 'userRegister':
            userForm.style.display = 'block';
            break;
        case 'vendorRegister':
            vendorForm.style.display = 'block';
            break;
        case 'userLogin':
            userLoginForm.style.display = 'block';
            break;
        case 'vendorLogin':
            vendorLoginForm.style.display = 'block';
            break;
        case 'adminLogin':
            adminLoginForm.style.display = 'block';
            break;
    }
}

// Attach click events
openLoginBtn.forEach(btn => btn.addEventListener('click', () => showForm('userLogin')));
openUserBtn.forEach(btn => btn.addEventListener('click', () => showForm('userRegister')));
openVendorBtn.forEach(btn => btn.addEventListener('click', () => showForm('vendorRegister')));

// Close modal
closeBtn.addEventListener('click', () => authModal.style.display = 'none');

// Close modal on outside click
window.addEventListener('click', (e) => {
    if (e.target === authModal) {
        authModal.style.display = 'none';
    }
});

});

