/*
 * invest.js
 * Lògica de la pàgina d'inversions redissenyada.
 * Gestiona: crear, editar, eliminar, afegir aportació, actualitzar valor de mercat.
 */

const API = `${API_BASE}/backend/api/controllers/InvestmentController.php`;

const TYPES = [
    { value: 'stocks',      label: 'Borsa' },
    { value: 'crypto',      label: 'Criptomonedes' },
    { value: 'funds',       label: 'Fons d\'inversió' },
    { value: 'real_estate', label: 'Immobles' },
    { value: 'other',       label: 'Altres' },
];

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnNewInvest')?.addEventListener('click', () => openCreateModal());
    document.getElementById('btnNewInvestEmpty')?.addEventListener('click', () => openCreateModal());

    document.querySelectorAll('.btn-edit-invest').forEach(btn => {
        btn.addEventListener('click', () => openEditModal({
            id:        btn.dataset.id,
            name:      btn.dataset.name,
            type:      btn.dataset.type,
            recurring: btn.dataset.recurring === '1',
            notes:     btn.dataset.notes,
        }));
    });

    document.querySelectorAll('.btn-delete-invest').forEach(btn => {
        btn.addEventListener('click', () => openDeleteModal(btn.dataset.id, btn.dataset.name));
    });

    document.querySelectorAll('.btn-contribute-invest').forEach(btn => {
        btn.addEventListener('click', () => openContributeModal(btn.dataset.id, btn.dataset.name));
    });

    document.querySelectorAll('.btn-update-value').forEach(btn => {
        btn.addEventListener('click', () => openUpdateValueModal(btn.dataset.id, btn.dataset.name, btn.dataset.current));
    });

    document.querySelectorAll('.btn-delete-contribution').forEach(btn => {
        btn.addEventListener('click', () => deleteContribution(btn.dataset.id));
    });

    initModalClose();
});

// ── Modal: crear inversió ────────────────────────────────────

function openCreateModal() {
    setModal('Nova inversió', buildCreateForm());
    openModal();
    document.getElementById('investSubmit').addEventListener('click', () => submitCreate());
}

function buildCreateForm() {
    const typeOptions = TYPES.map(t =>
        `<option value="${t.value}">${t.label}</option>`
    ).join('');

    return `
        <div class="form-group">
            <label class="form-label" for="investName">Nom</label>
            <input class="form-input" type="text" id="investName"
                   placeholder="Ex: Apple Inc., Bitcoin, Fons Indexat..." maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label" for="investType">Tipus</label>
            <select class="form-select" id="investType">${typeOptions}</select>
        </div>

        <div class="form-group">
            <label class="form-label" for="investInitial">Aportació inicial (€)</label>
            <input class="form-input" type="number" id="investInitial"
                   placeholder="0,00" min="0" step="0.01">
        </div>

        <div class="form-group">
            <label class="form-label" for="investDate">Data d'inici</label>
            <input class="form-input" type="date" id="investDate" value="${todayISO()}">
        </div>

        <div class="form-group">
            <label class="form-label" for="investNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="investNotes"
                   placeholder="Broker, plataforma...">
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="investRecurring" style="width:16px;height:16px;cursor:pointer;">
            <label for="investRecurring" style="font-size:0.88rem; color:var(--color-text-muted); cursor:pointer;">
                Faig aportacions periòdiques a aquesta inversió
            </label>
        </div>

        <div id="investAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="investSubmit" style="width:100%; padding:0.75rem;">
            Crear inversió
        </button>
    `;
}

async function submitCreate() {
    const name      = document.getElementById('investName').value.trim();
    const type      = document.getElementById('investType').value;
    const initial   = document.getElementById('investInitial').value || '0';
    const date      = document.getElementById('investDate').value;
    const notes     = document.getElementById('investNotes').value.trim();
    const recurring = document.getElementById('investRecurring').checked;
    const alertEl   = document.getElementById('investAlert');

    clearAlert(alertEl);
    if (!name) { showAlert(alertEl, 'El nom és obligatori.', 'error'); return; }
    if (!date) { showAlert(alertEl, 'La data d\'inici és obligatòria.', 'error'); return; }

    const btn = document.getElementById('investSubmit');
    btn.disabled = true; btn.textContent = 'Creant...';

    const fd = new FormData();
    fd.append('name', name);
    fd.append('type', type);
    fd.append('initialAmount', initial);
    fd.append('startDate', date);
    fd.append('isRecurring', recurring ? '1' : '0');
    fd.append('notes', notes);

    const result = await apiPost('create', fd);
    if (result.success) { closeModal(); window.location.reload(); }
    else {
        showAlert(alertEl, result.message, 'error');
        btn.disabled = false; btn.textContent = 'Crear inversió';
    }
}

