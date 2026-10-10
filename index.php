<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/database.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function redirect(): never {
    header('Location: index.php');
    exit;
}

try {
    $pdo = db();
} catch (Throwable $ex) {
    http_response_code(500);
    $dbError = $ex->getMessage();
}

if (!isset($dbError) && empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!isset($dbError) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        $destinatario = trim((string)($_POST['destinatario'] ?? ''));
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        if ($destinatario === '' || $direccion === '' || $descripcion === '') {
            $_SESSION['flash'] = ['type' => 'error', 'text' => 'Completa todos los campos antes de guardar.'];
            $_SESSION['old'] = compact('id', 'destinatario', 'direccion', 'descripcion');
            redirect();
        }
        if (mb_strlen($destinatario) > 150 || mb_strlen($direccion) > 255) {
            $_SESSION['flash'] = ['type' => 'error', 'text' => 'El destinatario o la dirección superan la longitud permitida.'];
            $_SESSION['old'] = compact('id', 'destinatario', 'direccion', 'descripcion');
            redirect();
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE envios SET destinatario = ?, direccion = ?, descripcion = ? WHERE id = ?');
            $stmt->execute([$destinatario, $direccion, $descripcion, $id]);
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'El envío se actualizó correctamente.'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO envios (destinatario, direccion, descripcion) VALUES (?, ?, ?)');
            $stmt->execute([$destinatario, $direccion, $descripcion]);
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'El envío se registró correctamente.'];
        }
        redirect();
    }

    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM envios WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'El envío fue eliminado.'];
        }
        redirect();
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$old = $_SESSION['old'] ?? null;
unset($_SESSION['old']);

