<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Demo Rechazada</title>
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
            height: 100vh;
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
            background-color: #dc3545;
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
            <h1>Solicitud de Demo Rechazada</h1>
        </div>
        <div class="content">
            <p>Hola {{ $demoRequest->email }},</p>
            <p>Lo sentimos, pero tu solicitud de demo ha sido rechazada. Si crees que esto es un error o necesitas más información, no dudes en contactarnos.</p>
            <p>Gracias por tu interés en nuestro servicio.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>