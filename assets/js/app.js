// Solicity Bank — shared interactivity. No build step: this runs as-is.
document.addEventListener('DOMContentLoaded', function () {

    // ---------- marketing nav toggle ----------
    var navToggle = document.querySelector('.nav-toggle');
    var navLinks = document.querySelector('.navlinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () { navLinks.classList.toggle('open'); });
    }

    // ---------- app sidebar toggle (mobile) ----------
    var sideToggle = document.querySelector('[data-sidebar-toggle]');
    var sidebar = document.querySelector('.sidebar');
    var backdrop = document.querySelector('.sidebar-backdrop');
    function closeSidebar() { sidebar && sidebar.classList.remove('open'); backdrop && backdrop.classList.remove('show'); }
    if (sideToggle && sidebar) {
        sideToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            backdrop && backdrop.classList.toggle('show');
        });
    }
    backdrop && backdrop.addEventListener('click', closeSidebar);

    // ---------- scroll reveal ----------
    var revealEls = document.querySelectorAll('.reveal, .reveal-scale');
    if ('IntersectionObserver' in window && revealEls.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('in'); });
    }

    // ---------- animated counters: <span data-count="12500" data-prefix="$" data-decimals="0"> ----------
    var counters = document.querySelectorAll('[data-count]');
    if (counters.length) {
        var countIo = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                animateCount(entry.target);
                countIo.unobserve(entry.target);
            });
        }, { threshold: 0.4 });
        counters.forEach(function (el) { countIo.observe(el); });
    }
    function animateCount(el) {
        var target = parseFloat(el.getAttribute('data-count'));
        var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var prefix = el.getAttribute('data-prefix') || '';
        var suffix = el.getAttribute('data-suffix') || '';
        var duration = 1400;
        var startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var value = target * eased;
            el.textContent = prefix + value.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // ---------- 3D tilt on hero card ----------
    var stage = document.querySelector('.card-stage');
    var tiltCard = stage ? stage.querySelector('.credit-card') : null;
    if (stage && tiltCard && window.matchMedia('(min-width: 900px)').matches) {
        stage.addEventListener('mousemove', function (e) {
            var rect = stage.getBoundingClientRect();
            var x = (e.clientX - rect.left) / rect.width - 0.5;
            var y = (e.clientY - rect.top) / rect.height - 0.5;
            tiltCard.style.transform = 'rotateY(' + (-14 + x * 18) + 'deg) rotateX(' + (6 - y * 14) + 'deg)';
        });
        stage.addEventListener('mouseleave', function () {
            tiltCard.style.transform = '';
        });
    }

    // ---------- flip card widget: <div class="flip-stage"><div class="flip-card">...front/back...</div></div> ----------
    document.querySelectorAll('.flip-card').forEach(function (card) {
        card.addEventListener('click', function () { card.classList.toggle('flipped'); });
    });

    // ---------- convert any server-rendered flash into a slide-in toast ----------
    var stack = document.getElementById('toast-stack');
    document.querySelectorAll('.flash[data-flash]').forEach(function (f) {
        var type = f.getAttribute('data-flash');
        var msg = f.textContent.trim();
        f.remove();
        if (stack && msg) solicityToast(type, msg);
    });

    // ---------- FAQ accordion ----------
    document.querySelectorAll('.faq-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.faq-item');
            var answer = item.querySelector('.faq-a');
            var isOpen = item.classList.contains('open');
            item.parentElement.querySelectorAll('.faq-item.open').forEach(function (other) {
                if (other !== item) { other.classList.remove('open'); other.querySelector('.faq-a').style.maxHeight = null; }
            });
            item.classList.toggle('open', !isOpen);
            answer.style.maxHeight = !isOpen ? (answer.scrollHeight + 'px') : null;
        });
    });

    // ---------- modal: [data-modal-open="id"] / [data-modal-close] ----------
    document.querySelectorAll('[data-modal-open]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var el = document.getElementById(trigger.getAttribute('data-modal-open'));
            if (el) el.classList.add('show');
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            trigger.closest('.modal-backdrop').classList.remove('show');
        });
    });
    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) backdrop.classList.remove('show');
        });
    });

    // ---------- first-visit welcome modal: <div id="welcome-modal" data-once="welcome-seen"> ----------
    var welcome = document.getElementById('welcome-modal');
    if (welcome) {
        var key = 'solicity:' + (welcome.getAttribute('data-once') || 'welcome-seen');
        var seen = false;
        try { seen = sessionStorage.getItem(key) === '1'; } catch (e) {}
        if (!seen) {
            setTimeout(function () { welcome.classList.add('show'); }, 500);
            try { sessionStorage.setItem(key, '1'); } catch (e) {}
        }
    }

    // ---------- sparklines: <canvas class="sparkline" data-points="1,2,3" data-color="#3ddc97"> ----------
    document.querySelectorAll('canvas.sparkline').forEach(function (c) {
        if (typeof Chart === 'undefined') return;
        var points = (c.getAttribute('data-points') || '').split(',').map(Number).filter(function (n) { return !isNaN(n); });
        if (points.length < 2) return;
        var color = c.getAttribute('data-color') || '#d4af6a';
        new Chart(c, {
            type: 'line',
            data: { labels: points.map(function (_, i) { return i; }), datasets: [{ data: points, borderColor: color, borderWidth: 2, pointRadius: 0, tension: .4, fill: false }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { x: { display: false }, y: { display: false } },
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                elements: { line: { capBezierPoints: true } }
            }
        });
    });
});

// ---------- toast notifications: solicityToast('success'|'error', 'message') ----------
function solicityToast(type, message, duration) {
    var stack = document.getElementById('toast-stack');
    if (!stack) return;
    var toast = document.createElement('div');
    toast.className = 'toast ' + (type || 'success');
    toast.innerHTML = '<span class="dot"></span><span>' + message + '</span>';
    stack.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('show'); });
    setTimeout(function () {
        toast.classList.remove('show');
        setTimeout(function () { toast.remove(); }, 400);
    }, duration || 5000);
}

// ---------- Chart.js theme defaults, applied once Chart.js has loaded ----------
function solicityChartDefaults() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.color = '#aab2c0';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.display = false;
    Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
}
