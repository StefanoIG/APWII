<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Acceso a Demo Aprobado</title>
    <style>
      body {
        font-family: "Helvetica Neue", Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        margin: 0;
        padding: 0;
      }
      .container {
        max-width: 600px;
        margin: 50px auto;
        background: #ffffff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      }
      /* estilos para el header */
      .header {
        text-align: center;
        margin-bottom: 20px;
        background-color: #007bff;
        padding: 10px;
        border-radius: 10px 10px 0 0;
      }
      .header img {
        width: 100px;
      }

      h1 {
        text-align: center;
        color: #007bff;
      }
      p {
        padding: 10px;
      }
      /* estilos para las credenciales */
      .credentials {
        background-color: #f4f4f4;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
      }
      .credentials p {
        margin: 0;
      }

      .button-container {
        text-align: center;
        margin: 30px 0;
      }
      .button {
        background-color: #007bff;
        color: #ffffff;
        padding: 15px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 16px;
        display: inline-block;
      }
      .button:hover {
        background-color: #0056b3;
      }

      /* estilos para el footer */
      .footer {
        text-align: center;
        margin-top: 20px;
        background-color: #007bff;
        padding: 10px;
        border-radius: 0 0 10px 10px;
        color: #f4f4f4;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <img src="logo.png" alt="Logo" />
      </div>
      <h1>Acceso a la Demo Aprobado</h1>
      <p>Hola {{ $usuarioDemo->correo_electronico }},</p>
      <p>
        Nos complace informarte que tu solicitud de demo ha sido aprobada. A
        continuación, encontrarás tus credenciales de acceso:
      </p>

      <div class="credentials">
        <p>
          <strong>Correo electrónico:</strong> <br />
          {{ $usuarioDemo->correo_electronico }}
        </p>
        <p>
          <strong>Contraseña:</strong> <br />
          {{ $passwordPlano }}
        </p>

        <div class="button-container">
          <a href="#" class="button">Iniciar Sesión</a>
        </div>
      </div>

      <p>
        Puedes iniciar sesión en nuestra plataforma utilizando las credenciales
        proporcionadas.
      </p>

      <p>Gracias por elegir nuestro servicio.</p>

      <div class="footer">
        <p>
          &copy; {{ date('Y') }} InventoryPro. Todos los derechos reservados.
        </p>
      </div>
    </div>
  </body>
</html>
