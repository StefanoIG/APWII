<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitud de Demo Rechazada</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        padding: 20px;
      }
      .container {
        max-width: 600px;
        margin: auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }
      header {
        text-align: center;
        margin-bottom: 20px;
        background-color: #dc3545;
        padding: 10px;
        border-radius: 10px 10px 0 0;
      }
      h1 {
        text-align: center;
        color: #dc3545;
      }
      p {
        padding: 10px;
        line-height: 1.6;
      }
      .button-container {
        text-align: center;
        margin: 30px 0;
      }
      .button {
        background-color: #dc3545;
        color: #ffffff;
        padding: 15px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 16px;
        display: inline-block;
      }
      .button:hover {
        background-color: #c82333;
      }

      .footer {
        margin-top: 20px;
        text-align: center;
        font-size: 0.9em;
        color: #fff;
        background-color: #dc3545;
        padding: 10px;
        border-radius: 0 0 10px 10px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <header>
        <img src="{{ asset('img/logo.png') }}" alt="InventoryPro" />
      </header>
      <h1>Solicitud de Demo Rechazada</h1>
      <p>Hola {{ $demoRequest->email }},</p>
      <p>
        Lo sentimos, pero tu solicitud de demo ha sido rechazada. Si crees que
        esto es un error o necesitas más información puedes contactarnos al
        siguiente link.
      </p>
      <div class="button-container">
        <a href="#" class="button">Contáctanos</a>
      </div>

      <p>Gracias por tu interés en nuestro servicio.</p>

      <div class="footer">
        <p>
          &copy; {{ date('Y') }} InventoryPro. Todos los derechos reservados.
        </p>
      </div>
    </div>
  </body>
</html>
