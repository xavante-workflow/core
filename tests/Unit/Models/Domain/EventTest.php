<?php

namespace Tests\Unit\Models\Domain;

class EventTest extends \PHPUnit\Framework\TestCase
{
    public function testEventCreation()
    {
        $event = new \Xavante\Models\Domain\Event('event1', 'Test Event');

        $this->assertEquals('event1', (string) $event->id);
        $this->assertEquals('Test Event', (string) $event->name);
        $this->assertCount(0, $event->actions);
    }

    public function testAddActionToEvent()
    {
        $event = new \Xavante\Models\Domain\Event('event1', 'Test Event');

        $mockAction = $this->createMock(\Xavante\Actions\Actionable::class);
        $event->addAction($mockAction);

        $this->assertCount(1, $event->actions);
    }

    public function testEventJsonSerialization()
    {
        $event = new \Xavante\Models\Domain\Event('event1', 'Test Event');

        $mockAction = $this->createMock(\Xavante\Actions\Actionable::class);
        $event->addAction($mockAction);

        $json = $event->jsonSerialize();
        $this->assertJson($json);
        $this->assertIsString($json);

        // json_decode only decode the first level since actions are serialized as strings
        $decoded = json_decode($json, true);
        $this->assertEquals('event1', $decoded['id']);
        $this->assertEquals('Test Event', $decoded['name']);
        $this->assertIsString($decoded['actions']);
        $this->assertEquals('[{}]', $decoded['actions']);
    }
}