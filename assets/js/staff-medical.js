document.addEventListener('DOMContentLoaded', () => {
    const BASE_URL = window.App?.BASE_URL || '/mc-pawpals-veterinary-clinic/';
    
    const dom = {
        searchInput: document.getElementById('petSearch'), listToday: document.getElementById('listToday'),
        listUpcoming: document.getElementById('listUpcoming'), listHistory: document.getElementById('listHistory'),
        searchListWrap: document.getElementById('searchListWrap'), searchList: document.getElementById('searchList'),
        recordView: document.querySelector('.record-view'), petHeader: document.getElementById('petHeader'),
        petHeaderDetails: document.getElementById('petHeaderDetails'),
        tabsContainer: document.querySelector('.tabs'), tabContents: document.querySelectorAll('.tab-content'),
        recordModal: document.getElementById('recordModal'), modalTitle: document.getElementById('modalTitle'),
        recordForm: document.getElementById('recordForm'), formFields: document.getElementById('formFields'),
        cancelModalBtn: document.getElementById('cancelModalBtn')
    };

    // --- START OF CHANGES ---
    let searchTimer, currentPetId = null, currentAppointmentId = null, canEditCurrentPet = false, currentPetItem = null, allRecords = {}, selectionContext = 'search';
    // --- END OF CHANGES ---

    const formDefinitions = {
        soap: [ { name: 'record_date', label: 'Record Date', type: 'datetime-local', required: true }, { name: 'weight_kg', label: 'Weight (kg)', type: 'number' }, { name: 'temperature_c', label: 'Temp (°C)', type: 'number' }, { name: 'subjective', label: 'Subjective', type: 'textarea', required: true }, { name: 'objective', label: 'Objective', type: 'textarea', required: true }, { name: 'assessment', label: 'Assessment', type: 'textarea', required: true }, { name: 'plan', label: 'Plan', type: 'textarea', required: true } ],
        vaccination: [ { name: 'vaccine_name', label: 'Vaccine Name', type: 'text', required: true }, { name: 'dose_no', label: 'Dose No.', type: 'text' }, { name: 'date_administered', label: 'Date', type: 'date', required: true }, { name: 'next_due_date', label: 'Next Due', type: 'date' }, { name: 'remarks', label: 'Remarks', type: 'textarea' } ],
        deworming: [ { name: 'product_name', label: 'Product', type: 'text', required: true }, { name: 'dose', label: 'Dose', type: 'text' }, { name: 'date_administered', label: 'Date', type: 'date', required: true }, { name: 'next_due_date', label: 'Next Due', type: 'date' }, { name: 'remarks', label: 'Remarks', type: 'textarea' } ],
        prevention: [ { name: 'product_name', label: 'Product', type: 'text', required: true }, { name: 'type', label: 'Type', type: 'select', options: ['tick_flea','heartworm','broad_spectrum','other']}, { name: 'route', label: 'Route', type: 'select', options: ['oral','topical','injection','other']}, { name: 'date_administered', label: 'Date', type: 'date', required: true }, { name: 'next_due_date', label: 'Next Due', type: 'date' }, { name: 'remarks', label: 'Remarks', type: 'textarea' } ],
        medication: [ { name: 'drug_name', label: 'Drug', type: 'text', required: true }, { name: 'dosage', label: 'Dosage', type: 'text' }, { name: 'frequency', label: 'Frequency', type: 'text' }, { name: 'start_date', label: 'Start Date', type: 'date' }, { name: 'end_date', label: 'End Date', type: 'date' }, { name: 'notes', label: 'Notes', type: 'textarea' } ],
        allergy: [ { name: 'allergen', label: 'Allergen', type: 'text', required: true }, { name: 'reaction', label: 'Reaction', type: 'text' }, { name: 'severity', label: 'Severity', type: 'select', options: ['mild', 'moderate', 'severe']}, { name: 'notes', label: 'Notes', type: 'textarea' } ]
    };
    
    const h = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const debounce = (fn, delay = 300) => (...args) => { clearTimeout(searchTimer); searchTimer = setTimeout(() => fn(...args), delay); };
    const NO_IMG_SVG = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96"><rect width="96" height="96" fill="%23f3f4f6"/><g fill="%239ca3af"><circle cx="48" cy="40" r="16"/><rect x="26" y="62" width="44" height="18" rx="8"/></g></svg>';
    const formatDate = (dateString) => dateString ? new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
    
    async function apiFetch(url, options = {}) {
        const defaultOptions = { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' } };
        if (options.body && typeof options.body == 'object') { options.body = JSON.stringify(options.body); }
        const fullUrl = url.startsWith('http') ? url : BASE_URL + url.replace(/^\//, '');
        const response = await fetch(fullUrl, { ...defaultOptions, ...options });
        const data = await response.json();
        if (!response.ok || data.ok === false) { throw new Error(data.error || `Server returned status ${response.status}`); }
        return data;
    }
  
    function createPetCardHTML(pet, withTime = false, statusType = 'confirmed') {
        const photo = pet.photo_url || NO_IMG_SVG;
        const time = withTime && pet.time_text ? `<span class="time-pill">${h(pet.time_text)}</span>` : '';
        let badge = `<span class="status-badge ${statusType}">${h(statusType)}</span>`;
        if (statusType === 'search') badge = '';
        return `<div class="pet-item" data-id="${pet.id}">${badge} ${time}<img class="thumb" src="${photo}" alt="${h(pet.pet_name)}"><div class="pi-main"><div class="name">${h(pet.pet_name)}</div><div class="sub">Owner: ${h(pet.owner_name)}</div></div></div>`;
    }
    function renderList(target, data, withTime, statusType) { if(target) target.innerHTML = data && data.length > 0 ? data.map(item => createPetCardHTML(item, withTime, statusType)).join('') : '<p class="muted p-4 text-center">No items found.</p>'; }
    
    function renderPetHeader(overview) {
        const { pet, owner, stats } = overview;
        dom.petHeader.innerHTML = `<div class="avatar"><img src="${pet.photo_url || NO_IMG_SVG}" alt="${h(pet.name)}"></div><div><div class="name">${h(pet.name)}</div><div class="owner">Owner: ${h(owner.full_name)}</div></div>`;
        dom.petHeaderDetails.innerHTML = `
            <div class="detail-item"><div class="detail-label">Species</div><div class="detail-value">${h(pet.species)} ${pet.breed ? `(${h(pet.breed)})` : ''}</div></div>
            <div class="detail-item"><div class="detail-label">Age</div><div class="detail-value">${h(stats.age)}</div></div>
            <div class="detail-item"><div class="detail-label">Sex</div><div class="detail-value" style="text-transform: capitalize;">${h(pet.sex)}</div></div>
            <div class="detail-item"><div class="detail-label">Last Visit</div><div class="detail-value">${h(stats.last_visit_human)}</div></div>`;
        canEditCurrentPet = stats.has_permission_to_edit;
    }
    
    function renderTable(type, records) {
        const container = document.getElementById(`tab-${type}`);
        if (!container) return;
    
        const tableDefinitions = {
            soap: {
                headers: ['Date', 'Subjective', 'Assessment', 'Recorded By'],
                rowData: r => [ new Date(r.record_date).toLocaleString(), `${h(r.subjective?.substring(0, 40))}...`, `${h(r.assessment?.substring(0, 40))}...`, h(r.staff_name) ]
            },
            vaccination: { headers: ['Vaccine', 'Date', 'Next Due'], rowData: r => [h(r.vaccine_name), formatDate(r.date_administered), formatDate(r.next_due_date)] },
            deworming: { headers: ['Product', 'Date', 'Next Due'], rowData: r => [h(r.product_name), formatDate(r.date_administered), formatDate(r.next_due_date)] },
            prevention: { headers: ['Product', 'Type', 'Date', 'Next Due'], rowData: r => [h(r.product_name), h(r.type), formatDate(r.date_administered), formatDate(r.next_due_date)] },
            medication: { headers: ['Drug', 'Dosage', 'Start', 'End'], rowData: r => [h(r.drug_name), h(r.dosage), formatDate(r.start_date), formatDate(r.end_date)] },
            allergy: { headers: ['Allergen', 'Severity', 'Noted On'], rowData: r => [h(r.allergen), h(r.severity), formatDate(r.noted_at)] }
        };
    
        const definition = tableDefinitions[type];
        if (!definition) return;
    
        const { headers, rowData } = definition;
    
        const actionsHeader = canEditCurrentPet ? `<th class="actions-header">Actions</th>` : '';
        const headHTML = `<thead><tr>${headers.map(hText => `<th>${hText}</th>`).join('')}${actionsHeader}</tr></thead>`;
    
        const actionsCell = (r) => {
            if (!canEditCurrentPet) return '';
            let buttons = '';
            if (type === 'soap') { buttons += `<button class="btn btn-sm btn-view" title="View Full Record"><i class="fa-solid fa-eye"></i></button>`; }
            buttons += `<button class="btn btn-sm btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></button>`;
            buttons += `<button class="btn btn-sm btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>`;
            return `<td class="actions">${buttons}</td>`;
        };
    
        const bodyHTML = `<tbody>` + (records && records.length > 0 ? records.map(r => {
            const cellsData = rowData(r);
            const cellsHTML = cellsData.map((cellData, index) => `<td data-label="${headers[index]}">${cellData}</td>`).join('');
            return `<tr data-record-id="${r.id}" data-record-type="${type}">${cellsHTML}${actionsCell(r)}</tr>`;
        }).join('') : `<tr><td colspan="${headers.length + (canEditCurrentPet ? 1 : 0)}">No records found.</td></tr>`) + `</tbody>`;
        
        // --- START OF CHANGES ---
        // Conditionally show the "Add New" button based on the context (Today, Upcoming, History)
        const showAddButton = canEditCurrentPet && (selectionContext === 'today' || selectionContext === 'search');
        const addBtnHTML = showAddButton ? `<div class="table-actions"><button class="btn btn-primary btn-sm btn-add" data-type="${type}"><i class="fa-solid fa-plus"></i> Add New</button></div>` : '';
        // --- END OF CHANGES ---

        container.innerHTML = `<div class="table-container">${addBtnHTML}<div class="table-responsive"><table>${headHTML}${bodyHTML}</table></div></div>`;
    }

    async function handlePetSelect(petItem) {
        if (!petItem) return;
        const petId = petItem.dataset.id;
        if (petItem.classList.contains('active')) {
            petItem.classList.remove('active'); dom.recordView.hidden = true; return;
        }
        currentPetId = petId; currentPetItem = petItem;

        // --- START OF CHANGES ---
        // Determine if the selected pet is from Today, Upcoming, or History list
        if (petItem.closest('#listToday')) {
            selectionContext = 'today';
        } else if (petItem.closest('#listUpcoming')) {
            selectionContext = 'upcoming';
        } else if (petItem.closest('#listHistory')) {
            selectionContext = 'history';
        } else {
            selectionContext = 'search'; // Default for search results
        }
        // --- END OF CHANGES ---

        document.querySelectorAll('.pet-item.active').forEach(el => el.classList.remove('active'));
        petItem.classList.add('active');
        dom.recordView.hidden = false;
        dom.petHeader.innerHTML = '<p class="muted p-4">Loading details...</p>'; dom.petHeaderDetails.innerHTML = '';
        dom.tabContents.forEach(tc => tc.innerHTML = '<div class="placeholder p-4">Loading records...</div>');
        try {
            const [overview, data] = await Promise.all([apiFetch(`api/staffs/medical/pet_overview?pet_id=${petId}`), apiFetch(`api/staffs/medical/records?pet_id=${petId}`)]);
            
            currentAppointmentId = overview.stats.appointment_id_for_form;

            allRecords = data.records;
            renderPetHeader(overview);
            Object.keys(formDefinitions).forEach(type => {
                renderTable(type, allRecords[type]);
            });

            dom.tabsContainer.querySelector('.active')?.classList.remove('active'); dom.tabContents.forEach(tc => tc.classList.remove('active'));
            const firstTab = dom.tabsContainer.firstElementChild;
            firstTab.classList.add('active');
            document.getElementById(firstTab.dataset.tab).classList.add('active');

            if (window.innerWidth < 1024) {
                dom.recordView.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) { dom.recordView.innerHTML = `<p class="error">Could not load records. ${error.message}</p>`; }
    }
    
    function openRecordModal(type, existingData = null) {
        const fields = formDefinitions[type]; if (!fields) return;
        dom.modalTitle.textContent = existingData ? `Edit Record` : `Add New Record`;

        let formHTML = `
            <input type="hidden" name="record_id" value="${existingData?.id || ''}">
            <input type="hidden" name="record_type" value="${type}">
            <input type="hidden" name="pet_id" value="${currentPetId}">
            <input type="hidden" name="appointment_id" value="${currentAppointmentId || ''}">
        `;

        const isGrid = fields.length > 3 && type !== 'soap'; if (isGrid) formHTML += '<div class="form-grid">';
        fields.forEach(field => {
            let value = existingData ? (existingData[field.name] || '') : (field.type === 'date' ? new Date().toISOString().split('T')[0] : '');
            if (type === 'soap' && field.name === 'record_date') { value = new Date(existingData?.record_date || Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16); }
            let fieldHTML = `<div class="form-group"><label for="modal_${field.name}">${field.label}${field.required?' *':''}</label>`;
            if (field.type === 'textarea') { fieldHTML += `<textarea id="modal_${field.name}" name="${field.name}" rows="${type==='soap'?4:3}" ${field.required?'required':''}>${h(value)}</textarea>`;
            } else if (field.type === 'select') {
                fieldHTML += `<select id="modal_${field.name}" name="${field.name}" ${field.required?'required':''}>`;
                field.options.forEach(opt => { fieldHTML += `<option value="${opt}" ${opt===value?'selected':''}>${opt.replace(/_/g,' ').charAt(0).toUpperCase()+opt.slice(1).replace(/_/g,' ')}</option>`; });
                fieldHTML += `</select>`;
            } else { fieldHTML += `<input type="${field.type}" step="${field.type==='number'?'0.01':''}" id="modal_${field.name}" name="${field.name}" value="${h(value)}" ${field.required?'required':''}>`; }
            fieldHTML += '</div>';
            formHTML += fieldHTML;
        });
        if (isGrid) formHTML += '</div>';
        dom.formFields.innerHTML = formHTML;
        
        dom.formFields.querySelectorAll('input[type="date"]').forEach(el => flatpickr(el, { dateFormat: "Y-m-d" }));
        dom.formFields.querySelectorAll('input[type="datetime-local"]').forEach(el => flatpickr(el, { enableTime: true, dateFormat: "Y-m-d H:i" }));

        dom.recordModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function closeRecordModal() { 
        dom.recordModal.hidden = true; 
        document.body.style.overflow = '';
    }
    async function handleFormSubmit(e) {
        e.preventDefault();
        const formData = new FormData(dom.recordForm); let data = Object.fromEntries(formData.entries());
        if(data.record_date) data.record_date = new Date(data.record_date).toISOString().slice(0, 19).replace('T', ' ');
        const method = data.record_id ? 'PUT' : 'POST';
        try {
            const result = await apiFetch('api/staffs/medical/records', { method, body: data });
            Swal.fire({ title: 'Success!', text: result.message, icon: 'success', timer: 1500 });
            closeRecordModal(); await handlePetSelect(currentPetItem);
        } catch (error) { Swal.fire({ title: 'Error!', text: error.message, icon: 'error' }); }
    }
    async function handleDeleteClick(e) {
        const deleteBtn = e.target.closest('.btn-delete'); if (!deleteBtn) return;
        const row = deleteBtn.closest('tr'); const record_id = row.dataset.recordId; const record_type = row.dataset.recordType;
        const result = await Swal.fire({ title: 'Are you sure?', text: "This cannot be undone!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!' });
        if (result.isConfirmed) {
            try {
                const res = await apiFetch('api/staffs/medical/records', { method: 'DELETE', body: { pet_id: currentPetId, record_id, record_type } });
                Swal.fire('Deleted!', res.message, 'success');
                await handlePetSelect(currentPetItem);
            } catch (error) { Swal.fire('Error!', error.message, 'error'); }
        }
    }
    function handleViewClick(e) {
        const viewBtn = e.target.closest('.btn-view'); if (!viewBtn) return;
        const row = viewBtn.closest('tr'); const recordId = row.dataset.recordId;
        const record = allRecords.soap.find(r => r.id == recordId);
        if (!record) return;
        Swal.fire({
            title: `S.O.A.P. Record (${formatDate(record.record_date)})`,
            html: `<div class="swal-soap-content"><h4>Subjective</h4><p>${h(record.subjective)}</p><h4>Objective</h4><p>${h(record.objective)}</p><h4>Assessment</h4><p>${h(record.assessment)}</p><h4>Plan</h4><p>${h(record.plan)}</p></div>`,
            showCloseButton: true, width: '600px',
        });
    }
    async function initializePage() {
        dom.recordView.hidden = true;
        try {
            const [todayUpcoming, history] = await Promise.all([ apiFetch(`api/staffs/pets/today_upcoming`), apiFetch(`api/staffs/medical/history?list=mine`) ]);
            renderList(dom.listToday, todayUpcoming.today, true, 'confirmed');
            renderList(dom.listUpcoming, todayUpcoming.upcoming, true, 'confirmed');
            renderList(dom.listHistory, history.history, true, 'completed');
        } catch (error) { dom.listToday.innerHTML = dom.listUpcoming.innerHTML = dom.listHistory.innerHTML = `<p class="error">Could not load appointments.</p>`; }
    }

    dom.searchInput?.addEventListener('input', debounce(async () => {
        const query = dom.searchInput.value.trim();
        if (query.length < 1) { dom.searchListWrap.hidden = true; ['todayWrap', 'upcomingWrap', 'historyWrap'].forEach(id => document.getElementById(id).hidden = false); return; }
        dom.searchList.innerHTML = '<p class="muted p-4 text-center">Searching...</p>'; dom.searchListWrap.hidden = false;
        ['todayWrap', 'upcomingWrap', 'historyWrap'].forEach(id => document.getElementById(id).hidden = true);
        try { const data = await apiFetch(`api/staffs/pets/search?q=${encodeURIComponent(query)}`); renderList(dom.searchList, data.pets, false, 'search'); } catch(error){ dom.searchList.innerHTML = `<p class="error">Search failed.</p>`; }
    }));
    document.querySelector('.col-left').addEventListener('click', e => {
        const petItem = e.target.closest('.pet-item'); if (petItem) handlePetSelect(petItem);
        const foldableHead = e.target.closest('.list-head.foldable');
        if (foldableHead) {
            const list = foldableHead.nextElementSibling;
            const icon = foldableHead.querySelector('.list-fold');
            list.classList.toggle('collapsed');
            icon.classList.toggle('rotated');
        }
    });
    dom.tabsContainer?.addEventListener('click', e => {
        const tabLink = e.target.closest('.tab-link'); if (!tabLink) return;
        dom.tabsContainer.querySelector('.active')?.classList.remove('active');
        tabLink.classList.add('active');
        dom.tabContents.forEach(tc => tc.classList.remove('active'));
        document.getElementById(tabLink.dataset.tab).classList.add('active');
    });
    dom.recordView.addEventListener('click', e => {
        const addBtn = e.target.closest('.btn-add'); const editBtn = e.target.closest('.btn-edit');
        if (addBtn) { openRecordModal(addBtn.dataset.type); }
        if (editBtn) {
            const row = editBtn.closest('tr'); const recordId = row.dataset.recordId;
            const recordType = row.dataset.recordType; const recordData = allRecords[recordType]?.find(r => r.id == recordId);
            if(recordData) openRecordModal(recordType, recordData);
        }
        handleDeleteClick(e);
        handleViewClick(e);
    });
    dom.recordForm.addEventListener('submit', handleFormSubmit);
    dom.cancelModalBtn?.addEventListener('click', closeRecordModal);
    
    initializePage();
});