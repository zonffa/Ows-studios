<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("192.168.5.50", "sebastian.cal", "55933151", "sebastian_cal");

if ($conexion->connect_errno) {
    die("Error al conectar a la base de datos: " . $conexion->connect_error);
}

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['horas'])) {

    $horas = intval($_POST['horas']);

    // Insertar horas en la tabla asistencias
    $stmt = $conexion->prepare("
        INSERT INTO asistencias (id_usuario, fecha, horas_registradas)
        VALUES (?, NOW(), ?)
    ");

    $stmt->bind_param("ii", $id_usuario, $horas);
    $stmt->execute();
    $stmt->close();
}

// Redirigir de vuelta al panel
header("Location: frontend.php");
exit;