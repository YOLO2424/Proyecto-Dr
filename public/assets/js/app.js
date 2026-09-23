(function () {
    'use strict';

    // ---- Delegacion: sobreviven al reemplazo de contenido (live) ----

    document.addEventListener('submit', function (e) {
        var form = e.target && e.target.closest ? e.target.closest('form[data-confirm]') : null;
        if (form && !window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    }, false);

    document.addEventListener('click', function (e) {
        var printBtn = e.target.closest ? e.target.closest('[data-print]') : null;
        if (printBtn) {
            window.print();
            return;
        }
        var gen = e.target.closest ? e.target.closest('[data-generate-regqr]') : null;
        if (gen) {
            fetch('/registros/qr/nuevo-token')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.url) return;
                    var box = document.getElementById('regqr-area');
                    if (box) box.style.display = 'inline-block';
                    var img = document.getElementById('regqr-img');
                    if (img) img.src = '/qr/imagen?t=' + encodeURIComponent(data.url);
                    var payload = document.getElementById('regqr-payload');
                    if (payload) payload.textContent = data.url;
                })
                .catch(function () {});
        }
    }, false);

    // ---- Utilidades de fecha ----

    function formatDate(iso) {
        if (!iso) return '—';
        var bits = iso.split('-');
        if (bits.length === 3) return bits[2] + '/' + bits[1] + '/' + bits[0];
        return iso;
    }

    function formatDatetime(v) {
        if (!v) return '—';
        var parts = v.split(' ');
        return formatDate(parts[0]) + (parts[1] ? ' ' + parts[1].slice(0, 5) : '');
    }

    function renderAutoDates() {
        document.querySelectorAll('.auto-date').forEach(function (el) {
            var v = el.getAttribute('data-value');
            if (v) el.textContent = formatDate(v);
        });
    }

    // ---- Contenido en vivo (sin recargar la pagina) ----

    var LIVE_PATHS = ['/', '/registros', '/backups'];
    var POLL_MS = 10000;
    var REFRESH_MS = 15000;

    function isLivePath(p) {
        return LIVE_PATHS.indexOf(p) !== -1;
    }

    function isEditingNow() {
        var el = document.activeElement;
        return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT');
    }

    function updateBadge(data) {
        var badge = document.getElementById('badge-prereg');
        if (!badge) return;
        var n = data && data.pending ? data.pending : 0;
        badge.textContent = n;
        badge.style.display = n > 0 ? '' : 'none';
    }

    function buildLiveUrl() {
        var sep = location.search ? '&' : '?';
        return location.pathname + location.search + sep + 'live=1';
    }

    function refreshLiveRegion() {
        if (isEditingNow()) return;
        fetch(buildLiveUrl(), { headers: { 'Accept': 'text/html' } })
            .then(function (r) {
                if (r.redirected) {
                    location.href = r.url;
                    return null;
                }
                if (!r.ok) return Promise.reject();
                return r.text();
            })
            .then(function (html) {
                if (html === null) return;
                var region = document.getElementById('live-region');
                if (!region) return;
                region.innerHTML = html;
                renderAutoDates();
                return fetch('/live/estado').then(function (r) { return r.json(); }).then(updateBadge);
            })
            .catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderAutoDates();

        setInterval(function () {
            fetch('/live/estado', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                .then(updateBadge)
                .catch(function () {});
        }, POLL_MS);

        if (isLivePath(location.pathname)) {
            setInterval(refreshLiveRegion, REFRESH_MS);
        }
    });
})();