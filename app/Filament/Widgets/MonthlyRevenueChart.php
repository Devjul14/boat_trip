<?php

namespace App\Filament\Widgets;

use App\Models\Invoices;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Revenue Overview';
    
    protected int | string | array $columnSpan = 'full';
    
    // Optionally set the maximum height for the chart
    protected static ?string $maxHeight = '400px';
    
    // Filter for the chart
    public ?string $filter = 'year';
    
    // For filter dropdown menu
    protected function getFilters(): ?array
    {
        return [
            'year' => 'This Year',
            'last_year' => 'Last Year',
            '6months' => 'Last 6 Months',
            'all_time' => 'All Time',
        ];
    }

    protected function getData(): array
    {
        // Get the current date variables
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        // Set up the month names for labels
        $monthNames = collect(range(1, 12))->map(function ($month) {
            return Carbon::create(null, $month)->format('F');
        })->toArray();
        
        // Create query based on filter
        $query = Invoices::query()
            ->select(
                'month',
                'year',
                DB::raw('SUM(total_amount) as total')
            )
            ->where('status', '!=', 'draft')  // Include only sent and paid invoices
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');
        
        // Apply filter conditions
        switch ($this->filter) {
            case 'last_year':
                $query->where('year', $currentYear - 1);
                $year = $currentYear - 1;
                break;
                
            case '6months':
                // For 6 months, we need to handle the month and year separately
                $sixMonthsAgo = Carbon::now()->subMonths(6);
                $startMonth = $sixMonthsAgo->month;
                $startYear = $sixMonthsAgo->year;
                
                $query->where(function ($q) use ($startMonth, $startYear, $currentYear, $currentMonth) {
                    $q->where(function ($q2) use ($startYear, $startMonth) {
                        $q2->where('year', $startYear)
                           ->where('month', '>=', $startMonth);
                    })->orWhere(function ($q2) use ($startYear, $currentYear) {
                        $q2->where('year', '>', $startYear)
                           ->where('year', '<=', $currentYear);
                    });
                });
                break;
                
            case 'all_time':
                // No additional filters for all time
                break;
                
            case 'year':
            default:
                $query->where('year', $currentYear);
                $year = $currentYear;
                break;
        }
        
        // Get the results
        $results = $query->get();
        
        // Initialize data array with zeros for all months
        $monthlyData = [];
        
        if ($this->filter === 'year' || $this->filter === 'last_year') {
            // For yearly filters, we show all 12 months
            for ($i = 1; $i <= 12; $i++) {
                $monthlyData[$i] = 0;
            }
            
            // Fill in the actual data
            foreach ($results as $result) {
                if ($result->year == $year) {
                    // Convert the month string to month number if needed
                    $monthNum = is_numeric($result->month) ? $result->month : date('n', strtotime("1 {$result->month} 2000"));
                    $monthlyData[$monthNum] = $result->total;
                }
            }
            
            // Format for chart
            $formattedData = array_values($monthlyData);
            $labels = $monthNames;
            
        } elseif ($this->filter === '6months') {
            // For 6 months filter, we show only the last 6 months
            $labels = [];
            $formattedData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $month = $date->month;
                $monthName = $date->format('F');
                $year = $date->year;
                
                $labels[] = $date->format('M Y');
                
                // Find matching data
                $found = false;
                foreach ($results as $result) {
                    $resultMonth = is_numeric($result->month) ? $result->month : date('n', strtotime("1 {$result->month} 2000"));
                    
                    if (($resultMonth == $month || $result->month == $monthName) && $result->year == $year) {
                        $formattedData[] = $result->total;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $formattedData[] = 0;
                }
            }
            
        } else {
            // For all time, we show data by year-month
            $labels = [];
            $formattedData = [];
            
            foreach ($results as $result) {
                $date = Carbon::createFromDate($result->year, $result->month, 1);
                $labels[] = $date->format('M Y');
                $formattedData[] = $result->total;
            }
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data' => $formattedData,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1,
                    // For bar chart
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // You can change 'line' to 'bar' for a bar chart if preferred
        return 'line';
    }
    
    // Optional configuration for the chart
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value; }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "$" + context.parsed.y; }',
                    ],
                ],
            ],
        ];
    }
}