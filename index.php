<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/database.php';

function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(): never { header('Location: index.php'); exit; }

try { $pdo = db(); } catch (Throwable $ex) { http_response_code(500); $dbError = $ex->getMessage(); }
if (!isset($dbError) && empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

if (!isset($dbError) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        $destinatario = trim((string)($_POST['destinatario'] ?? ''));
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        if ($destinatario === '' || $direccion === '' || $descripcion === '') {
            $_SESSION['flash'] = ['type'=>'error','text'=>'Completa todos los campos antes de guardar.'];
            $_SESSION['old'] = compact('id','destinatario','direccion','descripcion'); redirect();
        }
        if (mb_strlen($destinatario) > 150 || mb_strlen($direccion) > 255) {
            $_SESSION['flash'] = ['type'=>'error','text'=>'El destinatario o la dirección superan la longitud permitida.'];
            $_SESSION['old'] = compact('id','destinatario','direccion','descripcion'); redirect();
        }
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE envios SET destinatario=?, direccion=?, descripcion=? WHERE id=?');
            $st->execute([$destinatario,$direccion,$descripcion,$id]);
            $_SESSION['flash'] = ['type'=>'success','text'=>'Envío actualizado correctamente.'];
        } else {
            $st = $pdo->prepare('INSERT INTO envios (destinatario,direccion,descripcion) VALUES (?,?,?)');
            $st->execute([$destinatario,$direccion,$descripcion]);
            $_SESSION['flash'] = ['type'=>'success','text'=>'Nuevo envío registrado correctamente.'];
        }
        redirect();
    }
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($id > 0) {
            $st = $pdo->prepare('DELETE FROM envios WHERE id=?'); $st->execute([$id]);
            $_SESSION['flash'] = ['type'=>'success','text'=>'El envío fue eliminado.'];
        }
        redirect();
    }
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$old = $_SESSION['old'] ?? null; unset($_SESSION['old']);
$editing = null;
if (!isset($dbError)) {
    $editId = filter_input(INPUT_GET,'editar',FILTER_VALIDATE_INT);
    if ($old) $editing = $old;
    elseif ($editId) { $st=$pdo->prepare('SELECT * FROM envios WHERE id=?'); $st->execute([$editId]); $editing=$st->fetch() ?: null; }
    $search = trim((string)($_GET['buscar'] ?? ''));
    if ($search !== '') {
        $st=$pdo->prepare('SELECT * FROM envios WHERE destinatario LIKE ? OR direccion LIKE ? OR descripcion LIKE ? ORDER BY creado_en DESC');
        $term='%'.$search.'%'; $st->execute([$term,$term,$term]); $envios=$st->fetchAll();
    } else $envios=$pdo->query('SELECT * FROM envios ORDER BY creado_en DESC')->fetchAll();
    $total=(int)$pdo->query('SELECT COUNT(*) FROM envios')->fetchColumn();
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#11100f">
<title>NOCTURNA | Centro de logística</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="ambient ambient-one"></div><div class="ambient ambient-two"></div>
<div class="layout">
<aside class="sidebar">
  <a class="brand" href="index.php"><span class="brand-symbol"><span>n</span></span><span class="brand-text"><b>NOCTURNA<span>.</span></b><small>LOGISTICS SYSTEM</small></span></a>
  <div class="side-caption">ESPACIO DE TRABAJO</div>
  <a class="nav-item selected" href="index.php"><span class="nav-ico">◈</span><span>Centro de control</span><i></i></a>
  <a class="nav-item" href="#envios"><span class="nav-ico">▤</span><span>Todos los envíos</span><em><?= isset($total) ? (int)$total : 0 ?></em></a>
  <a class="nav-item" href="#formulario"><span class="nav-ico">＋</span><span>Crear un envío</span></a>
  <div class="sidebar-bottom">
    <div class="orb"><span></span></div>
    <div><small>ESTADO DEL SISTEMA</small><strong><i class="online-dot"></i> Operativo</strong></div>
    <span class="version">v2.0</span>
  </div>
</aside>

<main class="main">
  <header class="topbar">
    <div class="crumb"><span>Workspace</span><b>/</b><strong>Centro de control</strong></div>
    <div class="top-actions"><span class="time-indicator"><i></i> SISTEMA ACTIVO</span><button class="round-action" id="themeGlow" title="Cambiar intensidad de iluminación" aria-label="Cambiar intensidad de iluminación">☼</button><span class="user-chip"><span class="user-avatar">N</span><span><b>Administrador</b><small>Operaciones</small></span></span></div>
  </header>

  <section class="hero">
    <div class="hero-copy"><div class="kicker"><span class="kicker-line"></span> LOGÍSTICA · VISIÓN GENERAL</div>
      <h1>El control está<br><span>en tus manos.</span></h1>
      <p>Una vista precisa de cada entrega. Gestiona tu operación con claridad, velocidad y control.</p>
      <a href="#formulario" class="glow-button"><span class="button-plus">＋</span> Registrar un envío <span class="button-arrow">↗</span></a>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="art-ring ring-a"></div><div class="art-ring ring-b"></div><div class="art-ring ring-c"></div>
      <div class="art-core"><span class="core-cross">✦</span><span class="core-label">N / LOGISTICS</span></div>
      <div class="floating-tag tag-top"><span class="tag-icon">↗</span><span><small>OPERACIÓN</small><b>En movimiento</b></span></div>
      <div class="floating-tag tag-bottom"><span class="pulse-icon"></span><span><small>REGISTROS ACTIVOS</small><b><?= isset($total) ? number_format($total,0,',','.') : '—' ?> envíos</b></span></div>
    </div>
    <div class="hero-index">01 <span>/</span> 03</div>
  </section>

  <?php if (isset($dbError)): ?>
    <div class="notice error"><b>No se pudo conectar con MySQL.</b><br>Verifica las credenciales de <code>config/database.php</code>, que la base de datos exista y que el servidor permita la conexión.<details><summary>Detalle técnico</summary><pre><?= e($dbError) ?></pre></details></div>
  <?php else: ?>
    <?php if ($flash): ?><div class="notice <?= e($flash['type']) ?>"><?= e($flash['text']) ?></div><?php endif; ?>

    <section class="metric-row">
      <article class="metric-card"><div class="metric-top"><span class="metric-label">ENVÍOS REGISTRADOS</span><span class="metric-icon amber">▤</span></div><div class="metric-number"><?= number_format($total,0,',','.') ?><span class="metric-unit"> registros</span></div><div class="metric-bottom"><span class="tiny-line"></span> Inventario total de entregas</div></article>
      <article class="metric-card"><div class="metric-top"><span class="metric-label">RESULTADOS EN PANTALLA</span><span class="metric-icon copper">⌕</span></div><div class="metric-number"><?= number_format(count($envios),0,',','.') ?><span class="metric-unit"> resultados</span></div><div class="metric-bottom"><span class="tiny-line"></span><?= $search !== '' ? 'Coincidencias de búsqueda' : 'Vista general de operación' ?></div></article>
      <article class="quote-card"><div class="quote-mark">“</div><p>Cada entrega cuenta.<br><strong>Cada detalle importa.</strong></p><span>NOCTURNA · LOGISTICS</span><div class="quote-glow"></div></article>
    </section>

    <section class="content-grid">
      <article class="surface form-surface" id="formulario">
        <div class="section-heading"><div><div class="section-kicker"><?= $editing ? 'MODIFICAR REGISTRO' : 'NUEVA OPERACIÓN' ?></div><h2><?= $editing ? 'Editar envío' : 'Crear envío' ?></h2></div><span class="section-index"><?= $editing ? '02' : '01' ?></span></div>
        <p class="muted-copy">Completa los datos del destinatario y del paquete.</p>
        <form method="post" action="index.php" class="shipment-form">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? 0)) ?>">
          <label for="destinatario">DESTINATARIO <span>*</span></label><div class="input-wrap"><span class="input-icon">♙</span><input id="destinatario" name="destinatario" maxlength="150" required placeholder="Nombre completo" value="<?= e($editing['destinatario'] ?? '') ?>"></div>
          <label for="direccion">DIRECCIÓN DE ENTREGA <span>*</span></label><div class="input-wrap"><span class="input-icon">⌖</span><input id="direccion" name="direccion" maxlength="255" required placeholder="Calle, número, barrio, ciudad" value="<?= e($editing['direccion'] ?? '') ?>"></div>
          <label for="descripcion">DESCRIPCIÓN DEL PAQUETE <span>*</span></label><div class="input-wrap textarea-wrap"><span class="input-icon">▤</span><textarea id="descripcion" name="descripcion" rows="4" required placeholder="Contenido, características o instrucciones..."><?= e($editing['descripcion'] ?? '') ?></textarea></div>
          <div class="field-note"><span>✦</span> La información se almacena de forma centralizada.</div>
          <div class="form-footer"><?php if ($editing): ?><a href="index.php" class="ghost-button">Cancelar</a><?php endif; ?><button class="glow-button submit-button" type="submit"><span><?= $editing ? '✓' : '＋' ?></span><?= $editing ? 'Guardar cambios' : 'Guardar envío' ?><b>↗</b></button></div>
        </form>
      </article>

      <article class="surface table-surface" id="envios">
        <div class="table-title-row"><div><div class="section-kicker">SEGUIMIENTO EN TIEMPO REAL</div><h2>Registro de envíos</h2></div><span class="record-pill"><i></i><?= count($envios) ?> visibles</span></div>
        <form method="get" class="search-bar"><span class="search-glyph">⌕</span><input type="search" name="buscar" value="<?= e($search ?? '') ?>" placeholder="Buscar por nombre, dirección o descripción..."><button type="submit" class="search-submit" title="Buscar">↗</button><?php if (($search ?? '') !== ''): ?><a href="index.php" class="search-clear" title="Limpiar búsqueda">×</a><?php endif; ?></form>
        <?php if (!$envios): ?>
          <div class="empty-state"><div class="empty-orbit"><span>▤</span></div><h3><?= ($search ?? '') !== '' ? 'Sin coincidencias' : 'Tu registro comienza aquí' ?></h3><p><?= ($search ?? '') !== '' ? 'Prueba con otro término.' : 'Cuando crees un envío, aparecerá en esta sección.' ?></p><a href="#formulario" class="text-link">Crear primer envío <span>↗</span></a></div>
        <?php else: ?>
          <div class="table-wrap"><table><thead><tr><th>DESTINATARIO</th><th>DIRECCIÓN</th><th>FECHA</th><th>GESTIÓN</th></tr></thead><tbody>
          <?php foreach ($envios as $envio): ?><tr>
            <td><div class="person-cell"><span class="person-monogram"><?= e(mb_strtoupper(mb_substr($envio['destinatario'],0,1))) ?></span><div><strong><?= e($envio['destinatario']) ?></strong><small title="<?= e($envio['descripcion']) ?>"><?= e(mb_strimwidth($envio['descripcion'],0,55,'…')) ?></small></div></div></td>
            <td><div class="address-cell"><span>⌖</span><?= e($envio['direccion']) ?></div></td>
            <td><span class="date-chip"><?= e(date('d.m.Y',strtotime($envio['creado_en']))) ?></span></td>
            <td><div class="row-actions"><a href="index.php?editar=<?= (int)$envio['id'] ?>#formulario" class="mini-fab edit-fab" title="Editar envío" aria-label="Editar envío">✎</a><form method="post" onsubmit="return confirm('¿Deseas eliminar este envío? Esta acción no se puede deshacer.');"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$envio['id'] ?>"><button class="mini-fab delete-fab" type="submit" title="Eliminar envío" aria-label="Eliminar envío">×</button></form></div></td>
          </tr><?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
        <div class="table-bottom"><span>Mostrando <b><?= count($envios) ?></b> de <b><?= $total ?></b> registros</span><span><i class="online-dot"></i> Sincronizado con MySQL</span></div>
      </article>
    </section>
    <footer class="footer"><span>© <?= date('Y') ?> NOCTURNA LOGISTICS</span><span>PRECISIÓN EN CADA MOVIMIENTO <b>✦</b></span></footer>
  <?php endif; ?>
