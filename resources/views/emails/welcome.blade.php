<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Exitoso</title>
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
            background-color: #007BFF;
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
        ul {
            list-style-type: none;
            padding: 0;
            text-align: left;
            margin: 20px 0;
        }
        ul li {
            margin: 10px 0;
            padding: 10px;
            background-color: #444;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        ul li::before {
            content: '✔';
            color: #007BFF;
            margin-right: 10px;
            font-size: 20px;
        }
        .footer {
            padding-top: 20px;
            font-size: 12px;
            color: #bbb;
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
            <h1>Registro Exitoso</h1>
        </div>
        <div class="content">
            <p>Estimado {{ $nombre }},</p>
            <p>Su registro ha sido exitoso.</p>
            <p>Su superior se pondrá en contacto con usted para brindarle sus credenciales de acceso.</p>
            <p>En el sistema puede:</p>
            <ul>
                <li>Agregar productos</li>
                <li>Administrar lotes</li>
                <li>Generar comprobantes</li>
                <li>Y muchas otras funcionalidades.</li>
            </ul>
            <p>Saludos cordiales,</p>
            <p>El equipo de {{ config('app.name') }}</p>
        </div>
        <div class="footer">
            <p>Si tienes alguna pregunta, por favor contacta a nuestro <a href="mailto:soporte@example.com">soporte</a>.</p>
            <p>&copy; 2024 {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>