$editing = null;
if (!isset($dbError)) {
    $editId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($old) {
        $editing = $old;
    } elseif ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM envios WHERE id = ?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch() ?: null;
    }

    $search = trim((string)($_GET['buscar'] ?? ''));
    if ($search !== '') {
        $stmt = $pdo->prepare('SELECT * FROM envios WHERE destinatario LIKE ? OR direccion LIKE ? OR descripcion LIKE ? ORDER BY creado_en DESC');
        $term = '%' . $search . '%';
        $stmt->execute([$term, $term, $term]);
        $envios = $stmt->fetchAll();
    } else {
        $envios = $pdo->query('SELECT * FROM envios ORDER BY creado_en DESC')->fetchAll();
    }
    $total = (int)$pdo->query('SELECT COUNT(*) FROM envios')->fetchColumn();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Envíos Pro | Panel de gestión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-mark">EP</span>
            <span><strong>ENVÍOS<span class="brand-light">PRO</span></strong><small>CONTROL LOGÍSTICO</small></span>
        </a>
        <div class="side-label">GESTIÓN</div>
        <a class="side-link active" href="index.php"><span class="side-icon">▦</span> Panel de envíos</a>
        <div class="sidebar-bottom">
            <div class="status-dot"></div>
            <div><strong>Sistema operativo</strong><small>Base de datos automática</small></div>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">Operaciones <span>/</span> <strong>Envíos</strong></div>
            <div class="topbar-right"><span class="live-dot"></span> Panel de control <span class="avatar">EP</span></div>
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">CENTRO DE OPERACIONES</div>
                <h1>Gestión de envíos<span>.</span></h1>
                <p>Administra destinatarios, direcciones y detalles de cada entrega desde un solo lugar.</p>
            </div>
            <a href="#formulario" class="primary-button"><span>＋</span> Nuevo envío</a>
        </section>

        <?php if (isset($dbError)): ?>
            <div class="alert error"><strong>No se pudo conectar con MySQL.</strong><br>
                Verifica los datos de conexión en <code>config/database.php</code>, que la base de datos exista y que el servidor permita conexiones.
                <details><summary>Ver detalle técnico</summary><pre><?= e($dbError) ?></pre></details>
            </div>
        <?php else: ?>

        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['text']) ?></div>
        <?php endif; ?>

        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-icon navy">▣</div>
                <div><span class="stat-label">ENVÍOS REGISTRADOS</span><div class="stat-value"><?= number_format($total, 0, ',', '.') ?></div><small>Total de registros en el sistema</small></div>
                <div class="stat-accent"></div>
            </article>
            <article class="stat-card">
                <div class="stat-icon blue">↗</div>
                <div><span class="stat-label">RESULTADOS VISIBLES</span><div class="stat-value"><?= number_format(count($envios), 0, ',', '.') ?></div><small><?= $search !== '' ? 'Coincidencias de tu búsqueda' : 'Listado completo de envíos' ?></small></div>
                <div class="stat-accent blue-accent"></div>
            </article>
            <article class="stat-card welcome-card">
                <div class="welcome-symbol">✦</div>
                <div><span class="stat-label">TODO BAJO CONTROL</span><div class="welcome-title">Tu operación,<br>en orden.</div><small>Información centralizada y accesible.</small></div>
            </article>
        </section>

        <section class="workspace">
            <article class="panel form-panel" id="formulario">
                <div class="panel-heading">
                    <div><div class="eyebrow"><?= $editing ? 'ACTUALIZAR REGISTRO' : 'NUEVO REGISTRO' ?></div><h2><?= $editing ? 'Editar envío' : 'Registrar envío' ?></h2></div>
                    <span class="heading-icon"><?= $editing ? '✎' : '＋' ?></span>
                </div>
                <p class="panel-description">Ingresa la información necesaria para identificar y entregar el paquete.</p>
                <form method="post" action="index.php" class="shipment-form">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? 0)) ?>">
                    <label for="destinatario">Destinatario <span>*</span></label>
                    <input id="destinatario" name="destinatario" maxlength="150" required placeholder="Nombre completo del destinatario" value="<?= e($editing['destinatario'] ?? '') ?>">
                    <label for="direccion">Dirección de entrega <span>*</span></label>
                    <input id="direccion" name="direccion" maxlength="255" required placeholder="Calle, número, barrio, ciudad" value="<?= e($editing['direccion'] ?? '') ?>">
                    <label for="descripcion">Descripción del paquete <span>*</span></label>
                    <textarea id="descripcion" name="descripcion" rows="4" required placeholder="Contenido, características o instrucciones de entrega..."><?= e($editing['descripcion'] ?? '') ?></textarea>
                    <div class="form-hint"><span>ⓘ</span> Los campos marcados con * son obligatorios.</div>
                    <div class="form-actions">
                        <?php if ($editing): ?><a class="secondary-button" href="index.php">Cancelar</a><?php endif; ?>
                        <button class="primary-button submit-button" type="submit"><?= $editing ? 'Guardar cambios' : 'Guardar envío' ?> <span>→</span></button>
                    </div>
                </form>
            </article>

            <article class="panel list-panel">
                <div class="panel-heading list-heading">
                    <div><div class="eyebrow">SEGUIMIENTO DE REGISTROS</div><h2>Envíos recientes</h2></div>
                    <span class="count-badge"><?= count($envios) ?> registros</span>
                </div>
                <form method="get" class="search-form">
                    <span class="search-symbol">⌕</span>
                    <input type="search" name="buscar" placeholder="Buscar destinatario, dirección..." value="<?= e($search ?? '') ?>">
                    <button type="submit">Buscar</button>
                    <?php if (($search ?? '') !== ''): ?><a href="index.php" class="clear-search" title="Limpiar búsqueda">×</a><?php endif; ?>
                </form>

                <?php if (!$envios): ?>
                    <div class="empty-state">
                        <div class="empty-icon">▤</div>
                        <h3><?= ($search ?? '') !== '' ? 'No hay coincidencias' : 'Aún no hay envíos' ?></h3>
                        <p><?= ($search ?? '') !== '' ? 'Prueba con otro término de búsqueda.' : 'Registra tu primer envío usando el formulario.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>DESTINATARIO / DESCRIPCIÓN</th><th>DIRECCIÓN DE ENTREGA</th><th>FECHA</th><th class="actions-head">ACCIONES</th></tr></thead>
                            <tbody>
                            <?php foreach ($envios as $envio): ?>
                                <tr>
                                    <td>
                                        <div class="recipient-cell"><span class="recipient-avatar"><?= e(mb_strtoupper(mb_substr($envio['destinatario'], 0, 1))) ?></span><div><strong><?= e($envio['destinatario']) ?></strong><small><?= e(mb_strimwidth($envio['descripcion'], 0, 68, '…')) ?></small></div></div>
                                    </td>
                                    <td><div class="address-cell"><span>⌖</span><?= e($envio['direccion']) ?></div></td>
                                    <td><span class="date-cell"><?= e(date('d/m/Y', strtotime($envio['creado_en']))) ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="icon-button edit" href="index.php?editar=<?= (int)$envio['id'] ?>#formulario" title="Editar envío" aria-label="Editar envío">✎</a>
                                            <form method="post" onsubmit="return confirm('¿Seguro que deseas eliminar este envío? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$envio['id'] ?>">
                                                <button class="icon-button delete" type="submit" title="Eliminar envío" aria-label="Eliminar envío">⌫</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div class="table-footer"><span>Mostrando <strong><?= count($envios) ?></strong> de <strong><?= $total ?></strong> envíos</span><span class="secure-note">● Datos almacenados en MySQL</span></div>
            </article>
        </section>
        <footer class="footer"><span>© <?= date('Y') ?> Envíos Pro · Panel de gestión</span><span>Diseñado para una operación eficiente <b>◆</b></span></footer>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
