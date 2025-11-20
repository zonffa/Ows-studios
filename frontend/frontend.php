<?php
session_start();

$conn = new mysqli("192.168.5.50", "sebastian.cal", "55933151", "sebastian_cal");

// Verificar conexión
if ($conn->connect_errno) {
    die("Error al conectar a la base de datos: " . $conn->connect_error);
}

// Verificar sesión
if(!isset($_SESSION['id_usuario'])){
    header("Location: ingreso.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['usuario']; // 🔹 nombre para mostrar

// Consultar pago aprobado más reciente
$result_pago = $conn->query("SELECT * FROM pagos 
                             WHERE id_usuario=$id_usuario 
                             AND estado='aprobado' 
                             ORDER BY fecha_pago DESC LIMIT 1");
$pagos_al_dia = ($result_pago->num_rows > 0) 
    ? "✅ Al día (Último pago: ".$result_pago->fetch_assoc()['fecha_pago'].")"
    : "⏳ Pendiente";

// Consultar unidad asignada
$result_unidad = $conn->query("SELECT u.id_unidad, u.direccion 
                               FROM unidades_habitacionales u
                               JOIN usuarios_unidades uu ON u.id_unidad = uu.id_unidad
                               WHERE uu.id_usuario=$id_usuario");
$unidad = ($result_unidad->num_rows > 0)
    ? $result_unidad->fetch_assoc()['direccion']
    : "Sin asignar";

// Consultar horas trabajadas
$result_horas = $conn->query("SELECT SUM(horas_registradas) AS total_horas 
                              FROM asistencias 
                              WHERE id_usuario=$id_usuario");
$total_horas = ($result_horas->num_rows > 0)
    ? $result_horas->fetch_assoc()['total_horas']
    : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inicio | VoleuCoorp</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body>

<header>
    <nav>
        <div class="nav-content">
            <div class="nav-left">
                <h2 class="nombre-coop">VoleuCoorp</h2>
            </div>
            <div class="top-links">
                <a href="../principal.php">Inicio</a>
                <i class="ph ph-user-circle profile-icon"></i>
            </div>
        </div>
    </nav>
</header>

<main class="dashboard">

    <!-- 🔹 Mostrar nombre -->
    <h1>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?> </h1>
    <p class="subtitulo">Consulta tu información y estado en VoleuCoorp.</p>

    <!-- PANEL DE ESTADO -->
    <section class="status-panel">
        <div class="status-item">
            <i class="ph ph-currency-circle-dollar"></i>
            <div>
                <h3>Pagos al día</h3>
                <p><?php echo $pagos_al_dia; ?></p>
            </div>
        </div>

        <div class="status-item">
            <i class="ph ph-clock"></i>
            <div>
                <h3>Horas trabajadas</h3>
                <p><?php echo $total_horas; ?> horas esta semana</p>
            </div>
        </div>

        <div class="status-item">
            <i class="ph ph-house"></i>
            <div>
                <h3>Unidad asignada</h3>
                <p><?php echo $unidad; ?></p>
            </div>
        </div>
    </section>

    <!-- COMPROBANTES -->
    <section>
        <div class="card">
            <h2>Últimos Comprobantes</h2>
            <?php
            $res_comprobantes = $conn->query("SELECT fecha_pago, estado 
                                              FROM pagos 
                                              WHERE id_usuario=$id_usuario 
                                              ORDER BY fecha_pago DESC LIMIT 5");

            if($res_comprobantes->num_rows > 0){
                while($row = $res_comprobantes->fetch_assoc()){
                    echo "<p><strong>".$row['fecha_pago'].":</strong> ".ucfirst($row['estado'])."</p>";
                }
            } else {
                echo "<p>No hay comprobantes subidos</p>";
            }
            ?>
            <a href="subir_pago_form.php"><button>Subir nuevo</button></a>
        </div>

        <div class="card">
            <h2>Resumen General</h2>
            <ul>
                <li>Participación: ✅</li>
                <li>Pagos al día: <?php echo ($result_pago->num_rows > 0) ? "✅" : "⏳"; ?></li>
                <li>Horas mínimas: <?= ($total_horas >= 20 ? "🟢" : "🟡") ?></li>
            </ul>
        </div>

        <!-- HORAS -->
        <div class="card">
            <h2>Historial de Horas</h2>

            <div class="card">
                <h2>Registrar horas de esta semana</h2>
                <form action="registrar_horas.php" method="POST" class="form-horas">
                    <input type="number" id="horas" name="horas" min="1" placeholder="Ingrese horas trabajadas" required>
                    <button type="submit">Registrar</button>
                </form>
            </div>

            <?php
            $res_horas = $conn->query("SELECT fecha, horas_registradas 
                                       FROM asistencias 
                                       WHERE id_usuario=$id_usuario 
                                       ORDER BY fecha DESC LIMIT 5");

            if($res_horas->num_rows > 0){
                while($row = $res_horas->fetch_assoc()){
                    echo "<p><strong>".$row['fecha'].":</strong> ".$row['horas_registradas']." horas</p>";
                }
            } else {
                echo "<p>No hay registro de horas</p>";
            }
            ?>
        </div>

        <!-- EXONERACIONES -->
        <div class="card">
            <h2>Exoneración de horas trabajadas</h2>
            <p>Si no pudiste cumplir con tus horas, podés exonerarlas subiendo un comprobante y el motivo.</p>

            <form action="solicitar_exoneracion.php" method="POST" enctype="multipart/form-data" class="form-horas">

                <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">

                <label for="horas_exonerar">Horas a exonerar</label>
                <input type="number" name="horas_exonerar" min="1" required>

                <label for="monto_pagado">Monto pagado ($)</label>
                <input type="number" step="0.01" name="monto_pagado" required>

                <label for="motivo">Motivo</label>
                <textarea name="motivo" rows="3" required></textarea>

                <label for="comprobante">Subir comprobante (PDF / Imagen)</label>
                <input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" required>

                <button type="submit">Enviar solicitud</button>
            </form>

            <?php
            $res_exo = $conn->query("
                SELECT fecha_solicitud, motivo, estado, horas_exonerar, monto_pagado
                FROM exoneraciones 
                WHERE id_usuario=$id_usuario 
                ORDER BY fecha_solicitud DESC LIMIT 5
            ");

            if($res_exo->num_rows > 0){
                echo "<h3>Últimas solicitudes</h3>";
                while($row = $res_exo->fetch_assoc()){
                    echo "<p><strong>".$row['fecha_solicitud']."</strong> - ".
                         $row['horas_exonerar']." h | $".$row['monto_pagado'].
                         " - Estado: ".ucfirst($row['estado'])."<br>Motivo: ".
                         htmlspecialchars($row['motivo'])."</p>";
                }
            } else {
                echo "<p>No tenés solicitudes de exoneración registradas.</p>";
            }
            ?>
        </div>

    </section>

</main>

</body>
</html>