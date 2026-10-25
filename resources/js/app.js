import { createIcons, icons } from 'lucide';
import Swal from 'sweetalert2';

window.lucide = {
    createIcons: (opts) => createIcons(Object.assign({ icons }, opts)),
    icons,
};

window.Swal = Swal;

/* =====================================================================
 * نظام طلبات الإمداد — أدوات واجهة مشتركة (نسخة Laravel)
 * ===================================================================== */

/* ---------- أدوات مساعدة ---------- */
function formatMoney(n) {
    return Number(n || 0).toLocaleString('en-US') + ' ج.م';
}

function icon(name, cls) {
    return `<i data-lucide="${name}" class="${cls || ''}"></i>`;
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

let csrfRefreshing = null;

async function refreshCsrfToken() {
    csrfRefreshing = csrfRefreshing || (async () => {
        const res = await fetch(window.location.href, { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' });
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const fresh = doc.querySelector('meta[name="csrf-token"]');
        if (!fresh || !fresh.getAttribute('content')) throw new Error('no fresh token');
        const current = document.querySelector('meta[name="csrf-token"]');
        if (current) current.setAttribute('content', fresh.getAttribute('content'));
    })().finally(() => { csrfRefreshing = null; });
    return csrfRefreshing;
}

async function request(url, options) {
    options = options || {};
    options.headers = Object.assign({}, options.headers || {}, {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
    });
    if (options.body && !(options.body instanceof FormData) && typeof options.body === 'object') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }
    let res = await fetch(url, options);
    if (res.status === 419) {
        /* جرّب تجديد توكن الـ CSRF ثم أعد المحاولة مرة واحدة قبل إعلان انتهاء الجلسة */
        try {
            await refreshCsrfToken();
            options.headers['X-CSRF-TOKEN'] = csrfToken();
            res = await fetch(url, options);
        } catch (err) { /* فشل التجديد */ }
        if (res.status === 419) {
            toast('انتهت الجلسة، أعد تسجيل الدخول.', 'error');
            setTimeout(() => (window.location.href = '/login'), 1200);
            throw new Error('Session expired');
        }
    }
    if (res.status === 422) {
        const data = await res.json();
        return { ok: false, errors: data.errors || {}, message: data.message || '' };
    }
    if (res.status === 401) {
        window.location.href = '/login';
        throw new Error('Unauthorized');
    }
    if (!res.ok) {
        const text = await res.text();
        let msg = 'تعذر تنفيذ الطلب، حاول مرة أخرى.';
        try {
            const parsed = JSON.parse(text);
            msg = parsed.message || msg;
        } catch (e) {
            if (text && text.length < 200 && !/^\s*</.test(text)) msg = text;
        }
        throw new Error(msg);
    }
    return { ok: true, data: await res.json() };
}

/* ---------- Toast ---------- */
function toast(message, type) {
    type = type || 'success';
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-5 left-5 z-[70] flex flex-col gap-2';
        document.body.appendChild(container);
    }
    const palette = {
        success: 'border-success/40 text-success',
        error: 'border-destructive/40 text-destructive',
        info: 'border-primary/40 text-primary',
    };
    const ic = {
        success: 'circle-check-big',
        error: 'circle-x',
        info: 'info',
    };
    const el = document.createElement('div');
    el.className =
        'animate-toast-in flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-card ' +
        (palette[type] || palette.info);
    el.innerHTML =
        `${icon(ic[type] || 'info', 'h-4 w-4')}<span class="text-[13px] font-semibold">${message}</span>`;
    container.appendChild(el);
    window.lucide.createIcons();
    setTimeout(() => {
        el.style.transition = 'opacity .3s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 320);
    }, 3200);
}

/* ---------- Modal ---------- */
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'none';
    document.body.style.overflow = '';
}
function closeOnBackdrop(id, e) {
    if (e.target === e.currentTarget) closeModal(id);
}

/* ---------- Confirm ---------- */
let confirmCb = null;
function showConfirm(message, onYes) {
    const text = document.getElementById('confirm-text');
    if (text) text.textContent = message;
    confirmCb = onYes;
    openModal('confirm-modal');
}
function initConfirm() {
    const yes = document.getElementById('confirm-yes');
    const no = document.getElementById('confirm-no');
    if (yes) yes.addEventListener('click', () => { closeModal('confirm-modal'); if (confirmCb) confirmCb(); });
    if (no) no.addEventListener('click', () => closeModal('confirm-modal'));
}

