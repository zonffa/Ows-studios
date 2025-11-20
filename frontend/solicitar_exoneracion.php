<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ingreso.php");
    exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];

// Conexión a la base de datos
$conexion = new mysqli("192.168.5.50", "sebastian.cal", "55933151", "sebastian_cal");

if ($conexion->connect_errno) {
    die("Error al conectar a la base de datos: " . $conexion->connect_error);
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido");
}

// Tomar datos
$horas_exonerar = isset($_POST['horas_exonerar']) ? (int)$_POST['horas_exonerar'] : 0;
$monto_pagado   = isset($_POST['monto_pagado']) ? (float)$_POST['monto_pagado'] : 0;
$motivo         = isset($_POST['motivo']) ? $conexion->real_escape_string($_POST['motivo']) : '';

if ($horas_exonerar <= 0 || $monto_pagado <= 0 || $motivo === '') {
    die("Datos incompletos.");
}

// Manejo del archivo
$nombreArchivo = null;

if (
    isset($_FILES['comprobante']) &&
    $_FILES['comprobante']['error'] === UPLOAD_ERR_OK
) {
    $carpeta_destino = __DIR__ . '/../backoffice/exoneraciones/';

    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
    $nombreArchivo = "exo_{$id_usuario}_" . date('YmdHis') . "." . $ext;

    $ruta_final = $carpeta_destino . $nombreArchivo;

    if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $ruta_final)) {
        die("Error al subir el archivo.");
    }
}

// INSERT usando prepared statement
$stmt = $conexion->prepare("
    INSERT INTO exoneraciones
    (id_usuario, fecha_solicitud, motivo, horas_exonerar, monto_pagado, comprobante, estado)
    VALUES (?, NOW(), ?, ?, ?, ?, 'pendiente')
");

$stmt->bind_param(
    "isids",
    $id_usuario,
    $motivo,
    $horas_exonerar,
    $monto_pagado,
    $nombreArchivo
);

if (!$stmt->execute()) {
    die("Error al registrar exoneración: " . $stmt->error);
}

$stmt->close();
$conexion->close();

// Redirigir
header("Location: frontend.php");
exit;