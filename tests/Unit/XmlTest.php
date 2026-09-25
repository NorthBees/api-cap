<?php

declare(strict_types=1);

use NorthBees\CapApi\Exceptions\CapInvalidResponseException;
use NorthBees\CapApi\Xml\DataSetReader;
use NorthBees\CapApi\Xml\Row;
use NorthBees\CapApi\Xml\XmlLoader;

function dataSetElement(string $diffgramContent): DOMElement
{
    $document = XmlLoader::load(
        '<Returned_DataSet><xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" />'
        .'<diffgr:diffgram xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">'.$diffgramContent.'</diffgr:diffgram></Returned_DataSet>'
    );

    return $document->documentElement;
}

it('rejects documents with a DTD', function () {
    XmlLoader::load('<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>');
})->throws(CapInvalidResponseException::class, 'DTD');

it('rejects malformed and empty XML', function (string $xml) {
    XmlLoader::load($xml);
})->with(['<foo>', '', 'not xml'])->throws(CapInvalidResponseException::class);

it('always returns rows as a list, even for a single row', function () {
    $reader = DataSetReader::fromElement(dataSetElement('<NewDataSet xmlns=""><Table><CMan_Code>1</CMan_Code></Table></NewDataSet>'));

    expect($reader->rows())->toHaveCount(1)
        ->and($reader->first()->int('CMan_Code'))->toBe(1);
});

it('groups rows into tables and ignores diffgr:before blocks', function () {
    $reader = DataSetReader::fromElement(dataSetElement(
        '<P11D xmlns=""><CO2_Table><CO2>120</CO2></CO2_Table><CC_Table><CC>1598</CC></CC_Table><CC_Table><CC>1999</CC></CC_Table></P11D>'
        .'<diffgr:before><CC_Table xmlns=""><CC>1</CC></CC_Table></diffgr:before>'
    ));

    expect(array_keys($reader->tables()))->toBe(['CO2_Table', 'CC_Table'])
        ->and($reader->rows('cc_table'))->toHaveCount(2)
        ->and($reader->first('CO2_Table')->int('CO2'))->toBe(120);
});

it('treats a missing or empty dataset as empty', function () {
    expect(DataSetReader::fromElement(null)->isEmpty())->toBeTrue()
        ->and(DataSetReader::fromElement(dataSetElement(''))->rows())->toBe([])
        ->and(DataSetReader::fromElement(dataSetElement(''))->first())->toBeNull();
});

it('reads typed values null-safely with case-insensitive keys', function () {
    $row = new Row([
        'CAPID' => ' 56520 ',
        'Blank' => '   ',
        'Nil' => null,
        'Flag' => 'False',
        'Compact' => '20140901',
        'Iso' => '2014-09-01T00:00:00+01:00',
        'MinValue' => '0001-01-01T00:00:00',
        'Price' => '1234.56',
    ]);

    expect($row->int('capid'))->toBe(56520)
        ->and($row->string('Blank'))->toBeNull()
        ->and($row->string('Nil'))->toBeNull()
        ->and($row->string('Missing'))->toBeNull()
        ->and($row->bool('Flag'))->toBeFalse()
        ->and($row->date('Compact')->toDateString())->toBe('2014-09-01')
        ->and($row->date('Iso')->toDateString())->toBe('2014-09-01')
        ->and($row->date('MinValue'))->toBeNull()
        ->and($row->date('Missing'))->toBeNull()
        ->and($row->float('Price'))->toBe(1234.56)
        ->and($row->int('Price'))->toBe(1235);
});

it('treats xsi:nil elements as null', function () {
    $document = XmlLoader::load('<Row xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><A xsi:nil="true" /><B>x</B></Row>');

    expect(Row::fromElement($document->documentElement)->toArray())->toBe(['A' => null, 'B' => 'x']);
});
