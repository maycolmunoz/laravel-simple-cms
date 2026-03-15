<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Media;
use App\Models\MediaItem;
use App\Models\Page;
use App\Policies\OwnerablePolicy;
use App\View\Composers\NavigationComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('components.layouts.app', NavigationComposer::class);

        Gate::policy(Article::class, OwnerablePolicy::class);
        Gate::policy(Page::class, OwnerablePolicy::class);
        Gate::policy(Media::class, OwnerablePolicy::class);
        Gate::policy(MediaItem::class, OwnerablePolicy::class);
    }
}
