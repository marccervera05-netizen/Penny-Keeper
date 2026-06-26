/*
 * savings.js
 * Lògica de la pàgina de plans d'estalvi.
 * Gestiona els modals de crear, editar, afegir aportació i eliminar.
 */

const API = `${API_BASE}/backend/api/controllers/SavingController.php`;

document.addEventListener('DOMContentLoaded', () => {
    initNewPlanButtons();
    initEditPlanButtons();
    initDeletePlanButtons();
    initContributeButtons();
    initDeleteContributionButtons();
    initModalClose();
});

// ── Botons d'obertura ────────────────────────────────────────

function initNewPlanButtons() {
    document.getElementById('btnNewPlan')?.addEventListener('click', () => openPlanModal(null));
    document.getElementById('btnNewPlanEmpty')?.addEventListener('click', () => openPlanModal(null));
}

function initEditPlanButtons() {
    document.querySelectorAll('.btn-edit-plan').forEach(btn => {
        btn.addEventListener('click', () => {
            openPlanModal({
                id:       btn.dataset.id,
                name:     btn.dataset.name,
                target:   btn.dataset.target,
                deadline: btn.dataset.deadline,
                notes:    btn.dataset.notes,
            });
        });
    });
}

function initDeletePlanButtons() {
    document.querySelectorAll('.btn-delete-plan').forEach(btn => {
        btn.addEventListener('click', () => {
            openDeleteModal(btn.dataset.id, btn.dataset.name);
        });
    });
}

function initContributeButtons() {
    document.querySelectorAll('.btn-contribute').forEach(btn => {
        btn.addEventListener('click', () => {
            openContributeModal(btn.dataset.id, btn.dataset.name);
        });
    });
}

function initDeleteContributionButtons() {
    document.querySelectorAll('.btn-delete-contribution').forEach(btn => {
        btn.addEventListener('click', () => {
            submitDeleteContribution(btn.dataset.id, btn);
        });
    });
}

// ── Modal: crear / editar pla ────────────────────────────────

function openPlanModal(data) {
    const isEdit = data !== null;
    const title  = isEdit ? 'Editar pla' : 'Nou pla d\'estalvi';

    setModal(title, buildPlanForm(data));
    openModal();

    document.getElementById('planSubmit').addEventListener('click', () => {
        submitPlan(isEdit ? data.id : null);
    });
}

function buildPlanForm(data) {
    return `
        <div class="form-group">
            <label class="form-label" for="planName">Nom del pla</label>
            <input class="form-input" type="text" id="planName"
                   placeholder="Ex: Vacances, Cotxe nou, Fons d'emergència..."
                   maxlength="100" value="${escHtml(data?.name ?? '')}">
        </div>

        <div class="form-group">
            <label class="form-label" for="planTarget">Objectiu (€)</label>
            <input class="form-input" type="number" id="planTarget"
                   placeholder="0,00" min="0.01" step="0.01"
                   value="${data?.target ?? ''}">
        </div>

        <div class="form-group">
            <label class="form-label" for="planDeadline">Data límit (opcional)</label>
            <input class="form-input" type="date" id="planDeadline"
                   value="${data?.deadline ?? ''}">
        </div>

        <div class="form-group">
            <label class="form-label" for="planNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="planNotes"
                   placeholder="Per a què és aquest estalvi?"
                   value="${escHtml(data?.notes ?? '')}">
        </div>

        <div id="planAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="planSubmit" style="width:100%; padding:0.75rem;">
            ${data ? 'Guardar canvis' : 'Crear pla'}
        </button>
    `;
}

async function submitPlan(editId) {
    const name     = document.getElementById('planName').value.trim();
    const target   = document.getElementById('planTarget').value;
    const deadline = document.getElementById('planDeadline').value;
    const notes    = document.getElementById('planNotes').value.trim();
    const alertEl  = document.getElementById('planAlert');

    clearAlert(alertEl);

    if (!name)                               { showAlert(alertEl, 'El nom és obligatori.', 'error'); return; }
    if (!target || parseFloat(target) <= 0) { showAlert(alertEl, 'L\'objectiu ha de ser positiu.', 'error'); return; }

    const btn = document.getElementById('planSubmit');
    btn.disabled = true; btn.textContent = 'Guardant...';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('targetAmount', target);
    formData.append('deadline', deadline);
    formData.append('notes', notes);
    if (editId) formData.append('id', editId);

    const action = editId ? 'update' : 'create';
    const result = await apiPost(action, formData);

    if (result.success) {
        closeModal();
        window.location.reload();
    } else {
        showAlert(alertEl, result.message, 'error');
        btn.disabled = false;
        btn.textContent = editId ? 'Guardar canvis' : 'Crear pla';
    }
}

