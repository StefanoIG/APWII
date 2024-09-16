<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pago por Transferencia</title>
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
            background-color: #9c27b0;
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
            <h1>Confirmar Pago por Transferencia</h1>
        </div>
        <div class="content">
            <p>Estimado administrador,</p>
            <p>El usuario {{ $usuario->nombre }} ha realizado una transferencia bancaria y está esperando la confirmación de su pago.</p>
            <p>Por favor, accede al sistema y confirma el pago lo antes posible.</p>
            <p>Saludos cordiales,</p>
            <p>El equipo de InventoryPro</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>