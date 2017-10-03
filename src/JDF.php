<?php
declare(strict_types=1);
/**
 * JDF.php
 *
 * @category JDF
 * @author   Joe Pritchard
 *
 * Created:  02/10/2017 09:54
 *
 */

namespace JoePritchard\JDF;


use SimpleXMLElement;

/**
 * Class JDF
 *
 * @package JoePritchard\JDF
 */
class JDF extends BaseJDF
{
    /**
     * @var array
     * These are the names of valid top-level elements that can go under the opening JMF or JDF root element
     */
    protected $root_nodes = ['ResourcePool', 'ResourceLinkPool'];

    /**
     * JDF constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->initialiseMessage('JDF');
    }

    /**
     * Create an entry in ResourceLinkPool which refers to the specified resource
     *
     * @param string $resource_name The element name of the resource you want to create a link for
     * @param string $usage         The Usage attribute of the link (Input or Output)
     * @param array  $attributes    Any additional attributes you want on the link, like Amount etc.
     *
     * @return void
     */
    private function linkResource(string $resource_name, string $usage, array $attributes = []): void
    {
        // validate the usage string
        if (!in_array($usage, ['Input', 'Output'])) {
            throw new \InvalidArgumentException('$usage can only be Input or Output');
        }

        // validate the resource name
        if ($this->resourcePool()->$resource_name === null) {
            throw new \InvalidArgumentException('No ' . $resource_name . ' resource exists. Refusing to make link');
        }

        // create a link element for this resource
        $resource_link = $this->resourceLinkPool()->addChild($resource_name . 'Link');
        $resource_link->addAttribute('rRef', (string)$this->resourcePool()->$resource_name->attributes()->ID);
        $resource_link->addAttribute('Usage', $usage);

        foreach ($attributes as $name => $value) {
            $resource_link->addAttribute((string)$name, (string)$value);
        }
    }

    /**
     * Set a CustomerInfo node on the resourcePool
     *
     * @param array $customer_info_parameters
     *
     * @return $this
     */
    public function setCustomerInfo(array $customer_info_parameters)
    {
        $customer_info = $this->resourcePool()->CustomerInfo ?? $this->resourcePool()->addChild('CustomerInfo');

        foreach ($customer_info_parameters as $parament_name => $parameter_value) {
            $customer_info->addAttribute((string)$parament_name, (string)$parameter_value);
        }

        // add an ID to the customer info node so we can link it, and then link it!
        $customer_info->addAttribute('ID', 'CI1');
        $this->linkResource('CustomerInfo','Input');

        return $this;
    }

    /**
     * Add a print to a JDF message. We do NOT check this file for existence, as you're often going to want to send files relative to the JMF server
     *
     * @param $order_item
     */
    public function addPrintFile(string $file_name, int $quantity, string $item_id = '')
    {
        $run_id      = $item_id === '' ? '1' : $item_id;
        $run_list_id = 'R' . $item_id;

        // so it must have Type and Types attributes
        $this->root->addAttribute('Type', 'Combined'); // we might have several run lists in one jdf file, say if one order contains multiple items
        $this->root->addAttribute('Types', 'DigitalPrinting'); // in future could you do finishing and stuff here? Check the spec

        // one run list for each pdf file
        $run_list    = $this->ResourcePool()->addChild('RunList');
        $run_list->addAttribute('ID', $run_list_id);

        $run = $run_list->addChild('RunList');
        $run->addAttribute('Run', "1");

        $file_spec = $run->addChild('LayoutElement')->addChild('FileSpec');
        $file_spec->addAttribute('MimeType', 'application/pdf');
        $file_spec->addAttribute('URL', $this->server_file_path . $file_name);

        // add the quantity as a component node
        $quantity_element = $this->ResourcePool()->addChild('Component');
        $quantity_element->addAttribute('Class', 'Quantity');
        $quantity_element->addAttribute('ComponentType', 'FinalProduct');
        $quantity_element->addAttribute('ID', 'Q1');

        // now we need to reference our RunList and Component in ResourceLinkPool
        $this->linkResource('RunList', 'Input');
        $this->linkResource('Component', 'Output', ['Amount' => $quantity]);

        return $this;
    }

}