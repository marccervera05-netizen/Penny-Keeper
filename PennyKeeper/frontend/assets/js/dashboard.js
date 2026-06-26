/*
 * dashboard.js
 * Lògica del dashboard: barres de categories i modal millorat.
 */

document.addEventListener('DOMContentLoaded', () => {
    renderCategoryBars();
    initModal();
    initQuickActions();
});

// ── Barres de categories ─────────────────────────────────────

function renderCategoryBars() {
    const container = document.getElementById('categoryBars');
    if (!container) return;

    const categories = JSON.parse(container.dataset.categories || '[]');
    const total      = parseFloat(container.dataset.total) || 0;
    if (categories.length === 0) return;

    categories.forEach(cat => {
        const pct    = total > 0 ? (cat.total / total) * 100 : 0;
        const amount = parseFloat(cat.total).toLocaleString('ca-ES', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
        const row = document.createElement('div');
        row.className = 'category-bar-row';
        row.innerHTML = `
            <span class="category-bar-label" title="${escHtml(cat.category || 'Sense categoria')}">
                ${escHtml(cat.category || 'Sense categoria')}
            </span>
            <div class="category-bar-track">
                <div class="category-bar-fill" style="width:0%" data-width="${pct.toFixed(1)}"></div>
            </div>
            <span class="category-bar-amount">${amount} €</span>
        `;
        container.appendChild(row);
    });

    requestAnimationFrame(() => {
        container.querySelectorAll('.category-bar-fill').forEach(bar => {
            bar.style.width = bar.dataset.width + '%';
        });
    });
}

// ── Modal ────────────────────────────────────────────────────

function initModal() {
    const overlay  = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('modalClose');
    const addBtn   = document.getElementById('btnAddTransaction');

    addBtn.addEventListener('click', () => openTypeSelector());
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
}

function openTypeSelector() {
    setModal('Afegir moviment', `
        <div class="type-selector">
            <button class="type-btn type-btn--income" id="selectIncome">
                <i class="bi bi-arrow-up-circle"></i>
                <span>Ingrés</span>
                <small>Salari, freelance, altres...</small>
            </button>
            <button class="type-btn type-btn--expense" id="selectExpense">
                <i class="bi bi-arrow-down-circle"></i>
                <span>Despesa</span>
                <small>Lloguer, menjar, oci...</small>
            </button>
        </div>
    `);
    openModal();

    document.getElementById('selectIncome').addEventListener('click', () => openTransactionForm('income'));
    document.getElementById('selectExpense').addEventListener('click', () => openTransactionForm('expense'));
}

function openTransactionForm(type) {
    const title = type === 'income' ? 'Nou ingrés' : 'Nova despesa';
    const cats  = type === 'income' ? INCOME_CATEGORIES : EXPENSE_CATEGORIES;

    const categoryOptions = cats.map(cat =>
        `<option value="${cat.id}">${escHtml(cat.name)}</option>`
    ).join('');

    setModal(title, `
        <button class="btn-back" id="btnBack">
            <i class="bi bi-arrow-left"></i> Tornar
        </button>

        <div class="form-group">
            <label class="form-label" for="txDescription">Descripció</label>
            <input class="form-input" type="text" id="txDescription"
                   placeholder="${type === 'income' ? 'Ex: Salari, Freelance...' : 'Ex: Supermercat, Netflix...'}"
                   maxlength="150">
        </div>

        <div class="form-group">
            <label class="form-label" for="txAmount">Import (€)</label>
            <input class="form-input" type="number" id="txAmount"
                   placeholder="0,00" min="0.01" step="0.01">
        </div>

        <div class="form-group">
            <label class="form-label" for="txDate">Data</label>
            <input class="form-input" type="date" id="txDate" value="${todayISO()}">
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
                   placeholder="Afegeix una nota..." maxlength="200">
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="txRecurring" style="width:16px;height:16px;cursor:pointer;">
            <label for="txRecurring" style="font-size:0.88rem; color:var(--color-text-muted); cursor:pointer;">
                ${type === 'income' ? 'Ingrés' : 'Despesa'} recurrent mensual
            </label>
        </div>

        <div id="txAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="txSubmit" style="width:100%; padding:0.75rem;">
            Guardar
        </button>
    `);

    document.getElementById('btnBack').addEventListener('click', openTypeSelector);
    document.getElementById('txDescription').focus();
    document.getElementById('txSubmit').addEventListener('click', () => submitTransaction(type));
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
}

function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
}

function setModal(title, bodyHtml) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML    = bodyHtml;
}

async function submitTransaction(type) {
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

    const btn = document.getElementById('txSubmit');
    btn.disabled = true; btn.textContent = 'Guardant...';

    const fd = new FormData();
    fd.append('description', description);
    fd.append('amount', amount);
    fd.append('date', date);
    fd.append('notes', notes);
    fd.append('isRecurring', isRecurring ? '1' : '0');
    if (categoryId) fd.append('categoryId', categoryId);

    const result = await apiPost('TransactionController', type, fd);

    if (result.success) {
        closeModal();
        window.location.reload();
    } else {
        showAlert(alertEl, result.message, 'error');
        btn.disabled = false; btn.textContent = 'Guardar';
    }
}

// ── Accions ràpides ──────────────────────────────────────────

function initQuickActions() {
    document.getElementById('btnIncome')?.addEventListener('click',  () => openTransactionForm('income'));
    document.getElementById('btnExpense')?.addEventListener('click', () => openTransactionForm('expense'));
    document.getElementById('btnSaving')?.addEventListener('click',  () => { window.location.href = 'savings.php'; });
}

// ── Helpers ──────────────────────────────────────────────────

async function apiPost(controller, action, formData) {
    try {
        const res = await fetch(
            `${API_BASE}/backend/api/controllers/${controller}.php?action=${action}`,
            { method: 'POST', body: formData }
        );
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