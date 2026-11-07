<!-- =========================================
     Dialog: PDF hochladen
     Logik: components/document_upload_dialog.js
     ========================================= -->

<dialog id="document_upload_dialog" class="tool_dialog">
    <form id="document_upload_form" novalidate>

        <div class="dialog-header">
            <div>
                <span class="dialog-eyebrow">Dokumente</span>
                <h2>PDF hochladen</h2>
            </div>
            <button type="button" class="dialog-close" onclick="closeDocumentUploadDialog()" aria-label="Dialog schließen">&times;</button>
        </div>

        <div class="dialog-form">
            <p class="dialog-error hidden" id="document_upload_error"></p>

            <div class="form-row">
                <label for="document_upload_file">Datei *</label>
                <input type="file" id="document_upload_file" accept=".pdf,application/pdf" required>
            </div>

            <div class="form-row">
                <label for="document_upload_name">Name *</label>
                <input type="text" id="document_upload_name" maxlength="100" required>
            </div>
        </div>

        <div class="dialog-actions">
            <button type="button" class="button_negativ" onclick="closeDocumentUploadDialog()">Abbrechen</button>
            <button type="submit" class="button_positiv" id="document_upload_button">Hochladen</button>
        </div>

    </form>
</dialog>
