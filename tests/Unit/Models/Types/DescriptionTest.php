<?php

namespace Tests\Unit\Models\Types;

use Xavante\Models\Types\Description;

class DescriptionTest extends \PHPUnit\Framework\TestCase
{
    public function testDescriptionCreationFromString()
    {
        $descriptionString = 'This is a test workflow description that explains the purpose and functionality of the workflow.';

        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->getDescription());
        $this->assertEquals($descriptionString, (string) $description);
    }

    public function testDescriptionCreationWithEmptyString()
    {
        $descriptionString = '';

        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->getDescription());
        $this->assertEquals($descriptionString, (string) $description);
    }

    public function testDescriptionCreationWithMultilineText()
    {
        $descriptionString = "This is a multi-line description.\nIt contains line breaks\nand multiple sentences.";

        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->getDescription());
        $this->assertEquals($descriptionString, (string) $description);
    }

    public function testDescriptionCreationWithSpecialCharacters()
    {
        $descriptionString = 'Description with special chars: @#$%^&*()_+-={}[]|\\:";\'<>?,./ and numbers 123456789';

        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->getDescription());
        $this->assertEquals($descriptionString, (string) $description);
    }

    public function testDescriptionCreationWithUnicodeCharacters()
    {
        $descriptionString = 'Descrição com caracteres Unicode: ção, ñ, 中文, العربية, русский';

        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->getDescription());
        $this->assertEquals($descriptionString, (string) $description);
    }

    public function testDescriptionCreationWithLongText()
    {
        $descriptionString = str_repeat('This is a very long description text that tests the handling of large strings. ', 100);

        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->getDescription());
        $this->assertEquals($descriptionString, (string) $description);
    }

    public function testDescriptionToStringMethod()
    {
        $descriptionString = 'Another test description';
        $description = new Description($descriptionString);

        $this->assertEquals($descriptionString, $description->__toString());
    }
}