// ============================================
// GestionPro - Main JavaScript
// ============================================

// Service worker registration (PWA offline shell)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const swPath = (window.APP_BASE || '') + '/sw.js';
        navigator.serviceWorker.register(swPath, { scope: (window.APP_BASE || '') + '/' })
            .catch(() => {});
    });
}

// Theme Toggle with icon swap
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeIcon(next);
}

function updateThemeIcon(theme) {
    const btn = document.querySelector('.theme-toggle');
    if (!btn) return;
    const icon = btn.querySelector('i');
    if (!icon) return;
    icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Load saved theme
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
    }
    document.addEventListener('DOMContentLoaded', function() {
        updateThemeIcon(saved || (document.documentElement.getAttribute('data-theme') || 'light'));
    });
})();

// Auto-hide toasts with smooth animation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toast').forEach(function(toast) {
        // Progress bar for toast auto-dismiss
        const progress = document.createElement('div');
        progress.style.cssText = 'position:absolute;bottom:0;left:0;height:3px;background:rgba(255,255,255,0.4);border-radius:0 0 var(--radius) var(--radius);transition:width 4s linear;width:100%;';
        toast.style.position = 'relative';
        toast.style.overflow = 'hidden';
        toast.appendChild(progress);
        requestAnimationFrame(function() { progress.style.width = '0%'; });

        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    });
});

// Modal functions with smooth transitions
function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    // Focus first input
    requestAnimationFrame(function() {
        const input = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (input) input.focus();
    });
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// Sidebar mobile toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('open');
}

// Close sidebar on mobile when navigating
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.nav-item').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                var sidebar = document.getElementById('sidebar');
                if (sidebar) sidebar.classList.remove('open');
            }
        });
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024) {
            var sidebar = document.getElementById('sidebar');
            var toggle = document.querySelector('.mobile-toggle');
            if (sidebar && sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                (!toggle || !toggle.contains(e.target))) {
                sidebar.classList.remove('open');
            }
        }
    });
});

// Confirm delete
function confirmDelete(form, message) {
    if (confirm(message || 'Etes-vous sur de vouloir supprimer cet element ?')) {
        form.submit();
    }
    return false;
}

// Format number as currency
function formatMoney(amount) {
    const symbol = (typeof APP_CURRENCY_SYMBOL !== 'undefined') ? APP_CURRENCY_SYMBOL : '$';
    const locale = (typeof APP_LANG !== 'undefined') ? APP_LANG : 'en';
    return symbol + new Intl.NumberFormat(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
}

// Ripple effect on buttons
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn');
    if (!btn || btn.classList.contains('btn-icon')) return;

    const rect = btn.getBoundingClientRect();
    const ripple = document.createElement('span');
    const size = Math.max(rect.width, rect.height);
    ripple.style.cssText = 'position:absolute;border-radius:50%;background:rgba(255,255,255,0.3);transform:scale(0);animation:ripple 0.5s ease-out;pointer-events:none;width:' + size + 'px;height:' + size + 'px;left:' + (e.clientX - rect.left - size/2) + 'px;top:' + (e.clientY - rect.top - size/2) + 'px;';
    btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(ripple);
    setTimeout(function() { ripple.remove(); }, 500);
});

// Add ripple keyframe if not exists
(function() {
    if (!document.getElementById('ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = '@keyframes ripple{to{transform:scale(2.5);opacity:0;}}';
        document.head.appendChild(style);
    }
})();

// Smooth number counting for KPI values on dashboard
document.addEventListener('DOMContentLoaded', function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.kpi-card, .card').forEach(function(el) {
        observer.observe(el);
    });
});
