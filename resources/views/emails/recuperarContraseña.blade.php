<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            margin: 0;
        }
        .container {
            background-color: #333;
            color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2), 0 6px 20px rgba(0, 0, 0, 0.19);
            max-width: 700px;
            width: 100%;
            text-align: center;
        }
        .header {
            background-color: #007BFF;
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .header img {
            max-width: 100px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            padding-top: 20px;
            font-size: 12px;
            color: #bbb;
        }
        .footer a {
            color: #007BFF;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
            <h1>Recuperación de Contraseña</h1>
        </div>
        <div class="content">
            <p>Hola, {{ $nombre }}</p>
            <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para restablecerla:</p>
            <p><a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a></p>
            <p>Este enlace expirará en 60 minutos.</p>
            <p>Si no solicitaste un restablecimiento de contraseña, no es necesario que hagas nada.</p>
        </div>
        <div class="footer">
            <p>Si tienes alguna pregunta, por favor contacta a nuestro <a href="mailto:soporte@example.com">soporte</a>.</p>
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
            <br>
            <p>by InventoryPro</p>
        </div>
    </div>
</body>
</html>