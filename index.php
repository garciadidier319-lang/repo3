<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function goHome(): never {
    header('Location: index.php');
    exit;
}

$errors = [];
$form = ['id' => '', 'destinatario' => '', 'direccion_envio' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.');
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM envios WHERE id = ?');
            $stmt->execute([$id]);
        }
        goHome();
    }

    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
        $form = [
            'id' => $id ? (string)$id : '',
            'destinatario' => trim((string)($_POST['destinatario'] ?? '')),
            'direccion_envio' => trim((string)($_POST['direccion_envio'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
        ];

        if ($form['destinatario'] === '' || mb_strlen($form['destinatario']) > 150) $errors[] = 'El destinatario es obligatorio (máximo 150 caracteres).';
        if ($form['direccion_envio'] === '' || mb_strlen($form['direccion_envio']) > 255) $errors[] = 'La dirección es obligatoria (máximo 255 caracteres).';
        if ($form['descripcion'] === '') $errors[] = 'La descripción es obligatoria.';

        if (!$errors) {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE envios SET destinatario = ?, direccion_envio = ?, descripcion = ? WHERE id = ?');
                $stmt->execute([$form['destinatario'], $form['direccion_envio'], $form['descripcion'], $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO envios (destinatario, direccion_envio, descripcion) VALUES (?, ?, ?)');
                $stmt->execute([$form['destinatario'], $form['direccion_envio'], $form['descripcion']]);
            }
            goHome();
        }
    }
}

if (isset($_GET['edit']) && !$errors) {
    $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, destinatario, direccion_envio, descripcion FROM envios WHERE id = ?');
        $stmt->execute([$id]);
        $found = $stmt->fetch();
        if ($found) $form = array_map(static fn($v) => (string)$v, $found);
    }
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM envios WHERE destinatario LIKE ? OR direccion_envio LIKE ? OR descripcion LIKE ? ORDER BY creado_en DESC, id DESC');
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like, $like]);
    $envios = $stmt->fetchAll();
} else {
    $envios = $pdo->query('SELECT * FROM envios ORDER BY creado_en DESC, id DESC')->fetchAll();
}
$total = (int)$pdo->query('SELECT COUNT(*) FROM envios')->fetchColumn();
$editing = $form['id'] !== '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172b4d">
    <title>EnviaPro · Gestión de envíos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<aside class="sidebar">
    <a class="logo" href="index.php"><span class="logo-icon">↗</span><span>Envia<span class="logo-accent">Pro</span><small>GESTIÓN DE ENVÍOS</small></span></a>
    <div class="side-label">MENÚ PRINCIPAL</div>
    <a class="nav-link active" href="index.php"><span class="nav-icon">▦</span> Panel de envíos</a>
    <div class="side-note"><span class="note-icon">✦</span><strong>Todo bajo control</strong><p>Organiza la información de tus envíos en un solo lugar.</p></div>
    <div class="sidebar-bottom"><span class="status-dot"></span> Sistema de gestión<br><small>PHP + MySQL</small></div>
</aside>

