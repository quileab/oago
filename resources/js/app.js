import './bootstrap';
import './carousel';
import ApexCharts from 'apexcharts';

document.addEventListener('livewire:init', () => {
    var weeklySalesChart = null;
    var topProductsChart = null;
    var yearlySalesChart = null;

    Livewire.on('updateCharts', (data) => {
        const chartData = data[0] || data;

        // Chart 1: Yearly Sales
        if (chartData.yearlySales) {
            var yearlySalesData = Object.values(chartData.yearlySales);
            var yearlySalesCategories = Object.keys(chartData.yearlySales);

            var yearlySalesOptions = {
                chart: {
                    type: 'line',
                    height: 350
                },
                tooltip: {
                    theme: 'dark'
                },
                series: [{
                    name: 'Sales',
                    data: yearlySalesData.length > 0 ? yearlySalesData : [0]
                }],
                xaxis: {
                    categories: yearlySalesCategories.length > 0 ? yearlySalesCategories : ['No Data']
                }
            }

            if (yearlySalesChart) {
                yearlySalesChart.updateOptions(yearlySalesOptions);
            }
            else {
                yearlySalesChart = new ApexCharts(document.querySelector("#yearlySalesChart"), yearlySalesOptions);
                yearlySalesChart.render();
            }
        }

        // Chart 2: Weekly sales
        var weeklySalesData = Object.values(chartData.weeklySales);
        var weeklySalesCategories = Object.keys(chartData.weeklySales);

        var weeklySalesOptions = {
            chart: {
                type: 'bar'
            },
            tooltip: {
                theme: 'dark'
            },
            series: [{
                name: 'Sales',
                data: weeklySalesData.length > 0 ? weeklySalesData : [0] // Ensure data is not empty
            }],
            xaxis: {
                categories: weeklySalesCategories.length > 0 ? weeklySalesCategories : ['No Data'] // Ensure categories are not empty
            }
        }

        if (weeklySalesChart) {
            weeklySalesChart.updateOptions(weeklySalesOptions);
        }
        else {
            weeklySalesChart = new ApexCharts(document.querySelector("#weeklySalesChart"), weeklySalesOptions);
            weeklySalesChart.render();
        }

        // Chart 3: Top 5 most sold products
        var topProductsData = Object.values(chartData.topProducts);
        var topProductsCategories = Object.keys(chartData.topProducts);

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
                categories: topProductsCategories.length > 0 ? topProductsCategories : ['No Data'],
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
        }
        else {
            topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), topProductsOptions);
            topProductsChart.render();
        }

        // Force a redraw by dispatching a resize event
        window.dispatchEvent(new Event('resize'));
    });
});