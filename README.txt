ENVÍOS PRO — APLICATIVO PHP + MYSQL
===================================

FUNCIONES
- Listar envíos registrados.
- Crear nuevos envíos.
- Editar envíos existentes.
- Eliminar envíos con confirmación.
- Buscar por destinatario, dirección o descripción.
- Diseño adaptable a computador, tableta y celular.
- Creación automática de la tabla `envios` si no existe.
- Consultas preparadas PDO y token CSRF para formularios.

CAMPOS DE CADA ENVÍO
- destinatario
- direccion
- descripcion
- creado_en y actualizado_en (fechas automáticas)

INSTALACIÓN EN ALWAYSData
1. Descomprime el archivo ZIP en tu computador.
2. Sube la carpeta/archivos a la carpeta web de tu alojamiento Alwaysdata.
3. Revisa `config/database.php` y confirma las credenciales de MySQL.
4. Abre la dirección web de tu sitio en el navegador.
5. Al abrir el aplicativo, se conectará a MySQL y creará la tabla `envios` automáticamente si aún no existe.

REQUISITOS
- PHP 8.0 o superior recomendado.
- Extensión PDO MySQL habilitada.
- La base de datos `julianapi_enviosgit` debe existir previamente en el panel de hosting.
  El aplicativo crea la tabla, pero no crea la base de datos del servidor.

ARCHIVOS
- index.php: pantalla principal y operaciones CRUD.
- config/database.php: conexión y creación automática de la tabla.
- assets/style.css: diseño y estilos.
- README.txt: estas instrucciones.

SEGURIDAD
Las credenciales se han incluido porque fueron proporcionadas para configurar el proyecto.
No publiques el ZIP ni compartas el archivo de configuración con terceros. Si la clave es real
y se ha compartido en un lugar público, cámbiala desde el panel de hosting y actualiza este archivo.
Para un sitio público, se recomienda mover las credenciales a variables de entorno y habilitar HTTPS.
