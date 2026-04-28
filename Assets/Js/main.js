function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach((b, i) => {
      b.classList.toggle('active', (tab === 'login' && i === 0) || (tab === 'register' && i === 1));
    });
    document.getElementById('loginPanel').classList.toggle('active', tab === 'login');
    document.getElementById('registerPanel').classList.toggle('active', tab === 'register');

    // Force re-trigger animation
    const panel = document.getElementById(tab === 'login' ? 'loginPanel' : 'registerPanel');
    panel.classList.remove('active');
    requestAnimationFrame(() => panel.classList.add('active'));
  }

  // Toast
  function showToast(msg, icon = '✅') {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').textContent = icon;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  // Validation helpers
  function setError(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    const err = document.getElementById(errId);
    if (msg) {
      input.classList.add('invalid');
      err.textContent = msg;
      err.classList.add('show');
      return false;
    } else {
      input.classList.remove('invalid');
      err.textContent = '';
      err.classList.remove('show');
      return true;
    }
  }

  function isValidEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  function isValidUrl(v) {
    try { new URL(v); return true; } catch { return false; }
  }

  // Live validation on blur
  document.getElementById('loginEmail').addEventListener('blur', function() {
    const v = this.value.trim();
    if (v && !isValidEmail(v)) setError('loginEmail', 'loginEmailErr', 'Please enter a valid email');
    else if (v) setError('loginEmail', 'loginEmailErr', '');
  });

  document.getElementById('regEmail').addEventListener('blur', function() {
    const v = this.value.trim();
    if (v && !isValidEmail(v)) setError('regEmail', 'regEmailErr', 'Please enter a valid email');
    else if (v) setError('regEmail', 'regEmailErr', '');
  });

  document.getElementById('siteUrl').addEventListener('blur', function() {
    const v = this.value.trim();
    if (v && !isValidUrl(v)) setError('siteUrl', 'siteUrlErr', 'Please enter a valid URL (include https://)');
    else setError('siteUrl', 'siteUrlErr', '');
  });

  // Password strength meter
  const segColors = ['#e74c3c', '#e67e22', '#f1c40f', '#27ae60'];
  function updateStrength(pw) {
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw) && /[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    for (let i = 1; i <= 4; i++) {
      const seg = document.getElementById('seg' + i);
      seg.style.background = i <= score ? segColors[score - 1] : 'var(--border)';
    }
  }

  // Login submit
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
  
    const form = this;
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPassword').value.trim();
    let ok = true;
  
    // Validation
    if (!email)
      ok = setError('loginEmail','loginEmailErr','Email address is required') && ok;
    else if (!isValidEmail(email))
      ok = setError('loginEmail','loginEmailErr','Please enter a valid email') && ok;
    else
      setError('loginEmail','loginEmailErr','');
  
    if (!pass)
      ok = setError('loginPassword','loginPasswordErr','Password is required') && ok;
    else
      setError('loginPassword','loginPasswordErr','');
  
    if (!ok) return;
  
    // 🔥 AJAX CALL
    const formData = new FormData(form);
    
    fetch('/food-order-app/App/Controller/LoginAndSignup.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || 'Signed in successfully!', '🍽');
        if (data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        form.reset();
      } else {
        showToast(data.message || 'Login failed', '❌');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Server error!', '❌');
    });
  });

  // Register submit
  document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
  
    const form = this;
    const title   = document.getElementById('shopTitle').value.trim();
    const email   = document.getElementById('regEmail').value.trim();
    const pass    = document.getElementById('regPassword').value.trim();
    const siteUrl = document.getElementById('siteUrl').value.trim();
    let ok = true;
  
    // Validation
    if (!title) ok = setError('shopTitle', 'shopTitleErr', 'Shop name is required') && ok;
    else        setError('shopTitle', 'shopTitleErr', '');
  
    if (!email)
      ok = setError('regEmail', 'regEmailErr', 'Email address is required') && ok;
    else if (!isValidEmail(email))
      ok = setError('regEmail', 'regEmailErr', 'Please enter a valid email') && ok;
    else
      setError('regEmail', 'regEmailErr', '');
  
    if (siteUrl && !isValidUrl(siteUrl)) {
      ok = setError('siteUrl', 'siteUrlErr', 'Please enter a valid URL (include https://)') && ok;
    } else {
      setError('siteUrl', 'siteUrlErr', '');
    }
  
    if (pass.length < 6)
      ok = setError('regPassword', 'regPasswordErr', 'Password must be at least 6 characters') && ok;
    else
      setError('regPassword', 'regPasswordErr', '');
  
    if (!ok) return;
  
    // 🔥 AJAX CALL
    const formData = new FormData(form);
  
    fetch('/food-order-app/App/Controller/LoginAndSignup.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      console.log(data);
  
      if (data.success) {
        showToast('Shop created successfully!');
        switchTab('login');

        form.reset();

        // Reset progress UI
        document.getElementById('seg1').style.background = 'var(--border)';
        document.getElementById('seg2').style.background = 'var(--border)';
        document.getElementById('seg3').style.background = 'var(--border)';
        document.getElementById('seg4').style.background = 'var(--border)';
      } else {
        showToast(data.message || 'Registration failed', '❌');
      }
    })
    .catch(err => {
      console.error(err);
      showToast('Server error!', '❌');
    });
  });