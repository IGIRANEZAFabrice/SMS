<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Workshop Management System</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="stylesheet" href="./css/login.css">
  </head>
  <body>
    <div class="notification-container" id="notificationContainer"></div>
    <div class="login-container">
      <div class="login-header">
        <div class="logo-section">
          <div class="logo-icon">WM</div>
          <h1>Workshop Pro</h1>
          <p>Management System</p>
        </div>
        <div class="welcome-text">
          <h2>Welcome Back!</h2>
          <p>
            Login to manage your workshop efficiently<br />and keep track of
            everything in one place.
          </p>
        </div>
      </div>

      <div class="login-form">
        <div class="form-header">
          <h2>Sign In</h2>
          <p>Enter your credentials to access your account</p>
        </div>

        <form id="loginForm">
          <div class="form-group">
            <label for="email">Email </label>
            <div class="input-wrapper">
              <i class="fas fa-user"></i>
              <input
                type="text"
                id="email"
                class="form-control"
                placeholder="Enter your email or username"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <i class="fas fa-lock"></i>
              <input
                type="password"
                id="password"
                class="form-control"
                placeholder="Enter your password"
                required
              />
              <button type="button" class="password-toggle" id="togglePassword">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="forgot-password">
            <a href="">Forgot Password?</a>
          </div>

          <button type="submit" class="btn-login" id="loginBtn">
            <span class="spinner"></span>
            <span class="btn-text">
              <i class="fas fa-sign-in-alt"></i>
              Login
            </span>
          </button>
        </form>
      </div>
    </div>
   <script src="./js/login.js?v=1"></script>
  </body>
</html>
