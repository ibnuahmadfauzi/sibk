<?php

declare(strict_types=1);

namespace App\Integrations;

interface IntegrationDriver
{
    /** @var list<string> */
    public const array RESULT_CODES = IntegrationProbeResult::RESULT_CODES;

    public function id(): string;

    public function adapterVersion(): string;

    public function contractVersion(): string;

    public function isAvailable(): bool;

    public function probe(IntegrationRuntimeConfiguration $configuration): IntegrationProbeResult;
}
