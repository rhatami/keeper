function initializeAssetChart() {    
    Chart.register(ChartDataLabels);
    const labels = JSON.parse(document.getElementById('labels').textContent);
    const values = JSON.parse(document.getElementById('values').textContent);
    const colors = JSON.parse(document.getElementById('colors').textContent);
    const percentages = JSON.parse(document.getElementById('percentages').textContent);

    new Chart(document.getElementById('assetChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderColor: '#fff',
                borderWidth: 1,
                hoverOffset: 20,
                hoverBorderWidth: 3,
                hoverBorderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const dataset = ctx.chart.data.datasets[0];
                        const total = dataset.data.reduce((acc, data) => acc + data, 0);
                        const percentage = ((value / total) * 100).toFixed(1) + '%';
                        return percentage;
                    },
                    font: {
                        family: 'BMitra',
                        weight: 'bold',
                        size: 14
                    }
                },
               tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            return tooltipItems[0].label;
                        },
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                            const sharePercentage = ((value / total) * 100).toFixed(2);
                            const profitLossPercentage = percentages[context.dataIndex];
                
                            const profitLossLabel = parseFloat(profitLossPercentage) >= 0 ? 'درصد سود' : 'درصد زیان';
                            
                            return [
                                ` سهم از کل دارایی: ${sharePercentage.toLocaleString()} %`,
                                ` مبلغ کل: ${value.toLocaleString()} تومان`,
                                ` ${profitLossLabel}: ${Math.abs(profitLossPercentage)} %`
                            ];
                        }
                    },
                    titleFont: {
                        family: 'BYekan',
                        size: 14
                    },
                    bodyFont: {
                        family: 'BMitra',
                        size: 16
                    },
                    displayColors: true,
                    padding: 10,
                    bodySpacing: 5,
                    bodyAlign: 'right',
                    textDirection: 'rtl',
                    rtl: true
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            },
            hover: {
                mode: 'nearest',
                intersect: true
            }
        }
    });
}