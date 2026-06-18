<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login - Enhanced Enrollment System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-color: #0b1120; /* Deep calm slate/navy */
            --card-bg: rgba(22, 30, 49, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-primary: #0ea5e9; /* Sky blue (high contrast) */
            --accent-primary-glow: rgba(14, 165, 233, 0.25);
            --accent-secondary: #10b981; /* Emerald green (high contrast/calming) */
            --text-main: #f1f5f9; /* Slate 100 - high contrast */
            --text-muted: #cbd5e1; /* Slate 300 - high contrast for labels */
            --font-display: 'Outfit', sans-serif;
            --font-sans: 'Inter', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: 
                radial-gradient(at 10% 20%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(139, 92, 246, 0.12) 0px, transparent 50%);
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 2;
            animation: containerAppear 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes containerAppear {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 2.8rem;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 15px var(--accent-primary-glow));
            margin-bottom: 0.8rem;
            display: inline-block;
        }

        .login-header h1 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.4rem;
        }

        .login-header p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.prefix-icon {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .input-wrapper input {
            width: 100%;
            background: rgba(17, 24, 39, 0.4);
            border: 1px solid var(--card-border);
            color: #ffffff;
            padding: 1rem 1.2rem 1rem 2.8rem;
            border-radius: 12px;
            outline: none;
            font-family: var(--font-sans);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-wrapper input:focus {
            border-color: var(--accent-primary);
            background: rgba(17, 24, 39, 0.75);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.35);
        }

        .input-wrapper input:focus ~ i.prefix-icon {
            color: var(--accent-primary);
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
            border-radius: 6px;
        }

        .toggle-password-btn:hover {
            color: #ffffff;
        }

        .toggle-password-btn:focus-visible {
            outline: 2px solid var(--accent-primary);
            color: #ffffff;
        }

        /* Birthday Picker styling override */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.3s ease;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border: none;
            color: #ffffff;
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
            padding: 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px var(--accent-primary-glow);
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            filter: brightness(1.1);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:focus-visible {
            outline: 3px solid var(--accent-primary);
            outline-offset: 3px;
        }

        .footer-note {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .footer-note a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-note a:hover {
            color: #60a5fa;
            text-decoration: underline;
        }

        /* Alert styling helper */
        .toast-notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: #1e293b;
            border: 1px solid var(--accent-primary);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 10;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .toast-notification.active {
            transform: translateX(0);
        }

        .toast-notification.success {
            border-color: var(--accent-success);
        }

        .toast-notification.error {
            border-color: #ef4444;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fa-solid fa-user-lock logo-icon"></i>
            <h1>Student Portal</h1>
            <p>Access your academic profile & schedule</p>
        </div>

        <form id="login-form" onsubmit="handleLogin(event)" aria-label="Student Portal Login Form">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <div class="input-wrapper">
                    <input type="text" id="student_id" placeholder="TUPV-00-0000" required aria-required="true" aria-label="Student Identification Number">
                    <i class="fa-solid fa-id-card prefix-icon" aria-hidden="true"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" placeholder="••••••••" required aria-required="true" aria-label="Password">
                    <i class="fa-solid fa-key prefix-icon" aria-hidden="true"></i>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility()" aria-label="Toggle Password Visibility">
                        <i class="fa-solid fa-eye" id="toggle-password-icon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="birthday">Date of Birth</label>
                <div class="input-wrapper">
                    <input type="date" id="birthday" required aria-required="true" aria-label="Date of Birth">
                    <i class="fa-solid fa-calendar-days prefix-icon" aria-hidden="true"></i>
                </div>
            </div>

            <button type="submit" class="login-btn" id="submit-btn" aria-label="Sign In to Student Portal">
                <span>Sign In</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        <div class="footer-note">
            <p>Looking for the architect board? <a href="index.php" aria-label="Navigate to Architect Dashboard">Go to Dashboard</a></p>
            <p style="margin-top: 0.6rem;"><i class="fa-solid fa-user-plus" style="margin-right: 0.3rem; color: var(--accent-secondary);"></i><a href="add_student.php" style="color: var(--accent-secondary); font-weight: 700;" aria-label="Create test student profile">Create Test Student Profile</a></p>
        </div>
    </div>

    <!-- Custom Toast Notification -->
    <div id="toast" class="toast-notification">
        <i id="toast-icon" class="fa-solid"></i>
        <div>
            <strong id="toast-title" style="display: block; font-size: 0.9rem; color: #ffffff;">Notification</strong>
            <span id="toast-msg" style="font-size: 0.8rem; color: var(--text-muted);">Details here</span>
        </div>
    </div>

    <script>
        // Prefill fields from URL query params for testing convenience
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const studentId = params.get('student_id');
            const birthday = params.get('birthday');
            if (studentId) {
                document.getElementById('student_id').value = studentId;
            }
            if (birthday) {
                document.getElementById('birthday').value = birthday;
            }
            if (studentId && birthday) {
                document.getElementById('password').value = 'password123';
            }
        });

        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('toggle-password-icon');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function showToast(title, message, type = 'info') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const titleEl = document.getElementById('toast-title');
            const msgEl = document.getElementById('toast-msg');

            titleEl.textContent = title;
            msgEl.textContent = message;
            
            toast.className = 'toast-notification';
            icon.className = 'fa-solid';

            if (type === 'success') {
                toast.classList.add('success');
                icon.classList.add('fa-circle-check');
                icon.style.color = '#10b981';
            } else if (type === 'error') {
                toast.classList.add('error');
                icon.classList.add('fa-circle-xmark');
                icon.style.color = '#ef4444';
            } else {
                icon.classList.add('fa-info-circle');
                icon.style.color = '#3b82f6';
            }

            toast.classList.add('active');

            setTimeout(() => {
                toast.classList.remove('active');
            }, 4000);
        }

        async function handleLogin(e) {
            e.preventDefault();
            
            const studentId = document.getElementById('student_id').value;
            const password = document.getElementById('password').value;
            const birthday = document.getElementById('birthday').value;
            const submitBtn = document.getElementById('submit-btn');

            // Set loading state
            submitBtn.disabled = true;
            submitBtn.querySelector('span').textContent = 'Authenticating...';

            try {
                const response = await fetch('php/api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        password: password,
                        birthday: birthday
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Success', result.message, 'success');
                    
                    setTimeout(() => {
                        window.location.href = 'enrollment.php';
                    }, 800);

                } else {
                    showToast('Authentication Failed', result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Connection Error', 'Unable to reach the authentication service.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.querySelector('span').textContent = 'Sign In';
            }
        }
    </script>
</body>
</html>
