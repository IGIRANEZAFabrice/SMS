const loginForm = document.getElementById("loginForm");
const loginBtn = document.getElementById("loginBtn");
const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");
const emailInput = document.getElementById("email");
const notificationContainer = document.getElementById("notificationContainer");

// Notification System
function showNotification(type, title, message, duration = 3000) {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;

  const iconMap = {
    error: "fa-circle-xmark",
    success: "fa-circle-check",
    warning: "fa-triangle-exclamation",
  };

  notification.innerHTML = `
          <div class="notification-icon">
            <i class="fas ${iconMap[type]}"></i>
          </div>
          <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
          </div>
          <button class="notification-close" onclick="closeNotification(this)">
            <i class="fas fa-times"></i>
          </button>
        `;

  notificationContainer.appendChild(notification);

  // Auto remove after duration
  setTimeout(() => {
    notification.classList.add("slide-out");
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, duration);
}

function closeNotification(btn) {
  const notification = btn.closest(".notification");
  notification.classList.add("slide-out");
  setTimeout(() => {
    notification.remove();
  }, 300);
}

// Toggle Password Visibility
togglePassword.addEventListener("click", () => {
  const type = passwordInput.type === "password" ? "text" : "password";
  passwordInput.type = type;
  const icon = togglePassword.querySelector("i");
  icon.classList.toggle("fa-eye");
  icon.classList.toggle("fa-eye-slash");
});

// Form Submit Handler
loginForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value;

  if (!email || !password) {
    showNotification("warning", "Missing Information", "Please fill in all fields");
    return;
  }

  loginBtn.disabled = true;
  loginBtn.classList.add("loading");

  try {
    const res = await fetch("./auth/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ identifier: email, password })
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || "Login failed");
    }

    showNotification("success", "Login Successful", "Redirecting...");
    window.location.replace(data.redirect);
  } catch (err) {
    showNotification("error", "Login Failed", err.message || "Incorrect credentials");
    loginBtn.disabled = false;
    loginBtn.classList.remove("loading");
  }
});
