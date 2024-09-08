<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header .logo {
            width: 150px;
        }
        .header .details {
            text-align: right;
        }
        .header .details h2 {
            margin: 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .total, .right-align {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <!-- Logo -->
            <div class="logo">
                <img src="{{ ('./images/logo.png') }}" alt="Logo Empresa" style="width: 100%;">
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
            <br>
            <p>Todos los derechos reservados | by InventoryPro</p>
        </div>
    </div>
</body>
</html>
