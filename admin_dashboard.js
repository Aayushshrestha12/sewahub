alert("admin_dashboard.js loaded");

let currentTab = 'dashboard';
let sidebarCollapsed = false;

// DOM Elements
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const toggleIcon = document.getElementById('toggleIcon');
const navItems = document.querySelectorAll('.nav-item[data-tab]');
const contentSections = document.querySelectorAll('.content-section');
const quickActionBtns = document.querySelectorAll('.quick-action-btn[data-tab]');

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    showTab('dashboard');

});
// Event Listeners
function initializeEventListeners() {
    // Sidebar toggle
    sidebarToggle.addEventListener('click', toggleSidebar);
    
    // Navigation items
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.getAttribute('data-tab');
            showTab(tab);
        });
    });
    
    // Quick action buttons
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            showTab(tab);
        });
    });
    
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // Search functionality
    setupSearchFilters();
    
    // Modal close on outside click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

// Sidebar Functions
function toggleSidebar() {
    sidebarCollapsed = !sidebarCollapsed;
    
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        toggleIcon.className = 'fas fa-chevron-right';
    } else {
        sidebar.classList.remove('collapsed');
        toggleIcon.className = 'fas fa-chevron-left';
    }
}

// Tab Navigation
function showTab(tabName) {
    // Update active nav item
    navItems.forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('data-tab') === tabName) {
            item.classList.add('active');
        }
    });
    
    // Show content section
    contentSections.forEach(section => {
        section.classList.remove('active');
        if (section.id === tabName) {
            section.classList.add('active');
        }
    });
    
    currentTab = tabName;
}

// Search Filters
function setupSearchFilters() {
    const searchInputs = [
        { input: 'userSearch', table: 'usersTable' },
        { input: 'providerSearch', table: 'providersTable' },
        { input: 'serviceSearch', table: 'servicesTable' },
        { input: 'bookingSearch', table: 'bookingsTable' },
        { input: 'paymentSearch', table: 'paymentsTable' }
    ];
    
    searchInputs.forEach(({ input, table }) => {
        const searchInput = document.getElementById(input);
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterTable(this.value, table);
            });
        }
    });
}