</main>
</div>

<div class="floating-rail" aria-label="Accesos rápidos">
  <a class="rail-button rail-primary" href="#formulario" title="Crear nuevo envío" aria-label="Crear nuevo envío"><span>＋</span><em>Nuevo envío</em></a>
  <a class="rail-button" href="#envios" title="Ir a los envíos" aria-label="Ir a los envíos"><span>▤</span><em>Ver envíos</em></a>
  <button class="rail-button" id="backTop" title="Volver arriba" aria-label="Volver arriba"><span>↑</span><em>Volver arriba</em></button>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"><span>✦</span><div><b>Atajo de navegación</b><small>Acción lista.</small></div><button id="toastClose" aria-label="Cerrar aviso">×</button></div>
<script>
(() => {
  const glowButton = document.getElementById('themeGlow');
  const topButton = document.getElementById('backTop');
  const toast = document.getElementById('toast');
  const toastClose = document.getElementById('toastClose');
  let dim = false;
  if (glowButton) glowButton.addEventListener('click', () => {
    dim = !dim;
    document.body.classList.toggle('dim-glow', dim);
    glowButton.textContent = dim ? '☾' : '☼';
    glowButton.title = dim ? 'Restaurar iluminación cálida' : 'Reducir intensidad de iluminación';
    toast.querySelector('b').textContent = dim ? 'Modo luz tenue' : 'Iluminación cálida';
    toast.querySelector('small').textContent = dim ? 'Brillo ambiental reducido.' : 'Tonos cálidos restaurados.';
    toast.classList.add('show');
  });
  if (topButton) topButton.addEventListener('click', () => window.scrollTo({top: 0, behavior: 'smooth'}));
  if (toastClose) toastClose.addEventListener('click', () => toast.classList.remove('show'));
  document.querySelectorAll('.rail-button[href]').forEach(btn => btn.addEventListener('click', () => {
    toast.querySelector('b').textContent = btn.getAttribute('aria-label') || 'Navegación';
    toast.querySelector('small').textContent = 'Moviéndote a la sección seleccionada.';
    toast.classList.add('show');
    window.setTimeout(() => toast.classList.remove('show'), 2600);
  }));
  document.querySelectorAll('.input-wrap input, .input-wrap textarea').forEach(field => {
    field.addEventListener('focus', () => field.closest('.input-wrap').classList.add('focused'));
    field.addEventListener('blur', () => field.closest('.input-wrap').classList.remove('focused'));
  });
})();
</script>
</body></html>
