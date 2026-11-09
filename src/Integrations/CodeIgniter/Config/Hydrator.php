<?php

namespace Hydrastat\DtoHydrator\Integrations\CodeIgniter\Config;

use CodeIgniter\Config\BaseConfig;

class Hydrator extends BaseConfig
{
    public bool $allowPrivateAccess = true;

    public array $defaultOptions = [
        'groups' => ['default'],
        'exclude_null' => false,
        'skip_required' => false,
    ];

    public array $customValidators = [];

    public array $customCasters = [];
}