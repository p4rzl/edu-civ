const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

if (menuToggle && navLinks) {
    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('open');
    });
}

const notifyToggle = document.getElementById('notifyToggle');
const notifyMenu = document.getElementById('notifyMenu');

if (notifyToggle && notifyMenu) {
    notifyToggle.addEventListener('click', () => {
        const isOpen = notifyMenu.classList.toggle('open');
        notifyToggle.setAttribute('aria-expanded', String(isOpen));
        notifyMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
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

