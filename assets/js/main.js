document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('programming-form');
    if (!form) return; // Salir si no estamos en la página del formulario

    const areaSelect = document.getElementById('area-select');
    const peopleContainer = document.getElementById('people-container');
    const peopleFieldset = document.getElementById('people-fieldset');
    const dateInput = document.getElementById('programming-date');
    const submitBtn = document.getElementById('submit-btn');
    const formMessages = document.getElementById('form-messages');

    // 1. Asignar fecha de mañana y cargar áreas iniciales
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.value = tomorrow.toISOString().split('T')[0];

    fetch(`${App.dataUrl}?action=get_initial_data`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Llenar dropdown de áreas
                areaSelect.innerHTML = '<option value="">-- Seleccione un área --</option>';
                data.areas.forEach(area => {
                    areaSelect.innerHTML += `<option value="${area.id}">${area.nombre_area}</option>`;
                });
            }
        });

    // 2. Cargar personas cuando se selecciona un área
    areaSelect.addEventListener('change', function() {
        const areaId = this.value;
        peopleContainer.innerHTML = '';
        if (!areaId) {
            peopleFieldset.classList.add('hidden');
            return;
        }
        peopleFieldset.classList.remove('hidden');
        peopleContainer.innerHTML = '<p class="loading">Cargando personal...</p>';

        fetch(`${App.dataUrl}?action=get_people_by_area&area_id=${areaId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.people.length > 0) {
                    renderPeopleCards(data.people, data.sedes, data.transport_options, peopleContainer);
                } else {
                    peopleContainer.innerHTML = '<p>No hay personas activas en el área seleccionada.</p>';
                }
            });
    });

    // 3. Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        grecaptcha.ready(function() {
            grecaptcha.execute(App.recaptchaSiteKey, { action: 'submit_programming' }).then(function(token) {
                document.getElementById('recaptcha-response').value = token;
                const formData = new FormData(form);
                formData.append('action', 'submit_form'); // Acción para el handler de la API

                fetch(App.apiUrl, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        formMessages.className = data.success ? 'success-message' : 'error-message';
                        formMessages.textContent = data.message;
                        if (data.success) {
                            form.reset();
                            peopleFieldset.classList.add('hidden');
                            areaSelect.value = '';
                        }
                    })
                    .catch(() => {
                        formMessages.className = 'error-message';
                        formMessages.textContent = 'Error de conexión. Por favor, intente de nuevo.';
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Programación';
                    });
            });
        });
    });
});

function renderPeopleCards(people, sedes, transportOptions, container) {
    let html = '';
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
                        <select id="transport-${pId}" name="people[${pId}][transporte_tipo]" required>
                            ${transportOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>
                    </div>
                    <div class="service-section">
                         <h5><i class="fas fa-map-marker-alt"></i> Sede de Destino</h5>
                         ${sedes.map(sede => `
                            <label class="radio-label">
                                <input type="radio" name="people[${pId}][id_sede]" value="${sede.id}" required> ${sede.nombre_sede}
                            </label>
                         `).join('')}
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}