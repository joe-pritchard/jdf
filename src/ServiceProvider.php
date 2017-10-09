<?php
declare(strict_types=1);
/**
 * ServiceProvider.php
 *
 * @category JoePritchard\JDF
 * @author   Joe Pritchard
 *
 * Created:  03/10/2017 09:58
 *
 */

namespace JoePritchard\JDF;


/**
 * Class ServiceProvider
 *
 * @package JoePritchard\JDF
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/jdf.php', 'jdf'
        );
    }

    /**
     * Boot the service provider after registration
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/jdf.php' => config_path('jdf.php'),
        ]);
    }
}