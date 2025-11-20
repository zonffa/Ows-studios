<?php
$servername = "192.168.5.50";
$username = "sebastian.cal";
$password = "55933151";
$dbname = "sebastian_cal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}

$mensaje = "";
$MAX_SIZE = 10 * 1024 * 1024; // 10 MB
$ALLOWED_EXT = ['pdf','jpg','jpeg','png','webp'];
$ALLOWED_MIME = ['application/pdf','image/jpeg','image/png','image/webp'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nombre = trim($_POST['nombre'] ?? '');
  $cedula = trim($_POST['cedula'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $correo = trim($_POST['correo'] ?? '');
  $contrasena_plain = $_POST['contrasena'] ?? '';
  $fecha_registro = date("Y-m-d");

  if (empty($nombre) || empty($cedula) || empty($telefono) || empty($correo) || empty($contrasena_plain)) {
    $mensaje = "Por favor, completa todos los campos.";
  } else {
    // ¿Existe el correo?
    $check_sql = "SELECT 1 FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
      $mensaje = "Este correo ya está registrado.";
    } else {
      // Crear usuario
      $contrasena = password_hash($contrasena_plain, PASSWORD_DEFAULT);
      $sql = "INSERT INTO usuarios (nombre, cedula, telefono, correo, contrasena, fecha_registro, estado) 
              VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssss", $nombre, $cedula, $telefono, $correo, $contrasena, $fecha_registro);

      if ($stmt->execute()) {
        $id_usuario = $conn->insert_id;
        $mensaje = "Solicitud enviada correctamente. Espera aprobación del administrador.";

        // ====== Upload opcional ======
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
          $f = $_FILES['archivo'];

          if ($f['error'] !== UPLOAD_ERR_OK) {
            $mensaje .= "  (Subida fallida: código ".$f['error'].")";
          } else if ($f['size'] > $MAX_SIZE) {
            $mensaje .= " El archivo supera los 10 MB.";
          } else {
            // Seguridad básica: validar extensión y mime
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($f['tmp_name']);
            if (!in_array($ext, $ALLOWED_EXT) || !in_array($mime, $ALLOWED_MIME)) {
              $mensaje .= "Formato no permitido. Solo PDF/JPG/PNG/WEBP.";
            } else {
              // Carpeta de destino
              $destDir = __DIR__ . "/uploads/registro";
              if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
              }

              // Nombre único
              $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/','_', pathinfo($f['name'], PATHINFO_FILENAME));
              $unique = $id_usuario . "_" . time() . "_" . bin2hex(random_bytes(4));
              $finalName = $unique . "." . $ext;
              $destPath = $destDir . "/" . $finalName;

              if (move_uploaded_file($f['tmp_name'], $destPath)) {
                // Ruta relativa para servir en web
                $rutaRelativa = "uploads/registro/" . $finalName;

                // Guardar metadata en DB
                $doc_sql = "INSERT INTO documentos (id_usuario, tipo, nombre_original, ruta, mime, tamano) 
                            VALUES (?, 'registro', ?, ?, ?, ?)";
                $doc_stmt = $conn->prepare($doc_sql);
                $doc_stmt->bind_param("isssi", $id_usuario, $f['name'], $rutaRelativa, $mime, $f['size']);
                $doc_stmt->execute();
                $doc_stmt->close();

                $mensaje .= "";
              } else {
                $mensaje .= "";
              }
            }
          }
        }
        // ====== /Upload opcional ======

      } else {
        $mensaje = "Error al registrar: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Usuario</title>
  <style>
  body {
    background-image: url(imgs/imagenfondo.png);
    background-size: cover;
    background-position: center;
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
    top: 0; left: 0; z-index: 1000; box-sizing: border-box;
  }
  .banner img { height: 80px; width: auto; }
  .banner button {
    background: #fff; color: #2c3e50; border: none; padding: 12px 22px;
    font-weight: bold; border-radius: 6px; cursor: pointer; transition: .3s;
  }
  .banner button:hover { transform: scale(1.03); }
  .registro-container {
    background: #fff; padding: 35px 40px; border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    width: 100%; max-width: 420px; text-align: center;
    animation: fadeIn .6s ease-in-out; margin-top: 120px;
  }
  h2 { margin-bottom: 25px; color: #2c3e50; }
  input { width: 100%; padding: 12px; margin: 10px 0; border: 2px solid #ddd;
    border-radius: 8px; font-size: 15px; transition: .3s; }
  input:focus { border-color:#2c3e50; outline: none; box-shadow: 0 0 6px rgba(0,123,255,.3); }
  .help { font-size: 12px; color:#5f6b7a; margin-top: -6px; text-align:left; }
  button[type="submit"] {
    width: 100%; padding: 12px; background: #2c3e50; border: none; color: #fff;
    font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; margin-top: 15px; transition: .3s;
  }
  button[type="submit"]:hover { background:#1a252f; transform: scale(1.03); }
  .mensaje { margin-top: 18px; padding: 12px; border-radius: 8px; background:#e8f5e9; border:1px solid #4caf50; color:#2e7d32; font-weight: bold; }
  .mensaje.error { background:#ffebee; border:1px solid #f44336; color:#c62828; }
  @keyframes fadeIn { from {opacity:0; transform: translateY(20px);} to {opacity:1; transform: translateY(0);} }
  </style>
</head>
<body>
  <div class="banner">
    <img src="imgs/logo.png" alt="Logo Cooperativa">
    <button class="btn-volver" onclick="window.location.href='principal.php'">⬅ Volver</button>
  </div>

  <div class="registro-container">
    <h2>Registro</h2>
    <form method="POST" action="" enctype="multipart/form-data">
      <input type="text" name="nombre" placeholder="Nombre completo" required>
      <input type="text" name="cedula" placeholder="Cédula" required>
      <input type="text" name="telefono" placeholder="Teléfono" required>
      <input type="email" name="correo" placeholder="Correo" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>

      <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.webp">
      <div class="help">Opcional: subí un PDF/JPG/PNG (máx. 10 MB) como comprobante/documento de pago inicial.</div>

      <button type="submit">Registrar</button>
    </form>

    <?php if (!empty($mensaje)): ?>
      <div class="mensaje <?= (str_contains($mensaje, 'Error') || str_contains($mensaje, '⚠️')) ? 'error' : '' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
