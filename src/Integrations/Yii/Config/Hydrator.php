<?php

namespace Hydrastat\DtoHydrator\Integrations\Yii\Config;

use yii\base\BaseObject;

/**
 * Hydrator settings for the Yii 2 integration.
 *
 * Register the values in `config/main.php` under `params`:
 *
 * ```php
 * 'params' => [
 *     'hydrator' => [
 *         'allowPrivateAccess' => true,
 *         'defaultOptions' => [
 *             'groups' => ['default'],
 *             'exclude_null' => false,
 *             'skip_required' => false,
 *         ],
 *         'customValidators' => [
 *             // 'unique' => fn ($value, $dto, $params) => ...,
 *         ],
 *         'customCasters' => [
 *             // 'uuid' => fn ($value) => (string) $value,
 *         ],
 *     ],
 * ],
 * ```
 */
class Hydrator extends BaseObject
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
