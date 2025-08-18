<div>
    <div class="grid grid-cols-1 gap-4">
        <h2 class="text-lg font-semibold">VENTAS SEMANALES: ${{ number_format($totalWeeklySales, 2, ',', '.') }}</h2>
        <div id="weeklySalesChart"></div>
    </div>
    <div class="mt-4">
        PRODUCTOS MÁS VENDIDOS
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <x-input label="Desde" type="date" wire:model="dateFrom" />
            <x-input label="Hasta" type="date" wire:model="dateTo" />
            <x-button label="Actualizar" wire:click="updateChartData" class="btn-primary mt-8" />
        </div>
        <div id="topProductsChart"></div>
    </div>
</div>