/* ---------- SearchableSelect ---------- */
function createSearchableSelect(container, options, opts) {
    opts = opts || {};
    const placeholder = opts.placeholder || 'ابحث واختر...';
    const isObj = (o) => o && typeof o === 'object' && 'label' in o;
    const normLabel = (o) => (isObj(o) ? o.label : o);
    const normValue = (o) => (isObj(o) ? String(o.value) : o);
    let value = opts.value != null ? String(opts.value) : '';
    let open = false;
    let query = '';

    container.classList.add('relative');
    container.setAttribute('data-value', value);
    container.innerHTML =
        `<button type="button" class="ss-toggle flex h-10 w-full items-center justify-between rounded-lg border border-input bg-card px-3 text-[13px] outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
      <span class="ss-value ${value ? '' : 'text-muted-foreground/70'}">${value || placeholder}</span>
      ${icon('chevron-down', 'h-4 w-4 text-muted-foreground')}
    </button>
    <div class="ss-menu hidden absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-border bg-card shadow-card">
      <div class="flex items-center gap-2 border-b border-border px-3 py-2">
        ${icon('search', 'h-4 w-4 text-muted-foreground')}
        <input type="text" class="ss-query h-7 w-full bg-transparent text-[13px] outline-none placeholder:text-muted-foreground/70" placeholder="اكتب للبحث...">
      </div>
      <ul class="scroll-slim max-h-56 overflow-y-auto py-1"></ul>
    </div>`;

    const toggle = container.querySelector('.ss-toggle');
    const menu = container.querySelector('.ss-menu');
    const ul = container.querySelector('ul');
    const qInput = container.querySelector('.ss-query');
    const valEl = container.querySelector('.ss-value');

    function setValue(v) {
        value = v != null ? String(v) : '';
        container.setAttribute('data-value', value);
        const match = options.find((o) => normValue(o) === value);
        valEl.textContent = value ? normLabel(match || value) : placeholder;
        valEl.classList.toggle('text-muted-foreground/70', !value);
    }

    /* حل اسم العنصر المختار مبدئيًا بدل عرض القيمة الخام */
    setValue(value);

    function renderList() {
        const q = query.trim();
        const filtered = options.filter((o) => String(normLabel(o)).includes(q));
        if (filtered.length === 0) {
            ul.innerHTML =
                `<li class="px-3 py-3 text-center text-xs text-muted-foreground">لا توجد نتائج مطابقة</li>`;
        } else {
            ul.innerHTML = filtered
                .map(
                    (o) => {
                        const oVal = normValue(o);
                        const oLabel = normLabel(o);
                        const selected = String(oVal) === String(value);
                        return `<li><button type="button" class="ss-opt flex w-full items-center justify-between px-3 py-2 text-right text-[13px] transition-colors hover:bg-accent" data-val="${oVal.replace(/"/g, '&quot;')}">
              <span>${oLabel}</span>${selected ? icon('check', 'h-4 w-4 text-primary') : ''}
            </button></li>`;
                    },
                )
                .join('');
            ul.querySelectorAll('.ss-opt').forEach((btn) => {
                btn.addEventListener('click', () => {
                    setValue(btn.getAttribute('data-val'));
                    menu.classList.add('hidden');
                    open = false;
                    if (opts.onSelect) opts.onSelect(value);
                    window.lucide.createIcons();
                });
            });
        }
        window.lucide.createIcons();
    }

    toggle.addEventListener('click', () => {
        open = !open;
        if (open) {
            qInput.value = '';
            query = '';
            renderList();
            menu.classList.remove('hidden');
            /* افصل الاتجاه: افتح لأعلى لو مفيش مساحة تحت عشان متتقطرش جوه المودال */
            const rect = toggle.getBoundingClientRect();
            const gap = 8;
            const spaceBelow = window.innerHeight - rect.bottom - gap;
            const spaceAbove = rect.top - gap;
            const h = menu.offsetHeight;
            const up = h > spaceBelow && spaceAbove >= Math.min(h, spaceBelow);
            menu.classList.toggle('ss-open-up', up);
            qInput.focus();
        } else {
            menu.classList.add('hidden');
        }
    });
    qInput.addEventListener('input', () => {
        query = qInput.value;
        renderList();
    });
    document.addEventListener('mousedown', (e) => {
        if (!container.contains(e.target)) {
            menu.classList.add('hidden');
            open = false;
        }
    });
    renderList();

    return { getValue: () => value, setValue: setValue, setOptions: (optsAr) => { options = optsAr; renderList(); }, options: () => options };
}

