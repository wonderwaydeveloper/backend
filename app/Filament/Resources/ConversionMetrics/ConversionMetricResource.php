<?php

namespace App\Filament\Resources\ConversionMetrics;

use App\Filament\Resources\ConversionMetrics\Pages\CreateConversionMetric;
use App\Filament\Resources\ConversionMetrics\Pages\EditConversionMetric;
use App\Filament\Resources\ConversionMetrics\Pages\ListConversionMetrics;
use App\Filament\Resources\ConversionMetrics\Schemas\ConversionMetricForm;
use App\Filament\Resources\ConversionMetrics\Tables\ConversionMetricsTable;
use App\Models\ConversionMetric;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConversionMetricResource extends Resource
{
    protected static ?string $model = ConversionMetric::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ConversionMetricForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConversionMetricsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversionMetrics::route('/'),
            'create' => CreateConversionMetric::route('/create'),
            'edit' => EditConversionMetric::route('/{record}/edit'),
        ];
    }
}
