<?php
require_once 'auth.php';
include 'config.php';

require_guest();

$mode  = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$error = '';

// ============ LOGIN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $email_esc = mysqli_real_escape_string($conn, $email);
        $r = mysqli_query($conn, "SELECT * FROM profiles WHERE email = '$email_esc' LIMIT 1");

        if (!$r || mysqli_num_rows($r) === 0) {
            $error = 'Invalid email or password.';
        } else {
            $user = mysqli_fetch_assoc($r);

            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Contact an administrator.';
            } elseif (empty($user['password']) || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
            } else {
                login_user($conn, $user);
                flash("Welcome back, " . $user['full_name'] . "!", 'success', 'success');
                header("Location: home.php?section=dashboard");
                exit();
            }
        }
    }
    $mode = 'login';
}

// ============ REGISTER ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $name_esc  = mysqli_real_escape_string($conn, $full_name);
        $email_esc = mysqli_real_escape_string($conn, $email);

        $check = mysqli_query($conn, "SELECT id FROM profiles WHERE email = '$email_esc' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash     = password_hash($password, PASSWORD_DEFAULT);
            $hash_esc = mysqli_real_escape_string($conn, $hash);

            $sql = "INSERT INTO profiles (full_name, email, password, role, status)
                    VALUES ('$name_esc', '$email_esc', '$hash_esc', 'user', 'active')";

            if (mysqli_query($conn, $sql)) {
                flash("Welcome to the wild, " . $full_name . "! Please sign in.", 'success', 'success');
                header("Location: login.php");
                exit();
            } else {
                $error = 'Registration failed: ' . mysqli_error($conn);
            }
        }
    }
    $mode = 'register';
}

