<?php

namespace Tests\Unit\Models\Factories;

use Xavante\Models\Factories\VariableFactory;
class VariableFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testVariableCreation()
    {
        $id = 'var1';
        $name = 'Variable 1';
        $description = 'This is a test variable.';
        $defaultValue = 42;

        $variable = VariableFactory::create($id, $name, $description, $defaultValue);

        $this->assertEquals($id, (string) $variable->id);
        $this->assertEquals($name, (string) $variable->name);
        $this->assertEquals($description, (string) $variable->description);
        $this->assertEquals($defaultValue, $variable->defaultValue);
    }
}