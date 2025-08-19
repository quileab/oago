import './bootstrap';
import './carousel';
import ApexCharts from 'apexcharts';

document.addEventListener('livewire:init', () => {
    var weeklySalesChart = null;
    var topProductsChart = null;

    Livewire.on('updateCharts', (data) => {
        // Chart 2: Weekly sales
        var weeklySalesData = Object.values(data[0].weeklySales);
        var weeklySalesCategories = Object.keys(data[0].weeklySales);

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
        }
        else {
            topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), topProductsOptions);
            topProductsChart.render();
        }

        // Force a redraw by dispatching a resize event
        window.dispatchEvent(new Event('resize'));
    });
});