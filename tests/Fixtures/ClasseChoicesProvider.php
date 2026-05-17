<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Contract\ChoicesProviderInterface;

final class ClasseChoicesProvider implements ChoicesProviderInterface
{
    public ?object $capturedFilter = null;

    /** @var array<string, mixed> */
    public array $capturedExtraContext = [];

    public function getChoices(object $filter, array $extraContext = []): array
    {
        $this->capturedFilter = $filter;
        $this->capturedExtraContext = $extraContext;

        return [
            '4' => 'Clients',
            '6' => 'Fournisseurs',
        ];
    }
}
