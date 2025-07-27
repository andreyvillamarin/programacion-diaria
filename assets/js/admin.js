document.addEventListener('DOMContentLoaded', () => {
    // Solo ejecutar si estamos en el dashboard del admin
    const dateSelector = document.getElementById('date-selector');
    if (!dateSelector) return;

    const dashboardContent = document.getElementById('dashboard-content');
    const finalizeBtn = document.getElementById('finalize-day-btn');

    // Función principal para cargar los datos del dashboard
    const loadDashboardData = (date) => {
        dashboardContent.innerHTML = '<p class="loading-placeholder">Cargando datos de programación...</p>';
        fetch(`${App.apiUrl}?action=get_admin_dashboard&date=${date}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderDashboardTable(data.programacion, dashboardContent);
                } else {
                    dashboardContent.innerHTML = `<p class="error-message">${data.message}</p>`;
                }
            });
    };

    // Renderizar la tabla con los datos recibidos
    const renderDashboardTable = (programacion, container) => {
        if (programacion.length === 0) {
            container.innerHTML = '<p>No hay programaciones para la fecha seleccionada.</p>';
            return;
        }

        let tableHtml = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th>Sede</th>
                        <th>D</th><th>A</th><th>C</th><th>R1</th><th>RC</th>
                        <th>Transporte</th>
                        <th>Solicitante</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>`;

        programacion.forEach(item => {
            tableHtml += `
                <tr data-detail-id="${item.id}">
                    <td>${item.nombre_completo}</td>
                    <td>${item.nombre_sede}</td>
                    <td class="check-cell">${item.desayuno ? '✔️' : ''}</td>
                    <td class="check-cell">${item.almuerzo ? '✔️' : ''}</td>
                    <td class="check-cell">${item.comida ? '✔️' : ''}</td>
                    <td class="check-cell">${item.refrigerio_tipo1 ? '✔️' : ''}</td>
                    <td class="check-cell">${item.refrigerio_capacitacion ? '✔️' : ''}</td>
                    <td>${item.transporte_tipo}</td>
                    <td>${item.email_solicitante}</td>
                    <td>
                        <button class="btn btn-sm btn-danger delete-btn" data-detail-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });

        tableHtml += `</tbody></table>`;
        container.innerHTML = tableHtml;
    };

    // Event Listeners
    dateSelector.addEventListener('change', () => loadDashboardData(dateSelector.value));

    // Listener para botones de borrado (usando delegación de eventos)
    dashboardContent.addEventListener('click', function(e) {
        if (e.target.closest('.delete-btn')) {
            const btn = e.target.closest('.delete-btn');
            const detailId = btn.dataset.detailId;
            if (confirm(`¿Está seguro de que desea eliminar este registro (ID: ${detailId})?`)) {
                fetch(`${App.apiUrl}?action=delete_row&id=${detailId}`, { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Eliminar la fila de la tabla sin recargar toda la página
                            document.querySelector(`tr[data-detail-id='${detailId}']`).remove();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }
    });
    
    // Listener para el botón de finalizar y enviar
    finalizeBtn.addEventListener('click', () => {
        const date = dateSelector.value;
        if (confirm(`¿Está seguro de finalizar la programación para el ${date}?\n\nEsta acción consolidará los datos y enviará los reportes por correo a los Chefs y Transportadores. No se podrá revertir.`)) {
            finalizeBtn.disabled = true;
            finalizeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';
            
            const formData = new FormData();
            formData.append('action', 'finalize_and_send');
            formData.append('date', date);

            fetch(App.apiUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if(data.success) {
                       loadDashboardData(date); // Recargar datos para ver el estado "Finalizada"
                    }
                })
                .finally(() => {
                    finalizeBtn.disabled = false;
                    finalizeBtn.innerHTML = '<i class="fas fa-check-circle"></i> Finalizar y Enviar Reportes';
                });
        }
    });


    // Carga inicial de datos
    loadDashboardData(dateSelector.value);
});
// --- LÓGICA PARA LA PÁGINA DE ANALÍTICAS ---
const generateReportBtn = document.getElementById('generate-report-btn');
if (generateReportBtn) {
    let foodChart, transportChart; // Variables para guardar las instancias de los gráficos

    const generateCharts = () => {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;

        fetch(`${App.apiUrl}?action=get_analytics_data&start=${startDate}&end=${endDate}`)
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
        if (foodChart) foodChart.destroy(); // Destruir gráfico anterior si existe
        foodChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Desayuno', 'Almuerzo', 'Comida', 'Refrig. T1', 'Refrig. Cap.'],
                datasets: [{
                    label: 'Total Consumido',
                    data: data,
                    backgroundColor: ['#3e95cd', '#8e5ea2', '#3cba9f', '#e8c3b9', '#c45850']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    };
    
    const renderTransportChart = (data) => {
        const ctx = document.getElementById('transport-chart').getContext('2d');
        if (transportChart) transportChart.destroy(); // Destruir gráfico anterior
        transportChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: Object.keys(data),
                datasets: [{
                    data: Object.values(data),
                }]
            },
            options: { responsive: true }
        });
    };

    generateReportBtn.addEventListener('click', generateCharts);
    generateCharts(); // Generar reporte inicial al cargar la página
}