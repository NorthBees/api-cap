<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Data\Valuations;

use Carbon\CarbonImmutable;
use DOMElement;
use NorthBees\CapApi\Soap\SoapResult;
use NorthBees\CapApi\Xml\Row;

/**
 * A CAP Live (daily) valuation from the UsedValuesLive service.
 */
final readonly class LiveValuation
{
    /**
     * @param  list<string>  $comments
     */
    public function __construct(
        public UsedValuation $values,
        public ?CarbonImmutable $valuationDate,
        public ?bool $isMonthlyPosition,
        public ?int $plateYear,
        public ?int $plateMonth,
        public ?string $plateLetter,
        public array $comments,
    ) {}

    public static function fromResult(SoapResult $result): self
    {
        $valuationDate = $result->child('ValuationDate');
        $dateRow = Row::fromElement($valuationDate);
        $valuations = $valuationDate ? $result->child('Valuations', $valuationDate) : null;
        $first = $valuations ? $result->child('Valuation', $valuations) : null;
        $plate = $result->row('Plate');

        $comments = [];

        foreach (($valuationDate ? $result->child('Comments', $valuationDate) : null)->childNodes ?? [] as $node) {
            if ($node instanceof DOMElement && trim($node->textContent) !== '') {
                $comments[] = trim($node->textContent);
            }
        }

        return new self(
            values: UsedValuation::fromRow(Row::fromElement($first)),
            valuationDate: $dateRow->date('Date'),
            isMonthlyPosition: $dateRow->bool('IsMonthlyPosition'),
            plateYear: $plate->int('Year'),
            plateMonth: $plate->int('Month'),
            plateLetter: $plate->string('Letter'),
            comments: $comments,
        );
    }
}
