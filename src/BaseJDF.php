<?php
declare(strict_types=1);
/**
 * BaseJDF.php
 *
 * @category JDF
 * @author   Joe Pritchard
 *
 * Created:  02/10/2017 09:57
 *
 */

namespace JoePritchard\JDF;


use BadMethodCallException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use SimpleXMLElement;

/**
 * Class BaseJDF
 *
 * @package JoePritchard\JDF
 */
class BaseJDF
{
    /**
     * @var SimpleXMLElement
     * The root element
     */
    protected $root;

    /**
     * @var array
     * These are the names of valid top-level elements that can go under the opening JMF or JDF root element
     */
    private $root_nodes = ['Query', 'Command', 'ResourcePool', 'ResourceLinkPool'];

    /**
     * @var string
     */
    protected $server_url;

    /**
     * @var string
     * Path under which our printable PDF files live (relative to JMF Server)
     */
    protected $server_file_path;

    /**
     * @var string
     */
    private $sender_id;

    /**
     * BaseJDF constructor.
     */
    public function __construct()
    {
        $this->server_url       = Str::finish(config('jdf.server_url'), '/');
        $this->server_file_path = Str::finish(config('jdf.server_file_path'), '/');
        $this->sender_id        = config('jdf.sender_id', config('app.name'));
    }

    /**
     * Build the standard JMF or JDF message object to get us started
     */
    protected function initialiseMessage(string $type): void
    {
        // These are used to generate the initial XML field attributes
        $xml_encoding = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlns        = 'http://www.CIP4.org/JDFSchema_1_1';
        $xmlns_xsi    = "http://www.w3.org/2001/XMLSchema-instance";
        $version      = '1.4';

        // Initialize the JMF or JDF root node
        $root = new SimpleXMLElement($xml_encoding . '<'.$type.'/>', LIBXML_NOEMPTYTAG);
        $root->addAttribute('xmlns', $xmlns);
        $root->addAttribute('xmlns:xsi', $xmlns_xsi);
        $root->addAttribute('SenderID', $this->sender_id);
        $root->addAttribute('Version', $version);

        $this->root = $root;

        // Register the namespace.
        $this->root->registerXPathNamespace('xsi', $xmlns_xsi);
    }

    /**
     * Get the raw jdf or jmf message as xml
     *
     * @return string
     */
    public function getRawMessage(): string
    {
        return $this->root->asXML();
    }

    /**
     * function __call
     * If you try to call a method whose name is equal to a supported JMF message type, return the element requested
     *
     * @param $method
     * @param $arguments
     *
     * @return SimpleXMLElement|SimpleXMLElement[]
     */
    public function __call($method, $arguments): SimpleXMLElement
    {
        $node_type = Str::Studly($method);

        if (!in_array($node_type, $this->root_nodes, true)) {
            throw new BadMethodCallException('Unknown node type \'' . $node_type . '\'');
        }

        // return the node if it exists, or create a new one (so only ever one allowed)
        $child_node = $this->root->$node_type ?? $this->root->addChild($node_type);
        return $child_node;
    }
}