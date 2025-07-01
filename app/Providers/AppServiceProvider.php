<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'income' => 'App\Models\Income',
            'expense' => 'App\Models\Expense',
        ]);

        // Filament::navigationGroups([
        //     'Income/Expense',
        //     'Miscellaneous',
        // ]);
        
        Schema::defaultStringLength(191);

    }
}
