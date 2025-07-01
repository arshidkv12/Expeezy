<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Filament\Resources\IncomeResource;
use App\Models\Income;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;
use Filament\Forms;

class ViewIncome extends ViewRecord
{
    protected static string $resource = IncomeResource::class;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Card::make()
            ->view('filament.components.unstyled-card')
             ->extraAttributes([
                    'class' => '!bg-transparent border-0 shadow-none',
                ])
                ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Placeholder::make('client.name')
                            ->label('Client')
                            ->content(function ($record) {
                                if (! $record->client) {
                                    return '-';
                                }

                                $url = route('filament.resources.clients.view', ['record' => $record->client->id]);
                                $name = e($record->client->name);

                                return new HtmlString(
                                    "<a href='{$url}' class='text-primary underline'>{$name}</a>"
                                );
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\DatePicker::make('entry_date')
                            ->required(),
                        Forms\Components\TextInput::make('note'),
                    ])
                    ->columns([
                        'sm' => 1,
                    ])
                    ->columnSpan(2),
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn (?Income $record): string => $record ? $record->created_at->diffForHumans() : '-'),
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last modified at')
                            ->content(fn (?Income $record): string => $record ? $record->updated_at->diffForHumans() : '-'),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3)
            
        ];
            
    }
}
