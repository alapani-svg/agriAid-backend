<?php

namespace Src\Weather\Domain\Entities;

use Src\Shared\Contracts\AggregateRoot;
use Src\Weather\Domain\Events\WeatherDataRecorded;
use Src\Weather\Domain\ValueObjects\Temperature;
use Src\Weather\Domain\ValueObjects\Humidity;
use Src\Weather\Domain\ValueObjects\WindSpeed;

class WeatherData extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private readonly string $location,
        private Temperature $temperature,
        private Humidity $humidity,
        private WindSpeed $windSpeed,
        private readonly \DateTimeImmutable $recordedAt,
    ) {}

    public static function create(
        string $id,
        string $location,
        Temperature $temperature,
        Humidity $humidity,
        WindSpeed $windSpeed,
    ): self {
        $weatherData = new self(
            $id,
            $location,
            $temperature,
            $humidity,
            $windSpeed,
            new \DateTimeImmutable(),
        );

        $weatherData->recordEvent(new WeatherDataRecorded($weatherData));

        return $weatherData;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getTemperature(): Temperature
    {
        return $this->temperature;
    }

    public function getHumidity(): Humidity
    {
        return $this->humidity;
    }

    public function getWindSpeed(): WindSpeed
    {
        return $this->windSpeed;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
