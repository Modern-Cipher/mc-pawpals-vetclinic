document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('pets-documents-container');
    const API_URL = `${App.BASE_URL}api/users/pet-documents`;

    // Ensure the container exists before proceeding
    if (!container) {
        console.error('Error: The container with ID "pets-documents-container" was not found.');
        return;
    }

    async function loadPetDocuments() {
        try {
            const res = await fetch(API_URL);
            const data = await res.json();

            if (!res.ok || !data.ok) {
                throw new Error(data.error || 'Failed to load documents.');
            }

            renderGroupedDocuments(data.documents);

        } catch (error) {
            console.error('Error fetching documents:', error);
            container.innerHTML = `<div class="error-message">Could not load documents. Please try again later.</div>`;
        }
    }

    function renderGroupedDocuments(docs) {
        if (docs.length === 0) {
            container.innerHTML = `<div class="info-message">You have no documents uploaded for any of your pets yet.</div>`;
            return;
        }

        // 1. Group documents by pet name
        const docsByPet = docs.reduce((acc, doc) => {
            if (!acc[doc.pet_name]) {
                acc[doc.pet_name] = [];
            }
            acc[doc.pet_name].push(doc);
            return acc;
        }, {});

        // 2. Build HTML for each pet group
        let finalHtml = '';
        for (const petName in docsByPet) {
            finalHtml += `
                <div class="pet-docs-group">
                    <h2 class="pet-group-title">
                        <i class="fa-solid fa-paw"></i>
                        ${petName}
                    </h2>
                    <div class="documents-grid">
                        ${docsByPet[petName].map(doc => renderSingleDocument(doc)).join('')}
                    </div>
                </div>
            `;
        }
        container.innerHTML = finalHtml;
    }

    function renderSingleDocument(doc) {
        const fileIcon = getFileIcon(doc.doc_type, doc.file_path);
        const uploadedDate = new Date(doc.uploaded_at).toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
        });
        const uploaderName = (doc.uploader_name && doc.uploader_name.trim()) ? doc.uploader_name.trim() : 'the clinic';

        return `
            <div class="doc-card">
                <div class="doc-card-icon">
                    <i class="${fileIcon}"></i>
                </div>
                <div class="doc-card-details">
                    <h3 class="doc-title">${doc.title}</h3>
                    <p class="doc-meta">Uploaded: ${uploadedDate}</p>
                    <p class="doc-clinic">By: <strong>${uploaderName}</strong></p>
                </div>
                <div class="doc-card-actions">
                    <a href="${App.BASE_URL}api/pet-documents/download?id=${doc.id}" target="_blank" class="action-btn view" title="View/Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    <button class="action-btn print" data-url="${App.BASE_URL}api/pet-documents/download?id=${doc.id}" title="Print">
                        <i class="fa-solid fa-print"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function getFileIcon(docType, filePath) {
        const extension = filePath.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return 'fa-solid fa-file-image';
        if (extension === 'pdf') return 'fa-solid fa-file-pdf';
        if (['doc', 'docx'].includes(extension)) return 'fa-solid fa-file-word';
        if (['xls', 'xlsx', 'csv'].includes(extension)) return 'fa-solid fa-file-excel';
        if (['ppt', 'pptx'].includes(extension)) return 'fa-solid fa-file-powerpoint';
        return 'fa-solid fa-file-alt';
    }

    // Event delegation for print buttons
    container.addEventListener('click', (e) => {
        const printBtn = e.target.closest('.print');
        if (printBtn) {
            const fileUrl = printBtn.dataset.url;
            const printWindow = window.open(fileUrl, '_blank');
            if (printWindow) {
                printWindow.onload = () => {
                    setTimeout(() => {
                        try {
                            printWindow.print();
                        } catch (err) {
                            console.error("Print failed:", err);
                            printWindow.close();
                        }
                    }, 500); // Delay to ensure content (like PDF) is loaded
                };
            }
        }
    });

    loadPetDocuments();
});