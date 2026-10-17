<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HaulingOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $team = $user?->currentTeam ?? $user?->personalTeam();

        return [
            Stat::make('Active Team', $team?->name ?? 'Hauling Unit')
                ->description('Current operational workspace')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Fleet Readiness', '100%')
                ->description('Units ready for hauling dispatch')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),

            Stat::make('Daily Hauling Target', '0 / 2,500 MT')
                ->description('Ready for shift allocation')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}
