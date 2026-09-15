<?php
require_once 'auth.php';
include 'config.php';

require_guest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
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
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $hash_esc = mysqli_real_escape_string($conn, $hash);

            $sql = "INSERT INTO profiles (full_name, email, password, role, status)
                    VALUES ('$name_esc', '$email_esc', '$hash_esc', 'user', 'active')";

            if (mysqli_query($conn, $sql)) {
                flash("Account created! You can sign in now.", 'success', 'success');
                header("Location: login.php");
                exit();
            } else {
                $error = 'Registration failed: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register · Travel CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 30px 20px; font-family: 'Nunito', sans-serif;
        }
        .auth-card {
            max-width: 480px; width: 100%;
            background: #fff; border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            padding: 40px 35px;
        }
        .auth-logo {
            width: 70px; height: 70px; border-radius: 50%;
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 28px; margin: 0 auto 20px;
        }
        .auth-title { text-align: center; font-weight: 800; color: #333; margin-bottom: 6px; }
        .auth-sub   { text-align: center; color: #858796; margin-bottom: 30px; }
        .form-control { border-radius: 8px; height: 46px; }
        .btn-primary { border-radius: 8px; height: 46px; font-weight: 700; }
        .auth-footer { text-align: center; margin-top: 20px; color: #858796; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-logo"><i class="fas fa-user-plus"></i></div>
    <h3 class="auth-title">Create Account</h3>
    <p class="auth-sub">Register to access Travel CMS</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label class="font-weight-bold small text-uppercase">Full Name</label>
            <input type="text" name="full_name" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label class="font-weight-bold small text-uppercase">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="font-weight-bold small text-uppercase">Password</label>
                <input type="password" name="password" class="form-control"
                       minlength="6" required>
            </div>
            <div class="form-group col-md-6">
                <label class="font-weight-bold small text-uppercase">Confirm</label>
                <input type="password" name="confirm" class="form-control"
                       minlength="6" required>
            </div>
        </div>

        <button type="submit" name="register" class="btn btn-primary btn-block mt-3">
            <i class="fas fa-user-check"></i> Create Account
        </button>
    </form>

    <div class="auth-footer">
        Already have an account? <a href="login.php" class="font-weight-bold">Sign In</a>
    </div>
</div>

</body>
</html>