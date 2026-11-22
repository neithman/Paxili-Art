/* ==========================================================================
   PaxiliArt – plain JavaScript (no libraries)
   Every feature is a small function. They all start at the bottom of the file.
   ========================================================================== */

/* 1. Mobile menu (hamburger button) ------------------------------------- */
function setupMobileMenu() {
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (!toggle || !links) return;
    toggle.addEventListener('click', () => links.classList.toggle('open'));
}

/* 2. Dropdown menus (user menu) ----------------------------------------- */
function setupDropdowns() {
    document.querySelectorAll('.dropdown > button').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const dropdown = button.parentElement;
            const wasOpen = dropdown.classList.contains('open');
            document.querySelectorAll('.dropdown.open').forEach((d) => d.classList.remove('open'));
            if (!wasOpen) dropdown.classList.add('open');
        });
    });
    // Click anywhere else -> close
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown.open').forEach((d) => d.classList.remove('open'));
    });
}

/* 3. Close buttons on alerts + auto-hide success messages ---------------- */
function setupAlerts() {
    document.querySelectorAll('.alert').forEach((alert) => {
        const close = alert.querySelector('.alert-close');
        if (close) close.addEventListener('click', () => alert.remove());
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => alert.remove(), 6000);
        }
    });
}

/* 4. "Are you sure?" for dangerous forms --------------------------------
   Usage in Blade: <form data-confirm="Delete this artwork?"> */
function setupConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
}

/* 5. Show a preview when choosing an image file --------------------------
   Usage: <input type="file" data-preview="#logoPreview"> + <img id="logoPreview"> */
function setupImagePreviews() {
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const img = document.querySelector(input.dataset.preview);
            const file = input.files[0];
            if (!img || !file || !file.type.startsWith('image/')) return;
            img.src = URL.createObjectURL(file);
            img.hidden = false;
        });
    });
}

/* 6. GCash number: "09171234567" -> "0917 123 4567" while typing ------- */
function setupGcashInput() {
    const input = document.querySelector('#gcash_number');
    if (!input) return;
    input.addEventListener('input', () => {
        const d = input.value.replace(/\D/g, '').slice(0, 11);
        input.value = [d.slice(0, 4), d.slice(4, 7), d.slice(7)].filter(Boolean).join(' ');
    });
}

/* 7. Modals --------------------------------------------------------------
   Open:  <button data-modal-open="#reviewModal">
   Close: <button data-modal-close> (inside the modal) or click the dark background */
function openModal(modal) {
    modal.classList.add('open');
}
function closeModal(modal) {
    modal.classList.remove('open');
}
function setupModals() {
    document.querySelectorAll('[data-modal-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = document.querySelector(btn.dataset.modalOpen);
            if (modal) openModal(modal);
        });
    });
    document.querySelectorAll('.modal').forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.hasAttribute('data-modal-close')) closeModal(modal);
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(closeModal);
    });
}

/* 8. Review popup (My Purchases page) ------------------------------------
   One modal is reused for every artwork. We fill it with the right data. */
function openReviewModal(data) {
    const modal = document.querySelector('#reviewModal');
    if (!modal) return;
    modal.querySelector('form').action = data.action;
    modal.querySelector('[data-review-title]').textContent = data.title;
    modal.querySelector('[data-review-heading]').textContent = data.rating ? 'Edit your review' : 'Enjoying your new artwork?';
    modal.querySelectorAll('input[name=rating]').forEach((r) => (r.checked = String(r.value) === String(data.rating)));
    modal.querySelector('textarea[name=comment]').value = data.comment || '';
    openModal(modal);
}
function setupReviewButtons() {
    document.querySelectorAll('[data-review]').forEach((btn) => {
        btn.addEventListener('click', () => openReviewModal(JSON.parse(btn.dataset.review)));
    });
}

/* 9. ONE-TIME DOWNLOAD -----------------------------------------------------
   When the form is submitted the browser downloads the file and stays on the page.
   The server sends a cookie "download_done=<id>". We watch for it, then:
     - disable the button,
     - show the optional review popup. */
function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}
function setupDownloads() {
    document.querySelectorAll('form[data-download]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const ok = window.confirm('You can download this artwork only ONCE. Make sure you save the file. Continue?');
            if (!ok) {
                event.preventDefault();
                return;
            }
            const id = form.dataset.download;
            const button = form.querySelector('button');
            button.disabled = true;
            button.textContent = 'Preparing…';

            let tries = 0;
            const timer = setInterval(() => {
                tries++;
                if (getCookie('download_done') === id) {
                    clearInterval(timer);
                    document.cookie = 'download_done=; Max-Age=0; path=/';
                    button.textContent = '✔ Downloaded';
                    const status = document.querySelector('[data-download-status="' + id + '"]');
                    if (status) status.textContent = 'Downloaded just now';
                    if (form.dataset.reviewed !== '1') {
                        setTimeout(() => openReviewModal(JSON.parse(form.dataset.reviewData)), 700);
                    }
                }
                if (tries > 60) clearInterval(timer); // stop after ~30 seconds
            }, 500);
        });
    });
}

