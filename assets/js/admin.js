// --- FUNCIONES AUXILIARES GLOBALES ---
function renderPeopleCards(people, sedes, transportOptions, container) {
    let html = '';
    if (!people || people.length === 0) {
        container.innerHTML = '<p>No hay personas activas en el área seleccionada.</p>';
        return;
    }
    people.forEach(person => {
        const pId = person.id;
        html += `
            <div class="person-card">
                <h4 class="person-name">${person.nombre_completo}</h4>
                <div class="card-content">
                    <div class="service-section">
                        <h5><i class="fas fa-utensils"></i> Alimentación</h5>
                        <label><input type="checkbox" name="people[${pId}][desayuno]" value="1"> Desayuno</label>
                        <label><input type="checkbox" name="people[${pId}][almuerzo]" value="1"> Almuerzo</label>
                        <label><input type="checkbox" name="people[${pId}][comida]" value="1"> Comida</label>
                        <label><input type="checkbox" name="people[${pId}][refrigerio_tipo1]" value="1"> Refrigerio Tipo 1</label>
                        <label><input type="checkbox" name="people[${pId}][refrigerio_capacitacion]" value="1"> Refrigerio Capacitación</label>
                    </div>
                    <div class="service-section">
                        <h5><i class="fas fa-bus"></i> Transporte</h5>
                        <label for="transport-${pId}">Tipo:</label>
                        <select id="transport-${pId}" name="people[${pId}][transporte_tipo]">
                            <option value="">-- Seleccione --</option>
                            ${transportOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>
                    </div>
                    <div class="service-section">
                         <h5><i class="fas fa-map-marker-alt"></i> Sede de Destino</h5>
                         ${sedes.map(sede => `
                            <label class="radio-label">
                                <input type="radio" name="people[${pId}][id_sede]" value="${sede.id}"> ${sede.nombre_sede}
                            </label>
                         `).join('')}
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function renderServicesOnly(sedes, transportOptions, container, namePrefix = 'other') {
    container.innerHTML = `
        <div class="form-group">
            <label>Nombre Completo *</label>
            <input type="text" name="${namePrefix}[nombre_manual]" required>
        </div>
        <div class="form-group">
            <label>Área | WBE *</label>
            <input type="text" name="${namePrefix}[area_wbe]" required>
        </div>
        <div class="form-group">
            <label>Actividad a Realizar *</label>
            <textarea name="${namePrefix}[actividad]" required></textarea>
        </div>
        <div class="person-card">
            <div class="card-content">
                <div class="service-section">
                    <h5><i class="fas fa-utensils"></i> Alimentación</h5>
                    <label><input type="checkbox" name="${namePrefix}[desayuno]" value="1"> Desayuno</label>
                    <label><input type="checkbox" name="${namePrefix}[almuerzo]" value="1"> Almuerzo</label>
                    <label><input type="checkbox" name="${namePrefix}[comida]" value="1"> Comida</label>
                    <label><input type="checkbox" name="${namePrefix}[refrigerio_tipo1]" value="1"> Refrigerio Tipo 1</label>
                    <label><input type="checkbox" name="${namePrefix}[refrigerio_capacitacion]" value="1"> Refrigerio Capacitación</label>
                </div>
                <div class="service-section">
                    <h5><i class="fas fa-bus"></i> Transporte</h5>
                    <label>Tipo:</label>
                    <select name="${namePrefix}[transporte_tipo]">
                        <option value="">-- Seleccione --</option>
                        ${transportOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                    </select>
                </div>
                <div class="service-section">
                     <h5><i class="fas fa-map-marker-alt"></i> Sede de Destino</h5>
                     ${sedes.map(sede => `
                        <label class="radio-label">
                            <input type="radio" name="${namePrefix}[id_sede]" value="${sede.id}"> ${sede.nombre_sede}
                        </label>
                     `).join('')}
                </div>
            </div>
        </div>
    `;
}


document.addEventListener('DOMContentLoaded', () => {

    // --- LÓGICA PARA EL DASHBOARD PRINCIPAL DEL ADMIN ---
    const dateSelector = document.getElementById('date-selector');
    if (dateSelector) {
        const dashboardContent = document.getElementById('dashboard-content');
        const finalizeBtn = document.getElementById('finalize-day-btn');

        const loadDashboardData = (date) => {
            dashboardContent.innerHTML = '<p class="loading-placeholder">Cargando datos de programación...</p>';
            fetch(`../api/handler.php?action=get_admin_dashboard&date=${date}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderDashboardTable(data.programacion, dashboardContent);
                    } else {
                        dashboardContent.innerHTML = `<p class="error-message">${data.message || 'No se pudo cargar la información.'}</p>`;
                    }
                }).catch(() => {
                    dashboardContent.innerHTML = '<p class="error-message">Error de conexión al cargar los datos.</p>';
                });
        };

        const renderDashboardTable = (programacion, container) => {
            const deleteSelectedBtn = document.getElementById('delete-selected-btn');
            if (programacion.length === 0) {
                container.innerHTML = '<p>No hay programaciones pendientes para la fecha seleccionada.</p>';
                deleteSelectedBtn.style.display = 'none';
                return;
            }
            let tableHtml = `<table class="data-table"><thead><tr><th><input type="checkbox" id="select-all-checkbox"></th><th>Persona</th><th>Área | WBE</th><th>Actividad</th><th>Sede</th><th>D</th><th>A</th><th>C</th><th>R1</th><th>RC</th><th>Transporte</th><th>Solicitante</th><th>Acción</th></tr></thead><tbody>`;
            programacion.forEach(item => {
                const displayName = item.nombre_completo || item.nombre_manual || '(No especificado)';
                const areaWbe = item.id_persona ? item.nombre_area : item.area_wbe;
                tableHtml += `
                    <tr data-detail-id="${item.id}">
                        <td><input type="checkbox" class="row-checkbox" value="${item.id}"></td>
                        <td>${displayName}</td>
                        <td>${areaWbe}</td>
                        <td>${item.actividad || ''}</td>
                        <td>${item.nombre_sede}</td>
                        <td class="check-cell">${parseInt(item.desayuno) ? '✔️' : ''}</td>
                        <td class="check-cell">${parseInt(item.almuerzo) ? '✔️' : ''}</td>
                        <td class="check-cell">${parseInt(item.comida) ? '✔️' : ''}</td>
                        <td class="check-cell">${parseInt(item.refrigerio_tipo1) ? '✔️' : ''}</td>
                        <td class="check-cell">${parseInt(item.refrigerio_capacitacion) ? '✔️' : ''}</td>
                        <td>${item.transporte_tipo}</td>
                        <td>${item.email_solicitante}</td>
                        <td>
                            <a href="registro-editar.php?id=${item.id}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Editar</a>
                            <a href="eliminar.php?tipo=detalle_programacion&id=${item.id}" class="btn btn-sm btn-danger delete-btn"><i class="fas fa-trash"></i> Eliminar</a>
                        </td>
                    </tr>`;
            });
            tableHtml += `</tbody></table>`;
            container.innerHTML = tableHtml;

            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const rowCheckboxes = document.querySelectorAll('.row-checkbox');

            const toggleDeleteButton = () => {
                const anyChecked = Array.from(rowCheckboxes).some(checkbox => checkbox.checked);
                deleteSelectedBtn.style.display = anyChecked ? 'inline-block' : 'none';
            };

            selectAllCheckbox.addEventListener('change', (e) => {
                rowCheckboxes.forEach(checkbox => checkbox.checked = e.target.checked);
                toggleDeleteButton();
            });

            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    if (!checkbox.checked) {
                        selectAllCheckbox.checked = false;
                    }
                    toggleDeleteButton();
                });
            });

            deleteSelectedBtn.addEventListener('click', () => {
                const selectedIds = Array.from(rowCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
                if (selectedIds.length > 0 && confirm(`¿Está seguro de eliminar ${selectedIds.length} registros?`)) {
                    const formData = new FormData();
                    formData.append('action', 'bulk_delete');
                    formData.append('ids', JSON.stringify(selectedIds));

                    fetch('../api/handler.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) {
                                loadDashboardData(dateSelector.value);
                            }
                        });
                }
            });
        };

        dateSelector.addEventListener('change', () => loadDashboardData(dateSelector.value));

        finalizeBtn.addEventListener('click', () => {
            const date = dateSelector.value;
            if (confirm(`¿Está seguro de finalizar la programación para el ${date}?\n\nEsta acción enviará los reportes y no se podrá revertir.`)) {
                finalizeBtn.disabled = true;
                finalizeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';
                
                const formData = new FormData();
                formData.append('action', 'finalize_and_send');
                formData.append('date', date);

                fetch('../api/handler.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if(data.success) {
                           loadDashboardData(date);
                        }
                    })
                    .finally(() => {
                        finalizeBtn.disabled = false;
                        finalizeBtn.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar y Enviar Reportes';
                    });
            }
        });

        loadDashboardData(dateSelector.value);
    }

    // --- LÓGICA PARA MODAL DE AÑADIR REGISTRO MANUAL ---
    const addManualBtn = document.getElementById('add-manual-btn');
    if (addManualBtn) {
        const modal = document.getElementById('manual-add-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const modalAreaSelect = document.getElementById('modal-area-select');
        const manualAddForm = document.getElementById('manual-add-form');
        const modalPeopleFieldset = document.getElementById('modal-people-fieldset');
        const modalOtherAreaFieldset = document.getElementById('modal-other-area-fieldset');
        const modalPeopleContainer = document.getElementById('modal-people-container');
        const modalOtherAreaServicesContainer = document.getElementById('modal-other-area-services');

        const openModal = () => {
            if (modalAreaSelect.options.length <= 1) {
                fetch(`../api/data.php?action=get_initial_data`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            modalAreaSelect.innerHTML = '<option value="">-- Seleccione un área --</option>';
                            data.areas.forEach(area => {
                                const isOther = area.nombre_area.toLowerCase() === 'otras áreas' ? 'true' : 'false';
                                modalAreaSelect.innerHTML += `<option value="${area.id}" data-other-area="${isOther}">${area.nombre_area}</option>`;
                            });
                        }
                    });
            }
            modal.style.display = 'block';
        };

        const closeModal = () => {
            modal.style.display = 'none';
            manualAddForm.reset();
            modalPeopleFieldset.classList.add('hidden');
            modalOtherAreaFieldset.classList.add('hidden');
        };

        addManualBtn.addEventListener('click', openModal);
        closeModalBtn.addEventListener('click', closeModal);
        cancelModalBtn.addEventListener('click', closeModal);
        window.addEventListener('click', (event) => {
            if (event.target == modal) closeModal();
        });

        modalAreaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const isOtherArea = selectedOption.getAttribute('data-other-area') === 'true';
            const areaId = this.value;

            modalPeopleFieldset.classList.add('hidden');
            modalOtherAreaFieldset.classList.add('hidden');

            if (!areaId) return;

            if (isOtherArea) {
                modalOtherAreaFieldset.classList.remove('hidden');
                fetch(`../api/data.php?action=get_services_only`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            renderServicesOnly(data.sedes, data.transport_options, modalOtherAreaServicesContainer, 'other');
                        }
                    });
            } else {
                modalPeopleFieldset.classList.remove('hidden');
                modalPeopleContainer.innerHTML = '<p class="loading">Cargando...</p>';
                fetch(`../api/data.php?action=get_people_by_area&area_id=${areaId}`)
                    .then(res => res.json())
                    .then(data => {
                        renderPeopleCards(data.people, data.sedes, data.transport_options, modalPeopleContainer);
                    });
            }
        });

        manualAddForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const saveBtn = document.getElementById('save-manual-entry-btn');

            let isValid = true;
            manualAddForm.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
                if (input.offsetWidth > 0 || input.offsetHeight > 0) {
                    if (!input.checkValidity()) {
                        isValid = false;
                        input.reportValidity();
                    }
                }
            });

            if (!isValid) return;

            saveBtn.disabled = true;
            saveBtn.textContent = 'Guardando...';

            const formData = new FormData(manualAddForm);
            formData.append('action', 'add_manual_entry');

            fetch('../api/handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        closeModal();
                        dateSelector.dispatchEvent(new Event('change'));
                    }
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Guardar Registro';
                });
        });
    }

    // --- LÓGICA PARA CONFIRMACIÓN DE BORRADO ---
    document.addEventListener('click', function(event) {
        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton) {
            event.preventDefault();
            const confirmDelete = confirm('¿Estás seguro de que quieres eliminar este elemento? Esta acción no se puede deshacer.');
            if (confirmDelete) {
                window.location.href = deleteButton.href;
            }
        }
    });

    // --- LÓGICA PARA PÁGINA DE ANALÍTICAS ---
    const generateReportBtn = document.getElementById('generate-report-btn');
    if (generateReportBtn) {
        let foodChart, transportChart;

        const generateCharts = () => {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            fetch(`../api/handler.php?action=get_analytics_data&start=${startDate}&end=${endDate}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderFoodChart(data.food_data);
                        renderTransportChart(data.transport_data);
                    }
                });
        };

        const renderFoodChart = (data) => {
            const ctx = document.getElementById('food-chart').getContext('2d');
            if (foodChart) foodChart.destroy();
            foodChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Desayuno', 'Almuerzo', 'Comida', 'Refrig. T1', 'Refrig. Cap.'],
                    datasets: [{ label: 'Total Consumido', data: data, backgroundColor: ['#3e95cd', '#8e5ea2', '#3cba9f', '#e8c3b9', '#c45850'] }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        };
        
        const renderTransportChart = (data) => {
            const ctx = document.getElementById('transport-chart').getContext('2d');
            if (transportChart) transportChart.destroy();
            transportChart = new Chart(ctx, {
                type: 'pie',
                data: { labels: Object.keys(data), datasets: [{ data: Object.values(data) }] },
                options: { responsive: true }
            });
        };

        generateReportBtn.addEventListener('click', generateCharts);
        generateCharts();
    }
});