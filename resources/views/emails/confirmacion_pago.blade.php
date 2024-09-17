<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago</title>
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
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .header {
            background-color: #4caf50;
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
            <h1>Confirmación de Pago</h1>
        </div>
        <div class="content">
            <p>Hola {{ $usuario->nombre }},</p>
            <p>Nos complace informarte que tu pago ha sido confirmado con éxito.</p>
            <p>Tu suscripción ha sido activada y podrás disfrutar de todos los beneficios de nuestro servicio.</p>
            <p>Gracias por tu confianza en InventoryPro.</p>
            <p>Saludos cordiales,</p>
            <p>El equipo de InventoryPro</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>