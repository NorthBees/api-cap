<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Soap;

use DOMElement;
use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Exceptions\CapInvalidResponseException;
use NorthBees\CapApi\Exceptions\CapRequestFailedException;
use NorthBees\CapApi\Xml\DataSetReader;
use NorthBees\CapApi\Xml\Row;
use NorthBees\CapApi\Xml\XmlLoader;

/**
 * The <{Operation}Result> element of a SOAP response.
 */
final readonly class SoapResult
{
    public function __construct(
        private DOMElement $element,
        public CapService $service,
        public string $operation,
    ) {}

    public function element(): DOMElement
    {
        return $this->element;
    }

    /**
     * A direct child element by local name (case-insensitive).
     */
    public function child(string $name, ?DOMElement $parent = null): ?DOMElement
    {
        foreach (($parent ?? $this->element)->childNodes as $node) {
            if ($node instanceof DOMElement && strcasecmp($node->localName, $name) === 0) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Throw when CAP reports Success=false. Results without a Success element pass.
     */
    public function assertSuccess(): self
    {
        $row = $this->row();

        if ($row->bool('Success') === false) {
            $message = $row->string('FailMessage') ?? $row->string('FailReason') ?? 'Unknown error';

            throw new CapRequestFailedException("CAP {$this->operation} failed: {$message}", $this->service, $this->operation);
        }

        return $this;
    }

    /**
     * The leaf values of the result element (or of a named child).
     */
    public function row(?string $child = null): Row
    {
        return Row::fromElement($child === null ? $this->element : $this->child($child));
    }

    public function dataSet(): DataSetReader
    {
        return DataSetReader::fromElement($this->child('Returned_DataSet'));
    }

    public function scalar(): ?string
    {
        $value = trim($this->element->textContent);

        return $value === '' ? null : $value;
    }

    /**
     * The XML document embedded in an untyped (xs:any) result such as the DVLA lookup.
     * Handles both an inline child element and an escaped XML string.
     */
    public function embeddedXml(): DOMElement
    {
        foreach ($this->element->childNodes as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        $root = XmlLoader::load($this->element->textContent)->documentElement;

        if ($root === null) {
            throw new CapInvalidResponseException("CAP {$this->operation} returned no XML payload.", $this->service, $this->operation);
        }

        return $root;
    }
}
