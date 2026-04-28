<?php
session_start();
if(isset($_SESSION['user_id'])){
    header('Location: App/View/Dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Food Shop — Sign In</title>
<link rel="stylesheet" href="Assets/Css/main.css">
</head>
<body>
<div class="container">
<div class="card">
  <div class="card-header">
    <div class="brand">Food<span>Shop</span></div>
    <div class="tagline">Your kitchen, your storefront</div>
  </div>

  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
    <button class="tab-btn" onclick="switchTab('register')">Register</button>
  </div>

  <div class="form-body">

    <!-- LOGIN PANEL -->
    <div class="form-panel active" id="loginPanel">
      <div class="form-title">Welcome back</div>
      <div class="form-subtitle">Sign in to manage your shop</div>

      <form id="loginForm" action="App/Controller/LoginAndSignup.php" method="post" novalidate>
        <input type="hidden" name="action" value="login">
        <div class="field">
          <label for="loginEmail">Email Address <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">✉</span>
            <input type="email" id="loginEmail" name="loginEmail" placeholder="you@example.com" autocomplete="email" />
          </div>
          <div class="field-error" id="loginEmailErr"></div>
        </div>

        <div class="field">
          <label for="loginPassword">Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="loginPassword" name="loginPassword" placeholder="Enter your password" autocomplete="current-password" />
          </div>
          <div class="field-error" id="loginPasswordErr"></div>
        </div>

        <button type="submit" class="btn-submit">Sign In →</button>
      </form>

      <div class="switch-hint">
        New here? <a onclick="switchTab('register')">Create an account</a>
      </div>
    </div>

    <!-- REGISTER PANEL -->
    <div class="form-panel" id="registerPanel">
      <div class="form-title">Open your shop</div>
      <div class="form-subtitle">Set up your food business in seconds</div>

      <form id="registerForm" action="App/Controller/LoginAndSignup.php" method="post" novalidate>
        <input type="hidden" name="action" value="register">
        <div class="field">
          <label for="shopTitle">Shop Name <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🏪</span>
            <input type="text" id="shopTitle" name="shopTitle" placeholder="e.g. Mama's Kitchen" autocomplete="organization" />
          </div>
          <div class="field-error" id="shopTitleErr"></div>
        </div>

        <div class="field">
          <label for="regEmail">Email Address <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">✉</span>
            <input type="email" id="regEmail" name="regEmail" placeholder="you@example.com" autocomplete="email" />
          </div>
          <div class="field-error" id="regEmailErr"></div>
        </div>

        <div class="field">
          <label for="description">Description <span class="opt">(optional)</span></label>
          <div class="input-wrap textarea-wrap">
            <span class="input-icon">📝</span>
            <textarea id="description" name="description" placeholder="Tell customers what makes your shop special…"></textarea>
          </div>
        </div>

        <div class="field">
          <label for="siteUrl">Website URL <span class="opt">(optional)</span></label>
          <div class="input-wrap">
            <span class="input-icon">🌐</span>
            <input type="url" id="siteUrl" name="siteUrl" placeholder="https://yourshop.com" autocomplete="url" />
          </div>
          <div class="field-error" id="siteUrlErr"></div>
        </div>

        <div class="field">
          <label for="regPassword">Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="regPassword" name="regPassword" placeholder="Min. 6 characters" autocomplete="new-password"
                   oninput="updateStrength(this.value)" />
          </div>
          <div class="strength-bar-wrap" id="strengthBar">
            <div class="strength-seg" id="seg1"></div>
            <div class="strength-seg" id="seg2"></div>
            <div class="strength-seg" id="seg3"></div>
            <div class="strength-seg" id="seg4"></div>
          </div>
          <div class="field-error" id="regPasswordErr"></div>
        </div>

        <button type="submit" class="btn-submit">Create Shop →</button>
      </form>

      <div class="switch-hint">
        Already have an account? <a onclick="switchTab('login')">Sign in</a>
      </div>
    </div>

  </div>
</div>
</div>
<!-- Toast notification -->
<div class="toast" id="toast">
  <span class="toast-icon" id="toastIcon"></span>
  <span id="toastMsg"></span>
</div>

<script src="Assets/Js/main.js"></script>
</body>
</html>