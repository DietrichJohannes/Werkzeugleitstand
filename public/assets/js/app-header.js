// =========================================
// Globale Kopfzeile (/components/app_header.php)
// =========================================
// - markiert den aktiven Menüpunkt
// - zeigt den angemeldeten Benutzer
// - blendet Elemente mit data-permission="..." aus
//   (nur Anzeige – geprüft wird immer auf dem Server)

(function () {
    const nav = document.querySelector('.app-nav');

    // ---------- Aktiver Menüpunkt ----------

    function markCurrentPage() {
        const path = window.location.pathname;

        nav.querySelectorAll('a').forEach(link => {
            if (path.startsWith(new URL(link.href).pathname)) {
                link.setAttribute('aria-current', 'page');
                link.closest('.app-nav-menu')?.classList.add('is-current');
            }
        });
    }

    // ---------- Untermenü schließen ----------

    function initMenus() {
        const menus = document.querySelectorAll('.app-nav-menu');

        // Klick außerhalb oder Escape schließt offene Untermenüs
        document.addEventListener('click', event => {
            menus.forEach(menu => {
                if (!menu.contains(event.target)) menu.open = false;
            });
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                menus.forEach(menu => { menu.open = false; });
            }
        });
    }

    // ---------- Benutzer + Rechte ----------

    async function applyPermissions() {
        try {
            const response = await fetch('/api/me.php', { cache: 'no-store' });

            // Bei 401/403 gibt es keine Rechte -> alles ausblenden
            const data = response.ok ? await response.json() : {};
            const allowed = new Set(data.permissions ?? []);

            renderUser(data.user ?? null);

            document.querySelectorAll('[data-permission]').forEach(element => {
                element.hidden = !allowed.has(element.dataset.permission);
            });
        } catch (error) {
            // Netzwerkfehler: aktuellen Zustand beibehalten
            console.error('Berechtigungen konnten nicht geladen werden:', error);
        }
    }

    function renderUser(user) {
        const box = document.getElementById('app_user');

        if (!user || !user.name) {
            box.hidden = true;
            return;
        }

        document.getElementById('app_user_name').textContent = user.name;
        document.getElementById('app_user_role').textContent = user.role_label ?? user.role ?? '';
        document.getElementById('app_user_avatar').textContent = getInitials(user.name);
        box.title = `Angemeldet als ${user.name}`;
        box.hidden = false;
    }

    /*
     * "Johannes Dietrich"  → "JD"
     * "Dietrich, Johannes" → "JD"  (AD-Schreibweise "Nachname, Vorname")
     * "jdietrich"          → "JD"  (nur Anmeldename: erste zwei Buchstaben)
     */
    function getInitials(name) {
        let parts = String(name).trim().split(/\s+/);

        if (name.includes(',')) {
            const [last, first] = name.split(',', 2).map(part => part.trim());
            parts = [first, last].filter(Boolean).join(' ').split(/\s+/);
        }

        parts = parts.filter(Boolean);

        if (parts.length === 0) {
            return '?';
        }

        const initials = parts.length === 1
            ? parts[0].slice(0, 2)
            : parts[0][0] + parts[parts.length - 1][0];

        return initials.toUpperCase();
    }

    // ---------- Start ----------

    markCurrentPage();
    initMenus();
    applyPermissions();

    // Zurück-Button (bfcache): Rechte könnten sich geändert haben
    window.addEventListener('pageshow', event => {
        if (event.persisted) applyPermissions();
    });
})();
