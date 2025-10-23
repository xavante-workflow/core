<?php

namespace Tests\Unit\Models\Domain;

use Xavante\Models\Domain\Event;
use \Xavante\Models\Domain\Condition;

class ConditionTest extends \PHPUnit\Framework\TestCase
{
    public function testConditionCreation()
    {
        $condition = new Condition('condition1', 'equals');

        $this->assertEquals('condition1', (string) $condition->variablePath);
        $this->assertEquals('equals', (string) $condition->operator);
    }


    public function testConditionJsonSerialization()
    {
        $condition = new Condition('condition2', 'different');
        $condition->setValue('some-value');

        $json = $condition->jsonSerialize();
        $this->assertJson($json);
        $this->assertIsString($json);

        // json_decode only decode the first level since actions are serialized as strings
        $decoded = json_decode($json, true);
        $this->assertEquals('condition2', $decoded['variablePath']);
        $this->assertEquals('different', $decoded['operator']);
        $this->assertIsString($decoded['value']);
        $this->assertEquals('some-value', $decoded['value']);
    }
}