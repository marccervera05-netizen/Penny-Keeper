/*
 * dashboard.js
 * Lògica del dashboard: barres de categories i modal de transaccions.
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
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const row = document.createElement('div');
        row.className = 'category-bar-row';
        row.innerHTML = `
            <span class="category-bar-label" title="${escHtml(cat.category || 'Sense categoria')}">
                ${escHtml(cat.category || 'Sense categoria')}
            </span>
            <div class="category-bar-track">
                <div class="category-bar-fill" style="width: 0%"
                     data-width="${pct.toFixed(1)}"></div>
            </div>
            <span class="category-bar-amount">${amount} €</span>
        `;
        container.appendChild(row);
    });

    // Anima les barres un tick després de renderitzar
    requestAnimationFrame(() => {
        container.querySelectorAll('.category-bar-fill').forEach(bar => {
            bar.style.width = bar.dataset.width + '%';
        });
    });
}

// ── Modal ────────────────────────────────────────────────────

function initModal() {
    const overlay   = document.getElementById('modalOverlay');
    const closeBtn  = document.getElementById('modalClose');
    const addBtn    = document.getElementById('btnAddTransaction');

    addBtn.addEventListener('click', () => openModal('income'));

    closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
}

function openModal(type) {
    const overlay  = document.getElementById('modalOverlay');
    const title    = document.getElementById('modalTitle');
    const body     = document.getElementById('modalBody');

    title.textContent = type === 'income' ? 'Nou ingrés' : 'Nova despesa';
    body.innerHTML    = buildTransactionForm(type);

    overlay.classList.add('open');

    body.querySelector('#txDescription')?.focus();

    body.querySelector('#txSubmit').addEventListener('click', () => {
        submitTransaction(type);
    });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
}

function buildTransactionForm(type) {
    return `
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
            <input class="form-input" type="date" id="txDate"
                   value="${todayISO()}">
        </div>

        <div class="form-group">
            <label class="form-label" for="txNotes">Notes (opcional)</label>
            <input class="form-input" type="text" id="txNotes"
                   placeholder="Afegeix una nota..." maxlength="200">
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="txRecurring" style="width:16px;height:16px;cursor:pointer;">
            <label for="txRecurring" style="font-size:0.88rem; color:var(--color-text-muted); cursor:pointer;">
                Ingrés recurrent mensual
            </label>
        </div>

        <div id="txAlert" class="alert-pk" role="alert"></div>

        <button class="btn-primary-pk" id="txSubmit" style="width:100%; padding:0.75rem;">
            Guardar
        </button>
    `;
}

async function submitTransaction(type) {
    const description = document.getElementById('txDescription').value.trim();
    const amount      = document.getElementById('txAmount').value;
    const date        = document.getElementById('txDate').value;
    const notes       = document.getElementById('txNotes').value.trim();
    const isRecurring = document.getElementById('txRecurring').checked;
    const alertEl     = document.getElementById('txAlert');

    clearAlert(alertEl);

    if (!description) {
        showAlert(alertEl, 'La descripció és obligatòria.', 'error');
        return;
    }
    if (!amount || parseFloat(amount) <= 0) {
        showAlert(alertEl, 'L\'import ha de ser major que 0.', 'error');
        return;
    }
    if (!date) {
        showAlert(alertEl, 'La data és obligatòria.', 'error');
        return;
    }

    const submitBtn = document.getElementById('txSubmit');
    submitBtn.disabled    = true;
    submitBtn.textContent = 'Guardant...';

    const formData = new FormData();
    formData.append('description', description);
    formData.append('amount', amount);
    formData.append('date', date);
    formData.append('notes', notes);
    formData.append('isRecurring', isRecurring ? '1' : '0');

    const endpoint = type === 'income' ? 'income' : 'expense';

    try {
        const res    = await fetch(`../../backend/api/controllers/TransactionController.php?action=${endpoint}`, {
            method: 'POST',
            body: formData,
        });
        const result = await res.json();

        if (result.success) {
            closeModal();
            window.location.reload();
        } else {
            showAlert(alertEl, result.message, 'error');
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Guardar';
        }
    } catch {
        showAlert(alertEl, 'Error de connexió.', 'error');
        submitBtn.disabled    = false;
        submitBtn.textContent = 'Guardar';
    }
}

// ── Accions ràpides ──────────────────────────────────────────

function initQuickActions() {
    document.getElementById('btnIncome')?.addEventListener('click',  () => openModal('income'));
    document.getElementById('btnExpense')?.addEventListener('click', () => openModal('expense'));
    document.getElementById('btnSaving')?.addEventListener('click',  () => {
        window.location.href = 'savings.php';
    });
}

// ── Helpers ──────────────────────────────────────────────────

function todayISO() {
    return new Date().toISOString().split('T')[0];
}

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showAlert(el, message, type) {
    el.textContent = message;
    el.className   = `alert-pk alert-pk--${type} show`;
}

function clearAlert(el) {
    el.textContent = '';
    el.className   = 'alert-pk';
}