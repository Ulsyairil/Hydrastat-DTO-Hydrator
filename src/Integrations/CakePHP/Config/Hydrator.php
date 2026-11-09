<?php

namespace Hydrastat\DtoHydrator\Integrations\CakePHP\Config;

/**
 * Hydrator settings for the CakePHP integration.
 *
 * Write them in `config/bootstrap.php`:
 *
 * ```php
 * use Cake\Core\Configure;
 *
 * Configure::write('Hydrator', [
 *     'allowPrivateAccess' => true,
 *     'defaultOptions' => [
 *         'groups' => ['default'],
 *         'exclude_null' => false,
 *         'skip_required' => false,
 *     ],
 *     'customValidators' => [
 *         // 'unique' => fn ($value, $dto, $params) => ...,
 *     ],
 *     'customCasters' => [
 *         // 'uuid' => fn ($value) => (string) $value,
 *     ],
 * ]);
 * ```
 */
class Hydrator
{
    public bool $allowPrivateAccess = true;

    public array $defaultOptions = [
        'groups' => ['default'],
        'exclude_null' => false,
        'skip_required' => false,
    ];

    public array $customValidators = [];

    public array $customCasters = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
