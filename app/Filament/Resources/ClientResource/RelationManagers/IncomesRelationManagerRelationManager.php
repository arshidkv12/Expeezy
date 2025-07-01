<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\Income;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use NumberFormatter;

class IncomesRelationManagerRelationManager extends RelationManager
{
    protected static string $relationship = 'incomes';

    protected static ?string $recordTitleAttribute = 'name';


    protected static function formatAmount($value)
    {
        $currencyCode = auth()->user()->currency ?? 'USD';  
        $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($value, $currencyCode);
    }

    public function form(Form $form): Form
    {
         return $form
         ->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Forms\Components\Hidden::make('user_id'),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->minValue(1)
                ->required(),
            Forms\Components\DatePicker::make('entry_date')
                ->required(),
            Forms\Components\TextInput::make('note'),
            Forms\Components\Select::make('status')
                            ->options([
                                'Paid' => 'Paid',
                                'Pending' => 'Pending'
                            ])
                            ->required()
         ]);
            
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->url(fn ($record): string => 'clients/'.$record->client_id)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->sortable()
                    ->label('Entry Date')
                    ->date(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',   
                        'Pending' => 'warning',  
                        default => 'gray',    
                    })
                    ->sortable()
            ])
            ->filters([
                 SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pending'  => 'Pending',
                        'Paid'     => 'Paid',
                    ]),
                Filter::make('entry_date')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('From Date'),
                        DatePicker::make('end_date')
                            ->label('To Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_date'] ?? null, fn ($query, $date) => $query->whereDate('entry_date', '>=', $date))
                            ->when($data['end_date'] ?? null, fn ($query, $date) => $query->whereDate('entry_date', '<=', $date));
                    }) 
                ->indicateUsing(function (array $data) {
                    $indicators = [];
        
                    if (!empty($data['start_date'])) {
                        $indicators[] = 'From: ' . $data['start_date'];
                    }
        
                    if (!empty($data['end_date'])) {
                        $indicators[] = 'To: ' . $data['end_date'];
                    }
        
                    return $indicators;
                }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('total')
                ->label(function ($livewire) {
                    $total = $livewire->getFilteredTableQuery()->sum('amount');
                    return 'Total: ' . static::formatAmount($total);
                })
                ->disabled(),
                Tables\Actions\CreateAction::make()
                 ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    return $data;
                }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }    
}
