<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;


    public function getHeaderActions(): array
    {

        return [
            CreateAction::make(),
            ExportAction::make() 
            ->exports([
                ExcelExport::make()
                    ->fromTable()
                    ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                    ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ->withColumns([
                        Column::make('customer.phone')->heading('Mobile'),
                        Column::make('customer.email')->heading('Email'),
                        Column::make('customer.address')->heading('Address'),
                        Column::make('updated_at'),
                    ])
            ]),  
        ];
        
    }

    public function getTotalAmount(): float
    {
        return $this->getFilteredTableQuery()->sum('amount');
    }
}
