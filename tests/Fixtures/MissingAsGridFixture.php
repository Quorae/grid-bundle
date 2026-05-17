<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\Column;

final class MissingAsGridFixture
{
    #[Column(label: 'Label')]
    public string $label;
}
