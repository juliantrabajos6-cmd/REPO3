NOCTURNA LOGISTICS — GESTOR DE ENVÍOS PHP + MYSQL
===================================================

Incluye:
- Crear, listar, editar y eliminar envíos.
- Buscar por destinatario, dirección y descripción.
- Panel oscuro premium con iluminación cálida ámbar/cobre.
- Botones flotantes para crear, ver envíos y volver arriba.
- Controles interactivos de iluminación, mensajes de acción y efectos de foco.
- Diseño adaptable para escritorio, tableta y celular.
- Creación automática de la tabla `envios` cuando no exista.
- PDO con consultas preparadas y token CSRF para formularios.

Instalación:
1. Descomprime el ZIP.
2. Sube los archivos a la carpeta pública de tu alojamiento web.
3. Verifica las credenciales en config/database.php.
4. Abre tu sitio web en el navegador.
5. La tabla `envios` se crea automáticamente al conectar con la base de datos.

Requisitos:
- PHP 8.0 o superior recomendado.
- PDO MySQL habilitado.
- La base de datos indicada debe existir antes de abrir el aplicativo. El sistema crea la tabla, no la base de datos.

Seguridad:
Las credenciales fueron incluidas a solicitud del usuario. No compartas este ZIP públicamente.
Si la contraseña es real y ha sido expuesta, cámbiala en el panel de alojamiento y actualiza el archivo.
Para un sitio público, usa HTTPS y variables de entorno para almacenar secretos.
