
<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

# Sistema de Inventario Backend

Este proyecto es el backend de un sistema de inventario desarrollado con [Laravel](https://laravel.com). Proporciona una solución robusta para la gestión de inventarios, incluyendo la administración de productos, generación de códigos de barras, envío de correos electrónicos y autenticación segura.

## Características

- **Gestión de Inventarios**: Permite agregar, editar y eliminar productos en el inventario.
- **Generación de Códigos de Barras**: Crea códigos de barras únicos para cada producto.
- **Envío de Correos Electrónicos**: Envía correos electrónicos de bienvenida y notificaciones.
- **Autenticación JWT**: Utiliza JSON Web Tokens para una autenticación segura.
- **Roles y Permisos**: Soporta múltiples roles como empleado, admin, demo y owner.
- **Relaciones entre Modelos**: Maneja relaciones complejas entre productos, proveedores, lotes y sitios.
- **Paginación**: Soporta la paginación para manejar grandes conjuntos de datos.
- **Transacciones de Base de Datos**: Asegura la consistencia de los datos mediante transacciones.

## Requisitos

- **Laravel 11**: Asegúrate de tener Laravel 11 instalado para evitar problemas de compatibilidad.

## Instalación

1. **Instalar Laravel**: Si aún no tienes Laravel instalado, puedes hacerlo con:

    ```bash
    composer global require laravel/installer
    ```

2. **Clonar el Repositorio**: Clona el repositorio o haz un fork:

    ```bash
    git clone https://github.com/tu_usuario/tu_repositorio.git
    ```

    O realiza un fork en [GitHub](https://github.com/StefanoIG/APWII/tree/main) y clónalo.

3. **Instalar Dependencias**: Navega a la carpeta del proyecto y ejecuta:

    ```bash
    composer install
    ```

4. **Configurar el Archivo `.env`**: Copia el archivo de ejemplo y configura las variables necesarias:

    ```bash
    cp .env.example .env
    ```

    Luego, edita `.env` para incluir la clave JWT:

    ```plaintext
    JWT_SECRET=tu_clave_jwt_aqui
    ```

    Genera una clave JWT con:

    ```bash
    php artisan jwt:secret
    ```

5. **Ejecutar Migraciones**: Configura la base de datos con:

    ```bash
    php artisan migrate
    ```

6. **Iniciar el Servidor**: Inicia el servidor de desarrollo con:

    ```bash
    php artisan serve
    ```

## Uso

### Autenticación

Utiliza JWT para la autenticación. Incluye el token en el encabezado de tus solicitudes:

```http
Authorization: Bearer <tu_token_jwt>
```

### Endpoints Principales

- **Productos**: `/api/productos`
- **Proveedores**: `/api/proveedores`
- **Lotes**: `/api/lotes`
- **Sitios**: `/api/sitios`
- **Usuarios**: `/api/usuarios`

### Ejemplo de Solicitud

Para crear un nuevo producto, realiza una solicitud POST a `/api/productos` con el siguiente cuerpo:

```json
{
  "nombre": "Producto Ejemplo",
  "descripcion": "Descripción del producto",
  "precio": 100.00,
  "cantidad": 50
}
```

## Documentación

La documentación del proyecto está disponible en `public/docs`. Fue generada con la librería [Scribe](https://scribejs.dev) y puede ser consultada en tu navegador web.

## Contribuciones

Este proyecto fue desarrollado por:

- 🧑‍💻 Stefano Aguilar (Desarrollador Principal)
- 👩‍💻 Cristhian Ortiz (Dev)
- 👨‍💻 Julio Arias     (Dev y Docs)

Agradecemos sus contribuciones y esfuerzo en el desarrollo del sistema.

### Cómo Contribuir

1. **Fork el Repositorio**: Haz un fork del repositorio en GitHub.
2. **Clonar tu Fork**: Clona tu fork en tu máquina local.
3. **Crear una Rama**: Crea una nueva rama para tus cambios.
4. **Realizar Cambios**: Realiza los cambios necesarios en tu rama.
5. **Enviar un Pull Request**: Envía un pull request con una descripción detallada de tus cambios.

