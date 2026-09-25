<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Enums;

/**
 * The MATCHLEVEL flags returned by a DVLA lookup.
 */
enum MatchLevelFlag: string
{
    case Dvla = 'DVLA';
    case DvlaKeeper = 'DVLAKEEPER';
    case DvlaBasic = 'DVLABASIC';
    case Smmt = 'SMMT';
    case Cap = 'CAP';
    case AlternativeVrms = 'ALTERNATIVEVRMS';
    case AlternativeDerivatives = 'ALTERNATIVEDERIVATIVES';
}
