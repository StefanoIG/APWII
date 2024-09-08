<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Exitoso</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
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
            color: #333333;
            font-size: 24px;
        }
        p {
            color: #666666;
            line-height: 1.8;
            font-size: 16px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }
        ul li::before {
            content: '•';
            color: #007BFF;
            margin-right: 10px;
            font-size: 20px;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #999999;
            text-align: center;
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
        <div class="footer">
            <p>Si tienes alguna pregunta, por favor contacta a nuestro <a href="mailto:soporte@example.com">soporte</a>.</p>
            <p>&copy; 2024 {{ config('app.name') }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>