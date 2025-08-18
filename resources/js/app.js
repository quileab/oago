import './bootstrap';
import './carousel';
import ApexCharts from 'apexcharts';

document.addEventListener('livewire:init', () => {
    Livewire.on('updateCharts', (data) => {
        

        // Chart 2: Weekly sales
        var weeklySalesOptions = {
            chart: {
                type: 'bar'
            },
            tooltip: {
                theme: 'dark'
            },
            series: [{
                name: 'Sales',
                data: Object.values(data[0].weeklySales)
            }],
            xaxis: {
                categories: Object.keys(data[0].weeklySales)
            }
        }
        var weeklySalesChart = new ApexCharts(document.querySelector("#weeklySalesChart"), weeklySalesOptions);
        weeklySalesChart.render();

        console.log(data[0].topProducts);
        // Chart 3: Top 10 most sold products
        var topProductsOptions = {
            chart: {
                type: 'bar'
            },
            tooltip: {
                theme: 'dark'
            },
            series: [{
                name: 'Quantity',
                data: Object.values(data[0].topProducts)
            }],
            xaxis: {
                categories: Object.keys(data[0].topProducts)
            },
            plotOptions: {
                bar: {
                    distributed: true
                }
            }
        }
        var topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), topProductsOptions);
        topProductsChart.render();
    });
});