/* ---------- DataTable (server-side) ---------- */
function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = String(html == null ? '' : html);
    return tmp.textContent.trim();
}

function csvCell(value) {
    return '"' + String(value == null ? '' : value).replace(/"/g, '""') + '"';
}

function downloadCsv(filename, headers, rows) {
    const lines = [headers.map(csvCell).join(',')];
    rows.forEach((r) => lines.push(r.map(csvCell).join(',')));
    const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 500);
}

function renderServerTable(container, columns, config) {
    config = config || {};
    const emptyText = config.emptyText || 'لا توجد بيانات لعرضها';
    const loadUrl = config.loadUrl;
    const pageSize = config.pageSize || 8;
    const minWidth = config.minWidth !== undefined && config.minWidth !== '' ? config.minWidth : null;
    let current = 1;
    let last = 1;
    let total = 0;
    let currentSearch = '';
    let searchTimer = null;

    /* شريط الأدوات: بحث + تصدير (يُبنى مرة واحدة حتى لا يفقد البحث التركيز) */
    container.innerHTML =
        '<div class="dt-toolbar mb-3 flex flex-wrap items-center gap-2">' +
            (config.searchable !== false
                ? '<div class="relative w-full sm:max-w-xs">' +
                  '<i data-lucide="search" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>' +
                  '<input type="text" class="dt-search h-9 w-full rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" placeholder="بحث..." />' +
                  '</div>'
                : '') +
            (config.exportable !== false
                ? '<button type="button" class="dt-export inline-flex h-9 items-center gap-2 rounded-lg border border-border bg-card px-3.5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' +
                  icon('file-down', 'h-4 w-4') + 'تصدير Excel</button>'
                : '') +
        '</div>' +
        '<div class="dt-body relative"></div>';

    const body = container.querySelector('.dt-body');
    const searchInput = container.querySelector('.dt-search');
    const exportBtn = container.querySelector('.dt-export');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const val = searchInput.value.trim();
                if (val === currentSearch) return;
                currentSearch = val;
                current = 1;
                draw();
            }, 300);
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', async () => {
            exportBtn.disabled = true;
            try {
                const params = new URLSearchParams({ export: 1 });
                if (currentSearch) params.set('search', currentSearch);
                const res = await fetch((config.exportUrl || loadUrl) + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.status === 401) { window.location.href = '/login'; return; }
                if (!res.ok) throw new Error('export failed');
                const payload = await res.json();
                const rows = payload.rows || [];
                const cols = columns.filter((c) => c.key !== 'actions' && !c.skipExport);
                const headers = cols.map((c) => c.header);
                const dataRows = rows.map((row, i) =>
                    cols.map((c) => {
                        if (c.key === 'idx') return i + 1;
                        const raw = c.render ? c.render(row, i, payload) : row[c.key] == null ? '' : row[c.key];
                        return typeof raw === 'string' ? stripHtml(raw) : raw;
                    }),
                );
                downloadCsv((config.exportName || 'export') + '.csv', headers, dataRows);
                toast('تم تصدير ' + rows.length + ' سجل بنجاح.', 'success');
            } catch (err) {
                toast('تعذر التصدير، حاول مرة أخرى.', 'error');
            } finally {
                exportBtn.disabled = false;
            }
        });
    }

    function draw() {
        body.innerHTML =
            '<div class="absolute inset-0 z-10 grid place-items-center rounded-xl bg-background/50 backdrop-blur-[1px]"><span class="spinner"></span></div>';
        const params = new URLSearchParams({ page: current, per_page: pageSize });
        if (currentSearch) params.set('search', currentSearch);
        fetch(loadUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((r) => {
                if (r.status === 401) { window.location.href = '/login'; throw new Error('unauthorized'); }
                return r.json();
            })
            .then((payload) => {
                current = payload.page || 1;
                last = payload.lastPage || 1;
                total = payload.total || 0;
                body.innerHTML = buildTable(payload);
                bindPaging(body);
                window.lucide.createIcons();
                if (config.onLoaded) config.onLoaded(payload);
            })
            .catch((err) => {
                if (err.message !== 'unauthorized') {
                    body.innerHTML =
                        '<div class="rounded-xl border border-destructive/30 bg-destructive/5 p-6 text-center text-sm text-destructive">تعذر تحميل البيانات. أعد المحاولة.</div>';
                }
            });
    }

    function buildTable(payload) {
        const rows = payload.rows || [];
        const minStyle = minWidth ? ` style="min-width:${minWidth}"` : '';
        let html = '<div class="overflow-hidden rounded-xl border border-border bg-card shadow-card">';
        html += '<div class="scroll-slim overflow-x-auto">';
        html += `<table class="w-full border-collapse text-right text-sm"${minStyle}>`;
        html += '<thead><tr class="bg-brand text-primary-foreground">';
        columns.forEach((c) => {
            const w = c.width ? ` style="width:${c.width}"` : '';
            html +=
                `<th class="whitespace-nowrap px-4 py-3.5 text-right text-sm font-semibold"${w}>${c.header}</th>`;
        });
        html += '</tr></thead><tbody>';

        if (rows.length === 0) {
            html += `<tr><td colspan="${columns.length}" class="px-4 py-14 text-center">`;
            html += icon('inbox', 'mx-auto mb-3 h-8 w-8 text-muted-foreground/60');
            html += `<p class="text-sm text-muted-foreground">${emptyText}</p></td></tr>`;
        } else {
            rows.forEach((row, i) => {
                let cls = '';
                if (config.rowClass) cls = config.rowClass(row, i, payload);
                let style = '';
                if (config.rowStyle) style = ` style="${config.rowStyle(row, i, payload)}"`;
                html +=
                    `<tr class="border-t border-border/70 transition-colors odd:bg-card even:bg-muted/40 hover:bg-accent/60${cls ? ' ' + cls : ''}"${style}>`;
                columns.forEach((c) => {
                    const val = c.render ? c.render(row, i, payload) : row[c.key] == null ? '' : row[c.key];
                    html += `<td class="px-4 py-3.5 text-foreground/90">${val}</td>`;
                });
                html += '</tr>';
            });
        }
        html += '</tbody></table></div>';

        html +=
            '<div class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-muted/40 px-4 py-3">';
        html += `<p class="text-xs text-muted-foreground">عرض ${payload.from ? payload.to - payload.from + 1 : 0} من إجمالي ${total} سجل</p>`;
        html += '<div class="flex items-center gap-1">';
        html +=
            `<button type="button" data-page="${current - 1}" ${current === 1 ? 'disabled' : ''} class="inline-flex h-8 items-center gap-1 rounded-lg border border-border bg-card px-3 text-xs font-medium transition-colors hover:bg-accent disabled:opacity-40">${icon('chevron-right', 'h-4 w-4')} السابق</button>`;

        (payload.pages || []).forEach((p) => {
            if (p === '…') {
                html += '<span class="px-1 text-muted-foreground">…</span>';
            } else {
                const active = p === current;
                html +=
                    `<button type="button" data-page="${p}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-xs font-medium transition-colors ${active ? 'border-brand bg-brand text-primary-foreground' : 'border-border bg-card hover:bg-accent'}">${p}</button>`;
            }
        });

        html +=
            `<button type="button" data-page="${current + 1}" ${current === last ? 'disabled' : ''} class="inline-flex h-8 items-center gap-1 rounded-lg border border-border bg-card px-3 text-xs font-medium transition-colors hover:bg-accent disabled:opacity-40">التالي ${icon('chevron-left', 'h-4 w-4')}</button>`;
        html += '</div></div></div>';

        return html;
    }

    function bindPaging(root) {
        root.querySelectorAll('[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const p = parseInt(btn.getAttribute('data-page'), 10);
                if (p >= 1 && p <= last && p !== current) {
                    current = p;
                    draw();
                }
            });
        });
    }

    draw();

    return { refresh: draw, getPage: () => current };
}

