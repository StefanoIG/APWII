<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registro Exitoso</title>
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
        text-align: center;
        color: #333333;
        font-size: 24px;
        color: #007bff;
      }
      p {
        padding: 10px;
        color: #666666;
        line-height: 1.8;
        font-size: 16px;
      }
      ul {
        list-style-type: none;
        padding: 0;
      }
      ul li {
        margin: 10px 0;
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        align-items: center;
      }
      ul li::before {
        content: "•";
        color: #007bff;
        margin-right: 10px;
        font-size: 20px;
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
        margin: 0;
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
      <h1>Registro Exitoso</h1>
      <p>Estimado {{ $nombre }},</p>
      <p>Su registro ha sido exitoso.</p>
      <p>
        Su superior se pondrá en contacto con usted para brindarle sus
        credenciales de acceso.
      </p>
      <p>En el sistema puede:</p>
      <ul>
        <li>Agregar productos</li>
        <li>Administrar lotes</li>
        <li>Generar comprobantes</li>
        <li>Y muchas otras funcionalidades.</li>
      </ul>
      <p>Saludos cordiales,</p>
      <p>El equipo de {{ config('app.name') }}</p>
      <p>
        Si tienes alguna pregunta, por favor contacta a nuestro
        <a href="mailto:soporte@example.com">soporte</a>.
      </p>
      <div class="footer">
        <p>
          &copy; 2024 {{ config('app.name') }}. Todos los derechos reservados.
        </p>
      </div>
    </div>
  </body>
</html>
