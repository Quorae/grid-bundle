<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

enum DummyStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
