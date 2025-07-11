<?php
session_start();
$login_error = '';

// Check if user is already logged in, redirect to admin.php
if (isset($_SESSION['user_id'])) {
  header('Location: admin.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username_input = trim($_POST['username']); // Changed variable name to avoid confusion with column name
  $password_input = $_POST['password']; // Changed variable name

  // Koneksi database
  $host = getenv('DB_HOST') ?: 'localhost';
  $user = getenv('DB_USER') ?: 'root';
  $pass = getenv('DB_PASS') ?: '';
  $dbname = getenv('DB_NAME') ?: 'portofolio';
  $conn = new mysqli($host, $user, $pass, $dbname);

  if ($conn->connect_error) {
    // Display a generic error message for database connection issues
    $login_error = 'Terjadi kesalahan koneksi database. Silakan coba lagi nanti.';
  } else {
    // IMPORTANT: Changed 'users' column name to 'username' based on common practice and error message
    // Please verify your actual column name in the 'users' table.
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=? LIMIT 1"); // Corrected column name
    if ($stmt) {
      $stmt->bind_param('s', $username_input);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hash);
        $stmt->fetch();
        // TANPA HASH, cek langsung
        if ($password_input === $hash) {
          $_SESSION['user_id'] = $user_id;
          header('Location: admin.php');
          exit;
        } else {
          $login_error = 'Username atau password salah.';
        }
      } else {
        $login_error = 'Username atau password salah.';
      }
      $stmt->close();
    } else {
      // Error preparing statement, e.g., table or column doesn't exist
      $login_error = 'Terjadi kesalahan internal. Silakan coba lagi nanti.';
      error_log("Failed to prepare statement: " . $conn->error); // Log the actual error for debugging
    }
    $conn->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.9);
      /* Slightly less transparent */
      backdrop-filter: blur(12px);
      /* Stronger blur */
      border: 1px solid rgba(255, 255, 255, 0.2);
      /* Subtle border */
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      /* More pronounced shadow */
    }

    .input-focus:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
      /* Stronger focus shadow */
    }

    .btn-gradient {
      background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
      transition: all 0.3s ease-in-out;
    }

    .btn-gradient:hover {
      background: linear-gradient(90deg, #06b6d4 0%, #3b82f6 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-200 via-cyan-100 to-white font-inter p-4">
  <div class="w-full max-w-md glass-card rounded-2xl p-8">
    <div class="text-center mb-8">
      <i class="bx bx-lock-alt text-6xl text-blue-600 mb-4 animate-bounce-slow"></i>
      <h2 class="text-3xl font-extrabold text-gray-800">Admin Login</h2>
      <p class="text-gray-500 mt-2">Masuk untuk mengelola portofolio Anda</p>
    </div>

    <?php if ($login_error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-center font-medium shadow-sm" role="alert">
        <i class="bx bx-error-circle mr-2"></i> <?= htmlspecialchars($login_error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php" class="flex flex-col gap-5">
      <div>
        <label for="username" class="block text-gray-700 font-semibold mb-2 text-sm">Username</label>
        <input type="text" id="username" name="username" required autocomplete="off"
          class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none input-focus transition-all duration-300 shadow-sm"
          placeholder="Masukkan username Anda">
      </div>
      <div>
        <label for="password" class="block text-gray-700 font-semibold mb-2 text-sm">Password</label>
        <input type="password" id="password" name="password" required
          class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none input-focus transition-all duration-300 shadow-sm"
          placeholder="Masukkan password Anda">
      </div>
      <button type="submit"
        class="w-full btn-gradient text-white font-bold py-3 rounded-lg shadow-md hover:shadow-lg mt-4 flex items-center justify-center gap-2">
        <i class="bx bx-log-in-circle text-xl"></i> Login
      </button>
    </form>
  </div>
</body>

</html>