// ── Modal: editar inversió ───────────────────────────────────

function openEditModal(data) {
    setModal('Editar inversió', buildEditForm(data));
    openModal();
    document.getElementById('investSubmit').addEventListener('click', () => submitEdit(data.id));
}

function buildEditForm(data) {
    const typeOptions = TYPES.map(t =>
        `<option value="${t.value}" ${t.value === data.type ? 'selected' : ''}>${t.label}</option>`
    ).join('');

    return `
        <div class="form-group">
            <label class="form-label" for="investName">Nom</label>
            <input class="form-input" type="text" id="investName"
                   maxlength="100" value="${escHtml(data.name)}">
        </div>

        <div class="form-group">
            <label class="form-label" for="investType">Tipus</label>
            <select class="form-select" id="investType">${typeOptions}</select>
        </div>

        <div class="form-group">
            <label class="form-label" for="investNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="investNotes"
                   placeholder="Broker, plataforma..." value="${escHtml(data.notes)}">
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="investRecurring" style="width:16px;height:16px;cursor:pointer;"
                   ${data.recurring ? 'checked' : ''}>
            <label for="investRecurring" style="font-size:0.88rem; color:var(--color-text-muted); cursor:pointer;">
                Faig aportacions periòdiques a aquesta inversió
            </label>
        </div>

        <div id="investAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="investSubmit" style="width:100%; padding:0.75rem;">
            Guardar canvis
        </button>
    `;
}

async function submitEdit(id) {
    const name      = document.getElementById('investName').value.trim();
    const type      = document.getElementById('investType').value;
    const notes     = document.getElementById('investNotes').value.trim();
    const recurring = document.getElementById('investRecurring').checked;
    const alertEl   = document.getElementById('investAlert');

    clearAlert(alertEl);
    if (!name) { showAlert(alertEl, 'El nom és obligatori.', 'error'); return; }

    const btn = document.getElementById('investSubmit');
    btn.disabled = true; btn.textContent = 'Guardant...';

    const fd = new FormData();
    fd.append('id', id);
    fd.append('name', name);
    fd.append('type', type);
    fd.append('isRecurring', recurring ? '1' : '0');
    fd.append('notes', notes);

    const result = await apiPost('update', fd);
    if (result.success) { closeModal(); window.location.reload(); }
    else {
        showAlert(alertEl, result.message, 'error');
        btn.disabled = false; btn.textContent = 'Guardar canvis';
    }
}

// ── Modal: afegir aportació ──────────────────────────────────

function openContributeModal(investmentId, name) {
    setModal(`Aportació a "${escHtml(name)}"`, `
        <div class="form-group">
            <label class="form-label" for="contribAmount">Import aportat (€)</label>
            <input class="form-input" type="number" id="contribAmount"
                   placeholder="0,00" min="0.01" step="0.01">
        </div>
        <div class="form-group">
            <label class="form-label" for="contribDate">Data</label>
            <input class="form-input" type="date" id="contribDate" value="${todayISO()}">
        </div>
        <div class="form-group">
            <label class="form-label" for="contribNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="contribNotes"
                   placeholder="Ex: Aportació mensual de juny...">
        </div>
        <div id="contribAlert" class="alert-pk" role="alert"></div>
        <button class="btn-primary-pk" id="contribSubmit" style="width:100%; padding:0.75rem;">
            Afegir aportació
        </button>
    `);
    openModal();

    document.getElementById('contribSubmit').addEventListener('click', async () => {
        const amount  = document.getElementById('contribAmount').value;
        const date    = document.getElementById('contribDate').value;
        const notes   = document.getElementById('contribNotes').value.trim();
        const alertEl = document.getElementById('contribAlert');

        clearAlert(alertEl);
        if (!amount || parseFloat(amount) <= 0) { showAlert(alertEl, 'L\'import ha de ser positiu.', 'error'); return; }
        if (!date) { showAlert(alertEl, 'La data és obligatòria.', 'error'); return; }

        const btn = document.getElementById('contribSubmit');
        btn.disabled = true; btn.textContent = 'Afegint...';

        const fd = new FormData();
        fd.append('investmentId', investmentId);
        fd.append('amount', amount);
        fd.append('date', date);
        fd.append('notes', notes);

        const result = await apiPost('add_contribution', fd);
        if (result.success) { closeModal(); window.location.reload(); }
        else {
            showAlert(alertEl, result.message, 'error');
            btn.disabled = false; btn.textContent = 'Afegir aportació';
        }
    });
}

