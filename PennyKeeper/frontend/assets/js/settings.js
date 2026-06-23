/*
 * settings.js
 * Lògica de la pàgina de configuració.
 * Gestiona l'actualització de perfil i contrasenya via API.
 */

const API = '../../backend/api/controllers/SettingsController.php';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnSaveProfile').addEventListener('click', saveProfile);
    document.getElementById('btnSavePassword').addEventListener('click', savePassword);
});

// ── Perfil ───────────────────────────────────────────────────

async function saveProfile() {
    const username = document.getElementById('username').value.trim();
    const email    = document.getElementById('email').value.trim();
    const currency = document.getElementById('currency').value;
    const alertEl  = document.getElementById('profileAlert');

    clearAlert(alertEl);

    if (!username || username.length < 3) {
        showAlert(alertEl, 'El nom d\'usuari ha de tenir mínim 3 caràcters.', 'error');
        return;
    }

    if (!email) {
        showAlert(alertEl, 'L\'email és obligatori.', 'error');
        return;
    }

    const btn = document.getElementById('btnSaveProfile');
    btn.disabled = true; btn.textContent = 'Guardant...';

    const fd = new FormData();
    fd.append('username', username);
    fd.append('email', email);
    fd.append('currency', currency);

    const result = await apiPost('update_profile', fd);

    btn.disabled = false; btn.textContent = 'Guardar canvis';

    showAlert(alertEl, result.message, result.success ? 'success' : 'error');
}

// ── Contrasenya ──────────────────────────────────────────────

async function savePassword() {
    const current = document.getElementById('currentPassword').value;
    const next    = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    const alertEl = document.getElementById('passwordAlert');

    clearAlert(alertEl);

    if (!current || !next || !confirm) {
        showAlert(alertEl, 'Omple tots els camps.', 'error');
        return;
    }

    if (next.length < 8) {
        showAlert(alertEl, 'La nova contrasenya ha de tenir mínim 8 caràcters.', 'error');
        return;
    }

    if (next !== confirm) {
        showAlert(alertEl, 'Les contrasenyes no coincideixen.', 'error');
        return;
    }

    const btn = document.getElementById('btnSavePassword');
    btn.disabled = true; btn.textContent = 'Canviant...';

    const fd = new FormData();
    fd.append('currentPassword', current);
    fd.append('newPassword', next);
    fd.append('confirmPassword', confirm);

    const result = await apiPost('update_password', fd);

    btn.disabled = false; btn.textContent = 'Canviar contrasenya';

    showAlert(alertEl, result.message, result.success ? 'success' : 'error');

    if (result.success) {
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value     = '';
        document.getElementById('confirmPassword').value = '';
    }
}

// ── Helpers ──────────────────────────────────────────────────

async function apiPost(action, formData) {
    try {
        const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: formData });
        return await res.json();
    } catch {
        return { success: false, message: 'Error de connexió.' };
    }
}

function showAlert(el, msg, type) {
    el.textContent = msg;
    el.className   = `alert-pk alert-pk--${type} show`;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearAlert(el) {
    el.textContent = '';
    el.className   = 'alert-pk';
}