<?php

namespace App\Providers;

use App\Support\DbSchema;
use Illuminate\Support\Facades\Blade;
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
        Blade::directive('rupiah', function ($expression) {
            return "<?php echo 'Rp. ' . number_format($expression,0,',','.'); ?>";
        });

        View::composer('*', function ($view) {
            static $featureWa = null;
            if ($featureWa === null) {
                try {
                    $featureWa = DbSchema::scctcustHasNoWa();
                } catch (\Throwable $e) {
                    $featureWa = false;
                }
            }
            $view->with('featureWa', $featureWa);
        });
    }
}
