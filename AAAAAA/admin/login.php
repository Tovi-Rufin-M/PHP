<?php
// technowatch/admin/login.php
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// 1. Include the database connection
// NOTE: Make sure 'includes/db_connect.php' exists and functions correctly.
require_once 'includes/db_connect.php'; 

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Fetch the user record from the database
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $hashed_password = $user['password_hash'];

        // 3. Verify the password against the hash
        if (password_verify($password, $hashed_password)) {
            // Success: Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['user_id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_full_name'] = $user['full_name'];

            // 4. Redirect to the dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            // Password incorrect
            $error_message = 'Invalid username or password.';
        }
    } else {
        // Username not found
        $error_message = 'Invalid username or password.';
    }

    $stmt->close();
}
// $conn->close() is generally not necessary here as the script is about to end
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Technowatch</title>
    <!-- Load Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Custom configuration for Tailwind colors and fonts -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        // Using a slightly brighter indigo for better contrast in dark mode
                        'primary-indigo': '#6366f1', 
                    }
                }
            }
        }
    </script>
    <style>
        /* Apply Inter font and smooth body background with a deep, high-contrast gradient */
        body {
            font-family: 'Inter', sans-serif;
            /* Deep, high-contrast background gradient for a modern tech look */
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); /* Deep Navy to Dark Blue */
        }
    </style>
</head>
<!--
  Body: Uses a custom dark gradient background.
-->
<body class="flex items-center justify-center min-h-screen p-4">
    <!-- 
      Login Container: Enhanced Dark 'Glass' look. 
      - bg-gray-900/70 for dark transparency
      - backdrop-blur-md for the glass effect
      - shadow-blue-900/50 to add a subtle blue glow/aura
    -->
    <div class="login-container w-full max-w-md bg-gray-900/70 backdrop-blur-md p-8 md:p-10 rounded-3xl shadow-2xl shadow-blue-900/50 border border-gray-700 space-y-8">
        
        <!-- Header with Icon -->
        <div class="text-center space-y-2">
            <!-- Icon color changed to a bright indigo (400) for high contrast -->
            <svg class="mx-auto h-12 w-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v3h8z" />
            </svg>
            <h2 class="text-4xl font-extrabold text-white tracking-tight">
                Technowatch
            </h2>
            <p class="mt-1 text-base text-gray-400">
                Secure Admin Login
            </p>
        </div>

        <!-- Error Message Block -->
        <?php if ($error_message): ?>
            <!-- Dark Mode Error Block: Deep red background with light red text -->
            <div class="p-4 bg-red-900/40 text-red-300 rounded-xl border border-red-700 text-sm font-medium shadow-inner">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="POST" class="space-y-6">
            
            <!-- Username Field -->
            <div>
                <label for="username" class="block text-sm font-semibold text-gray-300">Username</label>
                <input 
                    type="text" 
                    id="username"
                    name="username" 
                    placeholder="Enter admin username" 
                    required 
                    autocomplete="username"
                    class="mt-1 block w-full px-4 py-3 border border-gray-600 rounded-xl shadow-sm bg-gray-700/50 text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition duration-200 ease-in-out placeholder-gray-400"
                >
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-300">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="Enter password" 
                    required 
                    autocomplete="current-password"
                    class="mt-1 block w-full px-4 py-3 border border-gray-600 rounded-xl shadow-sm bg-gray-700/50 text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition duration-200 ease-in-out placeholder-gray-400"
                >
            </div>

            <!-- Submit Button -->
            <div>
                <button 
                    type="submit" 
                    class="w-full py-3 px-4 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white bg-primary-indigo 
                           hover:bg-indigo-600 
                           active:bg-indigo-700 
                           focus:outline-none focus:ring-4 focus:ring-indigo-400/50 
                           transition duration-300 ease-in-out transform hover:-translate-y-0.5"
                >
                    Authenticate and Log In
                </button>
            </div>
        </form>
    </div>
</body>
</html>