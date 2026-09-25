<?php

declare(strict_types=1);

namespace NorthBees\CapApi\Testing;

use NorthBees\CapApi\Enums\CapService;
use NorthBees\CapApi\Soap\SoapEnvelope;

/**
 * Builds realistic CAP SOAP response bodies for tests.
 */
final class CapResponse
{
    /**
     * A CAPDataSetResult with one or more DataSet tables.
     *
     * @param  array<string, list<array<string, scalar|null>>>  $tables  e.g. ['Table' => [['CMan_Code' => 1, 'CMan_Name' => 'FORD']]]
     */
    public static function dataSet(CapService $service, string $operation, array $tables, string $dataSetName = 'NewDataSet'): string
    {
        $rows = '';

        foreach ($tables as $table => $tableRows) {
            foreach ($tableRows as $index => $row) {
                $rows .= sprintf('<%s diffgr:id="%s%d" msdata:rowOrder="%d">%s</%1$s>', $table, $table, $index + 1, $index, self::elements($row));
            }
        }

        $inner = '<Success>true</Success><FailMessage />'
            .'<Returned_DataSet>'
            .'<xs:schema id="'.$dataSetName.'" xmlns="" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">'
            .'<xs:element name="'.$dataSetName.'" msdata:IsDataSet="true" />'
            .'</xs:schema>'
            .'<diffgr:diffgram xmlns:msdata="urn:schemas-microsoft-com:xml-msdata" xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'
            .'<'.$dataSetName.' xmlns="">'.$rows.'</'.$dataSetName.'>'
            .'</diffgr:diffgram>'
            .'</Returned_DataSet>';

        return self::envelope($service, $operation, $inner);
    }

    /**
     * A typed (non-DataSet) result. Nested arrays become nested elements; a list
     * under a key repeats that element, e.g. ['SEData' => ['SEDataItem' => [[...], [...]]]].
     *
     * @param  array<string, mixed>  $values
     */
    public static function result(CapService $service, string $operation, array $values): string
    {
        return self::envelope($service, $operation, self::elements($values));
    }

    /**
     * A DVLA lookup <RESPONSE>. Match flags default from which blocks are supplied.
     *
     * @param  array<string, scalar|null>  $dvla
     * @param  array<string, scalar|null>  $cap
     * @param  array<string, int>  $matchLevel
     * @param  list<array<string, scalar|null>>  $alternativeDerivatives
     */
    public static function dvla(
        array $dvla = [],
        array $cap = [],
        array $matchLevel = [],
        bool $success = true,
        bool $limitExceeded = false,
        string $errorMessage = '',
        array $alternativeDerivatives = [],
        string $operation = 'DVLALookupVRM',
    ): string {
        $matchLevel += [
            'DVLA' => $dvla === [] ? 0 : 1,
            'DVLAKEEPER' => $dvla === [] ? 0 : 1,
            'DVLABASIC' => $dvla === [] ? 0 : 1,
            'SMMT' => 0,
            'CAP' => $cap === [] ? 0 : 1,
            'ALTERNATIVEVRMS' => 0,
            'ALTERNATIVEDERIVATIVES' => $alternativeDerivatives === [] ? 0 : 1,
        ];

        $alternatives = '';

        foreach ($alternativeDerivatives as $alternative) {
            $alternatives .= '<DERIVATIVE>'.self::elements($alternative).'</DERIVATIVE>';
        }

        $response = '<RESPONSE xmlns="">'
            .'<SUCCESS>'.($success ? 'true' : 'false').'</SUCCESS>'
            .'<ERRORMESSAGE>'.self::escape($errorMessage).'</ERRORMESSAGE>'
            .'<AUDITID>123456</AUDITID>'
            .'<MONTHLYLOOKUPLIMITEXCEEDED>'.($limitExceeded ? 'True' : 'False').'</MONTHLYLOOKUPLIMITEXCEEDED>'
            .'<MATCHLEVEL>'.self::elements($matchLevel).'</MATCHLEVEL>'
            .'<DATA>'
            .'<DVLA>'.self::elements($dvla).'</DVLA>'
            .'<CAP>'.self::elements($cap).'</CAP>'
            .'<ALTERNATIVEDERIVATIVES>'.$alternatives.'</ALTERNATIVEDERIVATIVES>'
            .'</DATA>'
            .'</RESPONSE>';

        return self::envelope(CapService::Dvla, $operation, $response);
    }

    /**
     * A result with Success=false.
     */
    public static function failure(CapService $service, string $operation, string $message): string
    {
        return self::envelope($service, $operation, '<Success>false</Success><FailMessage>'.self::escape($message).'</FailMessage>');
    }

    /**
     * A soap:Fault body (served with HTTP 500 by ASMX).
     */
    public static function fault(string $message, string $code = 'soap:Server'): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:soap="'.SoapEnvelope::SOAP_NAMESPACE.'"><soap:Body><soap:Fault>'
            .'<faultcode>'.self::escape($code).'</faultcode><faultstring>'.self::escape($message).'</faultstring><detail />'
            .'</soap:Fault></soap:Body></soap:Envelope>';
    }

    /**
     * Read a recorded response from a directory of fixtures.
     */
    public static function fixture(string $path): string
    {
        return (string) file_get_contents($path);
    }

    public static function envelope(CapService $service, string $operation, string $inner): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:soap="'.SoapEnvelope::SOAP_NAMESPACE.'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
            .'<soap:Body>'
            .'<'.$operation.'Response xmlns="'.$service->namespace().'">'
            .'<'.$operation.'Result>'.$inner.'</'.$operation.'Result>'
            .'</'.$operation.'Response>'
            .'</soap:Body></soap:Envelope>';
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private static function elements(array $values): string
    {
        $xml = '';

        foreach ($values as $name => $value) {
            if (is_array($value) && array_is_list($value)) {
                foreach ($value as $item) {
                    $xml .= "<{$name}>".(is_array($item) ? self::elements($item) : self::scalar($item))."</{$name}>";
                }

                continue;
            }

            $xml .= match (true) {
                $value === null => "<{$name} xsi:nil=\"true\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" />",
                is_array($value) => "<{$name}>".self::elements($value)."</{$name}>",
                default => "<{$name}>".self::scalar($value)."</{$name}>",
            };
        }

        return $xml;
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => self::escape((string) $value),
            default => '',
        };
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
