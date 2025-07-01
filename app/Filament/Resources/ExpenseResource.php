<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Entity;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\Filter;
use NumberFormatter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    // protected static ?string $navigationGroup = 'Income/Expense';

    protected static ?int $navigationSort = 2;

    protected static function formatAmount($value)
    {
        $currencyCode = auth()->user()->currency ?? 'USD';  
        $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($value, $currencyCode);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('entity_id')
                            ->helperText('Select an associated company or individual.')
                            ->relationship('entity', 'name', function ($query) {
                                $query->limit(10);  
                            }) 
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('address')
                                    ->maxLength(255),

                                Forms\Components\Hidden::make('user_id')
                                ->default(fn () => auth()->id())
                            ])
                            ->searchable()
                            ->preload()
                            ->required(),
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
                            ->required(),
                        Hidden::make('user_id')
                            ->default(auth()->id())
                    ])
                    ->columns([
                        'sm' => 1,
                    ])
                    ->columnSpan(2),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (?Expense $record): string => $record ? $record->created_at->diffForHumans() : '-'),
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn (?Expense $record): string => $record ? $record->updated_at->diffForHumans() : '-'),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entity.name')
                    ->url(fn ($record): string => 'entities/'.$record->entity_id)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->sortable()
                    ->label('Entry Date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
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
                 ->label(fn ($livewire) =>
                    new \Illuminate\Support\HtmlString('<strong>Total: ' . static::formatAmount($livewire->getTotalAmount()) . '</strong>')
                )
                ->disabled(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns([
                                Column::make('title')->heading('Ttitle'),
                                Column::make('entity.name')->heading('Entity Name'),
                                Column::make('entry_date')->heading('Entry Date'),
                                Column::make('updated_at'),
                            ])
                    ])
            ]);
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
            'index' => Pages\ListExpenses::route('/'),
            // 'create' => Pages\CreateExpense::route('/create'),
            // 'edit' => Pages\EditExpense::route('/{record}/edit'),
            // 'view' => Pages\ViewExpense::route('/{record}'),
        ];
    }



    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['category']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'category.name', 'amount'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->category) {
            $details['Category'] = $record->category->name;
        }

        return $details;
    }
}
