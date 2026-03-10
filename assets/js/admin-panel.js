document.addEventListener('DOMContentLoaded', () => {
    const users = Array.isArray(window.USERS_DATA) ? window.USERS_DATA : [];
    const buoys = Array.isArray(window.BUOYS_DATA) ? window.BUOYS_DATA : [];
    const readings = Array.isArray(window.BUOY_READINGS) ? window.BUOY_READINGS : [];

    const userSelect = document.getElementById('userSelect');
    const userFullName = document.getElementById('userFullName');
    const userUsername = document.getElementById('userUsername');
    const userEmail = document.getElementById('userEmail');
    const userRole = document.getElementById('userRole');

    const buoySelect = document.getElementById('buoySelect');
    const buoyName = document.getElementById('buoyName');
    const buoyZone = document.getElementById('buoyZone');
    const buoyLat = document.getElementById('buoyLat');
    const buoyLng = document.getElementById('buoyLng');
    const buoyStatus = document.getElementById('buoyStatus');
    const simulateBuoyId = document.getElementById('simulateBuoyId');

    const chartSelect = document.getElementById('chartBuoySelect');
    const chartCanvas = document.getElementById('buoyChart');

    const hydrateUserForm = () => {
        if (!userSelect) {
            return;
        }

        const selectedId = Number(userSelect.value);
        const current = users.find((item) => Number(item.id) === selectedId);
        if (!current) {
            return;
        }

        if (userFullName) userFullName.value = current.full_name || '';
        if (userUsername) userUsername.value = current.username || '';
        if (userEmail) userEmail.value = current.email || '';
        if (userRole) userRole.value = current.role || 'compratore';
    };

    const hydrateBuoyForm = () => {
        if (!buoySelect) {
            return;
        }

        const selectedId = Number(buoySelect.value);
        const current = buoys.find((item) => Number(item.id) === selectedId);
        if (!current) {
            return;
        }

        if (buoyName) buoyName.value = current.name || '';
        if (buoyZone) buoyZone.value = current.zone || '';
        if (buoyLat) buoyLat.value = current.lat || '';
        if (buoyLng) buoyLng.value = current.lng || '';
        if (buoyStatus) buoyStatus.value = current.status || '';
        if (simulateBuoyId) simulateBuoyId.value = String(selectedId);
    };

    if (userSelect) {
        hydrateUserForm();
        userSelect.addEventListener('change', hydrateUserForm);
    }

    if (buoySelect) {
        hydrateBuoyForm();
        buoySelect.addEventListener('change', hydrateBuoyForm);
    }

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