/* 10. BAR CHART (canvas) ---------------------------------------------------
   Usage: <div class="chart-box"><canvas data-chart='{"labels":[..],"values":[..],"prefix":"₱"}'></canvas></div> */
function drawBarChart(canvas) {
    const data = JSON.parse(canvas.dataset.chart);
    const labels = data.labels;
    const values = data.values;
    const prefix = data.prefix || '';

    // Make the canvas sharp on high-resolution screens
    const ratio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth;
    const height = canvas.clientHeight;
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    ctx.clearRect(0, 0, width, height);

    const pad = { top: 16, right: 10, bottom: 40, left: 62 };
    const chartW = width - pad.left - pad.right;
    const chartH = height - pad.top - pad.bottom;
    const max = Math.max(...values, 1);
    const niceMax = Math.ceil(max / 4) * 4 || 4;

    // Grid lines + Y labels
    ctx.font = '11px Inter, sans-serif';
    ctx.fillStyle = '#403D39';
    ctx.strokeStyle = '#ECE8E0';
    ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) {
        const y = pad.top + chartH - (chartH / 4) * i;
        ctx.beginPath();
        ctx.moveTo(pad.left, y);
        ctx.lineTo(width - pad.right, y);
        ctx.stroke();
        const val = (niceMax / 4) * i;
        ctx.fillText(prefix + (val >= 1000 ? (val / 1000).toFixed(1) + 'k' : Math.round(val)), pad.left - 8, y + 4);
    }

    // Bars
    const slot = chartW / labels.length;
    const barW = Math.max(4, Math.min(42, slot * 0.6));
    const bars = [];
    labels.forEach((label, i) => {
        const h = (values[i] / niceMax) * chartH;
        const x = pad.left + slot * i + (slot - barW) / 2;
        const y = pad.top + chartH - h;
        ctx.fillStyle = values[i] > 0 ? '#EB5E28' : '#CCC5B9';
        ctx.beginPath();
        if (ctx.roundRect) ctx.roundRect(x, y, barW, Math.max(h, 2), [4, 4, 0, 0]);
        else ctx.rect(x, y, barW, Math.max(h, 2));
        ctx.fill();
        bars.push({ x, y, w: barW, label, value: values[i] });

        // X labels (skip some when there are many)
        const every = labels.length > 8 && width < 600 ? 3 : labels.length > 8 ? 2 : 1;
        if (i % every === 0) {
            ctx.fillStyle = '#403D39';
            ctx.textAlign = 'center';
            ctx.fillText(label, x + barW / 2, height - pad.bottom + 18);
        }
    });

    // Tooltip on hover
    const tooltip = canvas.parentElement.querySelector('.chart-tooltip');
    canvas.onmousemove = (e) => {
        const rect = canvas.getBoundingClientRect();
        const mx = e.clientX - rect.left;
        const bar = bars.find((b) => mx >= b.x - 4 && mx <= b.x + b.w + 4);
        if (bar && tooltip) {
            tooltip.style.display = 'block';
            tooltip.style.left = bar.x + bar.w / 2 + 'px';
            tooltip.style.top = Math.min(bar.y, pad.top + chartH - 2) + 'px';
            tooltip.textContent = bar.label + ': ' + prefix + Number(bar.value).toLocaleString(undefined, { minimumFractionDigits: prefix ? 2 : 0 });
        } else if (tooltip) {
            tooltip.style.display = 'none';
        }
    };
    canvas.onmouseleave = () => tooltip && (tooltip.style.display = 'none');
}
function setupCharts() {
    const canvases = document.querySelectorAll('canvas[data-chart]');
    canvases.forEach(drawBarChart);
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => canvases.forEach(drawBarChart), 150);
    });
}

/* 11. Switch chart between "revenue" and "number of sales" ---------------- */
function setupChartSwitch() {
    document.querySelectorAll('[data-chart-switch]').forEach((select) => {
        select.addEventListener('change', () => {
            const canvas = document.querySelector(select.dataset.chartSwitch);
            const all = JSON.parse(canvas.dataset.all);
            const data = JSON.parse(canvas.dataset.chart);
            data.values = all[select.value];
            data.prefix = select.value === 'revenue' ? all.prefix : '';
            canvas.dataset.chart = JSON.stringify(data);
            drawBarChart(canvas);
        });
    });
}

/* START EVERYTHING when the page is loaded */
document.addEventListener('DOMContentLoaded', () => {
    setupMobileMenu();
    setupDropdowns();
    setupAlerts();
    setupConfirmForms();
    setupImagePreviews();
    setupGcashInput();
    setupModals();
    setupReviewButtons();
    setupDownloads();
    setupCharts();
    setupChartSwitch();
});
