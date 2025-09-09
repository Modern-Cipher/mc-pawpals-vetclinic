document.addEventListener('DOMContentLoaded', () => {
    const API = {
        listPets: `${App.BASE_URL}api/staffs/pets/all.php`,
        uploadDocs: `${App.BASE_URL}api/pet-documents/upload_document.php`,
        listDocs: `${App.BASE_URL}api/pet-documents/list_by_pet_staff.php`,
        deleteDoc: `${App.BASE_URL}api/pet-documents/delete_by_staff.php`,
    };

    const rightPanel = document.querySelector('.right-panel');
    const petSearch = document.getElementById('pet-search');
    const petListSidebar = document.getElementById('pet-list-sidebar');
    const petInfoHeader = document.getElementById('pet-info-header');
    const petPhoto = document.getElementById('pet-photo');
    const petName = document.getElementById('pet-name');
    const docsVault = document.getElementById('docs-vault');
    const uploadFormSection = document.getElementById('upload-form-section');
    const petIdInput = document.getElementById('pet-id-for-upload');
    const docsUploadList = document.getElementById('docs-upload-list');
    const docsForm = document.getElementById('pet-docs-form');
    const dropZone = document.getElementById('drop-zone');
    const fileInputHidden = document.getElementById('file-input-hidden');
    const uploadBtn = document.getElementById('upload-btn');

    let allPets = [];
    let currentPetId = null;
    let fileQueue = new Map();

    async function fetchAndRenderPets() {
        try {
            const res = await fetch(API.listPets);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to load pets from API.');
            allPets = data.pets;
            renderPetList(allPets);
        } catch (error) {
            console.error('Error fetching pets:', error);
            petListSidebar.innerHTML = `<li class="error-message">${error.message}</li>`;
        }
    }

    function renderPetList(pets) {
        let listHtml = pets.length ? pets.map(pet => `
            <li class="pet-list-item" data-id="${pet.id}">
                <div class="pet-photo-thumb" style="background-image: url('${App.BASE_URL}${pet.photo_path || 'assets/images/pet-placeholder.jpg'}')"></div>
                <div class="pet-details">
                    <span class="pet-name">${pet.pet_name}</span>
                    <span class="pet-owner">${pet.owner_name}</span>
                </div>
            </li>
        `).join('') : '<li class="muted-item">No pets found.</li>';
        
        listHtml += `<div class="initial-message-mobile" id="initial-message">Select a pet to begin.</div>`;
        petListSidebar.innerHTML = listHtml;
    }
    
    petSearch.addEventListener('input', () => {
        const term = petSearch.value.toLowerCase();
        const filtered = allPets.filter(p => 
            p.pet_name.toLowerCase().includes(term) || 
            p.owner_name.toLowerCase().includes(term)
        );
        renderPetList(filtered);
    });

    petListSidebar.addEventListener('click', (e) => {
        const petItem = e.target.closest('.pet-list-item');
        if (petItem && petItem.dataset.id !== currentPetId) {
            document.querySelectorAll('.pet-list-item.active').forEach(el => el.classList.remove('active'));
            petItem.classList.add('active');
            selectPet(petItem.dataset.id);
        }
    });

    function selectPet(petId) {
        currentPetId = petId;
        const pet = allPets.find(p => p.id == petId);
        if (!pet) return;

        rightPanel.classList.add('visible');
        const initialMessage = document.getElementById('initial-message');
        if(initialMessage) initialMessage.style.display = 'none';

        petPhoto.src = `${App.BASE_URL}${pet.photo_path || 'assets/images/pet-placeholder.jpg'}`;
        petName.textContent = pet.pet_name;
        petIdInput.value = petId;
        
        fileQueue.clear();
        renderUploadList();
        loadDocuments(petId);
    }

    async function loadDocuments(petId) {
        docsVault.innerHTML = '<div class="loading-message">Loading documents...</div>';
        try {
            const res = await fetch(`${API.listDocs}?pet_id=${petId}`);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to load documents.');
            renderDocuments(data.documents);
        } catch (error) {
            console.error('Error fetching documents:', error);
            docsVault.innerHTML = `<div class="error-message">${error.message}</div>`;
        }
    }

    function renderDocuments(documents) {
        if (!documents.length) {
            docsVault.innerHTML = '<div class="muted-message">No documents found for this pet.</div>';
            return;
        }
        docsVault.innerHTML = documents.map(doc => {
            const fileIcon = getFileIcon(doc.mime_type);
            const isImg = doc.mime_type && doc.mime_type.startsWith('image/');
            const preview = isImg 
                ? `<img src="${App.BASE_URL}api/pet-documents/download.php?id=${doc.id}" alt="${doc.title}">`
                : `<i class="${fileIcon}"></i>`;
            
            const uploaderInfo = doc.uploader_name ? `<span class="doc-uploader" title="Uploaded by ${doc.uploader_name.trim()}">by: ${doc.uploader_name.trim()}</span>` : '';

            return `
                <div class="document-card" data-id="${doc.id}" data-path="${doc.file_path}">
                    <div class="doc-preview">${preview}</div>
                    <div class="doc-info">
                        <span class="doc-title" title="${doc.title}">${doc.title}</span>
                        <span class="doc-type">${(doc.doc_type || '').replace('_', ' ')}</span>
                        ${uploaderInfo}
                    </div>
                    <div class="doc-actions">
                        <button class="action-btn view-doc-btn" title="View"><i class="fa-solid fa-eye"></i></button>
                        <button class="action-btn delete-doc-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>`;
        }).join('');
    }

    docsVault.addEventListener('click', async (e) => {
        const button = e.target.closest('.action-btn');
        if (!button) return;
        const card = button.closest('.document-card');
        const docId = card.dataset.id;
        if (button.classList.contains('view-doc-btn')) {
            window.open(`${App.BASE_URL}api/pet-documents/download.php?id=${docId}`, '_blank');
        } else if (button.classList.contains('delete-doc-btn')) {
            const { isConfirmed } = await Swal.fire({
                title: 'Confirm Deletion', text: "This cannot be undone.", icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Yes, delete it!'
            });
            if (isConfirmed) { deleteDocument(docId); }
        }
    });

    async function deleteDocument(docId) {
        Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const fd = new FormData();
            fd.append('doc_id', docId);
            const res = await fetch(API.deleteDoc, { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Deletion failed.');
            Swal.fire('Deleted!', 'The document has been removed.', 'success');
            loadDocuments(currentPetId);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
    });
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'));
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'));
    });

    dropZone.addEventListener('click', () => fileInputHidden.click());
    fileInputHidden.addEventListener('change', () => {
        addFilesToQueue(fileInputHidden.files);
        fileInputHidden.value = '';
    });
    dropZone.addEventListener('drop', (e) => addFilesToQueue(e.dataTransfer.files));

    function addFilesToQueue(files) {
        for (const file of files) {
            const fileId = `${file.name}-${file.size}-${file.lastModified}`;
            if (!fileQueue.has(fileId)) { fileQueue.set(fileId, file); }
        }
        renderUploadList();
    }

    function renderUploadList() {
        docsUploadList.innerHTML = '';
        if (fileQueue.size > 0) {
            let i = 0;
            for (const [id, file] of fileQueue.entries()) {
                docsUploadList.insertAdjacentHTML('beforeend', `
                    <div class="doc-upload-item" data-id="${id}">
                        <div class="row-fields">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="doc_title[]" class="form-control" value="${file.name.split('.').slice(0, -1).join('.')}" required>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="doc_type[]" class="form-control" required>
                                    <option value="vaccination_record">Vaccination Record</option>
                                    <option value="lab_result">Lab Result</option>
                                    <option value="prescription">Prescription</option>
                                    <option value="xray">X-Ray</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="others" selected>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>File</label>
                                <div class="file-info">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</div>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm remove-doc-btn" title="Remove"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>`);
                i++;
            }
        }
        uploadBtn.disabled = fileQueue.size === 0;
    }

    docsUploadList.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.remove-doc-btn');
        if (removeBtn) {
            const item = removeBtn.closest('.doc-upload-item');
            fileQueue.delete(item.dataset.id);
            renderUploadList();
        }
    });

    docsForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (fileQueue.size === 0 || !currentPetId) {
            Swal.fire('Wait', 'Please select a pet and add files to upload.', 'warning');
            return;
        }
        const formData = new FormData();
        formData.append('pet_id', petIdInput.value);

        let i = 0;
        for (const file of fileQueue.values()) {
            formData.append(`doc_file[${i}]`, file);
            const item = docsUploadList.querySelector(`[data-id="${`${file.name}-${file.size}-${file.lastModified}`}"]`);
            if(item){
                formData.append(`doc_title[${i}]`, item.querySelector(`input[name="doc_title[]"]`).value);
                formData.append(`doc_type[${i}]`, item.querySelector(`select[name="doc_type[]"]`).value);
            }
            i++;
        }
        
        Swal.fire({ title: 'Uploading...', html: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await fetch(API.uploadDocs, { method: 'POST', body: formData });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'An unknown error occurred.');
            Swal.fire({
                title: 'Upload Complete', text: data.message,
                icon: data.errors && data.errors.length > 0 ? 'warning' : 'success'
            });
            fileQueue.clear();
            renderUploadList();
            loadDocuments(currentPetId);
        } catch (error) {
            Swal.fire('Upload Failed', error.message, 'error');
        }
    });

    function getFileIcon(mimeType) {
        if (!mimeType) return 'fa-solid fa-file';
        if (mimeType.includes('pdf')) return 'fa-solid fa-file-pdf';
        if (mimeType.includes('word')) return 'fa-solid fa-file-word';
        if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return 'fa-solid fa-file-excel';
        if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'fa-solid fa-file-powerpoint';
        if (mimeType.startsWith('text/')) return 'fa-solid fa-file-lines';
        return 'fa-solid fa-file';
    }

    fetchAndRenderPets();
});