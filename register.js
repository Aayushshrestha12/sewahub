document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registrationForm");

    form.addEventListener("submit", function(e) {
        let errors = [];

        // Get form values
        const firstName = form.firstName.value.trim();
        const lastName = form.lastName.value.trim();
        const email = form.email.value.trim();
        const password = form.password.value;
        const confirmPassword = form.confirmPassword.value;
        const userType = form.userType.value;
        const agreeToTerms = form.agreeToTerms.checked;

        // Validate
        if (firstName === "") errors.push("First name is required.");
        if (lastName === "") errors.push("Last name is required.");

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) errors.push("Invalid email format.");

        if (password.length < 6) errors.push("Password must be at least 6 characters.");
        if (password !== confirmPassword) errors.push("Passwords do not match.");

        if (!userType) errors.push("Please select account type.");
        if (!agreeToTerms) errors.push("You must agree to Terms & Privacy Policy.");

        // If there are errors, prevent submission and show errors
        if (errors.length > 0) {
            e.preventDefault(); // Stop form submission

            // Remove old error messages
            const oldErrors = document.querySelector(".form-error");
            if (oldErrors) oldErrors.remove();

            // Create new error div
            const errorDiv = document.createElement("div");
            errorDiv.classList.add("form-error");
            errors.forEach(err => {
                const p = document.createElement("p");
                p.textContent = err;
                errorDiv.appendChild(p);
            });

            // Insert error messages above the form
            form.parentNode.insertBefore(errorDiv, form);
        }
    });
});