<div class="page">
    <header class="topbar">
        <div><div class="breadcrumb">Panel principal <span>/</span> Envíos</div><h1>Gestión de envíos</h1><p class="subtitle">Administra tus entregas de forma sencilla y organizada.</p></div>
        <div class="topbar-right"><div class="avatar">EP</div><div class="profile"><strong>Administrador</strong><span>Panel de control</span></div></div>
    </header>

    <main>
        <section class="welcome-banner">
            <div class="welcome-copy"><span class="banner-kicker">CENTRO DE OPERACIONES</span><h2>Un envío bien gestionado,<br>un cliente satisfecho.</h2><p>Registra y consulta toda la información importante de cada entrega.</p></div>
            <div class="banner-art" aria-hidden="true"><div class="art-circle circle-one"></div><div class="art-circle circle-two"></div><div class="parcel"><div class="parcel-top"></div><div class="parcel-tape"></div><div class="parcel-label"></div></div><div class="art-spark spark-one">✦</div><div class="art-spark spark-two">✧</div></div>
        </section>

        <section class="stats-grid">
            <article class="stat-card"><div class="stat-top"><span class="stat-icon blue">▣</span><span class="stat-caption">REGISTROS</span></div><div class="stat-value"><?= $total ?></div><div class="stat-foot">Total de envíos almacenados</div></article>
            <article class="stat-card"><div class="stat-top"><span class="stat-icon violet">⌕</span><span class="stat-caption">BÚSQUEDA</span></div><div class="stat-value"><?= $q !== '' ? count($envios) : '—' ?></div><div class="stat-foot"><?= $q !== '' ? 'Resultados para tu búsqueda' : 'Encuentra un envío rápidamente' ?></div></article>
            <article class="stat-card accent-stat"><div class="stat-top"><span class="stat-icon green">✓</span><span class="stat-caption">ORGANIZACIÓN</span></div><div class="stat-value">Simple<span class="value-period">.</span></div><div class="stat-foot">Datos claros, en un solo panel</div></article>
        </section>

        <section class="content-grid">
            <article class="card form-card">
                <div class="card-heading"><div class="heading-icon">＋</div><div><span class="section-kicker"><?= $editing ? 'MODIFICAR INFORMACIÓN' : 'AÑADIR INFORMACIÓN' ?></span><h2><?= $editing ? 'Editar envío' : 'Registrar nuevo envío' ?></h2></div></div>
                <?php if ($errors): ?><div class="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
                <form method="post" action="index.php" class="shipping-form">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= h($form['id']) ?>">
                    <label for="destinatario">Destinatario <span>*</span></label>
                    <div class="input-wrap"><span class="field-icon">♙</span><input id="destinatario" name="destinatario" maxlength="150" required placeholder="Nombre completo del destinatario" value="<?= h($form['destinatario']) ?>"></div>
                    <label for="direccion_envio">Dirección de envío <span>*</span></label>
                    <div class="input-wrap"><span class="field-icon">⌖</span><input id="direccion_envio" name="direccion_envio" maxlength="255" required placeholder="Calle, número, barrio, ciudad" value="<?= h($form['direccion_envio']) ?>"></div>
                    <label for="descripcion">Descripción del envío <span>*</span></label>
                    <div class="input-wrap textarea-wrap"><span class="field-icon">▤</span><textarea id="descripcion" name="descripcion" rows="4" required placeholder="Describe el paquete, su contenido o instrucciones especiales..."><?= h($form['descripcion']) ?></textarea></div>
                    <div class="form-actions"><span class="required-note"><span>*</span> Campos obligatorios</span><div class="button-group"><?php if ($editing): ?><a class="btn-light" href="index.php">Cancelar</a><?php endif; ?><button class="btn-primary" type="submit"><?= $editing ? '✓ Guardar cambios' : '＋ Crear envío' ?></button></div></div>
                </form>
            </article>

            <article class="card table-card">
                <div class="table-header"><div><span class="section-kicker">LISTADO GENERAL</span><h2>Envíos registrados <span class="count-pill"><?= count($envios) ?></span></h2><p>Consulta y administra los registros guardados.</p></div></div>
                <form class="search-bar" method="get" action="index.php"><span class="search-icon">⌕</span><input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar destinatario, dirección..."><button type="submit">Buscar</button><?php if ($q !== ''): ?><a href="index.php" class="clear-search" title="Limpiar búsqueda">×</a><?php endif; ?></form>
                <?php if (!$envios): ?>
                    <div class="empty-state"><div class="empty-illustration">↗</div><h3><?= $q !== '' ? 'No encontramos coincidencias' : 'Aún no hay envíos' ?></h3><p><?= $q !== '' ? 'Intenta con otro nombre, dirección o palabra.' : 'Completa el formulario para registrar tu primer envío.' ?></p></div>
                <?php else: ?>
                    <div class="table-wrap"><table><thead><tr><th>DESTINATARIO</th><th>DESCRIPCIÓN</th><th>REGISTRO</th><th>ACCIONES</th></tr></thead><tbody>
                    <?php foreach ($envios as $envio): ?><tr>
                        <td><div class="recipient-cell"><span class="recipient-avatar"><?= h(mb_strtoupper(mb_substr($envio['destinatario'], 0, 1))) ?></span><div><strong><?= h($envio['destinatario']) ?></strong><span class="address-line">⌖ <?= h($envio['direccion_envio']) ?></span></div></div></td>
                        <td><span class="description-text"><?= nl2br(h($envio['descripcion'])) ?></span></td>
                        <td><span class="date-badge"><?= h(date('d/m/Y', strtotime($envio['creado_en']))) ?></span><small class="time-line"><?= h(date('H:i', strtotime($envio['creado_en']))) ?></small></td>
                        <td><div class="row-actions"><a href="index.php?edit=<?= (int)$envio['id'] ?>" class="icon-action edit-action" title="Editar envío" aria-label="Editar envío">✎</a><form method="post" action="index.php" onsubmit="return confirm('¿Deseas eliminar este envío? Esta acción no se puede deshacer.');"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$envio['id'] ?>"><button class="icon-action delete-action" type="submit" title="Eliminar envío" aria-label="Eliminar envío">×</button></form></div></td>
                    </tr><?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
                <div class="table-footer"><span>Mostrando <strong><?= count($envios) ?></strong> de <strong><?= $total ?></strong> envíos</span><span class="footer-secure">▣ Datos almacenados en MySQL</span></div>
            </article>
        </section>
        <footer class="page-footer"><span>© <?= date('Y') ?> EnviaPro · Panel de gestión</span><span>Diseñado para mantener tus envíos organizados <b>✦</b></span></footer>
    </main>
</div>
</body>
</html>