/* ---------- إجراءات الحذف ---------- */
function initDeleteHandlers(getRefresh) {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-delete-url]');
        if (!btn) return;
        const url = btn.getAttribute('data-delete-url');
        showConfirm('هل أنت متأكد من حذف هذا السجل؟', async () => {
            try {
                const result = await request(url, { method: 'DELETE' });
                if (result && result.ok === false) {
                    toast(result.message || 'تعذر الحذف.', 'error');
                    return;
                }
                toast(result.data.message || 'تم الحذف بنجاح.', 'success');
                if (getRefresh) getRefresh().refresh();
            } catch (err) {
                if (!/Session expired/.test(err.message)) toast('تعذر الحذف، حاول مرة أخرى.', 'error');
            }
        });
    });
}

/* ---------- حقول النماذج ---------- */
function showFieldError(id, message) {
    const input = document.getElementById(id);
    if (!input) return;
    let err = input.closest('.field-wrap')?.querySelector('.field-error');
    if (!err) {
        err = document.createElement('p');
        err.className = 'field-error mt-1 text-xs font-medium text-destructive';
        const wrap = input.closest('.field-wrap') || input.parentElement;
        wrap.appendChild(err);
    }
    err.textContent = message;
}

function clearFieldErrors(form) {
    form.querySelectorAll('.field-error').forEach((el) => el.remove());
}

