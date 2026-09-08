<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SliderService;
use Illuminate\Http\JsonResponse;

class SliderController extends Controller
{
    public function __construct(private SliderService $sliderService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->sliderService->getSlides(), 200);
    }
}
