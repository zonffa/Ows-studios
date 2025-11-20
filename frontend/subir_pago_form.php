<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subir Pago - VoleuCoorp</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background-image: url('imgs/imagenfondo.png');
      background-size: cover;
      background-position: center;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      padding: 0;
      flex-direction: column;
    }

    .form-container {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.2);
      max-width: 420px;
      width: 100%;
      text-align: center;
    }

    h2 {
      color: #2c3e50;
      margin-bottom: 25px;
    }

    label {
      display: block;
      text-align: left;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }

    input[type="number"],
    input[type="file"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 2px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      transition: 0.3s ease;
    }

    input[type="number"]:focus,
    input[type="file"]:focus {
      border-color: #2c3e50;
      outline: none;
      box-shadow: 0 0 6px rgba(44,62,80,0.3);
    }

    input[type="submit"] {
      width: 100%;
      background-color: #2c3e50;
      color: white;
      padding: 12px 0;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s;
    }

    input[type="submit"]:hover {
      background-color: #1a242f;
      transform: scale(1.03);
    }

    .mensaje {
      background-color: #e6f4ea;
      border: 2px solid #2c662d;
      color: #2c662d;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="form-container">
    <h2>📤 Subir Pago</h2>

    <!-- Mensaje opcional de PHP -->
    <?php if (!empty($mensaje)) : ?>
      <div class="mensaje"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form action="subir_pago.php" method="post" enctype="multipart/form-data">
      <!-- ID del usuario, se enviará al PHP -->
      <input type="hidden" name="id_usuario" value="1"> 

      <label for="monto">Monto del pago:</label>
      <input type="number" name="monto" id="monto" placeholder="Ingrese el monto" required>

      <label for="comprobante">Comprobante:</label>
      <input type="file" name="comprobante" id="comprobante" accept="image/*" required>

      <input type="submit" value="Subir Pago">
    </form>
  </div>

</body>
</html>