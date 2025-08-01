<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  // still using the hardcoded credentials
  if ($username === 'Admin' && $password === 'admin123') {
    $_SESSION['username'] = $username;
    $_SESSION['loggedin'] = true;
    header('Location: ../pages/dashboard.php');
    exit;
  } else {
    $error = "Invalid username or password";
  }
}

if (isset($_SESSION['loggedin'])) {
  header('Location: ../pages/dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ModernTech | Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background: url('../assets/bg-2.png') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Segoe UI', sans-serif;
    }

    .login-card {
      backdrop-filter: blur(15px);
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      box-shadow: 0 0 32px rgba(0, 0, 0, 0.25);
      padding: 2rem;
      color: #fff;
      width: 100%;
      max-width: 400px;
    }

    .login-card img {
      width: 80px;
      margin: 0 auto 1rem;
      display: block;
      border-radius: 50%;
      box-shadow: 0 0 32px rgba(0, 0, 0, 0.8);
    }

    .login-card h2 {
      text-align: center;
      color: #fff;
      font-weight: 600;
    }

    .login-card p.subtext {
      text-align: center;
      margin-top: -0.5rem;
      color: #e0e0e0;
    }

    .form-control {
      border-radius: 8px;
    }

    .btn-login {
      background: #4f46e5;
      color: white;
      font-weight: 500;
      border-radius: 8px;
    }

    .btn-login:hover {
      background: #4338ca;
    }

    .credentials {
      text-align: center;
      font-size: 0.9rem;
      color: #ddd;
      margin-top: 1rem;
    }

    .alert {
      font-size: 0.9rem;
    }

    @media (max-width: 768px) {
      .login-card {
        padding: 1.5rem;
      }

      .login-card img {
        width: 70px;
      }

      .login-card h2 {
        font-size: 1.5rem;
      }

      .credentials {
        font-size: 0.85rem;
      }
    }

    @media (max-width: 576px) {
      .login-card {
        padding: 1.2rem;
      }

      .login-card img {
        width: 60px;
      }

      .login-card h2 {
        font-size: 1.3rem;
      }

      .btn-login {
        font-size: 0.95rem;
      }
    }
  </style>
</head>

<body>
  <div class="d-flex vh-100 align-items-center justify-content-center px-3">
    <div class="login-card">
      <img src="../assets/logo-2.png" alt="Logo" />
      <h2>ModernTech HR</h2>
      <p class="subtext">Login to your account</p>
      <p class="text-center text-secondary small">Admin</p>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label for="username" class="form-label text-white">Username</label>
          <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label text-white">Password</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password"
              required>
            <button class="btn btn-outline-light" type="button" id="togglePassword" tabindex="-1">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye"
                viewBox="0 0 16 16">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z" />
                <path d="M8 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z" /> 
                <!-- svg icon for the password visibility toggle -->
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-login w-100">Login</button>
      </form>

      <p ></p>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  const togglePassword = document.querySelector('#togglePassword');
  const password = document.querySelector('#password');

  togglePassword.addEventListener('click', () => {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);

    // Toggle eye icon
    togglePassword.innerHTML = type === 'password' 
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
           <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
           <path d="M8 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/>
         </svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
           <path d="M13.359 11.238l1.396 1.397-1.06 1.06-1.395-1.396A7.523 7.523 0 0 1 8 13.5C3 13.5 0 8 0 8c.52-.755 1.184-1.5 2-2.14L.146 3.858 1.206 2.798l12.32 12.32-1.06 1.06-1.107-1.107zM5.465 7.472a2 2 0 0 0 2.88 2.88l-2.88-2.88z"/>
           <path d="M11.27 8.762a3 3 0 0 0-3.365-3.365l3.365 3.365z"/>
         </svg>`;
  });
</script>

</body>

</html>