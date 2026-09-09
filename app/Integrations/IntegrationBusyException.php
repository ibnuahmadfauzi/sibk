<?php

declare(strict_types=1);

namespace App\Integrations;

final class IntegrationBusyException extends IntegrationConfigurationException
{
    public function __construct()
    {
        parent::__construct('busy');
    }
}
