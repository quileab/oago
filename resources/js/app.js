import './bootstrap';
import './carousel';
import ApexCharts from 'apexcharts';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

var weeklySalesChart = null;
var topProductsChart = null;
var yearlySalesChart = null;

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
    if(yearlySalesChart) {
        yearlySalesChart.destroy();
        yearlySalesChart = null;
    }
});

document.addEventListener('livewire:init', () => {
    const currencyFormatter = new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    });

    Livewire.on('updateCharts', (data) => {
        const chartData = data[0] || data;

        // Chart 1: Yearly Sales
        var yearlySalesDataRaw = chartData.yearlySales || {};
        var yearlySalesData = Object.values(yearlySalesDataRaw);
        var yearlySalesCategories = Object.keys(yearlySalesDataRaw);

        var yearlySalesOptions = {
            chart: {
                type: 'line',
                height: 350
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
                data: yearlySalesData.length > 0 ? yearlySalesData : [0]
            }],
            xaxis: {
                categories: yearlySalesCategories.length > 0 ? yearlySalesCategories : ['No Data']
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return currencyFormatter.format(value);
                    }
                }
            }
        };

        if (yearlySalesChart) {
            yearlySalesChart.updateOptions(yearlySalesOptions);
        }
        else {
            if (document.querySelector("#yearlySalesChart")) {
                yearlySalesChart = new ApexCharts(document.querySelector("#yearlySalesChart"), yearlySalesOptions);
                yearlySalesChart.render();
            }
        }

        // Chart 2: Weekly sales
        var weeklySalesDataRaw = chartData.weeklySales || {};
        var weeklySalesData = Object.values(weeklySalesDataRaw);
        var weeklySalesCategories = Object.keys(weeklySalesDataRaw);

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
        var topProductsDataRaw = chartData.topProducts || {};
        var topProductsData = Object.values(topProductsDataRaw);
        var topProductsCategories = Object.keys(topProductsDataRaw);

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
                categories: topProductsCategories.length > 0 ? topProductsCategories : ['No Data'], // Ensure categories are not empty
                labels: {
                    show: false
                }
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

        // Force a redraw by dispatching a resize event
        window.dispatchEvent(new Event('resize'));
    });
});
