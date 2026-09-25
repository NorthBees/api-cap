<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Xml;

use DOMElement;
use DOMXPath;

/**
 * Reads the .NET DataSet (xs:schema + diffgr:diffgram) held in a CAP Returned_DataSet element.
 *
 * Rows are always returned as lists, so a single-row result is never a special case.
 */
final class DataSetReader
{
    private const string DIFFGRAM_NAMESPACE = 'urn:schemas-microsoft-com:xml-diffgram-v1';

    /**
     * @param  array<string, list<Row>>  $tables
     */
    private function __construct(private readonly array $tables) {}

    public static function fromElement(?DOMElement $returnedDataSet): self
    {
        if ($returnedDataSet === null || $returnedDataSet->ownerDocument === null) {
            return new self([]);
        }

        $xpath = new DOMXPath($returnedDataSet->ownerDocument);
        $xpath->registerNamespace('diffgr', self::DIFFGRAM_NAMESPACE);

        $tables = [];
        $dataSets = $xpath->query('.//diffgr:diffgram/*[namespace-uri() != "'.self::DIFFGRAM_NAMESPACE.'"]', $returnedDataSet);

        foreach ($dataSets ?: [] as $dataSet) {
            foreach ($dataSet->childNodes as $row) {
                if ($row instanceof DOMElement) {
                    $tables[$row->localName][] = Row::fromElement($row);
                }
            }
        }

        return new self($tables);
    }

    /**
     * @return array<string, list<Row>>
     */
    public function tables(): array
    {
        return $this->tables;
    }

    /**
     * Rows for a table, or for the first table when no name is given.
     *
     * @return list<Row>
     */
    public function rows(?string $table = null): array
    {
        if ($table === null) {
            return $this->tables === [] ? [] : $this->tables[array_key_first($this->tables)];
        }

        foreach ($this->tables as $name => $rows) {
            if (strcasecmp($name, $table) === 0) {
                return $rows;
            }
        }

        return [];
    }

    public function first(?string $table = null): ?Row
    {
        return $this->rows($table)[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->tables === [];
    }
}
