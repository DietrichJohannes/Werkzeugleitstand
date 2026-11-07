<?php require_once __DIR__ . '/../../src/tool_status.php'; ?>
<dialog id="tool_info_dialog" class="tool_dialog">

    <div class="dialog-header">
        <div>
            <span class="dialog-eyebrow">Werkzeug</span>
            <h2 id="tool_name"></h2>
        </div>

        <button
            type="button"
            class="dialog-close"
            onclick="closeToolDialog()"
            aria-label="Dialog schließen"
        >
            &times;
        </button>
    </div>


    <div class="dialog-form">

        <div class="form-row">
            <label for="status_dropdown">Status</label>

            <select
                name="status_dropdown"
                id="status_dropdown"
            >
                <?php foreach (TOOL_STATUS_LABELS as $statusId => $statusLabel): ?>
                    <option value="<?= $statusId ?>"><?= htmlspecialchars($statusLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>


        <div class="form-row">
            <label for="location_dropdown">Standort</label>

            <select
                name="location_dropdown"
                id="location_dropdown"
            >
                <option value="-1">
                    Kein Standort gefunden
                </option>
            </select>
        </div>

    </div>


    <div class="dialog-section">
        <h3>Werkzeugdetails</h3>

        <table class="tool-details">
            <tbody>
                <tr>
                    <th>Verbindung</th>
                    <td id="tool_connection"></td>
                </tr>

                <tr>
                    <th>Lokring</th>
                    <td id="tool_lokring"></td>
                </tr>

                <tr>
                    <th>Backenset</th>
                    <td id="tool_backenset"></td>
                </tr>

                <tr>
                    <th>Grundkörper</th>
                    <td id="tool_body"></td>
                </tr>

                <tr>
                    <th>Letzte Reinigung</th>
                    <td id="tool_last_cleaning"></td>
                </tr>
            </tbody>
        </table>
        <br>
        <h3>Werkzeug Wartung</h3>
        <button class="button_neutral" onclick="openToolParts()">
            Material
        </button>
        <button class="button_neutral" onclick="openCleaningInstruction()">
            Reinigung
        </button>
        <button class="button_neutral" onclick="createRepair()">
            Neue Reparatur
        </button>
    </div>


    <div class="dialog-actions">
        <button
            type="button"
            class="button_negativ"
            onclick="closeToolDialog()">
            Abbrechen
        </button>

        <button
            type="button"
            class="button_positiv"
            onclick="saveToolChanges()">
            Speichern
        </button>
    </div>

</dialog>