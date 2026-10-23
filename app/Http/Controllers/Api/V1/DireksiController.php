<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DireksiAnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DireksiController extends Controller
{
    use ApiResponse;

    protected DireksiAnalyticsService $analyticsService;

    public function __construct(DireksiAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Executive Overview: Real cash per branch, Kas Pusat, Sales Today, Profit, Asset Value.
     */
    public function getOverview(): JsonResponse
    {
        $overview = $this->analyticsService->getExecutiveOverview();
        return $this->successResponse($overview);
    }

    /**
     * Top Debtors requiring urgent follow-up.
     */
    public function getTopDebtors(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        $debtors = $this->analyticsService->getTopDebtors((int) $limit);
        return $this->successResponse($debtors);
    }

    /**
     * Executive Smart Insights narrative highlights.
     */
    public function getSmartInsights(): JsonResponse
    {
        $insights = $this->analyticsService->getSmartInsights();
        return $this->successResponse($insights);
    }
}
