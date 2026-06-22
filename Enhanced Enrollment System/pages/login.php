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
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="css/style.css?v=<?= time(); ?>">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            background: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 2;
            animation: containerAppear 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: clamp(2.2rem, 6vw, 2.8rem);
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 15px var(--color-primary-glow));
            margin-bottom: 0.8rem;
            display: inline-block;
        }

        .login-header h1 {
            font-family: var(--font-display);
            font-size: clamp(1.4rem, 4vw, 1.8rem);
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.4rem;
        }

        .login-header p {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: var(--color-text-muted);
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color var(--transition-fast);
            border-radius: var(--radius-sm);
        }

        .toggle-password-btn:hover {
            color: #ffffff;
        }

        .toggle-password-btn:focus-visible {
            outline: 2px solid var(--color-primary);
            color: #ffffff;
        }

        /* Birthday Picker styling override */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.6;
            transition: opacity var(--transition-fast);
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }

        .login-btn {
            width: 100%;
            margin-top: 1.5rem;
        }

        .footer-note {
            text-align: center;
            margin-top: 2rem;
            font-size: clamp(0.72rem, 1.8vw, 0.8rem);
            color: var(--color-text-muted);
        }

        .footer-note a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition-fast);
        }

        .footer-note a:hover {
            color: #60a5fa;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <i id="header-icon" class="fa-solid fa-user-lock logo-icon"></i>
            <h1 id="header-title">Student Portal</h1>
            <p id="header-desc">Access your academic profile & schedule</p>
        </div>

        <div class="login-tabs" style="display: flex; background: rgba(17, 24, 39, 0.4); border-radius: 12px; padding: 0.3rem; margin-bottom: 1.5rem; border: 1px solid var(--color-card-border);">
            <button type="button" id="tab-student" onclick="switchLoginMode('student')" style="flex: 1; padding: 0.6rem; border-radius: 8px; border: none; background: var(--color-primary); color: #ffffff; font-family: var(--font-display); font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Student</button>
            <button type="button" id="tab-staff" onclick="switchLoginMode('staff')" style="flex: 1; padding: 0.6rem; border-radius: 8px; border: none; background: transparent; color: var(--color-text-muted); font-family: var(--font-display); font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Staff</button>
        </div>

        <form id="login-form" onsubmit="handleLogin(event)" aria-label="Portal Login Form">
            <div class="form-group">
                <label for="student_id" class="form-label" id="id-label">Student ID</label>
                <div class="input-wrapper">
                    <input type="text" id="student_id" class="form-input" placeholder="TUPV-00-0000" required aria-required="true" aria-label="Identification Number">
                    <i class="fa-solid fa-id-card prefix-icon" aria-hidden="true"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" class="form-input" placeholder="••••••••" required aria-required="true" aria-label="Password">
                    <i class="fa-solid fa-key prefix-icon" aria-hidden="true"></i>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility()" aria-label="Toggle Password Visibility">
                        <i class="fa-solid fa-eye" id="toggle-password-icon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="form-group" id="birthday-group">
                <label for="birthday" class="form-label">Date of Birth</label>
                <div class="input-wrapper">
                    <input type="date" id="birthday" class="form-input" required aria-required="true" aria-label="Date of Birth">
                    <i class="fa-solid fa-calendar-days prefix-icon" aria-hidden="true"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary login-btn" id="submit-btn" aria-label="Sign In to Portal">
                <span>Sign In</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        <div class="footer-note">
            <p>Enhanced Enrollment System &copy; 2026</p>
        </div>
    </div>

    <!-- Custom Toast Notification -->
    <div id="toast" class="toast">
        <i id="toast-icon" class="fa-solid"></i>
        <div>
            <strong id="toast-title" style="display: block; font-size: 0.9rem; color: #ffffff;">Notification</strong>
            <span id="toast-msg" style="font-size: 0.8rem; color: var(--color-text-muted);">Details here</span>
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
            
            toast.className = 'toast';
            icon.className = 'fa-solid';

            if (type === 'success') {
                toast.classList.add('toast-success');
                icon.classList.add('fa-circle-check');
                icon.style.color = 'var(--color-success)';
            } else if (type === 'error') {
                toast.classList.add('toast-error');
                icon.classList.add('fa-circle-xmark');
                icon.style.color = 'var(--color-danger)';
            } else {
                icon.classList.add('fa-circle-info');
                icon.style.color = 'var(--color-info)';
            }

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        let currentMode = 'student';

        function switchLoginMode(mode) {
            currentMode = mode;
            const tabStudent = document.getElementById('tab-student');
            const tabStaff = document.getElementById('tab-staff');
            const birthdayGroup = document.getElementById('birthday-group');
            const birthdayInput = document.getElementById('birthday');
            const idLabel = document.getElementById('id-label');
            const idInput = document.getElementById('student_id');
            const headerTitle = document.getElementById('header-title');
            const headerDesc = document.getElementById('header-desc');
            const headerIcon = document.getElementById('header-icon');

            if (mode === 'student') {
                tabStudent.style.background = 'var(--color-primary)';
                tabStudent.style.color = '#ffffff';
                tabStaff.style.background = 'transparent';
                tabStaff.style.color = 'var(--color-text-muted)';
                
                birthdayGroup.style.display = 'flex';
                birthdayInput.required = true;
                idLabel.textContent = 'Student ID';
                idInput.placeholder = 'TUPV-00-0000';
                
                headerTitle.textContent = 'Student Portal';
                headerDesc.textContent = 'Access your academic profile & schedule';
                headerIcon.className = 'fa-solid fa-user-lock logo-icon';
            } else {
                tabStaff.style.background = 'var(--color-primary)';
                tabStaff.style.color = '#ffffff';
                tabStudent.style.background = 'transparent';
                tabStudent.style.color = 'var(--color-text-muted)';
                
                birthdayGroup.style.display = 'none';
                birthdayInput.required = false;
                idLabel.textContent = 'Staff ID';
                idInput.placeholder = 'DEPT-01, REG-01, or ADMIN-01';
                
                headerTitle.textContent = 'Staff Portal';
                headerDesc.textContent = 'Access Department Head & Registrar tools';
                headerIcon.className = 'fa-solid fa-user-shield logo-icon';
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            
            const studentId = document.getElementById('student_id').value;
            const password = document.getElementById('password').value;
            const birthday = currentMode === 'student' ? document.getElementById('birthday').value : '';
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
                        window.location.href = result.redirect || 'index.php?page=enrollment';
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
