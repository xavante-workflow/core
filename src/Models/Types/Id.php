<?php

namespace Xavante\Models\Types;

class Id
{
    public readonly string $id;

    public function __construct(?string $id)
    {
        if ($id === null || $id === '') {
            $id = $this->generateUniqueId();
        }
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    public function isEqual(string $other): bool
    {
        return $this->id === $other;
    }


    private function generateUniqueId(): string
    {
        static $lastUnixMs = null;
        static $sequence = 0;

        // Get current time in milliseconds
        $unixMs = (int)(microtime(true) * 1000);

        // Compensate for same-millisecond collisions
        if ($unixMs === $lastUnixMs) {
            $sequence++;
            $sequence &= 0x3FFF; // Keep within the 14-bit sequence range
            if ($sequence === 0) {
                $unixMs++; // Bump time slightly to avoid collision
            }
        } else {
            $sequence = random_int(0, 0x3FFF); // Random start per ms
            $lastUnixMs = $unixMs;
        }

        // Extract time components
        $time_high = ($unixMs >> 16) & 0xFFFFFFFF;
        $time_low = $unixMs & 0xFFFF;

        // Set version and variant bits
        $time_hi_and_version = ($time_low & 0x0FFF) | (0x7 << 12);
        $clock_seq_hi_and_reserved = ($sequence & 0x3FFF) | 0x8000;

        // Generate 6 bytes (48 bits) of randomness
        $randBytes = random_bytes(6);
        $randHex = bin2hex($randBytes);

        // Format the UUID
        return sprintf(
            '%08x-%04x-%04x-%04x-%012s',
            $time_high,
            $time_low,
            $time_hi_and_version,
            $clock_seq_hi_and_reserved,
            $randHex
        );
    }
}
