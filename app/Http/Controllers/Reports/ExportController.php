<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ListPrice;
use App\Models\Product;
use App\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    // export products including listPrices to csv and download
    public function exportProducts(): BinaryFileResponse
    {
        $filename = 'products.csv';
        $filename_download = 'products'.date('dmYHi').'.csv';

        $handle = fopen($filename, 'w+');

        // Obtener todas las listas de nombres para las columnas
        $listNames = \App\Models\ListName::all();
        $priceService = app(\App\Services\PriceListService::class);

        // get all products
        $products = Product::with('listPrices')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-disposition' => 'attachment; filename=/'.$filename_download,
        ];

        $csv_headers = ['id', 'brand', 'model', 'description', 'description_html', 'tags', 'price'];
        foreach ($listNames as $list) {
            $csv_headers[] = $list->name;
        }
        fputcsv($handle, $csv_headers);

        foreach ($products as $product) {
            $row = [];
            $row[] = $product->id;
            $row[] = $product->brand;
            $row[] = $product->model;
            $row[] = $product->description;
            $row[] = strip_tags($product->description_html);
            $row[] = $product->tags;
            $row[] = $product->price;
            foreach ($listNames as $list) {
                $row[] = $priceService->getEffectivePrice($list->id, $product->id) ?? '0';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->download($filename, $filename_download, $headers)->deleteFileAfterSend(true);
    }

    public function exportCustomersProducts(): BinaryFileResponse
    {
        $filename = 'customers_products.csv';
        $filename_download = 'customers_products'.date('dmYHi').'.csv';

        $handle = fopen($filename, 'w+');

        // Obtener todas las listas de nombres para las columnas
        $listNames = \App\Models\ListName::all();
        $priceService = app(\App\Services\PriceListService::class);

        // get all products
        $products = Product::with('listPrices')
            ->where('published', 1)
            ->where('description', 'not like', 'CONS INT%')
            ->where('model', '!=', 'consumo interno')
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-disposition' => 'attachment; filename=/'.$filename_download,
        ];

        $csv_headers = ['id', 'brand', 'model', 'description', 'description_html', 'tags', 'price'];
        foreach ($listNames as $list) {
            $csv_headers[] = $list->name;
        }
        fputcsv($handle, $csv_headers);

        foreach ($products as $product) {
            $row = [];
            $row[] = $product->id;
            $row[] = $product->brand;
            $row[] = $product->model;
            $row[] = $product->description;
            $row[] = strip_tags($product->description_html);
            $row[] = $product->tags;
            $row[] = $product->price;
            foreach ($listNames as $list) {
                $row[] = $priceService->getEffectivePrice($list->id, $product->id) ?? '0';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->download($filename, $filename_download, $headers)->deleteFileAfterSend(true);
    }


    public function exportUsersOrderStats(): BinaryFileResponse
    {
        $filename = 'users_order_stats.csv';
        $filename_download = 'users_order_stats_'.date('dmYHi').'.csv';

        $handle = fopen($filename, 'w+');

        $users = User::withCount('orders')
            ->with('latestOrder')
            ->orderByDesc('orders_count')
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-disposition' => 'attachment; filename=/'.$filename_download,
        ];

        $csv_headers = ['ID', 'Nombre', 'Email', 'Teléfono', '# de Compras', 'Ultima compra'];
        fputcsv($handle, $csv_headers);

        foreach ($users as $user) {
            $row = [];
            $row[] = $user->id;
            $row[] = $user->name.' '.$user->lastname;
            $row[] = $user->email;
            $row[] = $user->phone;
            $row[] = $user->orders_count;
            $row[] = $user->latestOrder?->created_at?->format('d/m/Y') ?? 'N/A';
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->download($filename, $filename_download, $headers)->deleteFileAfterSend(true);
    }
}
