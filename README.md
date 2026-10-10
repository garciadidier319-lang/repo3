# EnviaPro — Gestor de envíos en PHP + MySQL

Aplicación web con diseño adaptable para registrar y administrar envíos.

## Funciones
- Crear envíos con destinatario, dirección de envío y descripción.
- Listar todos los registros, buscar por texto, editar y eliminar.
- Resumen con cantidad total y resultados de búsqueda.
- Creación automática de la tabla `envios` al conectar con MySQL.
- Consultas preparadas PDO, protección CSRF y escape HTML.

## Requisitos
- PHP 8+ con extensiones `pdo_mysql` y `mbstring`.
- MySQL/MariaDB.
- Base de datos creada y permisos para crear tablas, consultar, insertar, actualizar y eliminar registros.

## Instalación
1. Descomprime el ZIP.
2. Sube `index.php`, `config.php` y `styles.css` a la carpeta pública de tu hosting.
3. Las credenciales proporcionadas ya están configuradas en `config.php`. También puedes definir `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` como variables de entorno.
4. Abre la URL de `index.php`. Si la conexión funciona, la tabla `envios` se crea automáticamente cuando no existe.

## Seguridad
No publiques las credenciales en repositorios. Para producción, utiliza variables de entorno, HTTPS y una contraseña exclusiva. El nombre del host debe ser accesible desde el servidor donde corre PHP.

La conexión al servidor remoto no ha sido probada al generar el archivo; confirma la conectividad y los permisos en el panel de tu proveedor.