function displayErrors(errors, prefix) {
    if (!errors) return;
    Object.keys(errors).forEach((key) => {
        showFieldError(prefix + key, errors[key][0]);
    });
}

/* ---------- درج الموبايل ---------- */
function initDrawer() {
    const menuBtn = document.getElementById('menu-btn');
    const drawer = document.getElementById('mobile-drawer');
    const overlay = document.getElementById('mobile-overlay');
    const closeBtn = document.getElementById('drawer-close');
    const open = () => {
        if (!drawer || !overlay) return;
        drawer.style.display = 'flex';
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    const close = () => {
        if (!drawer || !overlay) return;
        drawer.style.display = 'none';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    };
    if (menuBtn) menuBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay) overlay.addEventListener('click', close);
}

/* ---------- إظهار/إخفاء كلمة المرور ---------- */
function initPasswordToggles() {
    document.querySelectorAll('[data-pw-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.getAttribute('data-pw-toggle'));
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').setAttribute('data-lucide', show ? 'eye-off' : 'eye');
            btn.classList.toggle('text-primary', show);
            window.lucide.createIcons();
        });
    });
}

/* ---------- القوائم المنسدلة (ملف شخصي / إشعارات) ---------- */
function initDropdowns() {
    const wraps = [
        { btn: document.getElementById('profile-btn'), menu: document.getElementById('profile-menu') },
        { btn: document.getElementById('notif-btn'), menu: document.getElementById('notif-menu') },
    ];

    wraps.forEach(({ btn, menu }) => {
        if (!btn || !menu) return;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !menu.classList.contains('hidden');
            wraps.forEach(({ menu: m }) => m.classList.add('hidden'));
            if (!isOpen) menu.classList.remove('hidden');
        });
    });

    document.addEventListener('click', () => {
        wraps.forEach(({ menu }) => menu.classList.add('hidden'));
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') wraps.forEach(({ menu }) => menu.classList.add('hidden'));
    });
}

/* ---------- تهيئة عامة ---------- */
function whenReady(cb) {
    document.addEventListener('DOMContentLoaded', cb);
}

document.addEventListener('DOMContentLoaded', () => {
    initConfirm();
    initDrawer();
    initPasswordToggles();
    initDropdowns();
    window.lucide.createIcons();
});

/* ---------- إضافة سريعة لقيم جديدة في قوائم الاختيار ---------- */
/*
 * config: {
 *   url,            // نهاية إضافة سريعة
 *   label,          // اسم الكيان (مستشفى، قسم، حصيلة، شركة)
 *   placeholder,    // نص الحقل في SweetAlert
 *   extra,          // قاموس حقول إضافية تُرسل مع الاسم (اختياري)
 *   asStringValue,  // true إذا كانت القيمة نص (الحصيلة) وليست id (اختياري)
 *   customForm: {   // نموذج مخصص بدل حقل واحد (اختياري)
 *     html,         // سلسلة HTML للنموذج
 *     init,         // دالة تُنفَّذ بعد فتح الـ SweetAlert لتهيئة العناصر
 *     getValue,     // دالة تُرجع { name, ...keys } من الحقول
 *     validate,     // دالة تتحقق من صحة البيانات (اختياري)
 *   }
 * }
 */
