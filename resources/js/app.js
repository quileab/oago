import './bootstrap';
import './carousel';
import ApexCharts from 'apexcharts';

var weeklySalesChart = null;
var topProductsChart = null;
var lastYearSalesByWeekChart = null;

document.addEventListener('livewire:navigated', () => {
    // Destroy charts on navigation to allow re-creation
    if(weeklySalesChart) {
        weeklySalesChart.destroy();
        weeklySalesChart = null;
    }
    if(topProductsChart) {
        topProductsChart.destroy();
        topProductsChart = null;
    }
    if(lastYearSalesByWeekChart) {
        lastYearSalesByWeekChart.destroy();
        lastYearSalesByWeekChart = null;
    }
});

document.addEventListener('livewire:init', () => {
    const currencyFormatter = new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    });

    Livewire.on('updateCharts', (data) => {
        // Chart 2: Weekly sales
        var weeklySalesData = Object.values(data[0].weeklySales);
        var weeklySalesCategories = Object.keys(data[0].weeklySales);

        var weeklySalesOptions = {
            chart: {
                type: 'bar'
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (value) {
                        return currencyFormatter.format(value);
                    }
                }
            },
            series: [{
                name: 'Sales',
                data: weeklySalesData.length > 0 ? weeklySalesData : [0] // Ensure data is not empty
            }],
            xaxis: {
                categories: weeklySalesCategories.length > 0 ? weeklySalesCategories : ['No Data'] // Ensure categories are not empty
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return currencyFormatter.format(value);
                    }
                }
            }
        }

        if (weeklySalesChart) {
            weeklySalesChart.updateOptions(weeklySalesOptions);
        } else {
            if (document.querySelector("#weeklySalesChart")) {
                weeklySalesChart = new ApexCharts(document.querySelector("#weeklySalesChart"), weeklySalesOptions);
                weeklySalesChart.render();
            }
        }

        // Chart 3: Top 5 most sold products
        var topProductsData = Object.values(data[0].topProducts);
        var topProductsCategories = Object.keys(data[0].topProducts);

        var topProductsOptions = {
            chart: {
                type: 'bar'
            },
            tooltip: {
                theme: 'dark'
            },
            series: [{
                name: 'Quantity',
                data: topProductsData.length > 0 ? topProductsData : [0] // Ensure data is not empty
            }],
            xaxis: {
                categories: topProductsCategories.length > 0 ? topProductsCategories : ['No Data'] // Ensure categories are not empty
            },
            plotOptions: {
                bar: {
                    distributed: true
                }
            }
        }

        if (topProductsChart) {
            topProductsChart.updateOptions(topProductsOptions);
        } else {
            if (document.querySelector("#topProductsChart")) {
                topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), topProductsOptions);
                topProductsChart.render();
            }
        }

        // Last Year Sales By Week Chart
        var lastYearSalesByWeekData = Object.values(data[0].lastYearSalesByWeek);
        var lastYearSalesByWeekCategories = Object.keys(data[0].lastYearSalesByWeek);

        var lastYearSalesByWeekOptions = {
            chart: {
                type: 'line'
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (value) {
                        return currencyFormatter.format(value);
                    }
                }
            },
            series: [{
                name: 'Sales',
                data: lastYearSalesByWeekData.length > 0 ? lastYearSalesByWeekData : [0]
            }],
            xaxis: {
                categories: lastYearSalesByWeekCategories.length > 0 ? lastYearSalesByWeekCategories : ['No Data']
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return currencyFormatter.format(value);
                    }
                }
            }
        };

        if (lastYearSalesByWeekChart) {
            lastYearSalesByWeekChart.updateOptions(lastYearSalesByWeekOptions);
        } else {
            if (document.querySelector("#lastYearSalesByWeekChart")) {
                lastYearSalesByWeekChart = new ApexCharts(document.querySelector("#lastYearSalesByWeekChart"), lastYearSalesByWeekOptions);
                lastYearSalesByWeekChart.render();
            }
        }

        // Force a redraw by dispatching a resize event
        window.dispatchEvent(new Event('resize'));
    });
});