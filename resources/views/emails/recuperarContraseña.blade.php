<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
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
        }
        .header img {
            max-width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333333;
            font-size: 24px;
        }
        p {
            color: #666666;
            line-height: 1.8;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #007BFF;
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
            color: #999999;
            text-align: center;
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
        <p>Hola, {{ $nombre }}</p>
        <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para restablecerla:</p>
        <p><a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a></p>
        <p>Este enlace expirará en 60 minutos.</p>
        <p>Si no solicitaste un restablecimiento de contraseña, no es necesario que hagas nada.</p>
        <div class="footer">
            <p>Si tienes alguna pregunta, por favor contacta a nuestro <a href="mailto:soporte@example.com">soporte</a>.</p>
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
            <br>
            <p>by InventoryPro</p>
        </div>
    </div>
</body>
</html>