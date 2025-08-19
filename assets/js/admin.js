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
                <hr class="form-separator">
                <div class="card-content">
                    <div class="service-section">
                        <h5><i class="fas fa-utensils"></i> Alimentación</h5>
                        <label><input type="checkbox" name="people[${pId}][desayuno]" value="1"> Desayuno</label>
                        <label><input type="checkbox" name="people[${pId}][almuerzo]" value="1"> Almuerzo</label>
                        <label><input type="checkbox" name="people[${pId}][comida]" value="1"> Comida</label>
                        <label><input type="checkbox" name="people[${pId}][refrigerio_tipo1]" value="1"> Refrigerio Tipo 1</label>
                        <label><input type="checkbox" name="people[${pId}][refrigerio_capacitacion]" value="1"> Refrigerio Capacitación</label>
                    </div>
                    <hr class="form-separator">
                    <div class="service-section">
                        <h5><i class="fas fa-bus"></i> Transporte</h5>
                        <label for="transport-${pId}">Tipo:</label>
                        <select id="transport-${pId}" name="people[${pId}][transporte_tipo]">
                            <option value="">-- Seleccione --</option>
                            ${transportOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>
                    </div>
                    <hr class="form-separator">
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
        <hr class="form-separator">
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
                <hr class="form-separator">
                <div class="service-section">
                    <h5><i class="fas fa-bus"></i> Transporte</h5>
                    <label>Tipo:</label>
                    <select name="${namePrefix}[transporte_tipo]">
                        <option value="">-- Seleccione --</option>
                        ${transportOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                    </select>
                </div>
                <hr class="form-separator">
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
            let tableHtml = `<table class="data-table"><thead><tr><th><input type="checkbox" id="select-all-checkbox"></th><th>Nombre</th><th>Área | WBE</th><th>Sede</th><th>Transporte</th><th>Zona</th><th>D</th><th>A</th><th>C</th><th>R1</th><th>RC</th><th>Actividad</th><th>Solicitante</th><th>Acción</th></tr></thead><tbody>`;
            programacion.forEach(item => {
                const displayName = item.nombre_completo || item.nombre_manual || '(No especificado)';
                const areaWbe = item.id_persona ? item.nombre_area : item.area_wbe;
                tableHtml += `
                    <tr data-detail-id="${item.id}">
                        <td data-label="Seleccionar"><input type="checkbox" class="row-checkbox" value="${item.id}"></td>
                        <td data-label="Nombre">${displayName}</td>
                        <td data-label="Área | WBE">${areaWbe}</td>
                        <td data-label="Sede">${item.nombre_sede}</td>
                        <td data-label="Transporte">${item.transporte_tipo}</td>
                        <td data-label="Zona">${item.zona || ''}</td>
                        <td data-label="D" class="check-cell">${parseInt(item.desayuno) ? '✔️' : ''}</td>
                        <td data-label="A" class="check-cell">${parseInt(item.almuerzo) ? '✔️' : ''}</td>
                        <td data-label="C" class="check-cell">${parseInt(item.comida) ? '✔️' : ''}</td>
                        <td data-label="R1" class="check-cell">${parseInt(item.refrigerio_tipo1) ? '✔️' : ''}</td>
                        <td data-label="RC" class="check-cell">${parseInt(item.refrigerio_capacitacion) ? '✔️' : ''}</td>
                        <td data-label="Actividad">${item.actividad || ''}</td>
                        <td data-label="Solicitante">${item.email_solicitante}</td>
                        <td data-label="Acción">
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
            if (confirm(`¿Está seguro de finalizar la programación para el ${date}?\n\nEsta acción enviará los reportes a todos los involucrados. Puede realizar esta acción cuantas veces sea necesario para ajustar la programación.`)) {
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

        const downloadPdfBtn = document.getElementById('download-pdf-btn');
        if (downloadPdfBtn) {
            downloadPdfBtn.addEventListener('click', () => {
                const selectedDate = dateSelector.value;
                if (selectedDate) {
                    window.open(`../api/handler.php?action=download_pdf&date=${selectedDate}`, '_blank');
                } else {
                    alert('Por favor, seleccione una fecha.');
                }
            });
        }

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
    
    // --- LÓGICA PARA BUSCADOR EN DASHBOARD ADMIN ---
    const dashboardSearchInput = document.getElementById('dashboard-search');
    if (dashboardSearchInput) {
        dashboardSearchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#dashboard-content .data-table tbody tr');
            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // --- LÓGICA PARA PÁGINA DE REPORTES (reportes.php) ---
    const transportPdfBtn = document.getElementById('download-transport-pdf');
    if (transportPdfBtn) {
        transportPdfBtn.addEventListener('click', () => {
            const date = document.getElementById('transport-date').value;
            if (date) {
                window.open(`../api/handler.php?action=download_transporter_pdf&date=${date}`, '_blank');
            } else {
                alert('Por favor, seleccione una fecha para el reporte de transporte.');
            }
        });
    }

    const casinoBetaniaPdfBtn = document.getElementById('download-casino-betania-pdf');
    if (casinoBetaniaPdfBtn) {
        casinoBetaniaPdfBtn.addEventListener('click', () => {
            const date = document.getElementById('casino-betania-date').value;
            if (date) {
                // Asumiendo que el ID de la sede Betania es 1
                window.open(`../api/handler.php?action=download_casino_pdf&date=${date}&sede_id=1`, '_blank');
            } else {
                alert('Por favor, seleccione una fecha para el reporte de Betania.');
            }
        });
    }

    const casinoQuimboPdfBtn = document.getElementById('download-casino-quimbo-pdf');
    if (casinoQuimboPdfBtn) {
        casinoQuimboPdfBtn.addEventListener('click', () => {
            const date = document.getElementById('casino-quimbo-date').value;
            if (date) {
                // Asumiendo que el ID de la sede Quimbo es 2
                window.open(`../api/handler.php?action=download_casino_pdf&date=${date}&sede_id=2`, '_blank');
            } else {
                alert('Por favor, seleccione una fecha para el reporte de Quimbo.');
            }
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
        let foodChart, transportChart, sedesChart, areasChart, personasChart;

        const generateCharts = () => {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            fetch(`../api/handler.php?action=get_analytics_data&start=${startDate}&end=${endDate}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderFoodChart(data.food_data);
                        renderTransportChart(data.transport_data);
                        renderSedesChart(data.sedes_data);
                        renderAreasChart(data.areas_data);
                        renderPersonasChart(data.personas_data);
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
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        };
        
        const renderTransportChart = (data) => {
            const ctx = document.getElementById('transport-chart').getContext('2d');
            if (transportChart) transportChart.destroy();
            const labels = Object.keys(data).map(key => `${key} (${data[key]})`);
            transportChart = new Chart(ctx, {
                type: 'pie',
                data: { labels: labels, datasets: [{ data: Object.values(data) }] },
                options: { responsive: true, maintainAspectRatio: false }
            });
        };

        const renderSedesChart = (data) => {
            const ctx = document.getElementById('sedes-chart').getContext('2d');
            if (sedesChart) sedesChart.destroy();
            const labels = Object.keys(data).map(key => `${key} (${data[key]})`);
            sedesChart = new Chart(ctx, {
                type: 'doughnut',
                data: { labels: labels, datasets: [{ data: Object.values(data) }] },
                options: { responsive: true, maintainAspectRatio: false }
            });
        };

        const renderAreasChart = (data) => {
            const ctx = document.getElementById('areas-chart').getContext('2d');
            if (areasChart) areasChart.destroy();
            areasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{ label: 'Personas por Área', data: Object.values(data), backgroundColor: '#36a2eb' }]
                },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } }
            });
        };

        const renderPersonasChart = (data) => {
            const ctx = document.getElementById('personas-chart').getContext('2d');
            if (personasChart) personasChart.destroy();
            personasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{ label: 'Programaciones por Persona', data: Object.values(data), backgroundColor: '#ff9f40' }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    indexAxis: 'x', 
                    plugins: { 
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Top 10 Personas con más programaciones'
                        }
                    } 
                }
            });
        };

        const downloadAnalyticsPdfBtn = document.getElementById('download-analytics-pdf-btn');

        if (downloadAnalyticsPdfBtn) {
            downloadAnalyticsPdfBtn.addEventListener('click', () => {
                const charts = [
                    { title: 'Consumo de Alimentos', image: foodChart ? foodChart.toBase64Image() : '' },
                    { title: 'Uso de Transporte', image: transportChart ? transportChart.toBase64Image() : '' },
                    { title: 'Total de Sedes', image: sedesChart ? sedesChart.toBase64Image() : '' },
                    { title: 'Total de Áreas', image: areasChart ? areasChart.toBase64Image() : '' },
                    { title: 'Total de Personas', image: personasChart ? personasChart.toBase64Image() : '' }
                ].filter(c => c.image);

                const startDate = document.getElementById('start-date').value;
                const endDate = document.getElementById('end-date').value;
                const date_range = `Desde ${startDate} hasta ${endDate}`;

                const formData = new FormData();
                formData.append('action', 'download_analytics_pdf');
                formData.append('charts', JSON.stringify(charts));
                formData.append('date_range', date_range);

                fetch('../api/handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'reporte_analiticas.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                });
            });
        }

        generateReportBtn.addEventListener('click', generateCharts);
        generateCharts();
    }

    // --- LÓGICA PARA BUSCADOR EN TABLAS (SECCIÓN PERSONAS) ---
    const personSearchInput = document.getElementById('person-search');
    if (personSearchInput) {
        personSearchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#personas-table tbody tr');
            tableRows.forEach(row => {
                // Se busca en el contenido de texto de toda la fila
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // --- LÓGICA PARA DASHBOARD DE CASINO ---
    const casinoDateSelector = document.getElementById('casino-date-selector');
    if (casinoDateSelector) {
        const casinoDashboardContent = document.getElementById('casino-dashboard-content');
        const downloadCasinoPdfBtn = document.getElementById('download-casino-pdf-btn');
        const casinoTitle = document.querySelector('.card-header h3');

        const loadCasinoData = (date) => {
            casinoDashboardContent.innerHTML = '<p class="loading-placeholder">Cargando datos de programación...</p>';
            fetch(`../api/handler.php?action=get_casino_dashboard&date=${date}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderCasinoDashboard(data.reporte, data.personas, casinoDashboardContent);
                    } else {
                        casinoDashboardContent.innerHTML = `<p class="error-message">${data.message || 'No se pudo cargar la información.'}</p>`;
                    }
                }).catch(() => {
                    casinoDashboardContent.innerHTML = '<p class="error-message">Error de conexión al cargar los datos.</p>';
                });
        };

        const renderCasinoDashboard = (reporte, personas, container) => {
            if (!reporte) {
                container.innerHTML = '<p>No hay datos de casino para la fecha seleccionada.</p>';
                return;
            }
            casinoTitle.textContent = `Programación de Casino - Sede ${reporte.nombre_sede || 'N/A'}`;
            let html = `
                <div class="stat-cards-container">
                    <div class="stat-card-item">
                        <div class="stat-card-icon"><i class="fas fa-bread-slice"></i></div>
                        <div class="stat-card-number">${reporte.total_desayunos || 0}</div>
                        <div class="stat-card-label">Desayunos</div>
                    </div>
                    <div class="stat-card-item">
                        <div class="stat-card-icon"><i class="fas fa-drumstick-bite"></i></div>
                        <div class="stat-card-number">${reporte.total_almuerzos || 0}</div>
                        <div class="stat-card-label">Almuerzos</div>
                    </div>
                    <div class="stat-card-item">
                        <div class="stat-card-icon"><i class="fas fa-hotdog"></i></div>
                        <div class="stat-card-number">${reporte.total_comidas || 0}</div>
                        <div class="stat-card-label">Comidas</div>
                    </div>
                    <div class="stat-card-item">
                        <div class="stat-card-icon"><i class="fas fa-cookie"></i></div>
                        <div class="stat-card-number">${reporte.total_ref1 || 0}</div>
                        <div class="stat-card-label">Refrigerio T1</div>
                    </div>
                    <div class="stat-card-item">
                        <div class="stat-card-icon"><i class="fas fa-apple-alt"></i></div>
                        <div class="stat-card-number">${reporte.total_ref_cap || 0}</div>
                        <div class="stat-card-label">Refrigerio Cap.</div>
                    </div>
                </div>
                <h4 class="mt-4">Personal Programado</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Área</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (personas && personas.length > 0) {
                personas.forEach(p => {
                    html += `
                        <tr>
                            <td>${p.nombre_persona}</td>
                            <td>${p.area || 'N/A'}</td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="2">No hay personal programado para esta fecha.</td></tr>';
            }
            html += '</tbody></table>';
            container.innerHTML = html;
        };

        casinoDateSelector.addEventListener('change', () => loadCasinoData(casinoDateSelector.value));
        downloadCasinoPdfBtn.addEventListener('click', () => {
            const selectedDate = casinoDateSelector.value;
            if (selectedDate) {
                window.open(`../api/handler.php?action=download_casino_pdf&date=${selectedDate}`, '_blank');
            } else {
                alert('Por favor, seleccione una fecha.');
            }
        });

        loadCasinoData(casinoDateSelector.value);
    }

    // --- LÓGICA PARA DASHBOARD DE TRANSPORTE ---
    const transporterDateSelector = document.getElementById('transporter-date-selector');
    if (transporterDateSelector) {
        const transporterDashboardContent = document.getElementById('transporter-dashboard-content');
        const downloadTransporterPdfBtn = document.getElementById('download-transporter-pdf-btn');

        const loadTransporterData = (date) => {
            transporterDashboardContent.innerHTML = '<p class="loading-placeholder">Cargando datos de programación...</p>';
            fetch(`../api/handler.php?action=get_transporter_dashboard&date=${date}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderTransporterDashboard(data.reporte, transporterDashboardContent);
                    } else {
                        transporterDashboardContent.innerHTML = `<p class="error-message">${data.message || 'No se pudo cargar la información.'}</p>`;
                    }
                }).catch(() => {
                    transporterDashboardContent.innerHTML = '<p class="error-message">Error de conexión al cargar los datos.</p>';
                });
        };

        const renderTransporterDashboard = (reporte, container) => {
            if (!reporte || reporte.length === 0) {
                container.innerHTML = '<p>No hay datos de transporte para la fecha seleccionada.</p>';
                return;
            }
            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo de Transporte</th>
                            <th>Sede de Destino</th>
                            <th>Zona</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            reporte.forEach(r => {
                html += `
                    <tr>
                        <td>${r.nombre_completo}</td>
                        <td>${r.transporte_tipo}</td>
                        <td>${r.nombre_sede}</td>
                        <td>${r.zona || ''}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        };

        transporterDateSelector.addEventListener('change', () => loadTransporterData(transporterDateSelector.value));
        downloadTransporterPdfBtn.addEventListener('click', () => {
            const selectedDate = transporterDateSelector.value;
            if (selectedDate) {
                window.open(`../api/handler.php?action=download_transporter_pdf&date=${selectedDate}`, '_blank');
            } else {
                alert('Por favor, seleccione una fecha.');
            }
        });

        loadTransporterData(transporterDateSelector.value);
    }
});