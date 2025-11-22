let currentStep = 1;
let verificationCode = "";
let userEmailValue = "";

// Update progress bar
function updateProgress() {
  const progressLine = document.getElementById("progressLine");
  const steps = [
    document.getElementById("step1"),
    document.getElementById("step2"),
    document.getElementById("step3"),
  ];

  steps.forEach((step, index) => {
    if (index < currentStep - 1) {
      step.classList.add("completed");
      step.classList.remove("active");
    } else if (index === currentStep - 1) {
      step.classList.add("active");
      step.classList.remove("completed");
    } else {
      step.classList.remove("active", "completed");
    }
  });

  const width = ((currentStep - 1) / 2) * 100;
  progressLine.style.width = width + "%";
}

// Show specific tab
function showTab(tabNumber) {
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });
  document.getElementById("tab" + tabNumber).classList.add("active");
  currentStep = tabNumber;
  updateProgress();
}

// Tab 1: Email Form
document.getElementById("emailForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const emailInput = document.getElementById("email");
  const emailBtn = document.getElementById("emailBtn");

  userEmailValue = emailInput.value.trim();

  if (!userEmailValue) return;

  emailBtn.disabled = true;
  emailBtn.classList.add("loading");

  // Generate 6-digit verification code
  verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
  console.log("Verification Code:", verificationCode); // For demo purposes

  // Simulate API call
  setTimeout(() => {
    document.getElementById("userEmail").textContent = userEmailValue;
    emailBtn.disabled = false;
    emailBtn.classList.remove("loading");
    showTab(2);
    document.querySelector(".otp-input").focus();
  }, 1500);
});

// OTP Input Handler
const otpInputs = document.querySelectorAll(".otp-input");

otpInputs.forEach((input, index) => {
  input.addEventListener("input", (e) => {
    const value = e.target.value;
    if (value.length === 1 && index < otpInputs.length - 1) {
      otpInputs[index + 1].focus();
    }
  });

  input.addEventListener("keydown", (e) => {
    if (e.key === "Backspace" && !e.target.value && index > 0) {
      otpInputs[index - 1].focus();
    }
  });

  input.addEventListener("paste", (e) => {
    e.preventDefault();
    const pastedData = e.clipboardData.getData("text").slice(0, 6);
    pastedData.split("").forEach((char, i) => {
      if (otpInputs[i]) {
        otpInputs[i].value = char;
      }
    });
    if (pastedData.length === 6) {
      otpInputs[5].focus();
    }
  });
});

// Tab 2: OTP Verification
document.getElementById("otpForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const otpBtn = document.getElementById("otpBtn");

  const enteredCode = Array.from(otpInputs)
    .map((input) => input.value)
    .join("");

  if (enteredCode.length !== 6) {
    Swal.fire({
      icon: "warning",
      title: "Incomplete Code",
      text: "Please enter all 6 digits",
      confirmButtonColor: "#3b82f6",
    });
    return;
  }

  otpBtn.disabled = true;
  otpBtn.classList.add("loading");

  setTimeout(() => {
    if (enteredCode === verificationCode) {
      otpBtn.disabled = false;
      otpBtn.classList.remove("loading");
      showTab(3);
      document.getElementById("newPassword").focus();
    } else {
      Swal.fire({
        icon: "error",
        title: "Invalid Code",
        text: "The verification code you entered is incorrect",
        confirmButtonColor: "#3b82f6",
      });
      otpBtn.disabled = false;
      otpBtn.classList.remove("loading");
      otpInputs.forEach((input) => (input.value = ""));
      otpInputs[0].focus();
    }
  }, 1000);
});

// Resend Code
document.getElementById("resendBtn").addEventListener("click", () => {
  verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
  console.log("New Verification Code:", verificationCode);

  Swal.fire({
    icon: "success",
    title: "Code Sent!",
    text: "A new verification code has been sent to your email",
    timer: 2000,
    showConfirmButton: false,
  });
});

// Password Requirements Checker
const newPasswordInput = document.getElementById("newPassword");
const requirements = {
  length: document.getElementById("reqLength"),
  uppercase: document.getElementById("reqUppercase"),
  number: document.getElementById("reqNumber"),
  special: document.getElementById("reqSpecial"),
};

newPasswordInput.addEventListener("input", () => {
  const password = newPasswordInput.value;

  // Check length
  if (password.length >= 8) {
    requirements.length.classList.add("met");
    requirements.length.classList.remove("unmet");
    requirements.length.querySelector("i").className = "fas fa-check-circle";
  } else {
    requirements.length.classList.remove("met");
    requirements.length.classList.add("unmet");
    requirements.length.querySelector("i").className = "fas fa-circle";
  }

  // Check uppercase
  if (/[A-Z]/.test(password)) {
    requirements.uppercase.classList.add("met");
    requirements.uppercase.classList.remove("unmet");
    requirements.uppercase.querySelector("i").className = "fas fa-check-circle";
  } else {
    requirements.uppercase.classList.remove("met");
    requirements.uppercase.classList.add("unmet");
    requirements.uppercase.querySelector("i").className = "fas fa-circle";
  }

  // Check number
  if (/[0-9]/.test(password)) {
    requirements.number.classList.add("met");
    requirements.number.classList.remove("unmet");
    requirements.number.querySelector("i").className = "fas fa-check-circle";
  } else {
    requirements.number.classList.remove("met");
    requirements.number.classList.add("unmet");
    requirements.number.querySelector("i").className = "fas fa-circle";
  }

  // Check special character
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
    requirements.special.classList.add("met");
    requirements.special.classList.remove("unmet");
    requirements.special.querySelector("i").className = "fas fa-check-circle";
  } else {
    requirements.special.classList.remove("met");
    requirements.special.classList.add("unmet");
    requirements.special.querySelector("i").className = "fas fa-circle";
  }
});

// Tab 3: Reset Password
document.getElementById("resetForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const resetBtn = document.getElementById("resetBtn");
  const newPassword = document.getElementById("newPassword").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  // Validate password requirements
  const hasLength = newPassword.length >= 8;
  const hasUppercase = /[A-Z]/.test(newPassword);
  const hasNumber = /[0-9]/.test(newPassword);
  const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(newPassword);

  if (!hasLength || !hasUppercase || !hasNumber || !hasSpecial) {
    Swal.fire({
      icon: "warning",
      title: "Weak Password",
      text: "Please meet all password requirements",
      confirmButtonColor: "#3b82f6",
    });
    return;
  }

  if (newPassword !== confirmPassword) {
    Swal.fire({
      icon: "error",
      title: "Passwords Don't Match",
      text: "Please make sure both passwords are the same",
      confirmButtonColor: "#3b82f6",
    });
    return;
  }

  resetBtn.disabled = true;
  resetBtn.classList.add("loading");

  setTimeout(() => {
    resetBtn.disabled = false;
    resetBtn.classList.remove("loading");

    Swal.fire({
      icon: "success",
      title: "Password Reset Successful!",
      text: "Your password has been changed successfully",
      confirmButtonText: "Go to Login",
      confirmButtonColor: "#3b82f6",
    }).then((result) => {
      if (result.isConfirmed) {
        // window.location.href = 'login.html';
        console.log("Redirecting to login page...");
      }
    });
  }, 1500);
});
