<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar pago de transferencia</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        .container {
            width: 90%;
            margin: auto;
            max-width: 600px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #f44336;
            color: white;
            padding: 10px;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirmar Pago por Transferencia</h1>
        </div>
        <p>Estimado administrador,</p>
        <p>El usuario {{ $usuario->nombre }} ha realizado una transferencia bancaria y está esperando la confirmación de su pago.</p>
        <p>Por favor, accede al sistema y confirma el pago lo antes posible.</p>
        <p>Saludos cordiales,</p>
        <p>El equipo de InventoryPro</p>
        <div class="footer">
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
