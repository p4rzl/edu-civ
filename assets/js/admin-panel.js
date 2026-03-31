document.addEventListener('DOMContentLoaded', () => {
    const users = Array.isArray(window.USERS_DATA) ? window.USERS_DATA : [];
    const buoys = Array.isArray(window.BUOYS_DATA) ? window.BUOYS_DATA : [];
    const readings = Array.isArray(window.BUOY_READINGS) ? window.BUOY_READINGS : [];

    const userModal = document.getElementById('userEditModal');
    const userModalForm = document.getElementById('userEditModalForm');
    const modalUserId = document.getElementById('modalUserId');
    const modalUserFullName = document.getElementById('modalUserFullName');
    const modalUserUsername = document.getElementById('modalUserUsername');
    const modalUserEmail = document.getElementById('modalUserEmail');
    const modalUserRole = document.getElementById('modalUserRole');

    const buoyModal = document.getElementById('buoyEditModal');
    const buoyModalForm = document.getElementById('buoyEditModalForm');
    const modalBuoyId = document.getElementById('modalBuoyId');
    const modalBuoyName = document.getElementById('modalBuoyName');
    const modalBuoyZone = document.getElementById('modalBuoyZone');
    const modalBuoyLat = document.getElementById('modalBuoyLat');
    const modalBuoyLng = document.getElementById('modalBuoyLng');
    const modalBuoyStatus = document.getElementById('modalBuoyStatus');

    const chartSelect = document.getElementById('chartBuoySelect');
    const chartCanvas = document.getElementById('buoyChart');

    const openModal = (modal) => {
        if (!modal) {
            return;
        }
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('.user-edit-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const id = Number(button.getAttribute('data-user-id'));
            const current = users.find((item) => Number(item.id) === id);
            if (!current || !userModalForm) {
                return;
            }
            if (modalUserId) modalUserId.value = String(current.id || '');
            if (modalUserFullName) modalUserFullName.value = current.full_name || '';
            if (modalUserUsername) modalUserUsername.value = current.username || '';
            if (modalUserEmail) modalUserEmail.value = current.email || '';
            if (modalUserRole) modalUserRole.value = current.role || 'compratore';
            openModal(userModal);
        });
    });

    document.querySelectorAll('.buoy-edit-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const id = Number(button.getAttribute('data-buoy-id'));
            const current = buoys.find((item) => Number(item.id) === id);
            if (!current || !buoyModalForm) {
                return;
            }
            if (modalBuoyId) modalBuoyId.value = String(current.id || '');
            if (modalBuoyName) modalBuoyName.value = current.name || '';
            if (modalBuoyZone) modalBuoyZone.value = current.zone || '';
            if (modalBuoyLat) modalBuoyLat.value = current.lat || '';
            if (modalBuoyLng) modalBuoyLng.value = current.lng || '';
            if (modalBuoyStatus) modalBuoyStatus.value = current.status || '';
            openModal(buoyModal);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-modal-close');
            const targetModal = targetId ? document.getElementById(targetId) : null;
            closeModal(targetModal);
        });
    });

    [userModal, buoyModal].forEach((modal) => {
        if (!modal) {
            return;
        }
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    if (!chartCanvas || typeof Chart === 'undefined') {
        return;
    }

    const byBuoy = readings.reduce((acc, row) => {
        const key = Number(row.buoy_id);
        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(row);
        return acc;
    }, {});

    let buoyChart = null;

    const renderChart = () => {
        const selectedId = Number((chartSelect && chartSelect.value) || 0);
        const rows = (byBuoy[selectedId] || []).slice(-20);

        const labels = rows.map((row) => row.recorded_at);
        const salinity = rows.map((row) => Number(row.salinity));
        const oxygen = rows.map((row) => Number(row.dissolved_oxygen));
        const microplastics = rows.map((row) => Number(row.microplastics_percent));

        if (buoyChart) {
            buoyChart.destroy();
        }

        buoyChart = new Chart(chartCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Salinita (PSU)',
                        data: salinity,
                        borderColor: '#0c7fb3',
                        backgroundColor: 'rgba(12,127,179,0.18)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Ossigeno disciolto (mg/L)',
                        data: oxygen,
                        borderColor: '#00a77f',
                        backgroundColor: 'rgba(0,167,127,0.12)',
                        tension: 0.35,
                        fill: false,
                    },
                    {
                        label: 'Microplastiche (%)',
                        data: microplastics,
                        borderColor: '#e7683c',
                        backgroundColor: 'rgba(231,104,60,0.1)',
                        tension: 0.35,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 8,
                        },
                    },
                },
            },
        });
    };

    renderChart();

    if (chartSelect) {
        chartSelect.addEventListener('change', renderChart);
    }
});
