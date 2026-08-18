<?php

namespace CodyPChristian\NativeLottie;

use CodyPChristian\NativeLottie\Commands\CopyAssetsCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the in-app Lottie plugin.
 *
 * The `lottie_view` component (element + Blade tag) is registered automatically
 * by nativephp/mobile's plugin discovery from this package's nativephp.json
 * `components[]` entry — see NativeServiceProvider::registerUiPluginComponents().
 * That path registers the Element in the ElementRegistry (so both
 * `<native:lottie-view>` and the short-form `<lottie-view>` compile) and the
 * Blade component alias `native-lottie-view`. Discovery only picks this up once
 * the provider is listed in the app's NativeServiceProvider::plugins() allowlist
 * (run `php artisan native:plugin:register codypchristian/nativephp-lottie`).
 *
 * This provider only wires the build-time asset-copy hook command.
 */
class NativeLottieServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