function attachQuickAdd(containerSelector, selectRefOrFn, config) {
    const wrap = document.querySelector(containerSelector);
    if (!wrap) return;
    const getRef = typeof selectRefOrFn === 'function' ? selectRefOrFn : () => selectRefOrFn;
    const addLabel = config.addLabel || ('إضافة ' + config.label + ' جديد');

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className =
        'quick-add-btn mt-1.5 inline-flex h-8 items-center gap-1.5 rounded-lg border border-dashed border-brand/40 bg-brand/5 px-2.5 text-[11px] font-semibold text-brand transition-colors hover:bg-brand/10';
    btn.innerHTML = icon('plus', 'h-3.5 w-3.5') + ' ' + addLabel;
    wrap.parentElement.appendChild(btn);

    btn.addEventListener('click', async () => {
        if (typeof config.beforeAdd === 'function' && !config.beforeAdd()) return;

        let name = '';
        let extraData = {};

        if (config.customForm) {
            /* نموذج مخصص (multi-field) */
            let formError = '';
            const cf = config.customForm;

            const result = await Swal.fire({
                title: addLabel,
                html: cf.html,
                showCancelButton: true,
                confirmButtonText: 'إضافة',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#0f766e',
                cancelButtonColor: '#64748b',
                focusConfirm: false,
                customClass: { popup: 'swal-wide' },
                didOpen: () => {
                    if (cf.init) cf.init();
                    window.lucide.createIcons();
                },
                preConfirm: () => {
                    const values = cf.getValue();
                    if (cf.validate) {
                        const err = cf.validate(values);
                        if (err) { Swal.showValidationMessage(err); return false; }
                    }
                    return values;
                },
            });

            if (!result.isConfirmed) return;
            name = result.value.name || '';
            extraData = result.value;
            delete extraData.name;
        } else {
            /* حقل نصي واحد (الحالة الافتراضية) */
            const res = await Swal.fire({
                title: addLabel,
                input: 'text',
                inputLabel: 'اسم ' + config.label,
                inputPlaceholder: config.placeholder || 'اكتب الاسم هنا...',
                showCancelButton: true,
                confirmButtonText: 'إضافة',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#0f766e',
                cancelButtonColor: '#64748b',
                inputValidator: (v) => (!v || !v.trim() ? 'الاسم مطلوب' : null),
            });
            if (!res.isConfirmed) return;
            name = res.value;
        }

        if (!name || !name.trim()) return;

        /* دمج extra الثابت مع extra من النموذج */
        const finalExtra = {};
        const extra = config.extra || {};
        Object.keys(extra).forEach((k) => { finalExtra[k] = typeof extra[k] === 'function' ? extra[k]() : extra[k]; });
        Object.assign(finalExtra, extraData);

        const payload = Object.assign({ name: name.trim() }, finalExtra);
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = icon('loader-circle', 'h-3.5 w-3.5 animate-spin');

        try {
            const res = await request(config.url, { method: 'POST', body: payload });
            if (!res.ok) {
                toast(res.message || 'تعذر الإضافة.', 'error');
                return;
            }
            const item = res.data.item;
            if (!item) {
                toast(res.data.message || 'تمت الإضافة.', 'success');
                return;
            }
            const ref = getRef();
            /* أضف للقائمة الحالية واختره */
            const opts = ref.options().slice();
            if (!opts.some((o) => String(o.value) === String(item.value))) opts.push(item);
            ref.setOptions(opts);
            ref.setValue(String(item.value));
            toast(res.data.message || 'تمت الإضافة.', 'success');
        } catch (err) {
            if (!/Session expired/.test(err.message)) toast(err.message || 'تعذر الإضافة.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
}

/* ---------- تصدير للأدوات العامة ---------- */
window.emdadat = {
    formatMoney,
    icon,
    toast,
    downloadCsv,
    openModal,
    closeModal,
    closeOnBackdrop,
    showConfirm,
    createSearchableSelect,
    renderServerTable,
    initDeleteHandlers,
    request,
    showFieldError,
    clearFieldErrors,
    displayErrors,
    attachQuickAdd,
    whenReady,
};