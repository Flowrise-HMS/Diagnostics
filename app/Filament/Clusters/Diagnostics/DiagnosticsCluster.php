<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class DiagnosticsCluster extends Cluster
{
    protected static ?string $slug = 'diagnostics-cluster';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
}