$flash = pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $mode === 'register' ? 'Join' : 'Sign In'; ?> · Travel CMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  html, body { height: 100%; }

  body {
    font-family: 'Inter', -apple-system, sans-serif;
    color: #2a1810;
    background: #faf7f0;
    -webkit-font-smoothing: antialiased;
  }

  .auth-page {
    display: flex;
    min-height: 100vh;
  }

  /* ============ VISUAL PANEL ============ */
  .auth-visual {
    flex: 1.15;
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg,
      #f4a261 0%,
      #e76f51 22%,
      #d94e3b 42%,
      #a8352b 60%,
      #4a1d10 82%,
      #1a0a04 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 60px 70px;
    color: #fff;
  }

  /* Sun glow */
  .auth-visual::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 42%;
    transform: translate(-50%, -50%);
    width: 480px;
    height: 480px;
    border-radius: 50%;
    background: radial-gradient(circle,
      rgba(255, 220, 130, 0.55) 0%,
      rgba(255, 180, 80, 0.35) 30%,
      rgba(255, 140, 60, 0) 70%);
    animation: sunPulse 6s ease-in-out infinite;
    pointer-events: none;
  }

  @keyframes sunPulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1);    opacity: 0.9; }
    50%      { transform: translate(-50%, -50%) scale(1.08); opacity: 1;   }
  }

  .scene {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
  }

  .brand-overlay {
    position: relative;
    z-index: 2;
    max-width: 520px;
  }

  .brand-eyebrow {
    display: inline-block;
    font-size: 10px;
    letter-spacing: 5px;
    text-transform: uppercase;
    opacity: 0.7;
    margin-bottom: 20px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.3);
  }

  .brand-mark h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(40px, 5vw, 62px);
    font-weight: 900;
    letter-spacing: 2px;
    line-height: 1;
    margin-bottom: 16px;
    text-shadow: 0 4px 30px rgba(0,0,0,0.5);
  }

  .brand-mark h1 em {
    font-style: italic;
    color: #e9c46a;
    font-weight: 400;
  }

  .brand-tagline {
    font-size: 14px;
    letter-spacing: 3px;
    text-transform: uppercase;
    opacity: 0.9;
    margin-bottom: 32px;
    line-height: 1.8;
  }

  .big5 {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    font-family: 'Playfair Display', serif;
    font-size: 13px;
    letter-spacing: 3px;
    text-transform: uppercase;
    opacity: 0.85;
  }

  .big5 .dot {
    color: #e9c46a;
    font-size: 18px;
    line-height: 1;
  }

  /* ============ FORM PANEL ============ */
  .auth-form-side {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 50px;
    background: #faf7f0;
    position: relative;
  }

  .form-wrap {
    width: 100%;
    max-width: 400px;
    animation: formIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  }

  @keyframes formIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .form-heading {
    font-family: 'Playfair Display', serif;
    font-size: 38px;
    font-weight: 700;
    color: #2a1810;
    line-height: 1.1;
    margin-bottom: 10px;
  }

  .form-heading em {
    font-style: italic;
    color: #c9873f;
  }

  .form-sub {
    color: #8a7f70;
    font-size: 14px;
    margin-bottom: 36px;
    line-height: 1.6;
  }

  /* Fields */
  .field {
    margin-bottom: 22px;
  }

  .field label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #6b5d4f;
    margin-bottom: 9px;
  }

  .field input {
    width: 100%;
    padding: 15px 16px;
    border: 1.5px solid #e6dfd0;
    border-radius: 6px;
    background: #fff;
    font-size: 15px;
    font-family: inherit;
    color: #2a1810;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
  }

  .field input::placeholder {
    color: #b8ae9e;
  }

  .field input:focus {
    outline: none;
    border-color: #c9873f;
    background: #fffdf8;
    box-shadow: 0 0 0 4px rgba(201, 135, 63, 0.12);
  }

  .password-wrap { position: relative; }

  .toggle-pwd {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #8a7f70;
    cursor: pointer;
    padding: 6px 8px;
    font-size: 14px;
    transition: color 0.15s;
  }

  .toggle-pwd:hover { color: #c9873f; }

  /* Submit button */
  .btn-submit {
    width: 100%;
    padding: 16px;
    background: #2a1810;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.2s, transform 0.06s;
    font-family: inherit;
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }

  .btn-submit:hover { background: #4a2818; }
  .btn-submit:active { transform: translateY(1px); }
  .btn-submit i { font-size: 13px; }

  /* Error alert */
  .alert-error {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #fdf3f0;
    color: #a8352b;
    border-left: 3px solid #c84d3a;
    padding: 14px 16px;
    border-radius: 4px;
    font-size: 13.5px;
    margin-bottom: 24px;
    line-height: 1.5;
  }

  .alert-error i { flex-shrink: 0; margin-top: 2px; }

  /* Toggle + footer */
  .form-switch {
    text-align: center;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #ebe3d5;
    font-size: 13.5px;
    color: #8a7f70;
  }

  .form-switch a {
    color: #2a1810;
    font-weight: 600;
    text-decoration: none;
    margin-left: 6px;
    border-bottom: 1.5px solid #c9873f;
    padding-bottom: 1px;
    transition: color 0.15s;
  }

  .form-switch a:hover { color: #c9873f; }

  .form-footnote {
    text-align: center;
    margin-top: 28px;
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #b8ae9e;
  }

  /* ============ RESPONSIVE ============ */
  @media (max-width: 960px) {
    .auth-page { flex-direction: column; }

    .auth-visual {
      min-height: 46vh;
      padding: 40px 32px;
    }

    .brand-mark h1 { font-size: 40px; }
    .brand-tagline { font-size: 11px; letter-spacing: 2px; margin-bottom: 22px; }
    .big5 { font-size: 11px; letter-spacing: 2px; }

    .auth-form-side { padding: 40px 28px 60px; }
    .form-heading { font-size: 30px; }
  }

  @media (max-width: 480px) {
    .auth-visual { padding: 30px 22px; min-height: 38vh; }
    .brand-mark h1 { font-size: 32px; }
    .auth-form-side { padding: 34px 20px 50px; }
  }
</style>
</head>
<body>

<div class="auth-page">

  <!-- ============ VISUAL SIDE ============ -->
  <aside class="auth-visual">
    <svg class="scene" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
      <!-- Birds in the sky -->
      <g stroke="#3d1f0f" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.45">
        <path d="M 250 200 q 10 -8 20 0 q 10 -8 20 0"/>
        <path d="M 320 240 q 8 -6 16 0 q 8 -6 16 0"/>
        <path d="M 900 170 q 10 -8 20 0 q 10 -8 20 0"/>
        <path d="M 960 210 q 8 -6 16 0 q 8 -6 16 0"/>
        <path d="M 830 250 q 6 -5 12 0 q 6 -5 12 0"/>
      </g>

      <!-- Far hills -->
      <path d="M 0 640 Q 220 590 440 620 T 880 610 Q 1060 600 1200 630 L 1200 900 L 0 900 Z"
            fill="#5c2a17" opacity="0.55"/>

      <!-- Mid hills -->
      <path d="M 0 720 Q 300 680 620 700 T 1200 690 L 1200 900 L 0 900 Z"
            fill="#3d1f0f" opacity="0.85"/>

      <!-- Acacia tree — large, left -->
      <g transform="translate(230, 680)" fill="#1a0a04">
        <path d="M 0 160 L 0 30" stroke="#1a0a04" stroke-width="6" stroke-linecap="round" fill="none"/>
        <path d="M 0 75 Q -35 55 -60 48" stroke="#1a0a04" stroke-width="3.5" stroke-linecap="round" fill="none"/>
        <path d="M 0 75 Q 35 55 60 48" stroke="#1a0a04" stroke-width="3.5" stroke-linecap="round" fill="none"/>
        <ellipse cx="0" cy="5" rx="95" ry="18"/>
        <ellipse cx="-55" cy="20" rx="52" ry="13"/>
        <ellipse cx="55" cy="20" rx="52" ry="13"/>
      </g>

      <!-- Acacia tree — medium, right -->
      <g transform="translate(950, 700)" fill="#1a0a04">
        <path d="M 0 150 L 0 30" stroke="#1a0a04" stroke-width="5" stroke-linecap="round" fill="none"/>
        <path d="M 0 70 Q -28 55 -48 50" stroke="#1a0a04" stroke-width="3" stroke-linecap="round" fill="none"/>
        <path d="M 0 70 Q 28 55 48 50" stroke="#1a0a04" stroke-width="3" stroke-linecap="round" fill="none"/>
        <ellipse cx="0" cy="5" rx="78" ry="15"/>
        <ellipse cx="-45" cy="18" rx="42" ry="11"/>
        <ellipse cx="45" cy="18" rx="42" ry="11"/>
      </g>

      <!-- Acacia tree — small, middle -->
      <g transform="translate(620, 720)" fill="#1a0a04" opacity="0.9">
        <path d="M 0 130 L 0 25" stroke="#1a0a04" stroke-width="4" stroke-linecap="round" fill="none"/>
        <ellipse cx="0" cy="5" rx="65" ry="13"/>
        <ellipse cx="-35" cy="16" rx="34" ry="9"/>
        <ellipse cx="35" cy="16" rx="34" ry="9"/>
      </g>

      <!-- Foreground ridge -->
      <path d="M 0 800 Q 400 770 800 785 T 1200 780 L 1200 900 L 0 900 Z"
            fill="#0d0502"/>
    </svg>

    <div class="brand-overlay">
      <span class="brand-eyebrow">East African Expeditions</span>
      <div class="brand-mark">
        <h1>TRAVEL<em>CMS</em></h1>
      </div>
      <p class="brand-tagline">Explore the wild · Manage your journey</p>
      <div class="big5">
        <span>Lion</span><span class="dot">·</span>
        <span>Leopard</span><span class="dot">·</span>
        <span>Elephant</span><span class="dot">·</span>
        <span>Buffalo</span><span class="dot">·</span>
        <span>Rhino</span>
      </div>
    </div>
  </aside>

  <!-- ============ FORM SIDE ============ -->
  <main class="auth-form-side">
    <div class="form-wrap">

      <?php if ($mode === 'login'): ?>

        <h2 class="form-heading">Welcome <em>back</em>.</h2>
        <p class="form-sub">Sign in to continue managing your expeditions, posts, and gallery.</p>

        <?php if ($error): ?>
          <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate>
          <input type="hidden" name="action" value="login">

          <div class="field">
            <label for="login-email">Email Address</label>
            <input type="email" id="login-email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="you@example.com" required autofocus>
          </div>

          <div class="field password-wrap">
            <label for="login-password">Password</label>
            <input type="password" id="login-password" name="password"
                   placeholder="••••••••" required>
            <button type="button" class="toggle-pwd" data-target="login-password" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>

          <button type="submit" class="btn-submit">
            <i class="fas fa-sign-in-alt"></i> Sign In
          </button>
        </form>

        <div class="form-switch">
          New to Travel CMS?<a href="?mode=register">Create an account</a>
        </div>

      <?php else: ?>

        <h2 class="form-heading">Join the <em>expedition</em>.</h2>
        <p class="form-sub">Create your account and start managing tours, wildlife, and travellers.</p>

        <?php if ($error): ?>
          <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate>
          <input type="hidden" name="action" value="register">

          <div class="field">
            <label for="reg-name">Full Name</label>
            <input type="text" id="reg-name" name="full_name"
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                   placeholder="Amara Okonkwo" required autofocus>
          </div>

          <div class="field">
            <label for="reg-email">Email Address</label>
            <input type="email" id="reg-email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="you@example.com" required>
          </div>

          <div class="field password-wrap">
            <label for="reg-password">Password</label>
            <input type="password" id="reg-password" name="password"
                   placeholder="At least 6 characters" minlength="6" required>
            <button type="button" class="toggle-pwd" data-target="reg-password" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>

          <div class="field password-wrap">
            <label for="reg-confirm">Confirm Password</label>
            <input type="password" id="reg-confirm" name="confirm"
                   placeholder="Repeat your password" minlength="6" required>
            <button type="button" class="toggle-pwd" data-target="reg-confirm" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
          </div>

          <button type="submit" class="btn-submit">
            <i class="fas fa-user-plus"></i> Create Account
          </button>
        </form>

        <div class="form-switch">
          Already registered?<a href="?mode=login">Sign in instead</a>
        </div>

      <?php endif; ?>

      <p class="form-footnote">© <?php echo date('Y'); ?> Travel CMS · Nairobi · Arusha · Serengeti</p>
    </div>
  </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Password visibility toggles
  document.querySelectorAll('.toggle-pwd').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = document.getElementById(this.dataset.target);
      const icon  = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });

  // Flash message
  <?php if ($flash['text'] !== ''): ?>
  Swal.fire({
    icon: <?php echo json_encode($flash['icon'] ?: 'success'); ?>,
    title: <?php echo json_encode($flash['type'] === 'success' ? 'Karibu!' : 'Notice'); ?>,
    text: <?php echo json_encode($flash['text']); ?>,
    confirmButtonColor: '#c9873f',
    timer: 3500,
    timerProgressBar: true,
    toast: true,
    position: 'top-end',
    showConfirmButton: false
  });
  <?php endif; ?>
</script>
</body>
</html>