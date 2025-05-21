<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    // export products including listPrices to csv and download
    public function exportProducts(): BinaryFileResponse
    {
        $filename = 'products.csv';
        $filename_download = 'products' . date("dmYHi") . '.csv';

        $handle = fopen($filename, 'w+');

        // get unique list_id columns from list_prices table to $listPrices
        $listPrices = \App\Models\ListPrice::select('list_id')->distinct()->get();

        // get all products
        $products = \App\Models\Product::with('listPrices')->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-disposition" => "attachment; filename=/" . $filename_download,
            // "Pragma" => "no-cache",
            // "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            // "Expires" => "0"
        ];

        $csv_headers = ['id', 'brand', 'model', 'description', 'price'];
        foreach ($listPrices as $listPrice) {
            $csv_headers[] = 'list_' . $listPrice->list_id;
        }
        fputcsv($handle, $csv_headers);

        foreach ($products as $product) {
            $row = [];
            $row[] = $product->id;
            $row[] = $product->brand;
            $row[] = $product->model;
            $row[] = $product->description;
            $row[] = $product->price;
            foreach ($listPrices as $listPrice) {
                $row[] = $product->listPrices->where('list_id', $listPrice->list_id)->first()->price ?? '0';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->download($filename, $filename_download, $headers)->deleteFileAfterSend(true);
    }
    public function exportCustomersProducts(): BinaryFileResponse
    {
        $filename = 'customers_products.csv';
        $filename_download = 'customers_products' . date("dmYHi") . '.csv';

        $handle = fopen($filename, 'w+');

        // get unique list_id columns from list_prices table to $listPrices
        $listPrices = \App\Models\ListPrice::select('list_id')->distinct()->get();

        // get all products
        $products = \App\Models\Product::with('listPrices')
            ->where('published', 1)
            ->where('description', 'not like', 'CONS INT%')
            ->where('model', '!=', 'consumo interno')
            ->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-disposition" => "attachment; filename=/" . $filename_download,
            // "Pragma" => "no-cache",
            // "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            // "Expires" => "0"
        ];

        $csv_headers = ['id', 'brand', 'model', 'description', 'price'];
        foreach ($listPrices as $listPrice) {
            $csv_headers[] = 'list_' . $listPrice->list_id;
        }
        fputcsv($handle, $csv_headers);

        foreach ($products as $product) {
            $row = [];
            $row[] = $product->id;
            $row[] = $product->brand;
            $row[] = $product->model;
            $row[] = $product->description;
            $row[] = $product->price;
            foreach ($listPrices as $listPrice) {
                $row[] = $product->listPrices->where('list_id', $listPrice->list_id)->first()->price ?? '0';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->download($filename, $filename_download, $headers)->deleteFileAfterSend(true);
    }
}
