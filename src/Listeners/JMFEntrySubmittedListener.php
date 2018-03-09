<?php
declare(strict_types=1);
/**
 * JMFEntrySubmittedListener.php
 *
 * @project  jdf.git
 * @category JoePritchard\JDF\Listeners
 * @author   joe
 *
 * Created:  12/10/2017 10:10
 *
 */

namespace JoePritchard\JDF\Listeners;

use JoePritchard\JDF\Events\JMFEntrySubmitted;


/**
 * Class JMFEntrySubmittedListener
 *
 * @package JoePritchard\JDF\Listeners
 */
class JMFEntrySubmittedListener
{
    /**
     * If you provided me with a callback class
     * @var null|Object
     */
    private $callback = null;

    /**
     * JMFEntrySubmittedListener constructor. Initialise the callback class if provided
     */
    public function __construct()
    {
        if (class_exists(config('jdf.submit_queue_entry_callback', ''))) {
            $class_name     = config('jdf.submit_queue_entry_callback');
            $this->callback = new $class_name;
        }
    }

    /**
     * Pass the event up the chain to your callback, if the handle method is defined
     *
     * @param JMFEntrySubmitted $event
     */
    public function handle(JMFEntrySubmitted $event): void
    {
        if ($this->callback !== null && method_exists($this->callback, 'handle')) {
            $this->callback->handle($event);
        }
    }
}