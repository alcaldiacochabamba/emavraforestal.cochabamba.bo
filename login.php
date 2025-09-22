<?php
session_start();

// Configuración de credenciales seguras (cambia estos valores por unos más seguros)
$admin_users = [
    'admin_skygreen' => password_hash('SkyGreen2024!Admin', PASSWORD_BCRYPT),
    'superuser' => password_hash('Tr33s@dmin2024!', PASSWORD_BCRYPT),
    'gestor_arboles' => password_hash('Forest$Manage123', PASSWORD_BCRYPT)
];

// Verificar si ya está logueado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: administrador.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validar credenciales
    if (isset($admin_users[$username]) && password_verify($password, $admin_users[$username])) {
        // Login exitoso
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        
        // Registrar el intento de login exitoso (opcional)
        error_log("Admin login successful: $username from IP: " . $_SERVER['REMOTE_ADDR']);
        
        header('Location: administrador.php');
        exit;
    } else {
        $error = 'Credenciales incorrectas. Acceso denegado.';
        
        // Registrar intento fallido
        error_log("Failed admin login attempt: $username from IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Añadir un pequeño delay para prevenir ataques de fuerza bruta
        sleep(2);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyGreen - Acceso Administrativo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #2d5016 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .login-container {
            background: white;
            padding: 3rem 2.5rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2d5016, #4a7c59, #2d5016);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d5016;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logo i {
            color: #4a7c59;
        }

        .admin-badge {
            background: linear-gradient(135deg, #2d5016, #4a7c59);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.5rem;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .form-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #2d5016;
            background: white;
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #2d5016;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #2d5016, #4a7c59);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(45, 80, 22, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .security-notice {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            text-align: center;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #2d5016;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .logo {
                font-size: 2rem;
            }
        }

        /* Animación de carga */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff40;
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Efecto de entrada */
        .login-container {
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                SkyGreen
            </div>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i>
                Panel Administrativo
            </div>
        </div>

        <h2 class="form-title">Acceso Restringido</h2>
        <p class="form-subtitle">Ingresa tus credenciales para acceder al panel de administración</p>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label">Usuario Administrativo</label>
                <div style="position: relative;">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" name="username" class="form-input" 
                           placeholder="Nombre de usuario" required 
                           autocomplete="username" maxlength="50">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Contraseña Segura</label>
                <div style="position: relative;">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-input" 
                           placeholder="Contraseña" required 
                           autocomplete="current-password" maxlength="100">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión Administrativa
            </button>
        </form>

        <div class="security-notice">
            <i class="fas fa-info-circle"></i>
            Área de acceso restringido. Todos los intentos de acceso son registrados y monitoreados.
        </div>

        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Volver al sitio principal
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Manejo del formulario con animación de carga
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const originalHTML = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> Verificando credenciales...';
            
            // Si hay error, restaurar el botón después de un tiempo
            setTimeout(() => {
                if (btn.disabled) {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }, 5000);
        });

        // Prevenir ataques automatizados básicos
        let attemptCount = 0;
        const maxAttempts = 5;
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            attemptCount++;
            if (attemptCount > maxAttempts) {
                e.preventDefault();
                alert('Demasiados intentos fallidos. Por favor, espera un momento antes de intentar nuevamente.');
                setTimeout(() => {
                    attemptCount = 0;
                }, 60000); // Reset después de 1 minuto
            }
        });

        // Auto-focus en el campo de usuario
        window.addEventListener('load', function() {
            document.querySelector('input[name="username"]').focus();
        });

        // Limpiar campos en caso de error persistente
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('retry') === 'true') {
                document.querySelector('input[name="username"]').value = '';
                document.querySelector('input[name="password"]').value = '';
            }
        });
    </script>
</body>
</html>