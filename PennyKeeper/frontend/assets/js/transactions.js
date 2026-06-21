/*
 * transactions.js
 * Lògica compartida per a les pàgines d'ingressos i despeses.
 * Gestiona el modal de crear/editar i la confirmació d'eliminar.
 *
 * Espera que el PHP hagi definit:
 *   PAGE_TYPE   → 'income' | 'expense'
 *   CATEGORIES  → array de categories de l'usuari
 *   CURRENT_URL → query string del mes/any actual
 */

document.addEventListener('DOMContentLoaded', () => {
    initAddButtons();
    initEditButtons();
    initDeleteButtons();
    initModalClose();
});

// ── Obertura del modal ───────────────────────────────────────

function initAddButtons() {
    document.getElementById('btnAdd')?.addEventListener('click', () => openCreateModal());
    document.getElementById('btnAddEmpty')?.addEventListener('click', () => openCreateModal());
}

function initEditButtons() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = {
                id:          btn.dataset.id,
                description: btn.dataset.description,
                amount:      btn.dataset.amount,
                date:        btn.dataset.date,
                notes:       btn.dataset.notes,
                isRecurring: btn.dataset.recurring === '1',
                categoryId:  btn.dataset.category,
            };
            openEditModal(data);
        });
    });
}

function initDeleteButtons() {
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            openDeleteModal(btn.dataset.id, btn.dataset.description);
        });
    });
}

// ── Modals ───────────────────────────────────────────────────

function openCreateModal() {
    const label = PAGE_TYPE === 'income' ? 'Nou ingrés' : 'Nova despesa';
    setModal(label, buildForm(null));
    setupFormSubmit(null);
    openModal();
}

function openEditModal(data) {
    const label = PAGE_TYPE === 'income' ? 'Editar ingrés' : 'Editar despesa';
    setModal(label, buildForm(data));
    setupFormSubmit(data.id);
    openModal();
}

function openDeleteModal(id, description) {
    const body = `
        <div class="delete-confirm">
            <p>Estàs a punt d'eliminar <strong>${escHtml(description)}</strong>. Aquesta acció no es pot desfer.</p>
            <div id="deleteAlert" class="alert-pk" role="alert"></div>
            <div class="delete-actions">
                <button class="btn-outline-pk" id="btnCancelDelete">Cancel·lar</button>
                <button class="btn-danger-pk" id="btnConfirmDelete">Eliminar</button>
            </div>
        </div>
    `;

    setModal('Confirmar eliminació', body);
    openModal();

    document.getElementById('btnCancelDelete').addEventListener('click', closeModal);
    document.getElementById('btnConfirmDelete').addEventListener('click', () => {
        submitDelete(id);
    });
}

// ── Formulari ────────────────────────────────────────────────

function buildForm(data) {
    const recurringLabel = PAGE_TYPE === 'income' ? 'Ingrés recurrent mensual' : 'Despesa recurrent mensual';
    const descPlaceholder = PAGE_TYPE === 'income'
        ? 'Ex: Salari, Freelance...'
        : 'Ex: Supermercat, Netflix...';

    const categoryOptions = CATEGORIES.map(cat => {
        const selected = data && String(data.categoryId) === String(cat.id) ? 'selected' : '';
        const icon = cat.icon ? `${cat.icon} ` : '';
        return `<option value="${cat.id}" ${selected}>${escHtml(cat.name)}</option>`;
    }).join('');

    return `
        <div class="form-group">
            <label class="form-label" for="txDescription">Descripció</label>
            <input class="form-input" type="text" id="txDescription"
                   placeholder="${descPlaceholder}" maxlength="150"
                   value="${escHtml(data?.description ?? '')}">
        </div>

        <div class="form-group">
            <label class="form-label" for="txAmount">Import (€)</label>
            <input class="form-input" type="number" id="txAmount"
                   placeholder="0,00" min="0.01" step="0.01"
                   value="${data?.amount ?? ''}">
        </div>

        <div class="form-group">
            <label class="form-label" for="txDate">Data</label>
            <input class="form-input" type="date" id="txDate"
                   value="${data?.date ?? todayISO()}">
        </div>

        <div class="form-group">
            <label class="form-label" for="txCategory">Categoria</label>
            <select class="form-select" id="txCategory">
                <option value="">Sense categoria</option>
                ${categoryOptions}
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="txNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="txNotes"
                   placeholder="Afegeix una nota..." maxlength="200"
                   value="${escHtml(data?.notes ?? '')}">
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="txRecurring" style="width:16px;height:16px;cursor:pointer;"
                   ${data?.isRecurring ? 'checked' : ''}>
            <label for="txRecurring" style="font-size:0.88rem; color:var(--color-text-muted); cursor:pointer;">
                ${recurringLabel}
            </label>
        </div>

        <div id="txAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="txSubmit" style="width:100%; padding:0.75rem;">
            Guardar
        </button>
    `;
}

