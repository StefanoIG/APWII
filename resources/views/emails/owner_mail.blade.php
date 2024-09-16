<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a InventoryPro</title>
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
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px 0;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #45a049;
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
            <h1>¡Bienvenido a InventoryPro!</h1>
        </div>
        <div class="content">
            <p>Hola {{ $owner->nombre }},</p>
            <p>Gracias por registrarte en nuestra plataforma. Actualmente estamos procesando tu suscripción.</p>
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            <p>Saludos cordiales,</p>
            <p>El equipo de InventoryPro</p>
            <a href="#" class="btn">Contactar Soporte</a>
        </div>
        <div class="footer">
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>