<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Habilitada</title>
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
            background-color: #28a745;
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
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #218838;
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
            <h1>Cuenta Habilitada</h1>
        </div>
        <div class="content">
            <p>Hola {{ $usuario->nombre }},</p>
            <p>Tu cuenta ha sido habilitada exitosamente. Ahora puedes acceder a todos los servicios.</p>
            <p>Gracias por ser parte de nuestro servicio.</p>
            <a href="#" class="btn">Ir a la Plataforma</a>
        </div>
        <div class="footer">
            <p>&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>