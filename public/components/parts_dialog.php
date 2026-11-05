<!-- =========================================
     Dialog: Ersatzteile eines Werkzeugs
     Logik: components/parts_dialog.js
     ========================================= -->

<dialog id="parts_dialog" class="tool_dialog parts-dialog">

    <div class="dialog-header">
        <div>
            <span class="dialog-eyebrow" id="parts_dialog_eyebrow"></span>
            <h2>Ersatzteile</h2>
        </div>

        <button type="button" class="dialog-close" data-parts-close aria-label="Dialog schließen">&times;</button>
    </div>

    <p class="dialog-error hidden" id="parts_dialog_error"></p>

    <div class="parts-table-wrapper">
        <table class="parts-table">
            <thead>
                <tr>
                    <th>Bauteil</th>
                    <th>HiBe-Nr.</th>
                    <th>Hersteller-Nr.</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="parts_table_body"></tbody>
        </table>
    </div>

    <div class="dialog-actions">
        <button type="button" class="button_neutral" data-parts-close>Schließen</button>
    </div>

</dialog>