function setupFormSubmit(editId) {
    document.getElementById('txSubmit').addEventListener('click', () => {
        submitForm(editId);
    });
}

async function submitForm(editId) {
    const description = document.getElementById('txDescription').value.trim();
    const amount      = document.getElementById('txAmount').value;
    const date        = document.getElementById('txDate').value;
    const categoryId  = document.getElementById('txCategory').value;
    const notes       = document.getElementById('txNotes').value.trim();
    const isRecurring = document.getElementById('txRecurring').checked;
    const alertEl     = document.getElementById('txAlert');

    clearAlert(alertEl);

    if (!description) { showAlert(alertEl, 'La descripció és obligatòria.', 'error'); return; }
    if (!amount || parseFloat(amount) <= 0) { showAlert(alertEl, 'L\'import ha de ser major que 0.', 'error'); return; }
    if (!date) { showAlert(alertEl, 'La data és obligatòria.', 'error'); return; }

    const submitBtn = document.getElementById('txSubmit');
    submitBtn.disabled    = true;
    submitBtn.textContent = 'Guardant...';

    const formData = new FormData();
    formData.append('description', description);
    formData.append('amount', amount);
    formData.append('date', date);
    formData.append('notes', notes);
    formData.append('isRecurring', isRecurring ? '1' : '0');
    if (categoryId) formData.append('categoryId', categoryId);

    const isEdit   = editId !== null;
    const action   = isEdit ? `update_${PAGE_TYPE}` : PAGE_TYPE;
    if (isEdit) formData.append('id', editId);

    const result = await apiPost(action, formData);

    if (result.success) {
        closeModal();
        window.location.href = window.location.pathname + CURRENT_URL;
    } else {
        showAlert(alertEl, result.message, 'error');
        submitBtn.disabled    = false;
        submitBtn.textContent = 'Guardar';
    }
}

async function submitDelete(id) {
    const btn     = document.getElementById('btnConfirmDelete');
    const alertEl = document.getElementById('deleteAlert');

    btn.disabled    = true;
    btn.textContent = 'Eliminant...';

    const formData = new FormData();
    formData.append('id', id);

    const action = `delete_${PAGE_TYPE}`;
    const result = await apiPost(action, formData);

    if (result.success) {
        closeModal();
        window.location.href = window.location.pathname + CURRENT_URL;
    } else {
        showAlert(alertEl, result.message, 'error');
        btn.disabled    = false;
        btn.textContent = 'Eliminar';
    }
}

// ── Helpers modal ────────────────────────────────────────────

function setModal(title, bodyHtml) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML    = bodyHtml;
}

function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
    document.getElementById('modalBody').querySelector('input, select')?.focus();
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

// ── API ──────────────────────────────────────────────────────

async function apiPost(action, formData) {
    try {
        const res = await fetch(
            `../../backend/api/controllers/TransactionController.php?action=${action}`,
            { method: 'POST', body: formData }
        );
        return await res.json();
    } catch {
        return { success: false, message: 'Error de connexió.' };
    }
}

// ── Utils ────────────────────────────────────────────────────

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