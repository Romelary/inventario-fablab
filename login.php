<?php
// login.php
require_once 'config/database.php';
require_once 'config/auth.php';

// Si ya inició sesión, redirigir a index.php
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerar el ID de sesión para mayor seguridad (evita fijación de sesiones)
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_username'] = $user['username'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_rol'] = $user['rol'];

                header("Location: index.php");
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            error_log("Error de inicio de sesión: " . $e->getMessage());
            $error = 'Ocurrió un error en el servidor. Inténtelo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - FABLAB UC</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #660099;
            --primary-light: #8e2de2;
            --primary-dark: #4a0072;
            --accent: #ff007f;
            --bg-dark: #0f0c1b;
            --card-bg: rgba(255, 255, 255, 0.08);
            --card-border: rgba(255, 255, 255, 0.12);
            --text-main: #ffffff;
            --text-muted: #b0a8c5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1e1136 50%, #0c081f 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* Efectos de luces de fondo (Glow) */
        .glow-sphere-1 {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(102,0,153,0.35) 0%, rgba(0,0,0,0) 70%);
            top: -100px;
            left: -100px;
            z-index: 0;
            filter: blur(40px);
        }

        .glow-sphere-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,0,127,0.18) 0%, rgba(0,0,0,0) 70%);
            bottom: -150px;
            right: -100px;
            z-index: 0;
            filter: blur(50px);
        }

        /* Contenedor del Login */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px 30px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo y Cabecera */
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo-container {
            display: inline-block;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(102, 0, 153, 0.3);
            margin-bottom: 15px;
            animation: scaleIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .brand-logo-container img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            display: block;
        }

        .brand h2 {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .brand p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Mensajes de error */
        .error-message {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        /* Formularios */
        .form-group {
            position: relative;
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            transition: color 0.3s;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.09);
            border-color: var(--primary-light);
            box-shadow: 0 0 15px rgba(142, 45, 226, 0.25);
        }

        .form-input:focus + .input-icon {
            color: var(--primary-light);
        }

        /* Toggle de contraseña */
        .toggle-password {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: var(--text-main);
        }

        /* Botón de envío */
        .submit-btn {
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 0, 153, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(142, 45, 226, 0.45);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Pie de página */
        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="glow-sphere-1"></div>
    <div class="glow-sphere-2"></div>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="brand">
                <div class="brand-logo-container">
                    <img src="img/logo-uc.png" alt="Universidad Continental Logo">
                </div>
                <h2>FABLAB</h2>
                <p>Sistema de Gestión de Inventario</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-input" placeholder="Nombre de usuario" required autofocus>
                        <div class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                        <div class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        <button type="button" class="toggle-password" id="togglePasswordBtn" tabindex="-1">
                            <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Ingresar al Sistema</button>
            </form>

            <div class="footer-text">
                &copy; <?php echo date('Y'); ?>Todos los derechos reservados.
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        toggleBtn.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        });
    </script>
</body>
</html>
