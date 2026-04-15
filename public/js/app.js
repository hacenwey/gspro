// ============================================
// GestionPro - Main JavaScript
// ============================================

// Theme Toggle
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}

// Load saved theme
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) document.documentElement.setAttribute('data-theme', saved);
})();

// Auto-hide toasts
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toast').forEach(function(toast) {
        setTimeout(function() {
            toast.style.animation = 'slideIn 0.3s ease reverse forwards';
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    });
});

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
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

// Confirm delete
function confirmDelete(form, message) {
    if (confirm(message || 'Etes-vous sur de vouloir supprimer cet element ?')) {
        form.submit();
    }
    return false;
}

// Format number as currency
function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' DA';
}

// Close sidebar on mobile when navigating
document.querySelectorAll('.nav-item').forEach(function(item) {
    item.addEventListener('click', function() {
        if (window.innerWidth <= 1024) {
            document.getElementById('sidebar').classList.remove('open');
        }
    });
});
