/*
 * dashboard.js
 * Lògica del dashboard: gràfic d'evolució, barres de categories i modal.
 */

document.addEventListener('DOMContentLoaded', () => {
    renderCategoryBars();
    loadEvolutionChart();
    initModal();
    initQuickActions();
});

// ── Gràfic d'evolució 6 mesos ────────────────────────────────

async function loadEvolutionChart() {
    const canvas = document.getElementById('evolutionChart');
    if (!canvas) return;

    try {
        const res  = await fetch('../../backend/api/controllers/ChartController.php');
        const data = await res.json();

        if (!data.success) return;

        if (!data.hasData) {
            canvas.closest('.card-pk').innerHTML = `
                <h2 class="card-title">Evolució dels últims 6 mesos</h2>
                <div class="empty-state">
                    <i class="bi bi-graph-up"></i>
                    <p>Afegeix dades durant uns mesos per veure l'evolució</p>
                </div>
            `;
            return;
        }

        renderEvolutionChart(canvas, data.labels, data.incomes, data.expenses);
    } catch {
        canvas.closest('.card-pk').style.display = 'none';
    }
}

function renderEvolutionChart(canvas, labels, incomes, expenses) {
    const style   = getComputedStyle(document.documentElement);
    const colorDark = '#714329';
    const colorMid  = '#B9937B';

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ingressos',
                    data: incomes,
                    borderColor: colorDark,
                    backgroundColor: 'rgba(113, 67, 41, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: colorDark,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Despeses',
                    data: expenses,
                    borderColor: colorMid,
                    backgroundColor: 'rgba(185, 147, 123, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: colorMid,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        borderRadius: 3,
                        useBorderRadius: true,
                        font: { family: 'Inter', size: 12 },
                        color: '#7A6558',
                    },
                },
                tooltip: {
                    backgroundColor: '#fff',
                    borderColor: '#E4D9D0',
                    borderWidth: 1,
                    titleColor: '#2C1A0E',
                    bodyColor: '#7A6558',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: 'Inter', size: 12, weight: '600' },
                    bodyFont:  { family: 'Inter', size: 12 },
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${formatAmount(ctx.raw)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#B5A192',
                    },
                    border: { display: false },
                },
                y: {
                    grid: {
                        color: '#F0E8E0',
                        drawTicks: false,
                    },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#B5A192',
                        padding: 8,
                        callback: val => formatAmount(val),
                    },
                    border: { display: false },
                },
            },
        },
    });
}

// ── Barres de categories ─────────────────────────────────────

function renderCategoryBars() {
    const container = document.getElementById('categoryBars');
    if (!container) return;

    const categories = JSON.parse(container.dataset.categories || '[]');
    const total      = parseFloat(container.dataset.total) || 0;

    if (categories.length === 0) return;

    categories.forEach(cat => {
        const pct    = total > 0 ? (cat.total / total) * 100 : 0;
        const amount = formatAmount(parseFloat(cat.total));

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
            <span class="category-bar-amount">${amount}</span>
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

    addBtn.addEventListener('click', () => openModal('income'));
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
}

function openModal(type) {
    const overlay = document.getElementById('modalOverlay');
    const title   = document.getElementById('modalTitle');
    const body    = document.getElementById('modalBody');

    title.textContent = type === 'income' ? 'Nou ingrés' : 'Nova despesa';
    body.innerHTML    = buildTransactionForm(type);
    overlay.classList.add('open');
    body.querySelector('#txDescription')?.focus();
    body.querySelector('#txSubmit').addEventListener('click', () => submitTransaction(type));
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
            <input class="form-input" type="date" id="txDate" value="${todayISO()}">
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

    const result = await apiPost(type, fd);

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
    document.getElementById('btnIncome')?.addEventListener('click',  () => openModal('income'));
    document.getElementById('btnExpense')?.addEventListener('click', () => openModal('expense'));
    document.getElementById('btnSaving')?.addEventListener('click',  () => { window.location.href = 'savings.php'; });
}

// ── Helpers ──────────────────────────────────────────────────

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

function formatAmount(val) {
    return parseFloat(val).toLocaleString('ca-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }) + ' €';
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