<?php declare(strict_types = 1);

namespace Dravencms\User\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class IsAllowed
{
    public function __construct(
        public readonly string $resource,
        public readonly string $operation
    ) {
    }
}
