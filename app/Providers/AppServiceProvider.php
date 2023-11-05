<?php

namespace App\Providers;

use App\WebCrawlerUtil;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(WebCrawlerUtil::class, function () {
            return new WebCrawlerUtil("https://agencyanalytics.com/");
        });
    }
}
