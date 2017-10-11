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
        $resource_link->addAttribute('rRef', (string)$this->resourcePool()->$resource_name[0]->attributes()->ID);
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
        // add a layout element and filespec for this document within the ResourcePool
        $found_layout_element = false;
        $layout_element       = $this->resourcePool()->LayoutElement;
        $file_path_for_jdf    = $this->formatPrintFilePath('MAX Default Source File Location/Doppelganger_Pdfs/' . $file_name);

        if ($layout_element->asXML() !== false) {
            // there are already LayoutElements, so check if one exists with a URL attribute equal to the file_name we're adding
            foreach ($this->resourcePool()->LayoutElement as $layout_element) {
                if ($layout_element->FileSpec->attributes()->URL === $file_path_for_jdf) {
                    // we found it :)
                    $found_layout_element = true;
                    break;
                }
            }
        }

        if (!$found_layout_element) {
            // we did not find a matching LayoutElement (or there wasn't one in the first place), so create a new one
            $layout_element = $this->resourcePool()->addChild('LayoutElement');
            $layout_element->addAttribute('Class', 'Parameter');
            $layout_element->addAttribute('ID', $item_id === '' ? 'LE' . count($this->resourcePool()->LayoutElement) : 'Item' . $item_id);
            $layout_element->addAttribute('Status', 'Available');

            $file_spec = $layout_element->addChild('FileSpec');
            $file_spec->addAttribute('URL', $file_path_for_jdf);
        }

        $run_list = $this->resourcePool()->RunList;
        if ($run_list->asXML() === false) {
            $run_list = $this->resourcePool()->addChild('RunList');
            $run_list->addAttribute('ID', 'RunListID');
        }

        // add a link to the file we just created a layout element for, within its own RunList (inside the parent RunList)
        for ($index = 0; $index < $quantity; $index++) {
            echo 'Adding a runlist element for print ' . ($index + 1) . 'of ' . $quantity . PHP_EOL;
            $child_run_list = $run_list->addChild('RunList');
            $ref = $child_run_list->addChild('LayoutElementRef');
            $ref->addAttribute('rRef', (string)$layout_element->attributes()->ID);
        }

        // now we need to reference our RunList in ResourceLinkPool
        if ($this->ResourceLinkPool()->RunListLink->asXML() === false) {
            $this->linkResource('RunList', 'Input', ['CombinedProcessIndex' => '0 4']);
        }

        return $this;
    }
}