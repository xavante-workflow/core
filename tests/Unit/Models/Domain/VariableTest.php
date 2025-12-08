<?php

namespace Tests\Unit\Models\Domain;

use Xavante\Models\Domain\Variable;

class VariableTest extends \PHPUnit\Framework\TestCase
{
    public function testVariableCreationFromArray()
    {
        $variableData = [
            'id' => 'var1', 
            'name' => 'Variable 1', 'description' => 'This is a test variable.', 
            'defaultValue' => 42,
        'value' => 100
        ];

        $variable = new Variable($variableData['id'], $variableData['name'], $variableData['description'], $variableData['defaultValue']);

        $this->assertEquals($variableData['id'], (string) $variable->id);
        $this->assertEquals($variableData['name'], (string) $variable->name);
        $this->assertEquals($variableData['description'], (string) $variable->description);
        $this->assertEquals($variableData['defaultValue'], $variable->defaultValue);

        // When the value is not set, it should return the default value
        $this->assertEquals($variableData['defaultValue'], $variable->getValue());

        // Set a new value and verify it
        $variable->setValue($variableData['value']);
        $this->assertEquals($variableData['value'], $variable->getValue());
    }


    public function testSerializedVariableRetainsValues()
    {
        $variable = new Variable('var2', 'Variable 2', 'Another test variable.', 'default');

        // Serialize and then unserialize the variable
        $serialized = $variable->jsonSerialize();


        $this->assertIsString($serialized);
        $this->assertNotEmpty($serialized);
        $this->assertJson($serialized);




        $unserializedVariable = Variable::jsonUnserialize($serialized);

        $this->assertEquals((string) $variable->id, (string) $unserializedVariable->id);
        $this->assertEquals((string) $variable->name, (string) $unserializedVariable->name);
        $this->assertEquals((string) $variable->description, (string) $unserializedVariable->description);
        $this->assertEquals($variable->defaultValue, $unserializedVariable->defaultValue);
    }

}