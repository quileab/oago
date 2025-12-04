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
    public $weeks = [];
    public $selectedWeek;
    public $weekOptions = [];

    public function mount()
    {
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->dateFrom = Carbon::now()->subYear()->format('Y-m-d');

        Carbon::setLocale('es'); // Set locale to Spanish

        $now = Carbon::now();
        $this->selectedWeek = $now->year . '-' . $now->weekOfYear;

        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();

        $weeks = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $month = $currentDate->format('F Y');
            $weekNumber = $currentDate->weekOfYear;
            $year = $currentDate->year;

            if (!isset($weeks[$month])) {
                $weeks[$month] = [];
            }

            $weekKey = $year . '-' . $weekNumber;
            if (!in_array($weekKey, $weeks[$month])) {
                $weeks[$month][] = $weekKey;
            }

            $currentDate->addWeek();
        }

        $this->weeks = $weeks;

        // Reverse the order of months
        $this->weeks = array_reverse($this->weeks, true); // true to preserve keys

        // Reverse the order of weeks within each month
        foreach ($this->weeks as $month => $monthWeeks) {
            $this->weeks[$month] = array_reverse($monthWeeks);
        }

        $weekOptions = [];
        foreach ($this->weeks as $month => $monthWeeks) {
            $weekOptions[] = ['id' => $month, 'name' => $month, 'disabled' => true];
            foreach ($monthWeeks as $week) {
                $weekOptions[] = ['id' => $week, 'name' => 'Semana ' . explode('-', $week)[1]];
            }
        }

        $this->weekOptions = $weekOptions;
    }

    public function updatedSelectedWeek($value)
    {
        $parts = explode('-', $value);
        if (count($parts) !== 2) {
            return;
        }
        $this->updateChartData();
    }

    public function updateChartData()
    {
        // This method will trigger the re-rendering of the component
        // and thus update the charts with the new date range.
    }

    public function render()
    {
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

        if ($this->selectedWeek) {
            $parts = explode('-', $this->selectedWeek);
            if (count($parts) == 2) {
                $year = $parts[0];
                $week = $parts[1];

                $date = Carbon::now();
                $date->setISODate($year, $week);

                $startOfWeek = $date->startOfWeek()->format('Y-m-d H:i:s');
                $endOfWeek = $date->endOfWeek()->format('Y-m-d H:i:s');
            }
        }

        // Chart 1: Yearly sales by week
        $yearlySales = Order::query()
            ->selectRaw('DATE(SUBDATE(created_at, WEEKDAY(created_at))) as week_start, SUM(total_price) as total')
            ->where('status', 'completed')
            ->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()])
            ->groupBy('week_start')
            ->orderBy('week_start', 'asc')
            ->get()
            ->pluck('total', 'week_start');

        // Chart 2: Weekly sales
        $weeklySales = Order::query()
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date');

        $this->totalWeeklySales = $weeklySales->sum();

        // Chart 3: Top 5 most sold products
        $topProducts = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->with('product')
            ->whereHas('order', function ($query) {
                $query->where('status', 'completed');
            })
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo]) // Filter by date
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->take(5)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->product->description => $item->total_quantity];
            });

        $this->dispatch('updateCharts', [
            'yearlySales' => $yearlySales,
            'weeklySales' => $weeklySales,
            'topProducts' => $topProducts,
        ]);

        return view('livewire.dashboard');
    }
}
