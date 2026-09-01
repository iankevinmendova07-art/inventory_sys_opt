document.addEventListener('DOMContentLoaded', function () {
    // 1. Logout confirmation using SweetAlert2
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the inventory system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0D3B66',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'controllers/login/logout.php';
                }
            });
        });
    }

    // 2. Total Supplies Column Chart (With Stock, Out of Stock, Non-Consumable)
    const suppliesCanvas = document.getElementById('suppliesColumnChart');
    if (suppliesCanvas) {
        const withStock = parseInt(suppliesCanvas.getAttribute('data-with-stock')) || 0;
        const outStock = parseInt(suppliesCanvas.getAttribute('data-out-stock')) || 0;
        const nonConsumable = parseInt(suppliesCanvas.getAttribute('data-non-consumable')) || 0;

        new Chart(suppliesCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Consumable (Stock)', 'Consumable (No Stock)', 'Non-Consumable'],
                datasets: [{
                    label: 'Items',
                    data: [withStock, outStock, nonConsumable],
                    backgroundColor: ['#0D3B66', '#dc3545', '#F4D35E'],
                    borderColor: ['#092847', '#b02a37', '#d6b647'],
                    borderWidth: 1.5,
                    borderRadius: 6,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.formattedValue + ' Items';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 3. Total Transactions Column Chart
    const transCanvas = document.getElementById('transactionsColumnChart');
    if (transCanvas) {
        const consumableTrans = parseInt(transCanvas.getAttribute('data-consumable')) || 0;
        const nonConsumableTrans = parseInt(transCanvas.getAttribute('data-non-consumable')) || 0;

        new Chart(transCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Consumable Trans.', 'Non-Consumable Trans.'],
                datasets: [{
                    label: 'Transactions',
                    data: [consumableTrans, nonConsumableTrans],
                    backgroundColor: ['#198754', '#0dcaf0'],
                    borderColor: ['#146c43', '#0baccc'],
                    borderWidth: 1.5,
                    borderRadius: 6,
                    barThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.formattedValue + ' Transactions';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 4. Learning Resources (LR SME) Column Chart (Science vs Math)
    const lrCanvas = document.getElementById('lrSmeColumnChart');
    if (lrCanvas) {
        const scienceCount = parseInt(lrCanvas.getAttribute('data-science')) || 0;
        const mathCount = parseInt(lrCanvas.getAttribute('data-math')) || 0;

        new Chart(lrCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Science Equipment', 'Math Equipment'],
                datasets: [{
                    label: 'Items',
                    data: [scienceCount, mathCount],
                    backgroundColor: ['#0d6efd', '#fd7e14'],
                    borderColor: ['#0b5ed7', '#e06b0e'],
                    borderWidth: 1.5,
                    borderRadius: 6,
                    barThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.formattedValue + ' Items';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
// Textbooks by Subject Chart
const subjCtx = document.getElementById('textbooksSubjectChart');
if (subjCtx) {
    const labels = JSON.parse(subjCtx.getAttribute('data-labels') || '[]');
    const values = JSON.parse(subjCtx.getAttribute('data-values') || '[]');

    new Chart(subjCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Quantity',
                data: values,
                backgroundColor: '#0dcaf0',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false } 
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { precision: 0 } 
                },
                x: {
                    ticks: {
                        // Automatically wrap long labels onto multiple lines or trim them to fit 1 line
                        callback: function(value, index) {
                            const label = this.getLabelForValue(value);
                            // If the label is too long, you can wrap it or return a shortened version
                            if (label.length > 16) {
                                return label.match(/.{1,16}(\s|$)/g); // Splits words into lines of max 16 chars
                            }
                            return label;
                        },
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

// Textbooks by Grade Level Chart
const gradeCtx = document.getElementById('textbooksGradeChart');
if (gradeCtx) {
    const labels = JSON.parse(gradeCtx.getAttribute('data-labels') || '[]');
    const values = JSON.parse(gradeCtx.getAttribute('data-values') || '[]');

    new Chart(gradeCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Quantity',
                data: values,
                backgroundColor: '#ffc107',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}