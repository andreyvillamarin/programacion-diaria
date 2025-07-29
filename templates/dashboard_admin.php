<?php include 'header.php'; ?>

<div class="dashboard-controls">
    <div class="form-group">
        <label for="date-selector">Seleccionar fecha de programación:</label>
        <input type="date" id="date-selector" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
    </div>
    <div class="control-buttons">
        <button id="add-manual-btn" class="btn btn-secondary"><i class="fas fa-plus"></i> Añadir Registro Manual</button>
        <button id="delete-selected-btn" class="btn btn-danger" style="display: none;"><i class="fas fa-trash"></i> Eliminar Seleccionados</button>
        <button id="finalize-day-btn" class="btn btn-success"><i class="fas fa-check-circle"></i> Finalizar y Enviar Reportes</button>
    </div>
</div>

<div id="dashboard-content">
    <p class="loading-placeholder">Cargando datos de programación...</p>
</div>

<div id="manual-add-modal" class="modal-container">
    <div class="modal-content">
        <header class="modal-header">
            <h3>Añadir Registro Manualmente</h3>
            <button id="close-modal-btn" class="close-btn">&times;</button>
        </header>
        <div class="modal-body">
            <form id="manual-add-form" autocomplete="off">
                <fieldset>
                    <legend>Datos Generales</legend>
                    <div class="form-group">
                        <label for="modal-area-select">Área *</label>
                        <select id="modal-area-select" name="area" required></select>
                    </div>
                    <div class="form-group">
                        <label for="modal-requester-email">Correo del Solicitante</label>
                        <input type="email" id="modal-requester-email" name="email_solicitante" value="<?= $_SESSION['user_email'] ?? ''?>">
                    </div>
                    <div class="form-group">
                        <label for="modal-programming-date">Fecha de Programación *</label>
                        <input type="date" id="modal-programming-date" name="fecha_programacion" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                </fieldset>

                <fieldset id="modal-people-fieldset" class="hidden">
                    <legend>Personal a Programar</legend>
                    <div id="modal-people-container"></div>
                </fieldset>

                <fieldset id="modal-other-area-fieldset" class="hidden">
                    <legend>Datos de la Persona</legend>
                    <div id="modal-other-area-services"></div>
                </fieldset>

                <footer class="modal-footer">
                    <button type="submit" id="save-manual-entry-btn" class="btn btn-success">Guardar Registro</button>
                    <button type="button" id="cancel-modal-btn" class="btn btn-secondary">Cancelar</button>
                </footer>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>