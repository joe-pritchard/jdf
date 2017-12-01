<?php
declare(strict_types=1);
/**
 * JMFEntryFailedhp
 *
 * @project  jdf.git
 * @category JoePritchard\JDF\Events
 * @author   joe
 *
 * Created:  12/10/2017 09:54
 *
 */

namespace JoePritchard\JDF\Events;

/**
 * Class JMFEntryFailed. Fires when a JDF file couldn't be submitted to the JMF server
 *
 * @package JoePritchard\JDF\Events
 */
class JMFEntryFailed
{
    /**
     * @var \SimpleXMLElement
     */
    public $jmf_request;

    /**
     * @var \SimpleXMLElement
     */
    public $jmf_response;

    /**
     * JMFEntryFailed constructor.
     *
     * @param \SimpleXMLElement $jmf_request
     * @param string $error_message
     */
    public function __construct(\SimpleXMLElement $jmf_request, string $error_message)
    {
        $this->error_message = $error_message;
        $this->jmf_request   = $jmf_request;
    }
}