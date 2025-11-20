<?php
session_start();
$conexion = new mysqli("192.168.5.50", "sebastian.cal", "55933151", "sebastian_cal");
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    $sql = "SELECT * FROM usuarios WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        if (password_verify($contrasena, $usuario['contrasena'])) {
            if ($usuario['estado'] !== 'Aprobado') {
                $mensaje = "❌ Tu cuenta todavía no ha sido aprobada por un administrador.";
            } else {
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['usuario'] = $usuario['nombre'];
                header("Location: frontend.php");
                exit;
            }
        } else {
            $mensaje = "❌ Contraseña incorrecta.";
        }
    } else {
        $mensaje = "❌ No existe un usuario con ese correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ingreso de Usuario</title>
  <style>
    body {
      background-image: url('../imgs/imagenfondo.png');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .banner {
      width: 100%;
      background: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 40px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      box-sizing: border-box;
    }
    .banner img { height: 80px; width: auto; }
    .banner button {
      background: #fff;
      color: #2c3e50;
      border: none;
      padding: 12px 22px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      transition: 0.3s;
      margin-right: 10px;
    }
    .banner button:hover { transform: scale(1.03); }
    .login-container {
      background: #fff;
      padding: 35px 40px;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 380px;
      text-align: center;
      animation: fadeIn 0.6s ease-in-out;
      margin-top: 120px;
    }
    h2 { margin-bottom: 25px; color: #2c3e50; }
    input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
      transition: 0.3s;
    }
    input:focus {
      border-color:#2c3e50;
      outline: none;
      box-shadow: 0 0 6px rgba(0,123,255,0.3);
    }
    button[type="submit"] {
      width: 100%;
      padding: 12px;
      background: #2c3e50;
      border: none;
      color: white;
      font-size: 16px;
      font-weight: bold;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 15px;
      transition: 0.3s;
    }
    button[type="submit"]:hover {
      background: #1a252f;
      transform: scale(1.03);
    }
    .mensaje {
      margin-top: 18px;
      padding: 12px;
      border-radius: 8px;
      background: #ffebee;
      border: 1px solid #e53935;
      color: #b71c1c;
      font-weight: bold;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="banner">
    <img src="../imgs/logo.png" alt="Logo Cooperativa">
    <button onclick="window.location.href='../principal.php'">⬅ Volver</button>
  </div>

  <div class="login-container">
    <h2>Ingreso</h2>
    <form method="POST" action="">
      <input type="email" name="correo" placeholder="Correo" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
      <button type="submit">Ingresar</button>
    </form>

    <?php if ($mensaje): ?>
      <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>
  </div>

</body>
</html>
