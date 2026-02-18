<?= $this->extend('layouts/admin_layout') ?>

<?= $this->section('content') ?>

<div class="header-actions">
    <h1 class="page-title">Nuevo Trámite TUPA</h1>
    <a href="<?= base_url('admin/tupa') ?>" class="btn" style="background: #e2e8f0;">Volver</a>
</div>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

<div class="card" style="padding: 2rem;">
    <form action="<?= base_url('admin/tupa/store') ?>" method="POST">
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
            <div class="form-group">
                <label for="codigo">Código</label>
                <input type="text" name="codigo" id="codigo" class="form-control" value="<?= old('codigo') ?>" placeholder="P.ej: 001">
            </div>
            <div class="form-group">
                <label for="nombre_procedimiento">Nombre del Procedimiento *</label>
                <input type="text" name="nombre_procedimiento" id="nombre_procedimiento" class="form-control" value="<?= old('nombre_procedimiento') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción (Subtítulo)</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="2" placeholder="Ej: PARA EDIFICACIONES..."><?= old('descripcion') ?></textarea>
        </div>

        <div class="form-group">
            <label for="requisitos">Requisitos (Uno por línea)</label>
            <textarea name="requisitos" id="requisitos" class="form-control" placeholder="1. Copia de DNI&#10;2. Solicitud Simple..."><?= old('requisitos') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="derecho_pago">Derecho de Pago (Costo)</label>
                <input type="text" name="derecho_pago" id="derecho_pago" class="form-control" value="<?= old('derecho_pago') ?>" placeholder="P.ej: 15.50 o Gratuito">
            </div>
            <div class="form-group">
                <label for="plazo_atencion">Plazo de Atención</label>
                <input type="text" name="plazo_atencion" id="plazo_atencion" class="form-control" value="<?= old('plazo_atencion') ?>" placeholder="P.ej: 5 días hábiles">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="area">Área / Unidad Orgánica *</label>
                <input type="text" name="area" id="area" class="form-control" value="<?= old('area') ?>" required>
            </div>
            <div class="form-group">
                <label for="donde_presentar">Dónde presentar documentación</label>
                <input type="text" name="donde_presentar" id="donde_presentar" class="form-control" value="<?= old('donde_presentar') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="base_legal">Base Legal</label>
            <textarea name="base_legal" id="base_legal" class="form-control" style="min-height: 80px;"><?= old('base_legal') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="categoria">Categoría</label>
                <input type="text" name="categoria" id="categoria" class="form-control" value="<?= old('categoria') ?>" placeholder="P.ej: REGISTRO CIVIL">
            </div>
            <div class="form-group">
                <label for="keywords">Palabras Clave (Separadas por coma)</label>
                <input type="text" name="keywords" id="keywords" class="form-control" value="<?= old('keywords') ?>" placeholder="dni, certificado, matrimonio">
            </div>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 10px;">
            <a href="<?= base_url('admin/tupa') ?>" class="btn" style="background: #e2e8f0;">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar Trámite</button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
