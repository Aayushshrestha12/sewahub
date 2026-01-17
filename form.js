document.addEventListener('DOMContentLoaded', function() {
  const authModal = document.getElementById('authModal');
  const closeBtn = authModal.querySelector('.close-btn');

  const forms = {
    userRegister: document.getElementById('userForm'),
    vendorRegister: document.getElementById('vendorForm'),
    userLogin: document.getElementById('userLoginForm'),
    vendorLogin: document.getElementById('vendorLoginForm'),
     forgot: document.getElementById("forgotPasswordForm"),
  };

  // Show specific form
  function showForm(formId) {
    Object.values(forms).forEach(f => f.style.display = 'none');
    if (forms[formId]) forms[formId].style.display = 'block';
  }

  // Open buttons
  document.querySelectorAll('.open-user').forEach(btn => {
    btn.addEventListener('click', () => {
      authModal.style.display = 'flex';
      showForm('userRegister');
    });
  });

  document.querySelectorAll('.open-vendor').forEach(btn => {
    btn.addEventListener('click', () => {
      authModal.style.display = 'flex';
      showForm('vendorRegister');
    });
  });

  document.querySelectorAll('.open-login').forEach(btn => {
    btn.addEventListener('click', () => {
      authModal.style.display = 'flex';
      showForm('userLogin'); // default login
    });
  });

  // Tab buttons inside modal (for switching login types)
  document.querySelectorAll('.tab-btn').forEach(tab => {
    tab.addEventListener('click', () => {
      showForm(tab.dataset.target);
      document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    });
  });

  // Close modal
  closeBtn.addEventListener('click', () => authModal.style.display = 'none');
  window.addEventListener('click', (e) => { if (e.target === authModal) authModal.style.display = 'none'; });
// ================= FORGOT PASSWORD HANDLING =================
const forgotForm = document.getElementById("forgotPasswordForm");

if (forgotForm) {
  // Handle "Forgot Password?" links in login forms
  document.querySelectorAll(".forgot-password a").forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const role = link.getAttribute("href").split("role=")[1] || "user";
      document.getElementById("forgotRole").value = role;
      showForm("forgot");
    });
  });

  // Handle "Back to Login" link
  document.querySelectorAll(".back-to-login a").forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const role = document.getElementById("forgotRole").value;
      if (role === "vendor") showForm("vendorLogin");
      else if (role === "admin") showForm("adminLogin");
      else showForm("userLogin");
    });
  });

  // Submit forgot password via AJAX
  forgotForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(forgotForm);

    fetch("forgot_password.php", {
      method: "POST",
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast("Reset link sent to your email!", "success");
          forgotForm.reset();
          const role = formData.get("role");
          if (role === "vendor") showForm("vendorLogin");
          else if (role === "admin") showForm("adminLogin");
          else showForm("userLogin");
        } else {
          showToast(data.message || "Failed to send reset link", "error");
        }
      })
      .catch(() => showToast("Server error. Try again.", "error"));
  });
}

  // ================= USER REGISTRATION AJAX =================
const userForm = document.getElementById('userForm');

if (userForm) {
  userForm.addEventListener('submit', function (e) {
    e.preventDefault(); // stop page reload

    const formData = new FormData(userForm);

    fetch('user_register.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('User registered successfully!', 'success');

          userForm.reset();

          // switch to login form after success
          showForm('userLogin');

          // activate login tab if exists
          document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
          const loginTab = document.querySelector('[data-target="userLogin"]');
          if (loginTab) loginTab.classList.add('active');

        } else {
          showToast(data.message || 'Registration failed', 'error');
        }
      })
      .catch(() => {
        showToast('Server error. Try again.', 'error');
      });
  });
}
// ================= VENDOR REGISTRATION AJAX =================
const vendorForm = document.getElementById('vendorForm');

if (vendorForm) {
  vendorForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(vendorForm);

    fetch('vendor_register.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('Vendor application submitted!', 'success');

          vendorForm.reset();
          authModal.style.display = 'none';
        } else {
          showToast(data.message || 'Vendor registration failed', 'error');
        }
      })
      .catch(() => {
        showToast('Server error. Try again.', 'error');
      });
  });
}
// ================= LOGIN AJAX (USER / VENDOR / ADMIN) =================
document.querySelectorAll(
  '#userLoginForm, #vendorLoginForm, #adminLoginForm'
).forEach(form => {

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch('login.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          if (data.role === 'admin') {
            window.location.href = 'admin_dashboard.php';
          } else if (data.role === 'vendor') {
            window.location.href = 'vendor_dashboard.php';
          } else {
            window.location.href = 'user_dashboard.php';
          }
        } else {
          showToast(data.message || 'Invalid login', 'error');
        }
      })
      .catch(() => {
        showToast('Server error. Try again.', 'error');
      });
  });

});

// ================= TOAST NOTIFICATION =================
function showToast(message, type) {
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerText = message;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 3000);
}

const serviceModal = document.getElementById("serviceModal");
const serviceTitle = document.getElementById("serviceTitle");
const serviceDesc = document.getElementById("serviceDesc");
const serviceFeatures = document.getElementById("serviceFeatures");

// Open service modal
document.querySelectorAll(".service-card").forEach(card => {
    card.addEventListener("click", () => {
        const service = card.dataset.service;
        serviceTitle.textContent = service;
        serviceDesc.textContent = serviceData[service].desc;

        serviceFeatures.innerHTML = "";
        serviceData[service].features.forEach(item => {
            serviceFeatures.innerHTML += `<li>✔ ${item}</li>`;
        });

        serviceModal.style.display = "flex";
    });
});

// Close modal
document.querySelector(".close-service").onclick = () => {
    serviceModal.style.display = "none";
};
document.querySelectorAll('.book-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const vendorId = btn.dataset.vendor; // get vendor ID
    if (!window.IS_LOGGED_IN) {
      document.getElementById('authModal').style.display = 'flex';
      showForm('userLogin'); // make sure this function exists in form.js
    } else {
      window.location.href = 'booking.php?vendor_id=' + vendorId;
    }
  });
});

});

