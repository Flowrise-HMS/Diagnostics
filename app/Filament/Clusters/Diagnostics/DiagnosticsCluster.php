<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Enums\SidebarGroup;

class DiagnosticsCluster extends Cluster
{
    protected static ?string $slug = 'diagnostics-cluster';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|\UnitEnum|null $navigationGroup = SidebarGroup::PatientCare;

    protected static ?int $navigationSort = 50;
}
