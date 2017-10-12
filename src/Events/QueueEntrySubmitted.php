<?php
declare(strict_types=1);
/**
 * QueueEntrySubmitted.php
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
 * Class QueueEntrySubmitted. Fires when a new JDF file is submitted to the JMF server
 *
 * @package JoePritchard\JDF\Events
 */
class QueueEntrySubmitted
{
    /**
     * @var \SimpleXMLElement
     */
    private $jmf_request;

    /**
     * @var \SimpleXMLElement
     */
    private $jmf_response;

    public function __construct(\SimpleXMLElement $jmf_request, \SimpleXMLElement $jmf_response)
    {
        $this->jmf_response = $jmf_response;
        $this->jmf_request  = $jmf_request;
    }
}