<?= $this->extend('layouts/admin_layout') ?>

<?= $this->section('content') ?>

<div class="header-actions">
    <h1 class="page-title">Gestión de Trámites TUPA</h1>
    <a href="<?= base_url('admin/tupa/create') ?>" class="btn btn-primary">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Trámite
    </a>
</div>

<div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
    <form action="<?= base_url('admin/tupa') ?>" method="GET" style="display: flex; gap: 10px;">
        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o código..." value="<?= esc($search) ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if($search): ?>
            <a href="<?= base_url('admin/tupa') ?>" class="btn" style="background: #e2e8f0;">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Procedimiento</th>
                    <th>Descripción</th>
                    <th>Área</th>
                    <th>Costo</th>
                    <th>Plazo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tramites)): ?>
                    <?php foreach ($tramites as $t): ?>
                        <tr>
                            <td><span class="badge badge-info"><?= esc($t['codigo'] ?: 'N/A') ?></span></td>
                            <td style="font-weight: 500; max-width: 300px;">
                                <?= esc($t['nombre_procedimiento']) ?>
                            </td>
                            <td style="max-width: 350px; font-size: 0.9em; color: #555;">
                                <?php 
                                    $desc = $t['descripcion'] ?? '';
                                    if (mb_strlen($desc) > 100) {
                                        echo esc(mb_substr($desc, 0, 100)) . '...';
                                    } else {
                                        echo esc($desc);
                                    }
                                ?>
                            </td>
                            <td><?= esc($t['area']) ?></td>
                            <td><?= esc($t['derecho_pago'] ?: 'Sin costo') ?></td>
                            <td><?= esc($t['plazo_atencion'] ?: 'Inmediato') ?></td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="<?= base_url('admin/tupa/edit/' . $t['id']) ?>" class="btn btn-sm" style="background: #edf2f7; color: #2d3748;">
                                        Editar
                                    </a>
                                    <form action="<?= base_url('admin/tupa/delete/' . $t['id']) ?>" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este trámite?')">
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No se encontraron trámites.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?= $pager->links() ?>
</div>

<?= $this->endSection() ?>