// ── Modal: afegir aportació ──────────────────────────────────

function openContributeModal(planId, planName) {
    setModal(`Aportar a "${escHtml(planName)}"`, buildContributeForm(planId));
    openModal();

    document.getElementById('contribSubmit').addEventListener('click', () => {
        submitContribution(planId);
    });
}

function buildContributeForm(planId) {
    return `
        <div class="form-group">
            <label class="form-label" for="contribAmount">Import (€)</label>
            <input class="form-input" type="number" id="contribAmount"
                   placeholder="0,00" min="0.01" step="0.01" autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="contribDate">Data</label>
            <input class="form-input" type="date" id="contribDate" value="${todayISO()}">
        </div>

        <div class="form-group">
            <label class="form-label" for="contribNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="contribNotes"
                   placeholder="Ex: Nòmina de juny...">
        </div>

        <div id="contribAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="contribSubmit" style="width:100%; padding:0.75rem;">
            Afegir aportació
        </button>
    `;
}

async function submitContribution(planId) {
    const amount  = document.getElementById('contribAmount').value;
    const date    = document.getElementById('contribDate').value;
    const notes   = document.getElementById('contribNotes').value.trim();
    const alertEl = document.getElementById('contribAlert');

    clearAlert(alertEl);

    if (!amount || parseFloat(amount) <= 0) { showAlert(alertEl, 'L\'import ha de ser positiu.', 'error'); return; }
    if (!date)                               { showAlert(alertEl, 'La data és obligatòria.', 'error'); return; }

    const btn = document.getElementById('contribSubmit');
    btn.disabled = true; btn.textContent = 'Afegint...';

    const formData = new FormData();
    formData.append('planId', planId);
    formData.append('amount', amount);
    formData.append('date', date);
    formData.append('notes', notes);

    const result = await apiPost('add_contribution', formData);

    if (result.success) {
        closeModal();
        window.location.reload();
    } else {
        showAlert(alertEl, result.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Afegir aportació';
    }
}

// ── Modal: eliminar pla ──────────────────────────────────────

function openDeleteModal(planId, planName) {
    setModal('Eliminar pla', `
        <div class="delete-confirm">
            <p>Estàs a punt d'eliminar el pla <strong>${escHtml(planName)}</strong> i totes les seves aportacions. Aquesta acció no es pot desfer.</p>
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

        const formData = new FormData();
        formData.append('id', planId);

        const result = await apiPost('delete', formData);

        if (result.success) {
            closeModal();
            window.location.reload();
        } else {
            showAlert(document.getElementById('deleteAlert'), result.message, 'error');
            btn.disabled = false; btn.textContent = 'Eliminar';
        }
    });
}

// ── Eliminar aportació (inline, sense modal) ─────────────────

async function submitDeleteContribution(id, btn) {
    if (!confirm('Eliminar aquesta aportació?')) return;

    const formData = new FormData();
    formData.append('id', id);

    const result = await apiPost('delete_contribution', formData);

    if (result.success) {
        window.location.reload();
    } else {
        alert(result.message);
    }
}

// ── Helpers modal ────────────────────────────────────────────

function setModal(title, bodyHtml) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML    = bodyHtml;
}

function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
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

// ── Utils ────────────────────────────────────────────────────

async function apiPost(action, formData) {
    try {
        const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: formData });
        return await res.json();
    } catch {
        return { success: false, message: 'Error de connexió.' };
    }
}

function todayISO() {
    return new Date().toISOString().split('T')[0];
}

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

function setLoading(btn, loading, label) {
    btn.disabled    = loading;
    btn.textContent = loading ? 'Espera...' : label;
}