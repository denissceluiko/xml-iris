<?php

namespace App\Services\Supplier\Parsers;

use App\Models\Supplier;
use Sabre\Xml\Reader;
use Sabre\Xml\Service;

class XmlParser extends Parser
{
    protected Supplier $supplier;
    protected string $namespace;
    protected string $path;

    public function __construct(Supplier $supplier, string $path)
    {
        $this->supplier = $supplier;
        $this->namespace = $supplier->config['xmlns'] ?? '';
        $this->path = $path;
    }

    public function parse() : void
    {
        $service = new Service();
        $service->elementMap = $this->generateElementMap();

        $parsed = $service->parse(file_get_contents($this->path));
        // return $this->getProductsList($parsed);
    }

    protected function generateElementMap() : array
    {
        return  $this->resolveFields($this->supplier->structure);
    }

    /**
     * Resolves fields based on $rules.
     * Removes XML namespaces and creates an assoc array.
     * Does not handle attributes though.
     *
     * @param array $fields
     * @return array
     */
    protected function resolveFields(array $rules) : array
    {
        $map = [];

        foreach ($rules as $name => $field) {
            if (!is_array($field) || !isset($field['type'])) continue;

            if ($field['type'] == 'repeatingElements')
            {
                $map[$this->xmlns($name)] = function (Reader $reader) use ($field) {
                    return $this->repeatingElements($reader, $field['child']);
                };
            } else if ($field['type'] == 'keyValue')
            {
                $map[$this->xmlns($name)] = function (Reader $reader) {
                    return $this->keyValue($reader, $this->namespace);
                };
            }
        }

        return $map;
    }

    /**
     * Adapted from Sabre\XML to process attributes
     *
     * @param Reader $reader
     * @param string $childElementName
     * @return array
     */
    protected function repeatingElements(Reader $reader, string $childElementName): array
    {
        if ('{' !== $childElementName[0]) {
            $childElementName = '{}'.$childElementName;
        }
        $result = [];

        foreach ($reader->parseGetElements() as $element) {
            if ($element['name'] === $childElementName) {
                $result[] = [
                    'name' => $element['name'],
                    'value' => $element['value'],
                    'attributes' => $element['attributes'],
                ];
            }
        }

        return $result;
    }

    /**
     * Adapted from Sabre\XML to process attributes
     *
     * @param Reader $reader
     * @param string|null $namespace
     * @return array
     */
    protected function keyValue(Reader $reader, string $namespace = null): array
    {
        // If there's no children, we don't do anything.
        if ($reader->isEmptyElement) {
            $reader->next();

            return [];
        }

        if (!$reader->read()) {
            $reader->next();

            return [];
        }

        if (Reader::END_ELEMENT === $reader->nodeType) {
            $reader->next();

            return [];
        }

        $values = [];

        do {
            if (Reader::ELEMENT === $reader->nodeType) {
                if (null !== $namespace && $reader->namespaceURI === $namespace) {
                    $values[$reader->localName] = $reader->parseCurrentElement();
                } else {
                    $clark = $reader->getClark();
                    $values[$clark] = $reader->parseCurrentElement();
                }
            } else {
                if (!$reader->read()) {
                    break;
                }
            }
        } while (Reader::END_ELEMENT !== $reader->nodeType);

        $reader->read();

        return $values;
    }

    /**
     * @param string $selector
     * @return string
     */
    protected function xmlns(string $selector = '') : string
    {
        return '{'.$this->namespace.'}'.$selector;
    }

    /**
     * Simplified extractor in case root tag is not the product container
     *
     * @param array $xmlArray
     * @return array
     */
    protected function getProductsList(array &$xmlArray) : array
    {
        return  isset($xmlArray[$this->supplier->config['root_tag']])
                    ? $xmlArray[$this->supplier->config['root_tag']]['value']
                    : $xmlArray;
    }
}
