<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("192.168.5.50", "sebastian.cal", "55933151", "sebastian_cal");

if ($conexion->connect_errno) {
    die("Error al conectar a la base de datos: " . $conexion->connect_error);
}

// Inicializamos mensaje
$mensaje = "";

// Verificamos que el usuario esté logueado
if (!isset($_SESSION['id_usuario'])) {
    die("Error: usuario no logueado.");
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $monto = $_POST['monto'] ?? 0;

    // Validar archivo
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === 0) {

        $archivo = $_FILES['comprobante'];
        $nombreArchivo = time() . "_" . basename($archivo['name']);

        // ❗❗ ESTA ES LA LÍNEA CORRECTA:
        $carpetaPagos = __DIR__ . "/../backoffice/pagos/";
        $rutaDestino  = $carpetaPagos . $nombreArchivo;

        // Crear carpeta si no existe
        if (!is_dir($carpetaPagos)) {
            mkdir($carpetaPagos, 0777, true);
        }

        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {

            // Insertar registro en la BD
            $stmt = $conexion->prepare("
                INSERT INTO pagos (id_usuario, fecha_pago, monto, comprobante, estado)
                VALUES (?, NOW(), ?, ?, 'Pendiente')
            ");

            $stmt->bind_param("ids", $id_usuario, $monto, $nombreArchivo);

            if ($stmt->execute()) {
                $mensaje = "✅ Su comprobante fue enviado con éxito. Esperá la verificación del administrador.";
            } else {
                $mensaje = "❌ Error al guardar en la base de datos: " . $stmt->error;
                unlink($rutaDestino);
            }

            $stmt->close();
        } else {
            $mensaje = "❌ Error al mover el archivo al servidor.";
        }

    } else {
        $mensaje = "❌ No se recibió un archivo válido.";
    }
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pago Subido</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Montserrat', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: linear-gradient(to bottom right, #ffffff, #f4f4f4);
        margin: 0;
    }
    .mensaje-container {
        background: #ffffffdd;
        backdrop-filter: blur(10px);
        padding: 40px 50px;
        border-radius: 15px;
        text-align: center;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .mensaje-container h1 {
        color: #2c3e50;
        margin-bottom: 20px;
    }
    .mensaje-container p {
        font-size: 1rem;
        color: #333;
        margin-bottom: 30px;
    }
    .btn-volver {
        display: inline-block;
        padding: 12px 25px;
        background-color: #2c3e50;
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        font-weight: bold;
        transition: 0.3s;
    }
    .btn-volver:hover {
        background-color: #1a242f;
        transform: scale(1.05);
    }
</style>
</head>

<body>
    <div class="mensaje-container">
        <h1>✔ Pago subido</h1>
        <p><?php echo $mensaje; ?></p>
        <a href="frontend.php" class="btn-volver">Ir a mi panel</a>
    </div>
</body>
</html>