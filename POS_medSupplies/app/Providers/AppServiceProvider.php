<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\ItemList;
use App\Observers\ItemListObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observer only when legacy item_lists table exists
        if (\Illuminate\Support\Facades\Schema::hasTable('item_lists')) {
            ItemList::observe(ItemListObserver::class);
        }

        if (! $this->app->runningInConsole()) {
            $request = request();
            $root = rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/');
            if ($root !== '') {
                URL::forceRootUrl($root);
            }
        }
    }
}
