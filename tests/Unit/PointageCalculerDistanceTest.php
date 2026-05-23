<?php

namespace Tests\Unit;

use App\Models\Pointage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PointageCalculerDistanceTest extends TestCase
{
    #[Test]
    public function same_coordinates_return_zero_distance(): void
    {
        $distance = Pointage::calculerDistance(5.3364, -4.0267, 5.3364, -4.0267);

        $this->assertLessThan(1, $distance);
    }

    #[Test]
    public function paris_to_lyon_is_roughly_four_hundred_kilometers(): void
    {
        $distance = Pointage::calculerDistance(48.8566, 2.3522, 45.7640, 4.8357);

        $this->assertGreaterThan(350_000, $distance);
        $this->assertLessThan(500_000, $distance);
    }
}
