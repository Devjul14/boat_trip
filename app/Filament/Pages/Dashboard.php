<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MonthlyRevenueChart;
use App\Filament\Widgets\ActiveHotelsVendorsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            MonthlyRevenueChart::class,
            ActiveHotelsVendorsWidget::class,
            // UpcomingRecentTripsWidget::class,
            OutstandingPaymentsWidget::class,
        ];
    }
}