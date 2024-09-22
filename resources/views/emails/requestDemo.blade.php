<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud de Demo</title>
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
            background-color: #007bff;
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
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            background-color: #007bff;
            color: white;
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
            padding-top: 20px;
            font-size: 12px;
            color: #bbb;
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
            <h1>Nueva Solicitud de Demo</h1>
        </div>
        <div class="content">
            <p>Estimado/a Administrador/a,</p>
            <p>Se ha recibido una nueva solicitud de demo en el sistema. A continuación, se encuentran los detalles:</p>
            <p><strong>Correo:</strong> {{ $demoRequest->email }}</p>
            <p>Por favor, revise la solicitud y tome las acciones necesarias.</p>
            <div class="button-container">
                <a href="#" class="button">Iniciar Sesión</a>
            </div>
            <p>Gracias.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} InventoryPro. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>