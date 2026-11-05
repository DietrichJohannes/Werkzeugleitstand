<!-- =========================================
     Dialoge: Ausfall melden / Reparatur erfassen
     Logik: components/report_dialog.js
     ========================================= -->

<dialog id="failure_dialog" class="tool_dialog">
    <form id="failure_form" novalidate>

        <div class="dialog-header">
            <div>
                <span class="dialog-eyebrow" id="failure_dialog_eyebrow"></span>
                <h2>Ausfall melden</h2>
            </div>
            <button type="button" class="dialog-close" data-dialog-cancel aria-label="Dialog schließen">&times;</button>
        </div>

        <div class="dialog-form">
            <p class="dialog-hint hidden" id="failure_dialog_hint"></p>
            <p class="dialog-error hidden" id="failure_dialog_error"></p>

            <div class="form-row">
                <label for="failure_cause">Ursache *</label>
                <select id="failure_cause" required></select>
            </div>

            <div class="form-row">
                <label for="failure_part">Bauteil</label>
                <select id="failure_part"></select>
            </div>

            <div class="form-row form-row-stacked">
                <label for="failure_description">Bemerkung</label>
                <textarea id="failure_description" rows="3" maxlength="500"
                          placeholder="optional, z. B. was genau aufgefallen ist"></textarea>
                <span class="char-counter" data-counter-for="failure_description"></span>
            </div>
        </div>

        <div class="dialog-actions">
            <button type="button" class="button_negativ" data-dialog-cancel>Abbrechen</button>
            <button type="submit" class="button_positiv" data-dialog-save>Speichern</button>
        </div>

    </form>
</dialog>


<dialog id="repair_dialog" class="tool_dialog">
    <form id="repair_form" novalidate>

        <div class="dialog-header">
            <div>
                <span class="dialog-eyebrow" id="repair_dialog_eyebrow"></span>
                <h2>Reparatur erfassen</h2>
            </div>
            <button type="button" class="dialog-close" data-dialog-cancel aria-label="Dialog schließen">&times;</button>
        </div>

        <div class="dialog-form">
            <p class="dialog-hint" id="repair_dialog_hint"></p>
            <p class="dialog-error hidden" id="repair_dialog_error"></p>

            <div class="form-row">
                <label for="repair_failure">Zu Ausfall</label>
                <select id="repair_failure"></select>
            </div>

            <div class="form-row form-row-stacked">
                <span class="form-label">Getauschte Bauteile</span>
                <div class="checkbox-list" id="repair_parts"></div>
            </div>

            <div class="form-row form-row-stacked">
                <label for="repair_description">Bemerkung</label>
                <textarea id="repair_description" rows="3" maxlength="500"
                          placeholder="optional, z. B. was gemacht wurde"></textarea>
                <span class="char-counter" data-counter-for="repair_description"></span>
            </div>
        </div>

        <div class="dialog-actions">
            <button type="button" class="button_negativ" data-dialog-cancel>Abbrechen</button>
            <button type="submit" class="button_positiv" data-dialog-save>Speichern</button>
        </div>

    </form>
</dialog>
