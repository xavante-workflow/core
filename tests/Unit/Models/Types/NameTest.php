<?php

namespace Tests\Unit\Models\Types;

use Xavante\Models\Types\Name;

class NameTest extends \PHPUnit\Framework\TestCase
{
    public function testNameCreationFromString()
    {
        $nameString = 'Test Workflow Name';

        $name = new Name($nameString);

        $this->assertEquals($nameString, $name->getName());
        $this->assertEquals($nameString, (string) $name);
    }

    public function testNameCreationWithEmptyString()
    {
        $nameString = '';

        $name = new Name($nameString);

        $this->assertEquals($nameString, $name->getName());
        $this->assertEquals($nameString, (string) $name);
    }

    public function testNameCreationWithSpecialCharacters()
    {
        $nameString = 'Workflow-Name_123 (v2.0)';

        $name = new Name($nameString);

        $this->assertEquals($nameString, $name->getName());
        $this->assertEquals($nameString, (string) $name);
    }

    public function testNameCreationWithUnicodeCharacters()
    {
        $nameString = 'Fluxo de Trabalho - 測試工作流';

        $name = new Name($nameString);

        $this->assertEquals($nameString, $name->getName());
        $this->assertEquals($nameString, (string) $name);
    }

    public function testNameToStringMethod()
    {
        $nameString = 'Another Test Name';
        $name = new Name($nameString);

        $this->assertEquals($nameString, $name->__toString());
    }
}