// ── Modal: actualitzar valor de mercat ───────────────────────

function openUpdateValueModal(investmentId, name, currentValue) {
    setModal(`Valor actual de "${escHtml(name)}"`, `
        <p style="font-size:0.88rem; color:var(--color-text-muted); margin-bottom:0.25rem;">
            Introdueix el valor actual que veus al teu broker o exchange. No afegeix diners a la inversió, només registra el valor de mercat.
        </p>
        <div class="form-group">
            <label class="form-label" for="marketValue">Valor de mercat actual (€)</label>
            <input class="form-input" type="number" id="marketValue"
                   placeholder="0,00" min="0" step="0.01"
                   value="${currentValue}">
        </div>
        <div class="form-group">
            <label class="form-label" for="marketDate">Data de consulta</label>
            <input class="form-input" type="date" id="marketDate" value="${todayISO()}">
        </div>
        <div id="marketAlert" class="alert-pk" role="alert"></div>
        <button class="btn-primary-pk" id="marketSubmit" style="width:100%; padding:0.75rem;">
            Actualitzar valor
        </button>
    `);
    openModal();

    document.getElementById('marketSubmit').addEventListener('click', async () => {
        const value   = document.getElementById('marketValue').value;
        const date    = document.getElementById('marketDate').value;
        const alertEl = document.getElementById('marketAlert');

        clearAlert(alertEl);
        if (value === '' || parseFloat(value) < 0) { showAlert(alertEl, 'El valor no pot ser negatiu.', 'error'); return; }
        if (!date) { showAlert(alertEl, 'La data és obligatòria.', 'error'); return; }

        const btn = document.getElementById('marketSubmit');
        btn.disabled = true; btn.textContent = 'Actualitzant...';

        const fd = new FormData();
        fd.append('investmentId', investmentId);
        fd.append('value', value);
        fd.append('date', date);

        const result = await apiPost('update_value', fd);
        if (result.success) { closeModal(); window.location.reload(); }
        else {
            showAlert(alertEl, result.message, 'error');
            btn.disabled = false; btn.textContent = 'Actualitzar valor';
        }
    });
}

// ── Eliminar inversió ────────────────────────────────────────

function openDeleteModal(id, name) {
    setModal('Eliminar inversió', `
        <div class="delete-confirm">
            <p>Estàs a punt d'eliminar <strong>${escHtml(name)}</strong> i tot l'historial d'aportacions. Aquesta acció no es pot desfer.</p>
            <div id="deleteAlert" class="alert-pk" role="alert"></div>
            <div class="delete-actions">
                <button class="btn-outline-pk" id="btnCancelDelete">Cancel·lar</button>
                <button class="btn-danger-pk" id="btnConfirmDelete">Eliminar</button>
            </div>
        </div>
    `);
    openModal();

    document.getElementById('btnCancelDelete').addEventListener('click', closeModal);
    document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
        const btn = document.getElementById('btnConfirmDelete');
        btn.disabled = true; btn.textContent = 'Eliminant...';

        const fd = new FormData();
        fd.append('id', id);

        const result = await apiPost('delete', fd);
        if (result.success) { closeModal(); window.location.reload(); }
        else {
            showAlert(document.getElementById('deleteAlert'), result.message, 'error');
            btn.disabled = false; btn.textContent = 'Eliminar';
        }
    });
}

// ── Eliminar aportació ───────────────────────────────────────

async function deleteContribution(id) {
    if (!confirm('Eliminar aquesta aportació?')) return;

    const fd = new FormData();
    fd.append('id', id);

    const result = await apiPost('delete_contribution', fd);
    if (result.success) { window.location.reload(); }
    else { alert(result.message); }
}

// ── Helpers ──────────────────────────────────────────────────

function setModal(title, bodyHtml) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML    = bodyHtml;
}

function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
    setTimeout(() => document.querySelector('#modalBody input')?.focus(), 50);
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
}

function initModalClose() {
    document.getElementById('modalClose').addEventListener('click', closeModal);
    document.getElementById('modalOverlay').addEventListener('click', e => {
        if (e.target === document.getElementById('modalOverlay')) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
}

async function apiPost(action, formData) {
    try {
        const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: formData });
        return await res.json();
    } catch {
        return { success: false, message: 'Error de connexió.' };
    }
}

function todayISO() { return new Date().toISOString().split('T')[0]; }

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = String(str ?? '');
    return div.innerHTML;
}

function showAlert(el, msg, type) {
    el.textContent = msg;
    el.className   = `alert-pk alert-pk--${type} show`;
}

function clearAlert(el) {
    el.textContent = '';
    el.className   = 'alert-pk';
}