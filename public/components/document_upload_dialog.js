// =========================================
// Dialog: PDF hochladen
// =========================================
// Benötigt: /assets/js/api.js, /components/document_upload_dialog.php
//
//   openDocumentUploadDialog(name => { ... })
// Der Callback bekommt den endgültigen Dateinamen nach dem Hochladen.

let documentUploadCallback = null;

function openDocumentUploadDialog(onUploaded) {
    documentUploadCallback = onUploaded;

    document.getElementById('document_upload_form').reset();
    document.getElementById('document_upload_error').classList.add('hidden');
    document.getElementById('document_upload_dialog').showModal();
}

function closeDocumentUploadDialog() {
    document.getElementById('document_upload_dialog').close();
}

function showDocumentUploadError(message) {
    const element = document.getElementById('document_upload_error');
    element.textContent = message;
    element.classList.remove('hidden');
}

async function submitDocumentUpload() {
    const file = document.getElementById('document_upload_file').files[0];
    const name = document.getElementById('document_upload_name').value.trim();
    const button = document.getElementById('document_upload_button');

    document.getElementById('document_upload_error').classList.add('hidden');

    if (!file || name === '') {
        showDocumentUploadError('Bitte Datei und Name angeben.');
        return;
    }

    const body = new FormData();
    body.append('file', file);
    body.append('name', name);

    button.disabled = true;

    try {
        const data = await apiRequest('documents.php', { method: 'POST', body });
        closeDocumentUploadDialog();
        documentUploadCallback(data.document.name);

    } catch (error) {
        showDocumentUploadError(error.message);
    } finally {
        button.disabled = false;
    }
}

// Name aus dem Dateinamen vorschlagen, ohne eine Eingabe zu überschreiben
document.getElementById('document_upload_file').addEventListener('change', event => {
    const nameInput = document.getElementById('document_upload_name');
    const file = event.target.files[0];

    if (file && nameInput.value.trim() === '') {
        nameInput.value = file.name.replace(/\.pdf$/i, '');
    }
});

document.getElementById('document_upload_form').addEventListener('submit', event => {
    event.preventDefault();
    submitDocumentUpload();
});
