<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Demo Aprobado</title>
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
            background-color: #1e3a8a;
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
        .credentials {
            background-color: #444;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        .credentials p {
            margin: 10px 0;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 15px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            display: inline-block;
        }
        .button:hover {
            background-color: #162d6a;
        }
        .footer {
            padding-top: 20px;
            font-size: 12px;
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="Logo">
            <h1>Acceso a la Demo Aprobado</h1>
        </div>
        <div class="content">
            <p>Hola {{ $usuarioDemo->correo_electronico }},</p>
            <p>Nos complace informarte que tu solicitud de demo ha sido aprobada. A continuación, encontrarás tus credenciales de acceso:</p>
            
            <div class="credentials">
                <p><strong>Correo electrónico:</strong> {{ $usuarioDemo->correo_electronico }}</p>
                <p><strong>Contraseña:</strong> {{ $passwordPlano }}</p>
            </div>
            
            <div class="button-container">
                <a href="#" class="button">Iniciar Sesión</a>
            </div>

            <p>Puedes iniciar sesión en nuestra plataforma utilizando las credenciales proporcionadas.</p>
            <p>Gracias por probar nuestro servicio.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>