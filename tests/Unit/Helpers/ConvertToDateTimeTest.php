<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Xavante\Helpers\ConvertToDateTime;

class ConvertToDateTimeTest extends TestCase
{
    protected $objConvertDateTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objConvertDateTime = new class {
            use ConvertToDateTime;
            
            public function process(mixed $value1): \DateTime
            {
                return $this->convertToDateTime($value1);
            }
        };
    }

    public function testConvertToDateTimeWithDateTimeObject()
    {
        $dateTime = new \DateTime('2024-06-15 10:00:00');

        $result = $this->objConvertDateTime->process($dateTime);
        // $result = $operator->evaluate($dateTime, null);        
        $this->assertIsObject($result);
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals($dateTime->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
    }

    public function testConvertToDateTimeWithUnixTimestamp()
    {
        $timestamp = 1718476800; // Corresponds to 2024-06-15 00:00:00 UTC

        $result = $this->objConvertDateTime->process($timestamp);
        $this->assertIsObject($result);
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('2024-06-15 18:40:00', $result->format('Y-m-d H:i:s'));
    }

    public function testConvertToDateTimeWithDateString()
    {
        $dateString = '2024-06-15 10:00:00';

        $result = $this->objConvertDateTime->process($dateString);
        $this->assertIsObject($result);
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('2024-06-15 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function testConvertToDateTimeWithInvalidString()
    {
        $this->expectException(\Exception::class);
        $invalidDateString = 'invalid-date-format';

        $this->objConvertDateTime->process($invalidDateString);
    }

    public function testConvertDateTimeWithInvalidInput() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot convert value to DateTime');
        $invalidInput = ['not', 'a', 'date'];

        $this->objConvertDateTime->process($invalidInput);
    }

}
