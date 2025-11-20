<?php
session_start();

// ========== CONEXIÓN ==========
$mysqli = new mysqli("192.168.5.50", "sebastian.cal", "55933151", "sebastian_cal");
if ($mysqli->connect_errno) {
    die("Error al conectar a la base de datos: " . $mysqli->connect_error);
}

// ========== LOGOUT ==========
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: backoffice.php");
    exit;
}

$login_error = "";

// ========== LOGIN ADMIN (MISMA PÁGINA) ==========
if (!isset($_SESSION['id_admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
        $usuario  = trim($_POST['usuario'] ?? '');
        $pass     = $_POST['contrasena'] ?? '';

        // Buscamos admin en la tabla administradores
        $stmt = $mysqli->prepare("SELECT id_admin, nombre, usuario, contrasena FROM administradores WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            // ⚠️ Si tus contraseñas están en TEXTO PLANO:
            if ($pass === $row['contrasena']) {

                $_SESSION['id_admin']      = $row['id_admin'];
                $_SESSION['admin_nombre']  = $row['nombre'];
                $_SESSION['admin_usuario'] = $row['usuario'];

                // Opcional: redirigir para limpiar POST
                header("Location: backoffice.php");
                exit;
            } else {
                $login_error = "Usuario o contraseña incorrectos.";
            }

            /*  Si algún día las guardás con password_hash:
                if (password_verify($pass, $row['contrasena'])) { ... }
            */
        } else {
            $login_error = "Usuario o contraseña incorrectos.";
        }
    }
}

// Si NO hay admin logueado => muestro SOLO formulario de login y salgo
if (!isset($_SESSION['id_admin'])):
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login Backoffice</title>
<style>
body{
  margin:0;
  font-family:'Montserrat',sans-serif;
  background:#1e1e1e;
  display:flex;
  align-items:center;
  justify-content:center;
  height:100vh;
  color:#f0f0f0;
}
.login-card{
  background:#2a2a2a;
  padding:2rem;
  border-radius:10px;
  width:320px;
  box-shadow:0 4px 12px rgba(0,0,0,.5);
}
.login-card h1{
  margin-bottom:1.5rem;
  text-align:center;
  color:#2c3e50;
}
.form-group{
  margin-bottom:1rem;
}
label{
  display:block;
  margin-bottom:.3rem;
  font-size:.9rem;
}
input[type="text"],
input[type="password"]{
  width:100%;
  padding:.5rem;
  border-radius:6px;
  border:1px solid #444;
  background:#1e1e1e;
  color:#f0f0f0;
}
input:focus{
  border-color:#2c3e50;
  outline:none;
}
.btn-login{
  width:100%;
  padding:.6rem;
  border:none;
  border-radius:6px;
  background:#2c3e50;
  color:#f0f0f0;
  cursor:pointer;
  font-weight:600;
  margin-top:.5rem;
}
.btn-login:hover{
  background:#34526b;
}
.error{
  background:#7f1d1d;
  color:#ffe5e5;
  padding:.4rem .6rem;
  border-radius:6px;
  font-size:.85rem;
  margin-bottom:1rem;
}
</style>
</head>
<body>

<div class="login-card">
  <h1>Backoffice Admin</h1>

  <?php if($login_error): ?>
    <div class="error"><?=htmlspecialchars($login_error)?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Usuario</label>
      <input type="text" name="usuario" required>
    </div>

    <div class="form-group">
      <label>Contraseña</label>
      <input type="password" name="contrasena" required>
    </div>

    <button class="btn-login" type="submit" name="login_admin">Ingresar</button>
  </form>
</div>

</body>
</html>
<?php
// Importantísimo: cortar acá para que no se ejecute el resto del backoffice
exit;
endif;

// =====================================================================
//  A PARTIR DE ACÁ SOLO ENTRA SI YA ESTÁ LOGUEADO EL ADMIN
// =====================================================================


// ========== ACCIONES ==========

// Pagos
if (isset($_GET['accion_pago'], $_GET['id_pago'])) {
    $id = (int)$_GET['id_pago'];
    if ($_GET['accion_pago'] === 'aprobar')  $mysqli->query("UPDATE pagos SET estado='Aprobado' WHERE id_pago=$id");
    if ($_GET['accion_pago'] === 'rechazar') $mysqli->query("UPDATE pagos SET estado='Rechazado' WHERE id_pago=$id");
}

// Usuarios
if (isset($_GET['accion_usuario'], $_GET['id_usuario'])) {
    $id = (int)$_GET['id_usuario'];
    if ($_GET['accion_usuario'] === 'aprobar')  $mysqli->query("UPDATE usuarios SET estado='aprobado' WHERE id_usuario=$id");
    if ($_GET['accion_usuario'] === 'rechazar') $mysqli->query("UPDATE usuarios SET estado='rechazado' WHERE id_usuario=$id");
}

// Exoneraciones
if (isset($_GET['accion_exo'], $_GET['id_exoneracion'])) {
    $id = (int)$_GET['id_exoneracion'];
    if ($_GET['accion_exo'] === 'aprobar')  $mysqli->query("UPDATE exoneraciones SET estado='aprobada' WHERE id_exoneracion=$id");
    if ($_GET['accion_exo'] === 'rechazar') $mysqli->query("UPDATE exoneraciones SET estado='rechazada' WHERE id_exoneracion=$id");
}

// Asignar unidad
if (isset($_POST['asignar_unidad'])) {
    $usuario = (int)$_POST['id_usuario'];
    $unidad  = (int)$_POST['id_unidad'];

    $res = $mysqli->query("SELECT * FROM usuarios_unidades WHERE id_usuario=$usuario");
    if ($res->num_rows > 0) {
        $mysqli->query("UPDATE usuarios_unidades 
                        SET id_unidad=$unidad, fecha_asignacion=NOW() 
                        WHERE id_usuario=$usuario");
    } else {
        $mysqli->query("INSERT INTO usuarios_unidades (id_usuario,id_unidad,fecha_asignacion) 
                        VALUES ($usuario,$unidad,NOW())");
    }
}

// ========== CONSULTAS ==========

$usuarios = $mysqli->query("SELECT * FROM usuarios");

$pagos = $mysqli->query("
    SELECT p.*, u.nombre 
    FROM pagos p 
    JOIN usuarios u ON p.id_usuario=u.id_usuario 
    ORDER BY p.fecha_pago DESC
");

$asistencias = $mysqli->query("
    SELECT a.*, u.nombre 
    FROM asistencias a 
    JOIN usuarios u ON a.id_usuario=u.id_usuario 
    ORDER BY fecha DESC
");

$unidades = $mysqli->query("SELECT * FROM unidades_habitacionales");

$reportes = $mysqli->query("
    SELECT u.nombre,
           (SELECT estado FROM pagos WHERE id_usuario=u.id_usuario ORDER BY fecha_pago DESC LIMIT 1) estado_pago,
           (SELECT SUM(horas_registradas) FROM asistencias WHERE id_usuario=u.id_usuario) horas_totales
    FROM usuarios u
");

$documentos = $mysqli->query("
    SELECT d.*, u.nombre, u.correo
    FROM documentos d
    JOIN usuarios u ON u.id_usuario=d.id_usuario
    WHERE d.tipo='registro'
    ORDER BY fecha_subida DESC
");

$exoneraciones = $mysqli->query("
    SELECT e.*, u.nombre
    FROM exoneraciones e
    JOIN usuarios u ON u.id_usuario=e.id_usuario
    ORDER BY fecha_solicitud DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Backoffice Completo</title>

<style>
/* RESET */
*{margin:0;padding:0;box-sizing:border-box}

body{
  display:flex;
  font-family:'Montserrat',sans-serif;
  background:#1e1e1e;
  color:#f0f0f0;
  min-height:100vh;
}

/* SIDEBAR */
.sidebar{
  width:240px;
  background:#121212;
  padding:2rem 1.5rem;
  position:fixed;
  height:100vh;
  box-shadow:2px 0 10px rgba(0,0,0,.5);
}

.logo-backoffice{
  width:100px;
  margin:0 auto 1.5rem;
  display:block;
}

.sidebar h2{
  color:#2c3e50;
  text-align:center;
  margin-bottom:2rem;
}

.sidebar ul{list-style:none;}
.sidebar li{margin:1rem 0;}

.sidebar a{
  color:#f0f0f0;
  text-decoration:none;
  display:block;
  padding:.5rem;
  border-radius:6px;
  transition:.3s;
}
.sidebar a:hover{
  background:#2a2a2a;
  color:#2c3e50;
}

/* DASHBOARD */
.dashboard{
  margin-left:240px;
  padding:2rem;
  width:100%;
}

header h1{
  font-size:2rem;
  color:#2c3e50;
  margin-bottom:2rem;
  border-bottom:1px solid #444;
  padding-bottom:.5rem;
}

/* CARD */
.card{
  background:#2a2a2a;
  padding:1.5rem;
  border-radius:10px;
  margin-bottom:2rem;
  box-shadow:0 4px 12px rgba(0,0,0,.4);
}

.card h2{
  color:#2c3e50;
  margin-bottom:1rem;
}

/* TABLAS */
.admin-table,
.table-docs,
.table-exo{
  width:100%;
  border-collapse:collapse;
  margin-top:1rem;
  font-size:.95rem;
}

.admin-table th,
.table-docs th,
.table-exo th{
  background:#3a3a3a;
  color:#f0f0f0;
  padding:.75rem;
  border:1px solid #444;
}

.admin-table td,
.table-docs td,
.table-exo td{
  background:#262626;
  color:#f0f0f0;
  padding:.75rem;
  border:1px solid #444;
}

.admin-table tbody tr:nth-child(even),
.table-docs tbody tr:nth-child(even),
.table-exo tbody tr:nth-child(even){
  background:#222;
}

/* BOTONES TABLAS */
.btn-aceptar{
  background:#4caf50;
  padding:.3rem .6rem;
  border-radius:4px;
  color:#fff;
  text-decoration:none;
}
.btn-rechazar{
  background:#d32f2f;
  padding:.3rem .6rem;
  border-radius:4px;
  color:#fff;
  text-decoration:none;
}

/* SELECT dark */
select{
  background:#262626;
  border:1px solid #444;
  color:#f0f0f0;
  padding:.35rem .6rem;
  border-radius:6px;
}
select:focus{
  border-color:#2c3e50;
}

/* ESTADOS */
.estado-pill{
  padding:2px 10px;
  border-radius:999px;
  font-size:.8rem;
  font-weight:600;
}
.estado-pendiente{background:#fff4ce;color:#996f00;}
.estado-aprobada{background:#1b5e20;color:#d7f5df;}
.estado-rechazada{background:#7f1d1d;color:#ffe5e5;}

/* BARRA SUPERIOR ADMIN */
.topbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:1rem;
  font-size:.9rem;
  color:#ccc;
}
.topbar a{
  color:#f0f0f0;
  text-decoration:none;
}
.topbar a:hover{
  text-decoration:underline;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <img src="logo.png" class="logo-backoffice">
  <h2>Backoffice</h2>

  <ul>
    <li><a href="#usuarios">- Usuarios</a></li>
    <li><a href="#pagos">- Pagos</a></li>
    <li><a href="#horas">- Horas</a></li>
    <li><a href="#unidades">- Unidades</a></li>
    <li><a href="#reportes">- Reportes</a></li>
    <li><a href="#exoneraciones">- Exoneraciones</a></li>
    <li><a href="#documentos">- Comprobantes</a></li>
    <li><a href="backoffice.php?logout=1">- Cerrar sesión</a></li>
  </ul>
</aside>

<!-- DASHBOARD -->
<main class="dashboard">
<header>
  <h1>Admin | VoleuCoorp</h1>
  <div class="topbar">
    <span>Conectado como: <strong><?=htmlspecialchars($_SESSION['admin_nombre'] ?? $_SESSION['admin_usuario'])?></strong></span>
    <a href="backoffice.php?logout=1">Cerrar sesión</a>
  </div>
</header>

<!-- USUARIOS -->
<section id="usuarios" class="card">
<h2>Usuarios</h2>
<table class="admin-table">
<thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php while($u=$usuarios->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($u['nombre'])?></td>
<td><?=htmlspecialchars($u['correo'])?></td>
<td><?= $u['estado'] ?? 'pendiente' ?></td>
<td>
<?php if(($u['estado'] ?? '')!=='aprobado'): ?>
<a class="btn-aceptar" href="?accion_usuario=aprobar&id_usuario=<?=$u['id_usuario']?>">Aprobar</a>
<a class="btn-rechazar" href="?accion_usuario=rechazar&id_usuario=<?=$u['id_usuario']?>">Rechazar</a>
<?php else: ?>✔️ Aprobado<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody></table>
</section>

<!-- PAGOS -->
<section id="pagos" class="card">
<h2>Pagos</h2>
<table class="admin-table">
<thead><tr><th>Usuario</th><th>Fecha</th><th>Monto</th><th>Comprobante</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php while($p=$pagos->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($p['nombre'])?></td>
<td><?=$p['fecha_pago']?></td>
<td>$<?=number_format($p['monto'],2)?></td>
<td>
<?php if($p['comprobante']): ?>
<a href="../backoffice/pagos/<?=$p['comprobante']?>" target="_blank">Ver archivo</a>
<?php else: ?>Sin archivo<?php endif; ?>
</td>
<td><?=$p['estado']?></td>
<td>
<?php if($p['estado']!=="Aprobado"): ?>
<a class="btn-aceptar" href="?accion_pago=aprobar&id_pago=<?=$p['id_pago']?>">Aprobar</a>
<?php endif; ?>
<?php if($p['estado']!=="Rechazado"): ?>
<a class="btn-rechazar" href="?accion_pago=rechazar&id_pago=<?=$p['id_pago']?>">Rechazar</a>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</section>

<!-- HORAS -->
<section id="horas" class="card">
<h2>Horas trabajadas</h2>
<table class="admin-table">
<thead><tr><th>Usuario</th><th>Fecha</th><th>Horas</th></tr></thead>
<tbody>
<?php while($a=$asistencias->fetch_assoc()): ?>
<tr>
<td><?=htmlspecialchars($a['nombre'])?></td>
<td><?=$a['fecha']?></td>
<td><?=$a['horas_registradas']?></td>
</tr>
<?php endwhile; ?>
</tbody></table>
</section>

<!-- UNIDADES -->
<section id="unidades" class="card">
<h2>Unidades</h2>
<table class="admin-table">
<thead><tr><th>Usuario</th><th>Unidad</th><th>Asignar</th></tr></thead>
<tbody>

<?php
$usuarios2 = $mysqli->query("SELECT * FROM usuarios");
$lista_unidades=[]; 
$unidades->data_seek(0);
while($u2=$unidades->fetch_assoc()) $lista_unidades[]=$u2;

while($u=$usuarios2->fetch_assoc()):
?>
<tr>
<td><?=htmlspecialchars($u['nombre'])?></td>
<td>
<?php
$uid=$u['id_usuario'];
$q=$mysqli->query("
    SELECT direccion 
    FROM usuarios_unidades uu 
    JOIN unidades_habitacionales u ON u.id_unidad=uu.id_unidad 
    WHERE id_usuario=$uid
");
echo ($q->num_rows? htmlspecialchars($q->fetch_assoc()['direccion']) : 'Sin asignar');
?>
</td>
<td>
<form method="POST" class="form-asignar">
  <input type="hidden" name="id_usuario" value="<?=$u['id_usuario']?>">
  <select name="id_unidad" required>
    <option value="">Seleccionar unidad</option>
    <?php foreach($lista_unidades as $lu): ?>
      <option value="<?=$lu['id_unidad']?>"><?=htmlspecialchars($lu['direccion'])?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-aceptar" name="asignar_unidad" type="submit">Asignar</button>
</form>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</section>

<!-- REPORTES -->
<section id="reportes" class="card">
<h2>Reportes</h2>
<table class="admin-table">
<thead><tr><th>Usuario</th><th>Pagos</th><th>Horas</th><th>Resumen</th></tr></thead>
<tbody>
<?php while($r=$reportes->fetch_assoc()):
$okPago = ($r['estado_pago'] === 'Aprobado');
$horas  = (float)$r['horas_totales'];
?>
<tr>
<td><?=htmlspecialchars($r['nombre'])?></td>
<td><?=$okPago?'✔️ Al día':'⚠️ Atrasado'?></td>
<td><?=$horas?></td>
<td><?=$okPago && $horas>=20? '🟢 Sin observaciones':'🟡 Revisar pagos/horas'?></td>
</tr>
<?php endwhile; ?>
</tbody></table>
</section>

<!-- EXONERACIONES -->
<section id="exoneraciones" class="card">
<h2>Exoneraciones de horas</h2>

<table class="table-exo">
<thead>
<tr>
<th>Usuario</th><th>Fecha</th><th>Horas</th><th>Monto</th><th>Motivo</th><th>Comprobante</th><th>Estado</th><th>Acciones</th>
</tr>
</thead>
<tbody>

<?php while($e=$exoneraciones->fetch_assoc()):
$estado = strtolower($e['estado']);
$class="estado-pendiente";
if($estado==='aprobada')  $class="estado-aprobada";
if($estado==='rechazada') $class="estado-rechazada";
?>
<tr>
<td><?=htmlspecialchars($e['nombre'])?></td>
<td><?=$e['fecha_solicitud']?></td>
<td><?=$e['horas_exonerar']?> h</td>
<td>$<?=number_format($e['monto_pagado'],2)?></td>
<td><?=nl2br(htmlspecialchars($e['motivo']))?></td>

<td>
<?php if($e['comprobante']): ?>
<a class="btn-aceptar" target="_blank" href="../backoffice/exoneraciones/<?=$e['comprobante']?>">Ver archivo</a>
<?php else: ?>Sin archivo<?php endif;?>
</td>

<td><span class="estado-pill <?=$class?>"><?=ucfirst($estado)?></span></td>

<td>
<?php if($estado!=='aprobada'): ?>
<a class="btn-aceptar" href="?accion_exo=aprobar&id_exoneracion=<?=$e['id_exoneracion']?>">Aprobar</a>
<?php endif; ?>
<?php if($estado!=='rechazada'): ?>
<a class="btn-rechazar" href="?accion_exo=rechazar&id_exoneracion=<?=$e['id_exoneracion']?>">Rechazar</a>
<?php endif; ?>
</td>

</tr>
<?php endwhile; ?>

</tbody></table>
</section>

<!-- DOCUMENTOS -->
<section id="documentos" class="card">
<h2>Documentos de registro</h2>

<table class="table-docs">
<thead>
<tr>
<th>ID</th><th>Usuario</th><th>Correo</th><th>Archivo</th><th>MIME</th><th>Tamaño</th><th>Fecha</th><th>Ver</th>
</tr>
</thead>
<tbody>

<?php while($d=$documentos->fetch_assoc()): ?>
<tr>
<td><?=$d['id']?></td>
<td><?=htmlspecialchars($d['nombre'])?></td>
<td><?=htmlspecialchars($d['correo'])?></td>
<td><span class="badge"><?=htmlspecialchars($d['nombre_original'])?></span></td>
<td><?=htmlspecialchars($d['mime'])?></td>
<td><?=round($d['tamano']/1024,1)?> KB</td>
<td><?=$d['fecha_subida']?></td>
<td><a class="btn-aceptar" href="../<?=$d['ruta']?>" target="_blank">Abrir</a></td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</section>

</main>
</body>
</html>
