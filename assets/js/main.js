const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');
const headerTools = document.getElementById('headerTools');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.classList.toggle('is-open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        if (navLinks) {
            navLinks.classList.toggle('open', isOpen);
        }
        if (headerTools) {
            headerTools.classList.toggle('open', isOpen);
        }
    });
}

const notifyToggle = document.getElementById('notifyToggle');
const notifyMenu = document.getElementById('notifyMenu');
const userMenuToggle = document.getElementById('userMenuToggle');
const userMenu = document.getElementById('userMenu');
const settingsToggle = document.getElementById('settingsToggle');
const settingsModal = document.getElementById('userSettingsModal');
const openAddProductModal = document.getElementById('openAddProductModal');
const addProductModal = document.getElementById('addProductModal');
const themeToggleHeader = document.getElementById('themeToggleHeader');

if (notifyToggle && notifyMenu) {
    notifyToggle.addEventListener('click', () => {
        const isOpen = notifyMenu.classList.toggle('open');
        notifyToggle.setAttribute('aria-expanded', String(isOpen));
        notifyMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (userMenu) {
            userMenu.classList.remove('open');
            if (userMenuToggle) {
                userMenuToggle.setAttribute('aria-expanded', 'false');
            }
            userMenu.setAttribute('aria-hidden', 'true');
        }
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (!notifyMenu.contains(target) && !notifyToggle.contains(target)) {
            notifyMenu.classList.remove('open');
            notifyToggle.setAttribute('aria-expanded', 'false');
            notifyMenu.setAttribute('aria-hidden', 'true');
        }

        if (userMenu && userMenuToggle && !userMenu.contains(target) && !userMenuToggle.contains(target)) {
            userMenu.classList.remove('open');
            userMenuToggle.setAttribute('aria-expanded', 'false');
            userMenu.setAttribute('aria-hidden', 'true');
        }
    });
}

if (userMenuToggle && userMenu) {
    userMenuToggle.addEventListener('click', () => {
        const isOpen = userMenu.classList.toggle('open');
        userMenuToggle.setAttribute('aria-expanded', String(isOpen));
        userMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (notifyMenu && notifyToggle) {
            notifyMenu.classList.remove('open');
            notifyToggle.setAttribute('aria-expanded', 'false');
            notifyMenu.setAttribute('aria-hidden', 'true');
        }
    });
}

if (settingsToggle) {
    settingsToggle.addEventListener('click', () => {
        if (settingsModal) {
            settingsModal.classList.add('open');
            settingsModal.setAttribute('aria-hidden', 'false');
            settingsToggle.setAttribute('aria-expanded', 'true');
            return;
        }
    });
}

if (openAddProductModal && addProductModal) {
    openAddProductModal.addEventListener('click', () => {
        addProductModal.classList.add('open');
        addProductModal.setAttribute('aria-hidden', 'false');
    });
}

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        const modalId = button.getAttribute('data-modal-close');
        if (!modalId) {
            return;
        }

        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (settingsToggle && modalId === 'userSettingsModal') {
            settingsToggle.setAttribute('aria-expanded', 'false');
        }

        modal.dispatchEvent(new CustomEvent('modal:closed', { bubbles: true }));
    });
});

const settingsTabs = document.querySelectorAll('[data-settings-tab]');
const settingsPanes = document.querySelectorAll('[data-settings-pane]');
const settingsPanesContainer = document.getElementById('settingsPanes');

const syncSettingsPaneHeight = () => {
    if (!settingsPanesContainer || settingsPanes.length === 0) {
        return;
    }

    let maxHeight = 0;
    settingsPanes.forEach((pane) => {
        pane.classList.add('is-measuring');
        pane.style.visibility = 'hidden';
        pane.style.position = 'absolute';
        pane.style.inset = '0';
        pane.style.display = 'block';
        maxHeight = Math.max(maxHeight, pane.scrollHeight);
        pane.classList.remove('is-measuring');
        pane.style.removeProperty('visibility');
        pane.style.removeProperty('position');
        pane.style.removeProperty('inset');
        pane.style.removeProperty('display');
    });

    settingsPanesContainer.style.minHeight = `${Math.max(maxHeight, 320)}px`;
};

syncSettingsPaneHeight();
window.addEventListener('resize', syncSettingsPaneHeight);

settingsTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-settings-tab');
        if (!target) {
            return;
        }

        settingsTabs.forEach((item) => item.classList.remove('is-active'));
        settingsPanes.forEach((pane) => {
            pane.classList.toggle('is-active', pane.getAttribute('data-settings-pane') === target);
        });
        tab.classList.add('is-active');
        syncSettingsPaneHeight();
    });
});

