document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () { window.print(); });
    });

    const says = document.querySelectorAll('.auto-date');
    says.forEach(function (el) {
        const v = el.getAttribute('data-value');
        if (v) el.textContent = formatDate(v);
    });
});

function formatDate(iso) {
    if (!iso) return '—';
    const bits = iso.split('-');
    if (bits.length === 3) return bits[2] + '/' + bits[1] + '/' + bits[0];
    return iso;
}

function formatDatetime(v) {
    if (!v) return '—';
    const parts = v.split(' ');
    return formatDate(parts[0]) + (parts[1] ? ' ' + parts[1].slice(0, 5) : '');
}