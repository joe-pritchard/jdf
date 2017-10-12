<?php
declare(strict_types=1);
/**
 * EventServiceProvider.php
 *
 * @project  JDF
 * @category JoePritchard\JDF\Providers
 * @author   Joe Pritchard
 *
 * Created:  12/10/2017 12:59
 *
 */

namespace JoePritchard\JDF\Providers;

use Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use JoePritchard\JDF\Events\JMFEntrySubmitted;
use JoePritchard\JDF\Listeners\JMFEntrySubmittedListener;


/**
 * Class EventServiceProvider
 *
 * @package JoePritchard\JDF\Providers
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Boot the event service provider
     */
    public function boot()
    {
        parent::boot();

        Event::listen(JMFEntrySubmitted::class, JMFEntrySubmittedListener::class);
    }
}