<?php
declare(strict_types=1);
/**
 * JMF.php
 *
 * @category JDF
 * @author   Joe Pritchard
 *
 * Created:  02/10/2017 10:13
 *
 */

namespace JoePritchard\JDF;

use Illuminate\Support\Str;
use JoePritchard\JDF\Exceptions\JMFResponseException;
use JoePritchard\JDF\Exceptions\JMFSubmissionException;
use SimpleXMLElement;

/**
 * Class JMF
 *
 * @package JoePritchard\JDF
 */
class JMF extends BaseJDF
{
    /**
     * JMF constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->initialiseMessage('JMF');
    }

    /**
     * Add the DeviceID attribute to the root JMF element
     *
     * @param string $device
     *
     * @return JMF
     */
    public function setDevice(string $device): JMF
    {
        $this->root->addAttribute('DeviceID', $device);

        return $this;
    }

    /**
     * Submit the current JMF message to the JMF server using cURL. Re-init the message afterwards and return the response
     *
     * @param string|null $url If no URL is specified we will submit the message to the base JMF server url
     *
     * @return SimpleXMLElement
     * @throws JMFSubmissionException
     */
    public function submitMessage($url = null): SimpleXMLElement
    {
        // Make sure cURL is enabled
        if (!function_exists('curl_version')) {
            throw new \RuntimeException("cURL must be installed to send JMF messages.");
        }

        // add a timestamp to this message
        $this->root->addAttribute('TimeStamp', \Carbon\Carbon::now()
            ->toIso8601String());

        // use the url provided to us, or alternatively the base JMF server url
        $target = $url ?? $this->server_url;

        // replace the server's local IP address with
        $target = Str::replaceFirst('http://192.168.0.47:7751/', $this->server_url, $target);

        echo 'Sending a message to ' . $target . PHP_EOL;
        dump($this->root->asXML());

        $curl_handle = curl_init($target);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $this->root->asXML());
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, [
            'Content-type: application/vnd.cip4-jmf+xml',
        ]);

        $raw_result = curl_exec($curl_handle);
        curl_close($curl_handle);

        $this->initialiseMessage('JMF');

        if ($raw_result === false) {
            throw new JMFSubmissionException('Failed to communicate with the JMF server');
        }

        try {
            $result = new SimpleXMLElement($raw_result);
        } catch (\Exception $exception) {
            throw new JMFResponseException('The JMF server responded with Invalid XML: ' . $raw_result);
        }

        if ((int) $result->attributes['ReturnCode'] > 0) {
            throw new JMFSubmissionException($result->asXML());
        }

        return $result->Response;
    }
}