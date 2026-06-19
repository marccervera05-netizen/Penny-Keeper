/*
 * auth.js
 * Lògica de client per a les pàgines de login i registre.
 * Detecta quina pàgina és activa i actua en conseqüència.
 */

document.addEventListener('DOMContentLoaded', () => {
    const isRegister = document.getElementById('registerBtn') !== null;

    if (isRegister) {
        initRegister();
    } else {
        initLogin();
    }

    initPasswordToggle();
});

// ── Login ────────────────────────────────────────────────────

function initLogin() {
    const btn      = document.getElementById('loginBtn');
    const emailEl  = document.getElementById('email');
    const passEl   = document.getElementById('password');
    const alertEl  = document.getElementById('loginAlert');

    btn.addEventListener('click', async () => {
        clearAlert(alertEl);

        const email    = emailEl.value.trim();
        const password = passEl.value;

        if (!email || !password) {
            showAlert(alertEl, 'Omple tots els camps.', 'error');
            return;
        }

        setLoading(btn, true);

        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        const result = await postToApi('login', formData);

        setLoading(btn, false);

        if (result.success) {
            window.location.href = result.redirect;
        } else {
            showAlert(alertEl, result.message, 'error');
            emailEl.classList.add('is-error');
            passEl.classList.add('is-error');
        }
    });

    // Neteja error quan l'usuari torna a escriure
    [emailEl, passEl].forEach(el => {
        el.addEventListener('input', () => {
            el.classList.remove('is-error');
            clearAlert(alertEl);
        });
    });

    // Permet enviar amb Enter
    [emailEl, passEl].forEach(el => {
        el.addEventListener('keydown', e => {
            if (e.key === 'Enter') btn.click();
        });
    });
}

// ── Registre ─────────────────────────────────────────────────

function initRegister() {
    const btn        = document.getElementById('registerBtn');
    const usernameEl = document.getElementById('username');
    const emailEl    = document.getElementById('email');
    const passEl     = document.getElementById('password');
    const confirmEl  = document.getElementById('passwordConfirm');
    const alertEl    = document.getElementById('registerAlert');

    btn.addEventListener('click', async () => {
        clearAlert(alertEl);

        const username        = usernameEl.value.trim();
        const email           = emailEl.value.trim();
        const password        = passEl.value;
        const passwordConfirm = confirmEl.value;

        const error = validateRegisterForm(username, email, password, passwordConfirm);
        if (error) {
            showAlert(alertEl, error, 'error');
            return;
        }

        setLoading(btn, true);

        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('passwordConfirm', passwordConfirm);

        const result = await postToApi('register', formData);

        setLoading(btn, false);

        if (result.success) {
            showAlert(alertEl, result.message, 'success');
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 800);
        } else {
            showAlert(alertEl, result.message, 'error');
        }
    });

    confirmEl.addEventListener('keydown', e => {
        if (e.key === 'Enter') btn.click();
    });
}

function validateRegisterForm(username, email, password, passwordConfirm) {
    if (!username || !email || !password || !passwordConfirm) {
        return 'Omple tots els camps.';
    }
    if (username.length < 3) {
        return 'El nom d\'usuari ha de tenir mínim 3 caràcters.';
    }
    if (!email.includes('@')) {
        return 'L\'email no és vàlid.';
    }
    if (password.length < 8) {
        return 'La contrasenya ha de tenir mínim 8 caràcters.';
    }
    if (password !== passwordConfirm) {
        return 'Les contrasenyes no coincideixen.';
    }
    return null;
}

// ── Toggle contrasenya ───────────────────────────────────────

function initPasswordToggle() {
    const btn     = document.getElementById('togglePassword');
    const passEl  = document.getElementById('password');
    const iconEl  = document.getElementById('toggleIcon');

    if (!btn) return;

    btn.addEventListener('click', () => {
        const isHidden = passEl.type === 'password';
        passEl.type    = isHidden ? 'text' : 'password';
        iconEl.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}

// ── Helpers ──────────────────────────────────────────────────

async function postToApi(action, formData) {
    try {
        const response = await fetch(
            `../../backend/api/controllers/AuthController.php?action=${action}`,
            { method: 'POST', body: formData }
        );
        return await response.json();
    } catch {
        return { success: false, message: 'Error de connexió. Torna-ho a intentar.' };
    }
}

function showAlert(el, message, type) {
    el.textContent = message;
    el.className   = `alert-pk alert-pk--${type} show`;
}

function clearAlert(el) {
    el.textContent = '';
    el.className   = 'alert-pk';
}

function setLoading(btn, loading) {
    btn.disabled    = loading;
    btn.textContent = loading ? 'Espera...' : btn.dataset.label || btn.textContent;
    if (!btn.dataset.label && !loading) {
        btn.textContent = btn.id === 'loginBtn' ? 'Inicia sessió' : 'Crea el compte';
    }
}