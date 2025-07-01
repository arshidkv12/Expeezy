<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Filament\Resources\IncomeResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\CreateAction;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;
    
    public function getTotalAmount(): float
    {
        return $this->getFilteredTableQuery()->sum('amount');
    }



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
                        Column::make('title')->heading('Title'),
                        Column::make('client.name')->heading('Client Name'),
                        Column::make('client.phone')->heading('Mobile'),
                        Column::make('amount')->heading('Amount'),
                        Column::make('status')->heading('Status'),
                        Column::make('updated_at'),
                    ])
            ]),  
        ];
    }
}
