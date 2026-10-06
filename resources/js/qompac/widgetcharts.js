const initWidgetCharts = () => {
    if (typeof window.ApexCharts === 'undefined') {
        return;
    }

    const createChart = (selector, options) => {
        const element = document.querySelector(selector);

        if (!element) {
            return null;
        }

        // Evitar duplicar una instancia al navegar con Livewire
        if (element.__qompacChart) {
            return element.__qompacChart;
        }

        const chart = new window.ApexCharts(element, options);

        element.__qompacChart = chart;

        chart.render();

        return chart;
    };

    const colors = window.IQUtils?.getVariableColor?.();

    if (!colors) {
        return;
    }

    // ============================================================
    // Chart 1
    // ============================================================
    createChart('#chart-1', {
        chart: {
            height: 80,
            type: 'area',
            sparkline: {
                enabled: true,
            },
            group: 'sparklines',
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            width: 3,
            curve: 'smooth',
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0,
            },
        },
        series: [
            {
                name: 'series1',
                data: [60, 15, 50, 30, 70],
            },
        ],
        colors: [colors.primary],
        xaxis: {
            type: 'datetime',
            categories: [
                '2018-08-19T00:00:00',
                '2018-09-19T01:30:00',
                '2018-10-19T02:30:00',
                '2018-11-19T01:30:00',
                '2018-12-19T01:30:00',
            ],
        },
        tooltip: {
            x: {
                format: 'dd/MM/yy HH:mm',
            },
            enabled: false,
        },
    });

    // ============================================================
    // Chart 2
    // ============================================================
    createChart('#chart-2', {
        chart: {
            height: 80,
            type: 'area',
            sparkline: {
                enabled: true,
            },
            group: 'sparklines',
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            width: 3,
            curve: 'smooth',
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0,
            },
        },
        series: [
            {
                name: 'series1',
                data: [70, 40, 60, 30, 60],
            },
        ],
        colors: [colors.warning],
        xaxis: {
            type: 'datetime',
            categories: [
                '2018-08-19T00:00:00',
                '2018-09-19T01:30:00',
                '2018-10-19T02:30:00',
                '2018-11-19T01:30:00',
                '2018-12-19T01:30:00',
            ],
        },
        tooltip: {
            x: {
                format: 'dd/MM/yy HH:mm',
            },
            enabled: false,
        },
    });

    // ============================================================
    // Chart 3
    // ============================================================
    createChart('#chart-3', {
        chart: {
            height: 80,
            type: 'area',
            sparkline: {
                enabled: true,
            },
            group: 'sparklines',
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            width: 3,
            curve: 'smooth',
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0,
            },
        },
        series: [
            {
                name: 'series1',
                data: [60, 40, 60, 40, 70],
            },
        ],
        colors: [colors.info],
        xaxis: {
            type: 'datetime',
            categories: [
                '2018-08-19T00:00:00',
                '2018-09-19T01:30:00',
                '2018-10-19T02:30:00',
                '2018-11-19T01:30:00',
                '2018-12-19T01:30:00',
            ],
        },
        tooltip: {
            x: {
                format: 'dd/MM/yy HH:mm',
            },
            enabled: false,
        },
    });

    // ============================================================
    // Chart 4
    // ============================================================
    createChart('#chart-4', {
        chart: {
            height: 80,
            type: 'area',
            sparkline: {
                enabled: true,
            },
            group: 'sparklines',
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            width: 3,
            curve: 'smooth',
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0,
            },
        },
        series: [
            {
                name: 'series1',
                data: [75, 30, 60, 35, 60],
            },
        ],
        colors: [colors.secondary],
        xaxis: {
            type: 'datetime',
            categories: [
                '2018-08-19T00:00:00',
                '2018-09-19T01:30:00',
                '2018-10-19T02:30:00',
                '2018-11-19T01:30:00',
                '2018-12-19T01:30:00',
            ],
        },
        tooltip: {
            x: {
                format: 'dd/MM/yy HH:mm',
            },
            enabled: false,
        },
    });

    // ============================================================
    // Chart 5
    // ============================================================
    createChart('#chart-5', {
        series: [
            {
                name: 'series1',
                data: [25, 27, 24, 26],
            },
        ],
        colors: [colors.primary],
        chart: {
            height: 65,
            width: 140,
            type: 'area',
            sparkline: {
                enabled: true,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'smooth',
        },
        xaxis: {
            type: 'datetime',
            categories: [
                '2018-09-19T00:00:00.000Z',
                '2018-09-19T01:30:00.000Z',
                '2018-09-19T02:30:00.000Z',
                '2018-09-19T03:30:00.000Z',
            ],
        },
        tooltip: {
            enabled: false,
        },
    });

    // ============================================================
    // Widget Box 1
    // ============================================================
    createChart('#iq-chart-box1', {
        series: [
            {
                name: 'Total sales',
                data: [10, 10, 35, 10],
            },
        ],
        colors: [colors.warning],
        chart: {
            height: 50,
            width: 100,
            type: 'line',
            sparkline: {
                enabled: true,
            },
            zoom: {
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'straight',
        },
        title: {
            text: '',
            align: 'left',
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar'],
        },
        tooltip: {
            enabled: false,
        },
    });

    // ============================================================
    // Widget Box 2
    // ============================================================
    createChart('#iq-chart-box2', {
        series: [
            {
                name: 'Sale Today',
                data: [10, 10, 35, 10],
            },
        ],
        colors: [colors.info],
        chart: {
            height: 50,
            width: 100,
            type: 'line',
            sparkline: {
                enabled: true,
            },
            zoom: {
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'straight',
        },
        title: {
            text: '',
            align: 'left',
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar'],
        },
        tooltip: {
            enabled: false,
        },
    });

    // ============================================================
    // Widget Box 3
    // ============================================================
    createChart('#iq-chart-box3', {
        series: [
            {
                name: 'Total Classon',
                data: [10, 10, 35, 10],
            },
        ],
        colors: [colors.secondary],
        chart: {
            height: 50,
            width: 100,
            type: 'line',
            sparkline: {
                enabled: true,
            },
            zoom: {
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'straight',
        },
        title: {
            text: '',
            align: 'left',
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar'],
        },
        tooltip: {
            enabled: false,
        },
    });

    // ============================================================
    // Widget Box 4
    // ============================================================
    createChart('#iq-chart-box4', {
        series: [
            {
                name: 'Total Profit',
                data: [10, 10, 35, 10],
            },
        ],
        colors: [colors.primary],
        chart: {
            height: 50,
            width: 100,
            type: 'line',
            sparkline: {
                enabled: true,
            },
            zoom: {
                enabled: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'straight',
        },
        title: {
            text: '',
            align: 'left',
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar'],
        },
        tooltip: {
            enabled: false,
        },
    });

    // ============================================================
    // Extra Chart
    // ============================================================
    createChart('#extrachart', {
        series: [
            {
                name: 'Net Profit',
                data: [44, 55, 57, 56, 61, 58],
            },
            {
                name: 'Revenue',
                data: [76, 85, 101, 98, 87, 105],
            },
        ],
        chart: {
            type: 'bar',
            height: 250,
            sparkline: {
                enabled: true,
            },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            show: true,
            width: 2,
            curve: 'smooth',
            colors: ['transparent'],
        },
        xaxis: {
            categories: [
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
            ],
        },
        yaxis: {
            title: {
                text: '$ (thousands)',
            },
        },
        fill: {
            opacity: 1,
            colors: [
                colors.primary,
                colors.secondary,
            ],
        },
        tooltip: {
            y: {
                formatter: (value) =>
                    `$ ${value} thousands`,
            },
        },
    });

    // ============================================================
    // Service Charts
    // ============================================================
    const serviceChartOptions = [
        {
            selector: '#service-chart-01',
            color: colors.info,
            data: [25, 30, 22, 29],
        },
        {
            selector: '#service-chart-02',
            color: colors.secondary,
            data: [25, 23, 28, 26],
        },
        {
            selector: '#service-chart-03',
            color: colors.warning,
            data: [25, 23, 28, 23],
        },
        {
            selector: '#service-chart-04',
            color: colors.primary,
            data: [25, 27, 24, 26],
        },
    ];

    serviceChartOptions.forEach(
        ({ selector, color, data }) => {
            createChart(selector, {
                series: [
                    {
                        name: 'series1',
                        data,
                    },
                ],
                colors: [color],
                chart: {
                    height: 65,
                    width: 140,
                    type: 'area',
                    sparkline: {
                        enabled: true,
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    curve: 'smooth',
                },
                xaxis: {
                    type: 'datetime',
                    categories: [
                        '2018-09-19T00:00:00.000Z',
                        '2018-09-19T01:30:00.000Z',
                        '2018-09-19T02:30:00.000Z',
                        '2018-09-19T03:30:00.000Z',
                    ],
                },
                tooltip: {
                    enabled: false,
                },
            });
        }
    );

    // ============================================================
    // Ethernet Charts
    // ============================================================
    const ethernetCharts = [
        {
            selector: '#ethernet-chart-01',
            color: colors.primary,
        },
        {
            selector: '#ethernet-chart-02',
            color: colors.secondary,
        },
        {
            selector: '#ethernet-chart-03',
            color: colors.info,
        },
        {
            selector: '#ethernet-chart-04',
            color: colors.warning,
        },
    ];

    ethernetCharts.forEach(
        ({ selector, color }) => {
            createChart(selector, {
                series: [
                    {
                        name: 'Bitcoin',
                        data: [
                            5,
                            20,
                            6,
                            18,
                            5,
                            15,
                            4,
                        ],
                    },
                ],
                colors: [color],
                chart: {
                    height: 60,
                    sparkline: {
                        enabled: true,
                    },
                    type: 'line',
                    zoom: {
                        enabled: false,
                    },
                    toolbar: {
                        show: false,
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                },
                legend: {
                    show: false,
                },
                grid: {
                    show: false,
                },
                xaxis: {
                    labels: {
                        show: false,
                    },
                },
                yaxis: {
                    labels: {
                        show: false,
                    },
                },
            });
        }
    );
};

export default initWidgetCharts;
