<?php

namespace Src\AI\Domain\Entities;

use Src\Shared\Contracts\AggregateRoot;
use Src\AI\Domain\Events\PredictionGenerated;
use Src\AI\Domain\ValueObjects\PredictionType;
use Src\AI\Domain\ValueObjects\ConfidenceScore;

class Prediction extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private readonly string $farmId,
        private PredictionType $type,
        private array $inputData,
        private array $result,
        private ConfidenceScore $confidence,
        private readonly \DateTimeImmutable $generatedAt,
    ) {}

    public static function generate(
        string $id,
        string $farmId,
        PredictionType $type,
        array $inputData,
        array $result,
        ConfidenceScore $confidence,
    ): self {
        $prediction = new self(
            $id,
            $farmId,
            $type,
            $inputData,
            $result,
            $confidence,
            new \DateTimeImmutable(),
        );

        $prediction->recordEvent(new PredictionGenerated($prediction));

        return $prediction;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFarmId(): string
    {
        return $this->farmId;
    }

    public function getType(): PredictionType
    {
        return $this->type;
    }

    public function getInputData(): array
    {
        return $this->inputData;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function getConfidence(): ConfidenceScore
    {
        return $this->confidence;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }
}