function filterTable(searchTerm, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

// Modal Functions
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function showAddUserModal() {
    showModal('addUserModal');
}

function showAddServiceModal() {
    showModal('addServiceModal');
}

// Toast Notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Profile Functions
function saveProfile() {
    const name = document.getElementById('adminName').value;
    const email = document.getElementById('adminEmail').value;
    const phone = document.getElementById('adminPhone').value;
    const address = document.getElementById('adminAddress').value;
    const bio = document.getElementById('adminBio').value;
    
    // Simulate saving
    setTimeout(() => {
        showToast('Profile updated successfully!');
    }, 500);
}

// User Management Functions
function addUser() {
    const name = document.getElementById('newUserName').value;
    const email = document.getElementById('newUserEmail').value;
    const phone = document.getElementById('newUserPhone').value;
    
    if (!name || !email || !phone) {
        showToast('Please fill all required fields', 'error');
        return;
    }
    
    // Add to table
    const table = document.getElementById('usersTable').querySelector('tbody');
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td>${name}</td>
        <td>${email}</td>
        <td>${phone}</td>
        <td><span class="badge active">Active</span></td>
        <td>${new Date().toISOString().split('T')[0]}</td>
        <td>
            <button class="edit-btn" onclick="editUser(this)"><i class="fas fa-edit"></i></button>
            <button class="delete-btn" onclick="deleteUser(this)"><i class="fas fa-trash"></i></button>
        </td>
    `;
    
    // Clear form and close modal
    document.getElementById('addUserForm').reset();
    closeModal('addUserModal');
    showToast('User added successfully!');
}

function editUser(button) {
    const row = button.closest('tr');
    const cells = row.querySelectorAll('td');
    
    // Create inline editing
    cells[0].innerHTML = `<input type="text" value="${cells[0].textContent}" class="edit-input">`;
    cells[1].innerHTML = `<input type="email" value="${cells[1].textContent}" class="edit-input">`;
    cells[2].innerHTML = `<input type="tel" value="${cells[2].textContent}" class="edit-input">`;
    
    // Change buttons
    cells[5].innerHTML = `
        <button class="approve-btn" onclick="saveUser(this)"><i class="fas fa-save"></i></button>
        <button class="reject-btn" onclick="cancelEdit(this)"><i class="fas fa-times"></i></button>
    `;
}

function saveUser(button) {
    const row = button.closest('tr');
    const inputs = row.querySelectorAll('.edit-input');
    const cells = row.querySelectorAll('td');
    
    // Update cells with input values
    cells[0].textContent = inputs[0].value;
    cells[1].textContent = inputs[1].value;
    cells[2].textContent = inputs[2].value;
    
    // Restore action buttons
    cells[5].innerHTML = `
        <button class="edit-btn" onclick="editUser(this)"><i class="fas fa-edit"></i></button>
        <button class="delete-btn" onclick="deleteUser(this)"><i class="fas fa-trash"></i></button>
    `;
    
    showToast('User updated successfully!');
}

function cancelEdit(button) {
    location.reload(); // Simple way to cancel edit
}

function deleteUser(button) {
    if (confirm('Are you sure you want to delete this user?')) {
        button.closest('tr').remove();
        showToast('User deleted successfully!');
    }
}


// Service Management Functions
function addService() {
    const name = document.getElementById('newServiceName').value;
    const category = document.getElementById('newServiceCategory').value;
    const provider = document.getElementById('newServiceProvider').value;
    const price = document.getElementById('newServicePrice').value;
    
    if (!name || !category || !provider || !price) {
        showToast('Please fill all required fields', 'error');
        return;
    }
    
    // Add to table
    const table = document.getElementById('servicesTable').querySelector('tbody');
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td>${name}</td>
        <td>${category}</td>
        <td>${provider}</td>
        <td>${price}</td>
        <td><span class="badge active">Active</span></td>
        <td>
            <button class="edit-btn" onclick="editService(this)"><i class="fas fa-edit"></i></button>
            <button class="delete-btn" onclick="deleteService(this)"><i class="fas fa-trash"></i></button>
        </td>
    `;
    
    // Clear form and close modal
    document.getElementById('addServiceForm').reset();
    closeModal('addServiceModal');
    showToast('Service added successfully!');
}

function editService(button) {
    const row = button.closest('tr');
    const cells = row.querySelectorAll('td');
    
    // Create inline editing
    cells[0].innerHTML = `<input type="text" value="${cells[0].textContent}" class="edit-input">`;
    cells[1].innerHTML = `<input type="text" value="${cells[1].textContent}" class="edit-input">`;
    cells[2].innerHTML = `<input type="text" value="${cells[2].textContent}" class="edit-input">`;
    cells[3].innerHTML = `<input type="text" value="${cells[3].textContent}" class="edit-input">`;
    
    // Change buttons
    cells[5].innerHTML = `
        <button class="approve-btn" onclick="saveService(this)"><i class="fas fa-save"></i></button>
        <button class="reject-btn" onclick="cancelEdit(this)"><i class="fas fa-times"></i></button>
    `;
}

function saveService(button) {
    const row = button.closest('tr');
    const inputs = row.querySelectorAll('.edit-input');
    const cells = row.querySelectorAll('td');
    
    // Update cells with input values
    cells[0].textContent = inputs[0].value;
    cells[1].textContent = inputs[1].value;
    cells[2].textContent = inputs[2].value;
    cells[3].textContent = inputs[3].value;
    
    // Restore action buttons
    cells[5].innerHTML = `
        <button class="edit-btn" onclick="editService(this)"><i class="fas fa-edit"></i></button>
        <button class="delete-btn" onclick="deleteService(this)"><i class="fas fa-trash"></i></button>
    `;
    
    showToast('Service updated successfully!');
}

function deleteService(button) {
    if (confirm('Are you sure you want to delete this service?')) {
        button.closest('tr').remove();
        showToast('Service deleted successfully!');
    }
}

// Booking Management Functions

function updateBooking(bookingId, status) {
    if (!confirm("Are you sure you want to " + status + " this booking?")) {
        return;
    }

    const formData = new FormData();
    formData.append("booking_id", bookingId);
    formData.append("status", status);

    fetch("admin_booking_action.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {

            // ✅ Update status column instantly
            document.getElementById(`status-${bookingId}`).innerText =
                status.charAt(0).toUpperCase() + status.slice(1);

            // ✅ Replace action buttons with text
            document.getElementById(`action-${bookingId}`).innerHTML =
                '<span class="completed">Action Completed</span>';

        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Something went wrong");
    });
}



