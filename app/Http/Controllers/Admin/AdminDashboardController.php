<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Harvest;
use App\Models\LoanApplication;
use App\Models\Notification;
use App\Models\Stock;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'users' => User::count(),
            'farmers' => Farmer::count(),
            'harvests' => Harvest::count(),
            'stocks' => Stock::count(),
            'warehouses' => Warehouse::count(),
            'orders' => StoreOrder::count(),
            'notifications' => Notification::count(),
            'charts' => $this->buildChartData(),
            'financing' => $this->buildFinancingStats(),
        ]);
    }

    /**
     * Real statistical chart data pulled from the database.
     *
     * @return array<string, mixed>
     */
    private function buildChartData(): array
    {
        return [
            'harvests_by_month' => $this->harvestsByMonth(),
            'stock_by_crop' => $this->stockByCrop(),
            'users_by_role' => $this->usersByRole(),
            'orders_by_status' => $this->ordersByStatus(),
            'stock_by_status' => $this->stockByStatus(),
            'harvests_by_crop' => $this->harvestsByCrop(),
        ];
    }

    /**
     * Active vs pending institutional financing summary.
     *
     * @return array<string, mixed>
     */
    private function buildFinancingStats(): array
    {
        $activeStatuses = ['Active', 'approved', 'Repaid'];
        $pendingStatuses = ['Pending Review', 'pending'];

        $active = LoanApplication::whereIn('status', $activeStatuses);
        $pending = LoanApplication::whereIn('status', $pendingStatuses);
        $rejected = LoanApplication::where('status', 'Rejected')
            ->orWhere('status', 'rejected');

        $activeCount = $active->count();
        $pendingCount = $pending->count();
        $rejectedCount = $rejected->count();
        $totalCount = LoanApplication::count();

        $activeAmountFcfa = (int) $active->sum('requested_amount_fcfa');
        $pendingAmountFcfa = (int) $pending->sum('requested_amount_fcfa');

        $totalDisbursedFcfa = (int) LoanApplication::whereIn('status', $activeStatuses)->sum('requested_amount_fcfa');
        $totalRepaidFcfa = (int) LoanApplication::where('status', 'Repaid')->sum('amount_paid_usd') * 655.957;

        return [
            'active_count' => $activeCount,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'total_count' => $totalCount,
            'active_amount_fcfa' => $activeAmountFcfa,
            'pending_amount_fcfa' => $pendingAmountFcfa,
            'total_disbursed_fcfa' => $totalDisbursedFcfa,
            'total_repaid_fcfa' => (int) round($totalRepaidFcfa),
            'by_institution' => $this->loansByInstitution(),
            'monthly_applications' => $this->loanApplicationsByMonth(),
        ];
    }

    /**
     * @return array<int, array{month: string, count: int, total_kg: float}>
     */
    private function harvestsByMonth(): array
    {
        $rows = Harvest::selectRaw("
                DATE_FORMAT(harvest_date, '%Y-%m') as month,
                COUNT(*) as count,
                COALESCE(SUM(quantity_kg), 0) as total_kg
            ")
            ->where('harvest_date', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[$row->month] = [
                'month' => $row->month,
                'count' => (int) $row->count,
                'total_kg' => (float) $row->total_kg,
            ];
        }

        // Fill missing months with zeros so the chart is continuous
        $result = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $key = $date->format('Y-m');
            $result[] = $byMonth[$key] ?? [
                'month' => $key,
                'count' => 0,
                'total_kg' => 0.0,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{crop: string, quantity_kg: float, count: int}>
     */
    private function stockByCrop(): array
    {
        return Stock::selectRaw("
                crop_type as crop,
                COALESCE(SUM(quantity_kg), 0) as quantity_kg,
                COUNT(*) as count
            ")
            ->groupBy('crop_type')
            ->orderByDesc('quantity_kg')
            ->get()
            ->map(fn ($row) => [
                'crop' => $row->crop,
                'quantity_kg' => (float) $row->quantity_kg,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{crop: string, count: int, total_kg: float}>
     */
    private function harvestsByCrop(): array
    {
        return Harvest::selectRaw("
                crop_type as crop,
                COUNT(*) as count,
                COALESCE(SUM(quantity_kg), 0) as total_kg
            ")
            ->groupBy('crop_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'crop' => $row->crop,
                'count' => (int) $row->count,
                'total_kg' => (float) $row->total_kg,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{role: string, count: int}>
     */
    private function usersByRole(): array
    {
        return User::selectRaw("role, COUNT(*) as count")
            ->groupBy('role')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'role' => $row->role,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{status: string, count: int}>
     */
    private function ordersByStatus(): array
    {
        return StoreOrder::selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{status: string, count: int, total_kg: float}>
     */
    private function stockByStatus(): array
    {
        return Stock::selectRaw("
                status,
                COUNT(*) as count,
                COALESCE(SUM(quantity_kg), 0) as total_kg
            ")
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
                'total_kg' => (float) $row->total_kg,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{institution: string, count: int, amount_fcfa: int}>
     */
    private function loansByInstitution(): array
    {
        return LoanApplication::selectRaw("
                institution_name as institution,
                COUNT(*) as count,
                COALESCE(SUM(requested_amount_fcfa), 0) as amount_fcfa
            ")
            ->whereNotNull('institution_name')
            ->groupBy('institution_name')
            ->orderByDesc('amount_fcfa')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'institution' => $row->institution,
                'count' => (int) $row->count,
                'amount_fcfa' => (int) $row->amount_fcfa,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{month: string, count: int, amount_fcfa: int}>
     */
    private function loanApplicationsByMonth(): array
    {
        $rows = LoanApplication::selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count,
                COALESCE(SUM(requested_amount_fcfa), 0) as amount_fcfa
            ")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[$row->month] = [
                'month' => $row->month,
                'count' => (int) $row->count,
                'amount_fcfa' => (int) $row->amount_fcfa,
            ];
        }

        $result = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $key = $date->format('Y-m');
            $result[] = $byMonth[$key] ?? [
                'month' => $key,
                'count' => 0,
                'amount_fcfa' => 0,
            ];
        }

        return $result;
    }
}
