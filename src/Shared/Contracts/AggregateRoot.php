<?php

namespace Src\Shared\Contracts;

abstract class AggregateRoot
{
    /**
     * @var DomainEvent[]
     */
    private array $events = [];

    protected function record(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }
}
