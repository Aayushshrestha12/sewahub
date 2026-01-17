document.addEventListener("DOMContentLoaded", function() {
    // ===========================
    // Sidebar Toggle (LIKE ADMIN)
    // ===========================
    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const toggleIcon = document.getElementById("toggleIcon");
    const mainContent = document.querySelector(".main-content");

    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            mainContent.classList.toggle("expanded");

            if (sidebar.classList.contains("collapsed")) {
                toggleIcon.classList.remove("fa-chevron-left");
                toggleIcon.classList.add("fa-chevron-right");
            } else {
                toggleIcon.classList.remove("fa-chevron-right");
                toggleIcon.classList.add("fa-chevron-left");
            }
        });
    }

    // ===========================
    // Profile Dropdown + AJAX Update
    // ===========================
    const profileAvatar = document.getElementById("profileAvatar");
    const profileDropdown = document.getElementById("profileDropdown");
    const profileForm = document.getElementById("profileForm");

    if (profileAvatar && profileDropdown) {

        // Toggle dropdown on avatar click
        profileAvatar.addEventListener("click", (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle("show");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            if (!profileDropdown.contains(e.target) && !profileAvatar.contains(e.target)) {
                profileDropdown.classList.remove("show");
            }
        });

        // Change Photo button triggers file input
        const changePhotoBtn = document.getElementById("changePhotoBtn");
        const profilePhotoInput = document.getElementById("profilePhotoInput");
        const uploadPhotoForm = document.getElementById("uploadPhotoForm");

        changePhotoBtn.addEventListener("click", () => {
            profilePhotoInput.click();
        });

        // AJAX upload on file select
        profilePhotoInput.addEventListener("change", () => {
            if (profilePhotoInput.files.length === 0) return;

            const formData = new FormData();
            formData.append('action', 'upload_photo');
            formData.append('profile_photo', profilePhotoInput.files[0]);

            fetch('vendor_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(() => {
                const fileURL = URL.createObjectURL(profilePhotoInput.files[0]);
                profileAvatar.src = fileURL;
                showToast("✅ Photo updated", true);
            })
            .catch(err => {
                console.error(err);
                showToast("❌ Photo upload failed", false);
            });
        });

        // AJAX profile update
        profileForm.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(profileForm);

            fetch("update_vendor_profile.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, true);
                } else {
                    showToast(data.message || "Profile update failed", false);
                }
            })
            .catch(err => {
                console.error(err);
                showToast("AJAX error: check console", false);
            });
        });

        // Toast container
        let toastContainer = document.getElementById("toastContainer");
        if(!toastContainer) {
            toastContainer = document.createElement("div");
            toastContainer.id = "toastContainer";
            toastContainer.style.position = "fixed";
            toastContainer.style.top = "20px";
            toastContainer.style.right = "20px";
            toastContainer.style.zIndex = "9999";
            document.body.appendChild(toastContainer);
        }

        function showToast(message, success = true) {
            const toast = document.createElement("div");
            toast.innerText = message;
            toast.style.background = success ? "#4CAF50" : "#f44336";
            toast.style.color = "#fff";
            toast.style.padding = "10px 20px";
            toast.style.marginTop = "10px";
            toast.style.borderRadius = "5px";
            toast.style.boxShadow = "0 2px 6px rgba(0,0,0,0.2)";
            toast.style.opacity = "0";
            toast.style.transition = "opacity 0.5s";
            toastContainer.appendChild(toast);

            requestAnimationFrame(() => { toast.style.opacity = "1"; });

            setTimeout(() => {
                toast.style.opacity = "0";
                toast.addEventListener("transitionend", () => toast.remove());
            }, 3000);
        }
    }

    // ===========================
    // Services Section (Add/Edit/Delete)
    // ===========================
    const serviceModal = document.getElementById('serviceModal');
    const addServiceBtn = document.getElementById('addServiceBtn');
    const closeServiceModal = document.getElementById('closeServiceModal');
    const serviceForm = document.getElementById('serviceForm');
    const serviceIdSelect = document.getElementById('service_id_select');
    const priceInput = document.getElementById('price');
    const availableFromInput = document.getElementById('available_from');
    const availableToInput = document.getElementById('available_to');
    const vendorServiceIdInput = document.getElementById('vendor_service_id');
    const serviceModalTitle = document.getElementById('serviceModalTitle');

    addServiceBtn.addEventListener('click', () => {
        serviceForm.reset();
        vendorServiceIdInput.value = '';
        serviceModalTitle.innerText = 'Add Service';
        serviceModal.style.display = 'block';
    });

    closeServiceModal.addEventListener('click', () => {
        serviceModal.style.display = 'none';
    });

    document.querySelectorAll('.editServiceBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            vendorServiceIdInput.value = btn.dataset.id;
            serviceIdSelect.value = btn.dataset.service_id;
            priceInput.value = btn.dataset.price;
            availableFromInput.value = btn.dataset.available_from ? btn.dataset.available_from.replace(' ', 'T') : '';
            availableToInput.value = btn.dataset.available_to ? btn.dataset.available_to.replace(' ', 'T') : '';
            serviceModalTitle.innerText = 'Edit Service';
            serviceModal.style.display = 'block';
        });
    });

    serviceForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(serviceForm);
        fetch('save_service.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    alert('Service saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error(err));
    });

    document.querySelectorAll('.deleteServiceBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            if(!confirm('Are you sure you want to delete this service?')) return;
            const vendorServiceId = btn.dataset.id;
            fetch('delete_vendor_service.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'vendor_service_id=' + encodeURIComponent(vendorServiceId)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    alert('Service deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error(err));
        });
    });

    // ===========================
    // Bookings Section (Mark Completed)
    // ===========================
    document.querySelectorAll('.markCompletedBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            const bookingId = btn.dataset.bookingId;
            if (!confirm('Mark this booking as completed?')) return;

            fetch('update_booking_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'booking_id=' + encodeURIComponent(bookingId)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = btn.closest('tr');
                    const statusCell = row.querySelector('.booking-status');
                    if (statusCell) statusCell.textContent = 'Completed';

                    const completedSpan = document.createElement('span');
                    completedSpan.className = 'completed-label';
                    completedSpan.textContent = 'Completed';
                    btn.replaceWith(completedSpan);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('AJAX error: check console');
            });
        });
    });

  // ===========================
// Sidebar navigation
// ===========================
function initSidebarNavigation() {
    const links = document.querySelectorAll('.sidebar-nav a');
    if (!links.length) return;

    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.dataset.section;
            if (!section) return;

            const targetSection = document.getElementById(section + '-section');
            if (!targetSection) return;

            // Hide all sections
            document.querySelectorAll('.content-section').forEach(sec => {
                sec.classList.remove('active');
            });

            // Show selected section
            targetSection.classList.add('active');

            // Update sidebar active state
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
                item.classList.remove('active');
            });
            link.parentElement.classList.add('active');

            // Close profile dropdown if open
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) profileDropdown.classList.remove('show');
        });
    });
}

 initSidebarNavigation();

});