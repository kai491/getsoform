/**
 * JavaScript para el Editor de Getso Forms
 *
 * Maneja las pestañas, el modal de campos y la lógica del constructor de campos.
 */
document.addEventListener('DOMContentLoaded', function() {

    const editorForm = document.getElementById('getso-form-editor');
    if (!editorForm) {
        return; // No estamos en la página del editor
    }

    // --- 1. LÓGICA DE PESTAÑAS (TABS) ---
    const tabLinks = document.querySelectorAll('.getso-tabs .nav-tab');
    const tabPanels = document.querySelectorAll('.getso-tab-panel');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            
            // Ocultar todas las pestañas y paneles
            tabLinks.forEach(lnk => lnk.classList.remove('nav-tab-active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));

            // Mostrar la pestaña y panel seleccionados
            this.classList.add('nav-tab-active');
            document.querySelector(targetId).classList.add('active');
        });
    });

    // --- 2. LÓGICA DEL MODAL DE CAMPOS ---
    const modal = document.getElementById('field-modal');
    const addFieldBtn = document.getElementById('add-field-btn');
    const closeModalBtn = modal.querySelector('.getso-modal-close');
    const cancelModalBtn = modal.querySelector('#cancel-field-btn');
    const saveFieldBtn = modal.querySelector('#save-field-btn');
    const modalTitle = modal.querySelector('#field-modal-title');
    const fieldForm = modal.querySelector('#field-form');
    const fieldsContainer = document.getElementById('fields-container');
    const fieldOptionsRow = modal.querySelector('#field-options-row');

    // Mostrar u ocultar el campo de opciones (para select, radio, checkbox)
    modal.querySelector('#field-type').addEventListener('change', function() {
        const fieldType = this.value;
        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            fieldOptionsRow.style.display = 'block';
        } else {
            fieldOptionsRow.style.display = 'none';
        }
    });

    // Abrir el modal (para crear)
    if (addFieldBtn) {
        addFieldBtn.addEventListener('click', function() {
            fieldForm.reset(); // Limpiar formulario
            modal.querySelector('#field-index').value = '';
            modalTitle.textContent = 'Agregar Campo';
            fieldOptionsRow.style.display = 'none';
            modal.style.display = 'block';
        });
    }

    // Cerrar el modal
    function closeModal() {
        modal.style.display = 'none';
    }
    closeModalBtn.addEventListener('click', closeModal);
    cancelModalBtn.addEventListener('click', closeModal);
    modal.querySelector('.getso-modal-overlay').addEventListener('click', closeModal);

    // --- 3. LÓGICA DE GUARDAR CAMPO (Modal) ---
    saveFieldBtn.addEventListener('click', function() {
        // Recolectar datos del modal
        const fieldIndex = modal.querySelector('#field-index').value;
        const field = {
            type: modal.querySelector('#field-type').value,
            name: modal.querySelector('#field-name').value,
            label: modal.querySelector('#field-label').value,
            placeholder: modal.querySelector('#field-placeholder').value,
            required: modal.querySelector('#field-required').checked,
            options: modal.querySelector('#field-options').value.split('\n').map(opt => {
                const parts = opt.split('|');
                return { value: parts[0], label: parts[1] || parts[0] };
            }),
            class: modal.querySelector('#field-class').value,
        };

        // Validación simple
        if (!field.type || !field.name) {
            alert('Por favor, selecciona un "Tipo de Campo" y un "Nombre Interno".');
            return;
        }

        if (fieldIndex) {
            // Editando un campo existente
            const fieldItem = fieldsContainer.querySelector(`.getso-field-item[data-index="${fieldIndex}"]`);
            fieldItem.remove(); // Quitar el viejo
            const newFieldHTML = createFieldHTML(field, fieldIndex);
            fieldsContainer.insertAdjacentHTML('beforeend', newFieldHTML); // Añadir el actualizado
        } else {
            // Creando un campo nuevo
            const newIndex = fieldsContainer.querySelectorAll('.getso-field-item').length;
            const newFieldHTML = createFieldHTML(field, newIndex);
            fieldsContainer.insertAdjacentHTML('beforeend', newFieldHTML);
        }

        // Re-indexar y limpiar "No hay campos"
        reindexFields();
        closeModal();
    });

    // --- 4. LÓGICA DE EDITAR Y ELIMINAR CAMPOS (Event Delegation) ---
    fieldsContainer.addEventListener('click', function(e) {
        
        // Botón Eliminar
        const deleteBtn = e.target.closest('.delete-field-btn');
        if (deleteBtn) {
            if (confirm('¿Estás seguro de que quieres eliminar este campo?')) {
                deleteBtn.closest('.getso-field-item').remove();
                reindexFields();
            }
        }

        // Botón Editar
        const editBtn = e.target.closest('.edit-field-btn');
        if (editBtn) {
            const fieldItem = editBtn.closest('.getso-field-item');
            const fieldIndex = fieldItem.dataset.index;
            
            // Obtener datos del input oculto
            const fieldDataJSON = fieldItem.querySelector('input[type="hidden"]').value;
            const field = JSON.parse(fieldDataJSON);
            
            // Rellenar el modal
            modal.querySelector('#field-index').value = fieldIndex;
            modalTitle.textContent = 'Editar Campo';
            
            modal.querySelector('#field-type').value = field.type;
            modal.querySelector('#field-name').value = field.name;
            modal.querySelector('#field-label').value = field.label;
            modal.querySelector('#field-placeholder').value = field.placeholder;
            modal.querySelector('#field-required').checked = field.required;
            modal.querySelector('#field-class').value = field.class;
            
            // Rellenar opciones
            if (['select', 'radio', 'checkbox'].includes(field.type)) {
                const optionsText = field.options.map(opt => `${opt.value}|${opt.label}`).join('\n');
                modal.querySelector('#field-options').value = optionsText;
                fieldOptionsRow.style.display = 'block';
            } else {
                modal.querySelector('#field-options').value = '';
                fieldOptionsRow.style.display = 'none';
            }

            modal.style.display = 'block';
        }
    });

    // --- 5. FUNCIONES AUXILIARES ---

    /**
     * Re-indexa los campos y actualiza los inputs ocultos.
     */
    function reindexFields() {
        const fieldItems = fieldsContainer.querySelectorAll('.getso-field-item');
        const noFieldsMessage = fieldsContainer.querySelector('.getso-no-fields');

        if (fieldItems.length === 0) {
            if (!noFieldsMessage) {
                fieldsContainer.innerHTML = `
                <div class="getso-no-fields">
                    <span class="dashicons dashicons-editor-table"></span>
                    <p>No hay campos. Haz clic en "Agregar Campo" para comenzar.</p>
                </div>`;
            }
        } else {
            if (noFieldsMessage) {
                noFieldsMessage.remove();
            }
        }
        
        fieldItems.forEach((item, index) => {
            item.dataset.index = index;
            item.querySelector('.edit-field-btn').dataset.index = index;
            item.querySelector('.delete-field-btn').dataset.index = index;
            
            // Actualizar el input oculto
            const hiddenInput = item.querySelector('input[type="hidden"]');
            const fieldData = JSON.parse(hiddenInput.value);
            hiddenInput.name = `fields[]`; // Asegurar el nombre como array
            hiddenInput.value = JSON.stringify(fieldData); // Mantener el valor JSON
        });
    }

    /**
     * Crea el HTML para un item de campo (réplica JS de la función PHP)
     */
    function createFieldHTML(field, index) {
        const requiredBadge = field.required ? '<span class="required-badge">Requerido</span>' : '';
        const fieldLabel = field.label || field.name;
        const fieldDataJSON = escapeHTML(JSON.stringify(field));

        return `
        <div class="getso-field-item" data-index="${index}">
            <div class="field-item-header">
                <span class="field-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </span>
                <span class="field-type-badge">${escapeHTML(field.type)}</span>
                <strong>${escapeHTML(fieldLabel)}</strong>
                ${requiredBadge}
            </div>
            <div class="field-item-actions">
                <button type="button" class="button button-small edit-field-btn" data-index="${index}">
                    <span class="dashicons dashicons-edit"></span>
                    Editar
                </button>
                <button type="button" class="button button-small delete-field-btn" data-index="${index}">
                    <span class="dashicons dashicons-trash"></span>
                    Eliminar
                </button>
            </div>
            <input type="hidden" name="fields[]" value='${fieldDataJSON}'>
        </div>`;
    }

    /**
     * Helper para escapar HTML
     */
    function escapeHTML(str) {
        const p = document.createElement("p");
        p.textContent = str;
        return p.innerHTML.replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    // Inicializar (por si hay campos cargados)
    reindexFields();

});