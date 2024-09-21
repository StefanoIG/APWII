<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recuperación de Contraseña</title>
    <style>
      body {
        font-family: "Helvetica Neue", Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
        color: #333333;
      }
      .container {
        max-width: 600px;
        margin: 50px auto;
        background-color: #ffffff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      }
      .header {
        text-align: center;
        margin-bottom: 30px;
        background-color: #007bff;
        padding: 20px;
        border-radius: 10px 10px 0 0;
      }
      .header img {
        max-width: 100px;
        margin-bottom: 20px;
      }
      h1 {
        color: #333333;
        font-size: 24px;
        text-align: center;
      }
      p {
        color: #666666;
        line-height: 1.8;
        font-size: 16px;
        padding: 10px;
      }
      .button-container {
        text-align: center;
        margin: 30px 0;
      }
      .button {
        display: inline-block;
        padding: 10px 20px;
        margin: 20px 0;
        background-color: #007bff;
        color: #ffffff;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
      }
      .button:hover {
        background-color: #0056b3;
      }
      .footer {
        margin-top: 30px;
        font-size: 14px;
        color: #ffffff;
        text-align: center;
        background-color: #007bff;
        padding: 10px;
        border-radius: 0 0 10px 10px;
      }
      .footer p {
        color: #ffffff;
      }
      p a {
        color: #007bff;
        text-decoration: none;
      }
      p a:hover {
        text-decoration: underline;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" />
      </div>
      <h1>Recuperación de Contraseña</h1>
      <p>Hola, {{ $nombre }}</p>
      <p>
        Has solicitado restablecer tu contraseña. Haz clic en el siguiente
        enlace para restablecerla:
      </p>
      <div class="button-container">
        <a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a>
      </div>
      <p>Este enlace expirará en 60 minutos.</p>
      <p>
        Si no solicitaste un restablecimiento de contraseña, no es necesario que
        hagas nada.
      </p>
      <p>
        Si tienes alguna pregunta, por favor contacta a nuestro
        <a href="mailto:soporte@example.com">soporte</a>.
      </p>
      <div class="footer">
        <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
      </div>
    </div>
  </body>
</html>
