<?php

namespace App\Tests\Entity;

use App\Entity\Event;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EventTest extends TestCase
{
    #[DataProvider('provideRemainingPlaces')]
    public function testGetRemainingPlaces(int $places, int $participantsCount, int $expected): void
    {
        $event = new Event();
        $event->setPlaces($places);

        for ($i = 0; $i < $participantsCount; $i++) {
            $event->addParticipant(new User());
        }

        $this->assertSame($expected, $event->getRemainingPlaces());
    }

    public static function provideRemainingPlaces(): array
    {
        return [
            'aucun inscrit' => [10, 0, 10],
            'quelques inscrits' => [10, 3, 7],
            'complet' => [5, 5, 0],
        ];
    }

    #[DataProvider('provideCategories')]
    public function testGetCategoryLabel(string $category, string $expectedLabel): void
    {
        $event = new Event();
        $event->setCategory($category);

        $this->assertSame($expectedLabel, $event->getCategoryLabel());
    }

    public static function provideCategories(): array
    {
        return [
            'Concert' => ['concert', 'Concert'],
            'Conférence' => ['conference', 'Conférence'],
            'Atelier' => ['atelier', 'Atelier'],
            'Sport' => ['sport', 'Sport'],
        ];
    }
}