<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VoleuCoorp | Bienvenid@</title>
</head>
<body>
  <header class="banner">
    <img src="logo.png" class="logo" alt="Logo VoleuCoorp">
    <nav class="subbanner">
      <?php if(isset($_SESSION['id_usuario'])): ?>
        <a href="frontend/frontend.php" class="item">Miembros</a>
        <a href="frontend/logout.php" class="item">Cerrar sesión</a>
      <?php else: ?>
        <a href="frontend/ingreso.php" class="item">Ingresar</a>
        <a href="about.html" class="item">Nosotros</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="contenido-principal">
    <div class="textomedio">
      <h1 class="textovoleu">VoLeuCoorp</h1>
      <p>Cooperation & Work</p>
    </div>
  </main>

  <footer class="footer">
    <p>© 2025 II Owl Studio's</p>
  </footer>
</body>
</html>
