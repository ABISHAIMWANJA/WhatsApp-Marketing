<?php

namespace App\Providers;

use App\Yantrana\Components\Vendor\Models\VendorModel;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

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
        if(config('__misc.force_https', false)) {
            \URL::forceScheme('https');
        }
        require app_path('Yantrana/__Laraware/Support/helpers.php');
        require app_path('Yantrana/Support/app-helpers.php');
        require app_path('Yantrana/Support/extended-validations.php');
        // config items requires gettext helper function to work
        require app_path('Yantrana/Support/custom-tech-config.php');
        require app_path('Yantrana/Support/extended-blade-directive.php');
        Cashier::useCustomerModel(VendorModel::class);
        if (getAppSettings('enable_stripe') and getAppSettings('stripe_enable_calculate_taxes')) {
            Cashier::calculateTaxes();
        }

        $addonsPath = base_path('addons');
        if (is_dir($addonsPath)) {
            $skipDirsItems = [
                '.',
                '..',
                '.DS_Store'
            ];
            foreach (scandir($addonsPath) as $addon) {
                if (in_array($addon, $skipDirsItems)) {
                    continue;
                }
                $addonPath = $addonsPath . '/' . $addon;
                // Load addon if it has a service provider
                $addonAutoload = $addonPath . '/vendor/autoload.php';
                if (is_dir($addonPath) and file_exists($addonAutoload)) {
                    require_once $addonAutoload;
                    $serviceProvider = "Addons\\{$addon}\\{$addon}ServiceProvider";
                    if (class_exists($serviceProvider)) {
                        $this->app->register($serviceProvider);
                    }
                }
            }
        }
    }
}
