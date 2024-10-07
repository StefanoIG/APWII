<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f9f9f9;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #4CAF50; /* Verde menta */
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header .logo img {
            width: 150px;
        }
        .header .details {
            text-align: right;
        }
        .header .details h2 {
            margin: 0;
            color: #4CAF50; /* Verde menta */
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.8em;
            color: #4CAF50; /* Verde menta */
        }
        .info-section {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .info-section p {
            margin: 5px 0;
        }
        .info-section_bodega {
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50; /* Verde menta */
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 10px;
            text-align: left;
        }
        .right-align {
            text-align: right;
        }
        .total {
            text-align: right;
            font-weight: bold;
        }
        tfoot td {
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-style: italic;
            color: #666;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer p.copyright {
            font-size: 0.9em;
            color: #999;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <!-- Logo -->
        <div class="logo">
            <img src="{{ ('./images/logo.png') }}" alt="Logo Empresa">
        </div>
        <!-- Comprobante Details -->
        <div class="details">
            <h2>Inventory Pro</h2>
            <h2>Comprobante de Salida</h2>
            <p><strong>Número:</strong> {{ $comprobante->id_comprobante }}</p>
            <p><strong>Fecha de Emisión:</strong> {{ $comprobante->fecha_emision }}</p>
        </div>
    </div>

    <!-- Cliente/Empleado and Lote Details -->
    <div class="info-section">
        <p><strong>Empleado a Cargo:</strong> {{ $comprobante->usuario->nombre }}</p>
        <div class="info-section_bodega">
            <p><strong>Bodega:</strong> {{ $comprobante->lote->sitio->nombre_sitio }}</p>
            <p><strong>Fecha de Salida:</strong> {{ $comprobante->fecha_emision }}</p>
            <p><strong>Dirección de la Bodega:</strong> {{ $comprobante->lote->sitio->direccion }}</p>
        </div>
    </div>

    <!-- Table with Product Details -->
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Descripción</th>
                <th>Monto Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $comprobante->producto->nombre_producto }}</td>
                <td>{{ $comprobante->cantidad }}</td>
                <td>{{ $comprobante->producto->descripcion_producto }}</td>
                <td>${{ number_format($comprobante->producto->precio_unitario, 2) }}</td>
                <td>${{ number_format($comprobante->precio_total, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right-align"><strong>Subtotal:</strong></td>
                <td class="total">${{ number_format($comprobante->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right-align"><strong>IVA (15%):</strong></td>
                <td class="total">${{ number_format($comprobante->iva, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right-align"><strong>Monto Final:</strong></td>
                <td class="total">${{ number_format($comprobante->monto_final, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Salió de la bodega {{ $comprobante->lote->sitio->nombre_sitio }}, fue sacado por el empleado {{ $comprobante->usuario->nombre }}.</p>
        <p class="copyright">&copy; 2024 InventoryPro. Todos los derechos reservados.</p>
        <p>by InventoryPro</p>
    </div>
</body>
</html>