<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Component;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $dateFrom;
    public $dateTo;
    public $totalWeeklySales = 0;

    public function mount()
    {
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->dateFrom = Carbon::now()->subYear()->format('Y-m-d');
    }

    public function updateChartData()
    {
        // This method will trigger the re-rendering of the component
        // and thus update the charts with the new date range.
    }

    public function render()
    {
        // Chart 2: Weekly sales
        $weeklySales = Order::query()
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date');

        $this->totalWeeklySales = $weeklySales->sum();

        // Chart 3: Top 5 most sold products
        $topProducts = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->with('product')
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo]) // Filter by date
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->take(5)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->product->description => $item->total_quantity];
            });

        $this->dispatch('updateCharts', [
            'weeklySales' => $weeklySales,
            'topProducts' => $topProducts,
        ]);

        return view('livewire.dashboard');
    }
}
