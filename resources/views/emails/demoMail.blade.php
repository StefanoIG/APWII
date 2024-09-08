<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Demo Aprobado</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
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
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .credentials {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #999999;
        }
        .footer a {
            color: #007bff;
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
            <img src="logo.png" alt="Logo">
        </div>
        <h1>Acceso a la Demo Aprobado</h1>
        <p>Hola {{ $usuarioDemo->correo_electronico }},</p>
        <p>Nos complace informarte que tu solicitud de demo ha sido aprobada. A continuación, encontrarás tus credenciales de acceso:</p>
        
        <div class="credentials">
            <p><strong>Correo electrónico:</strong> {{ $usuarioDemo->correo_electronico }}</p>
            <p>Para mayor seguridad, por favor recupera tu contraseña en el siguiente enlace:</p>
            <div class="button-container">
                <a href="#" class="button">Recuperar Contraseña</a>
            </div>
        </div>

        <p>Puedes iniciar sesión en nuestra plataforma utilizando las credenciales proporcionadas.</p>

        <p>Gracias por probar nuestro servicio.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>