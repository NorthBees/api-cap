<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Valuations;

use DOMElement;
use NorthBees\CapApi\Data\Nvd\StandardEquipmentItem;
use NorthBees\CapApi\Soap\SoapResult;
use NorthBees\CapApi\Xml\Row;

/**
 * A combined lookup + valuation (+ optional standard equipment) from the VRM service.
 */
final readonly class VrmValuation
{
    /**
     * @param  list<StandardEquipmentItem>  $standardEquipment
     */
    public function __construct(
        public VrmLookup $lookup,
        public ?UsedValuation $valuation,
        public ?bool $valuationDateMatch,
        public bool $mileageOutOfBounds,
        public array $standardEquipment,
    ) {}

    public static function fromResult(SoapResult $result, string $lookupElement = 'VRMLookup'): self
    {
        $valuation = $result->row('Valuation');
        $standardEquipment = [];

        $equipment = $result->child('StandardEquipment');
        $items = $equipment ? $result->child('SEData', $equipment) : null;

        foreach ($items->childNodes ?? [] as $node) {
            if ($node instanceof DOMElement) {
                $standardEquipment[] = StandardEquipmentItem::fromVrmRow(Row::fromElement($node));
            }
        }

        return new self(
            lookup: VrmLookup::fromRow($result->row($lookupElement)),
            valuation: $valuation->bool('Success') === false ? null : UsedValuation::fromRow($valuation),
            valuationDateMatch: $valuation->bool('ValuationDateMatch'),
            mileageOutOfBounds: $result->row()->bool('MileageOutOfBounds') ?? false,
            standardEquipment: $standardEquipment,
        );
    }
}
