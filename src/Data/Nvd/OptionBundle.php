<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Nvd;

use NorthBees\CapApi\Xml\DataSetReader;
use NorthBees\CapApi\Xml\Row;

/**
 * The multi-table result of GetCapOptionsBundle (description, options,
 * relationships, packs and technical record sets, as requested).
 */
final readonly class OptionBundle
{
    public function __construct(public int $capId, public DataSetReader $dataSet) {}

    /**
     * @return list<string>
     */
    public function tableNames(): array
    {
        return array_keys($this->dataSet->tables());
    }

    /**
     * @return list<Row>
     */
    public function table(string $name): array
    {
        return $this->dataSet->rows($name);
    }
}