// Fetch and refresh bookings table dynamically
function fetchBookingsTable() {
    fetch('fetch_admin_bookings.php')  // new PHP endpoint to return bookings <tbody>
        .then(res => res.text())
        .then(html => {
            const tbody = document.querySelector('#bookings tbody');
            if (tbody) tbody.innerHTML = html;
        })
        .catch(err => console.error(err));
}



// Logout Function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Redirect to index.html
        window.location.href = 'index.html';
    }
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Animation for page load
window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s ease';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('collapsed');
        sidebarCollapsed = false;
        toggleIcon.className = 'fas fa-chevron-left';
    }
});
//for profile in top right
function toggleProfile() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}
function saveProfile() {
    const id = document.getElementById('adminId').value; // Hidden input with admin ID
    const first_name = document.getElementById('adminName').value;
    const email = document.getElementById('adminEmail').value;
    const password = document.getElementById('adminPassword').value; // optional

    if (!id || !first_name || !email) {
        showToast('ID, Name, and Email are required', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('first_name', first_name);
    formData.append('email', email);
    formData.append('password', password); // optional

    fetch('update_admin_profile_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Server error', 'error');
    });
}


// Close dropdown if clicked outside
window.onclick = function(event) {
    const dropdown = document.getElementById('profileDropdown');
    if (!event.target.closest('.profile-avatar') && !event.target.closest('.profile-dropdown')) {
        dropdown.classList.remove('active');
    }
}



function toggleProfile() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}
// Profile image preview
const profileInput = document.getElementById('profilePhotoInput');
const profilePreview = document.getElementById('profilePreview');

profileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            profilePreview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
        }
        reader.readAsDataURL(file);
    }
});

// Close dropdown if clicked outside
window.onclick = function(event) {
    const dropdown = document.getElementById('profileDropdown');
    if (!event.target.closest('.profile-avatar') && !event.target.closest('.profile-dropdown')) {
        dropdown.classList.remove('active');
    }
}


//for editing services
function enableEdit(id) {
    const row = document.getElementById('service_row_' + id);
    row.querySelector('.category').style.display = 'none';
    row.querySelector('.description').style.display = 'none';
    row.querySelector('.edit-category').style.display = 'inline';
    row.querySelector('.edit-description').style.display = 'inline';
    row.querySelector('.edit-btn').style.display = 'none';
    row.querySelector('.save-btn').style.display = 'inline';
    row.querySelector('.cancel-btn').style.display = 'inline';
}

function cancelEdit(id) {
    const row = document.getElementById('service_row_' + id);
    row.querySelector('.category').style.display = 'inline';
    row.querySelector('.description').style.display = 'inline';
    row.querySelector('.edit-category').style.display = 'none';
    row.querySelector('.edit-description').style.display = 'none';
    row.querySelector('.edit-btn').style.display = 'inline';
    row.querySelector('.save-btn').style.display = 'none';
    row.querySelector('.cancel-btn').style.display = 'none';
}

function saveEdit(id) {
    const row = document.getElementById('service_row_' + id);
    const category = row.querySelector('.edit-category').value;
    const description = row.querySelector('.edit-description').value;

    // Submit via POST to service_action.php
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'service_action.php';

    const inputs = [
        {name:'update_service', value:1},
        {name:'service_id', value:id},
        {name:'category', value:category},
        {name:'description', value:description}
    ];

    inputs.forEach(i => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = i.name;
        input.value = i.value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
// ===== MANAGE VENDORS (APPROVE / REJECT) =====
document.addEventListener('click', function (e) {

    const btn = e.target.closest('.action-btn');
    if (!btn) return;

    e.preventDefault();

    const vendorId = btn.dataset.id;
    const action = btn.dataset.action; // approve | reject

    if (!confirm(`Are you sure you want to ${action} this vendor?`)) return;

    fetch('vendor_action_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vendor_id: vendorId,
            action: action
        })
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP error');
        return res.json();
    })
    .then(data => {
        alert(data.message);
        if (data.success) {
            const row = document.getElementById(`vendor-${vendorId}`);
            if (row) row.remove();
        }
    })
    .catch(err => {
        console.error(err);
        alert('AJAX failed');
    });
});











