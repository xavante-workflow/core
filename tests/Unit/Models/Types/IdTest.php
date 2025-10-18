<?php

namespace Tests\Unit\Models\Types;

use Xavante\Models\Types\Id;

class IdTest extends \PHPUnit\Framework\TestCase
{
    public function testIdCreationFromString()
    {
        $idString = '123e4567-e89b-12d3-a456-426614174000';

        $id = new Id($idString);

        $this->assertEquals($idString, (string) $id);
    }

    public function testIdCreationGeneratesUuidWhenNoValueProvided()
    {
        $id = new Id();

        $this->assertNotEmpty((string) $id);
        $this->assertEquals(36, strlen((string) $id));
    }
}