<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Dvla;

use DOMElement;
use NorthBees\CapApi\Enums\MatchLevelFlag;
use NorthBees\CapApi\Xml\Row;

/**
 * The parsed <RESPONSE> document from DVLALookupVRM / DVLALookupVIN.
 */
final readonly class DvlaLookupResult
{
    /**
     * @param  list<AlternativeDerivative>  $alternativeDerivatives
     */
    public function __construct(
        public bool $success,
        public ?string $errorMessage,
        public bool $monthlyLookupLimitExceeded,
        public ?int $auditId,
        public MatchLevel $matchLevel,
        public ?DvlaRecord $dvla,
        public ?CapIdentity $cap,
        public array $alternativeDerivatives,
    ) {}

    public static function fromXml(DOMElement $response): self
    {
        $root = Row::fromElement($response);
        $data = self::childElement($response, 'DATA');
        $matchLevel = MatchLevel::fromRow(Row::fromElement(self::childElement($response, 'MATCHLEVEL')));

        $dvla = self::childElement($data, 'DVLA');
        $cap = self::childElement($data, 'CAP');
        $capRow = Row::fromElement($cap);

        $alternatives = [];

        foreach (self::childElement($data, 'ALTERNATIVEDERIVATIVES')?->childNodes ?? [] as $node) {
            if ($node instanceof DOMElement) {
                $alternatives[] = AlternativeDerivative::fromRow(Row::fromElement($node));
            }
        }

        return new self(
            success: $root->bool('SUCCESS') ?? false,
            errorMessage: $root->string('ERRORMESSAGE'),
            monthlyLookupLimitExceeded: $root->bool('MONTHLYLOOKUPLIMITEXCEEDED') ?? false,
            auditId: $root->int('AUDITID'),
            matchLevel: $matchLevel,
            dvla: $dvla !== null && $matchLevel->has(MatchLevelFlag::Dvla) ? DvlaRecord::fromRow(Row::fromElement($dvla)) : null,
            cap: $cap !== null && $capRow->int('CAPID') ? CapIdentity::fromRow($capRow) : null,
            alternativeDerivatives: $alternatives,
        );
    }

    public function isMatched(): bool
    {
        return $this->cap !== null || $this->dvla !== null;
    }

    private static function childElement(?DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent?->childNodes ?? [] as $node) {
            if ($node instanceof DOMElement && strcasecmp($node->localName, $name) === 0) {
                return $node;
            }
        }

        return null;
    }
}
