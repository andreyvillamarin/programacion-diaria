document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('programming-form');
    if (!form) return;

    const areaSelect = document.getElementById('area-select');
    const peopleFieldset = document.getElementById('people-fieldset');
    const otherAreaFieldset = document.getElementById('other-area-fieldset');
    const peopleContainer = document.getElementById('people-container');
    const otherAreaServicesContainer = document.getElementById('other-area-services');
    const dateInput = document.getElementById('programming-date');
    const submitBtn = document.getElementById('submit-btn');
    const formMessages = document.getElementById('form-messages');

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.value = tomorrow.toISOString().split('T')[0];

    fetch('api/data.php?action=get_initial_data')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                areaSelect.innerHTML = '<option value="">-- Seleccione un área --</option>';
                data.areas.forEach(area => {
                    const isOther = area.nombre_area.toLowerCase() === 'otras áreas' ? 'true' : 'false';
                    areaSelect.innerHTML += `<option value="${area.id}" data-other-area="${isOther}">${area.nombre_area}</option>`;
                });
            }
        });

    areaSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const isOtherArea = selectedOption.getAttribute('data-other-area') === 'true';
        const areaId = this.value;

        peopleFieldset.classList.add('hidden');
        otherAreaFieldset.classList.add('hidden');

        if (!areaId) return;

        if (isOtherArea) {
            otherAreaFieldset.classList.remove('hidden');
            otherAreaServicesContainer.innerHTML = '<p class="loading">Cargando servicios...</p>';
            fetch(`api/data.php?action=get_services_only`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderServicesOnly(data.sedes, data.transport_options, otherAreaServicesContainer);
                    }
                });
        } else {
            peopleFieldset.classList.remove('hidden');
            peopleContainer.innerHTML = '<p class="loading">Cargando personal...</p>';
            fetch(`api/data.php?action=get_people_by_area&area_id=${areaId}`)
                .then(res => res.json())
                .then(data => {
                    renderPeopleCards(data.people, data.sedes, data.transport_options, peopleContainer);
                });
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
            if (input.offsetWidth > 0 || input.offsetHeight > 0) {
                if (!input.checkValidity()) {
                    isValid = false;
                    input.reportValidity();
                }
            }
        });

        if (!isValid) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        const formData = new FormData(form);
        formData.append('action', 'submit_form');

        fetch('api/handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                formMessages.innerHTML = `<div class="${data.success ? 'success-message' : 'error-message'}">${data.message}</div>`;
                if (data.success) {
                    form.reset();
                    peopleFieldset.classList.add('hidden');
                    otherAreaFieldset.classList.add('hidden');
                    areaSelect.value = '';
                }
            })
            .catch(() => {
                formMessages.innerHTML = `<div class="error-message">Error de conexión. Por favor, intente de nuevo.</div>`;
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Programación';
            });
    });
});


function renderServicesOnly(sedes, transportOptions, container, namePrefix = 'other') {
    container.innerHTML = `
        <div class="person-card">
            <div class="card-header">
                <div class="form-group">
                    <label for="other-name">Nombre Completo *</label>
                    <input type="text" id="other-name" name="${namePrefix}[nombre_manual]" required>
                </div>
                <div class="form-group">
                    <label for="other-area-wbe">Área WBE *</label>
                    <input type="text" id="other-area-wbe" name="${namePrefix}[area_wbe]" required>
                </div>
                 <div class="form-group">
                    <label for="other-activity">Actividad a Realizar *</label>
                    <textarea id="other-activity" name="${namePrefix}[actividad]" rows="2" required></textarea>
                </div>
            </div>
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
                    <select name="${namePrefix}[transporte_tipo]" required>
                        <option value="">-- Seleccione --</option>
                        ${transportOptions.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                    </select>
                </div>
                <div class="service-section">
                     <h5><i class="fas fa-map-marker-alt"></i> Sede de Destino *</h5>
                     ${sedes.map(sede => `
                        <label class="radio-label">
                            <input type="radio" name="${namePrefix}[id_sede]" value="${sede.id}" required> ${sede.nombre_sede}
                        </label>
                     `).join('')}
                </div>
            </div>
        </div>
    `;
}

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
                         ${sedes.map(sede => `<label class="radio-label"><input type="radio" name="people[${pId}][id_sede]" value="${sede.id}"> ${sede.nombre_sede}</label>`).join('')}
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}