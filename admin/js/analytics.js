/**
 * Getso Forms Analytics
 *
 * Maneja los gráficos y estadísticas del dashboard
 *
 * @package Getso_Forms
 * @since 1.1.0
 */

(function($) {
    'use strict';

    class GetsoFormsAnalytics {
        constructor() {
            this.charts = {};
            this.init();
        }

        init() {
            // Inicializar cuando DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.initCharts());
            } else {
                this.initCharts();
            }
        }

        /**
         * Inicializar todos los gráficos
         */
        initCharts() {
            // Verificar que Chart.js esté disponible
            if (typeof Chart === 'undefined') {
                console.error('Chart.js no está cargado');
                return;
            }

            // Gráfico de envíos por día
            this.initSubmissionsChart();

            // Gráfico de envíos por formulario
            this.initFormsChart();
        }

        /**
         * Gráfico de envíos por día (últimos 7 días)
         */
        initSubmissionsChart() {
            const canvas = document.getElementById('submissions-chart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Obtener datos del elemento (pasados desde PHP)
            const dataAttr = canvas.dataset.chartData;
            let chartData;

            try {
                chartData = dataAttr ? JSON.parse(dataAttr) : this.getDefaultSubmissionsData();
            } catch (e) {
                chartData = this.getDefaultSubmissionsData();
            }

            this.charts.submissions = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Envíos',
                        data: chartData.data,
                        borderColor: 'rgb(13, 42, 87)',
                        backgroundColor: 'rgba(13, 42, 87, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(13, 42, 87)',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 5,
                        pointRadius: 4,
                        pointHitRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 4,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        /**
         * Gráfico de envíos por formulario (top 5)
         */
        initFormsChart() {
            const canvas = document.getElementById('forms-chart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Obtener datos del elemento (pasados desde PHP)
            const dataAttr = canvas.dataset.chartData;
            let chartData;

            try {
                chartData = dataAttr ? JSON.parse(dataAttr) : this.getDefaultFormsData();
            } catch (e) {
                chartData = this.getDefaultFormsData();
            }

            this.charts.forms = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Envíos',
                        data: chartData.data,
                        backgroundColor: [
                            'rgba(13, 42, 87, 0.8)',
                            'rgba(26, 69, 120, 0.8)',
                            'rgba(39, 96, 153, 0.8)',
                            'rgba(52, 123, 186, 0.8)',
                            'rgba(65, 150, 219, 0.8)'
                        ],
                        borderColor: [
                            'rgb(13, 42, 87)',
                            'rgb(26, 69, 120)',
                            'rgb(39, 96, 153)',
                            'rgb(52, 123, 186)',
                            'rgb(65, 150, 219)'
                        ],
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 4,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        /**
         * Datos por defecto para gráfico de envíos
         */
        getDefaultSubmissionsData() {
            const labels = [];
            const data = [];
            const today = new Date();

            for (let i = 6; i >= 0; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);

                const day = date.getDate().toString().padStart(2, '0');
                const month = (date.getMonth() + 1).toString().padStart(2, '0');

                labels.push(`${day}/${month}`);
                data.push(0);
            }

            return { labels, data };
        }

        /**
         * Datos por defecto para gráfico de formularios
         */
        getDefaultFormsData() {
            return {
                labels: ['Sin datos'],
                data: [0]
            };
        }

        /**
         * Actualizar datos de un gráfico
         */
        updateChart(chartName, newData) {
            if (!this.charts[chartName]) {
                console.error(`Gráfico ${chartName} no encontrado`);
                return;
            }

            const chart = this.charts[chartName];
            chart.data.labels = newData.labels;
            chart.data.datasets[0].data = newData.data;
            chart.update();
        }

        /**
         * Destruir todos los gráficos
         */
        destroy() {
            Object.keys(this.charts).forEach(key => {
                if (this.charts[key]) {
                    this.charts[key].destroy();
                }
            });
            this.charts = {};
        }

        /**
         * Recargar datos del dashboard
         */
        async refreshData() {
            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'getso_forms_get_dashboard_data',
                        nonce: getsoFormsAdmin.nonce
                    }
                });

                if (response.success && response.data) {
                    // Actualizar gráficos
                    if (response.data.submissions_chart) {
                        this.updateChart('submissions', response.data.submissions_chart);
                    }

                    if (response.data.forms_chart) {
                        this.updateChart('forms', response.data.forms_chart);
                    }

                    // Actualizar estadísticas
                    this.updateStats(response.data.stats);
                }
            } catch (error) {
                console.error('Error al refrescar datos:', error);
            }
        }

        /**
         * Actualizar estadísticas del dashboard
         */
        updateStats(stats) {
            if (!stats) return;

            // Actualizar cada estadística si existe el elemento
            Object.keys(stats).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = stats[key];
                }
            });
        }
    }

    // Exportar para uso global
    window.GetsoFormsAnalytics = GetsoFormsAnalytics;

    // Auto-inicializar
    $(document).ready(function() {
        window.getsoAnalytics = new GetsoFormsAnalytics();
    });

})(jQuery);