const setTheme = (theme) => {
    const isDark = theme === 'dark';
    document.body.classList.toggle('theme-dark', isDark);
    if (themeToggleHeader) {
        themeToggleHeader.setAttribute('aria-pressed', String(isDark));
        themeToggleHeader.setAttribute('aria-label', isDark ? 'Passa al tema chiaro' : 'Passa al tema scuro');
        const icon = themeToggleHeader.querySelector('i');
        if (icon) {
            icon.className = isDark ? 'bi bi-moon-stars' : 'bi bi-sun';
        }
    }
};

const preferredTheme = window.localStorage.getItem('lanz-theme') || 'light';
setTheme(preferredTheme);

if (themeToggleHeader) {
    themeToggleHeader.addEventListener('click', () => {
        const next = document.body.classList.contains('theme-dark') ? 'light' : 'dark';
        window.localStorage.setItem('lanz-theme', next);
        setTheme(next);
    });
}

const alerts = document.querySelectorAll('.alert');
alerts.forEach((alert) => {
    setTimeout(() => {
        alert.classList.add('alert-hide');
        setTimeout(() => alert.remove(), 450);
    }, 3800);
});

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.15 }
);

document.querySelectorAll('.reveal').forEach((node) => observer.observe(node));

document.querySelectorAll('.reveal').forEach((node, index) => {
    node.style.transitionDelay = `${Math.min(index * 60, 360)}ms`;
});

// ===== ANIMAZIONI SCROLL AVANZATE =====
document.addEventListener('DOMContentLoaded', () => {
    const chartTextColor = '#3c5f76';
    const chartGridColor = 'rgba(4, 36, 61, 0.08)';

    // Animazioni reveal con delay
    const revealElements = document.querySelectorAll('.reveal');
    revealElements.forEach((el, idx) => {
        if (!el.style.animationDelay) {
            el.style.animationDelay = `${idx * 0.1}s`;
        }
    });

    // ===== GRAFICI CHART.JS =====
    if (typeof Chart !== 'undefined') {
        // Grafico qualita acqua
        const qualityChart = document.getElementById('qualityChart');
        if (qualityChart) {
            new Chart(qualityChart, {
                type: 'line',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
                    datasets: [{
                        label: 'Ossigeno (mg/L)',
                        data: [7.2, 7.4, 7.1, 7.5, 7.3, 7.6, 7.4],
                        borderColor: '#0077b6' /* brand primary */,
                        backgroundColor: 'rgba(0, 119, 182, 0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3
                    }, {
                        label: 'Salinita (PSU)',
                        data: [37.9, 38.1, 37.8, 38.2, 38.0, 37.9, 38.1],
                        borderColor: '#00a7b5' /* brand secondary */,
                        backgroundColor: 'rgba(0, 167, 181, 0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                usePointStyle: true,
                                color: chartTextColor
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: chartTextColor },
                            grid: { color: chartGridColor }
                        },
                        y: {
                            beginAtZero: false,
                            ticks: { color: chartTextColor },
                            grid: { color: chartGridColor }
                        }
                    }
                }
            });
        }

        // Grafico microplastiche
        const microplasticsChart = document.getElementById('microplasticsChart');
        if (microplasticsChart) {
            new Chart(microplasticsChart, {
                type: 'bar',
                data: {
                    labels: ['Boa 01', 'Boa 02', 'Boa 03', 'Boa 04'],
                    datasets: [{
                        label: 'Microplastiche (%)',
                        data: [0.8, 1.1, 0.6, 0.9],
                        backgroundColor: [
                            'rgba(0, 119, 182, 0.6)',
                            'rgba(0, 167, 181, 0.6)',
                            'rgba(0, 119, 182, 0.4)',
                            'rgba(0, 167, 181, 0.4)'
                        ],
                        borderColor: '#0077b6' /* brand primary */,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            ticks: { color: chartTextColor },
                            grid: { color: chartGridColor }
                        },
                        y: {
                            ticks: { color: chartTextColor },
                            grid: { color: 'rgba(4, 36, 61, 0.04)' }
                        }
                    }
                }
            });
        }

        // Grafico status boe
        const buoyStatusChart = document.getElementById('buoyStatusChart');
        if (buoyStatusChart) {
            new Chart(buoyStatusChart, {
                type: 'doughnut',
                data: {
                    labels: ['Online', 'Manutenzione', 'Progetto'],
                    datasets: [{
                        data: [3, 1, 0],
                        backgroundColor: [
                                'rgba(0, 167, 181, 0.8)',
                                'rgba(255, 220, 168, 0.8)',
                                'rgba(0, 119, 182, 0.5)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                color: chartTextColor
                            }
                        }
                    }
                }
            });
        }
    }
});

