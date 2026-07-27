<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class NameResolverTest extends TestCase
{
    public function test_it_resolves_names_from_table(): void
    {
        $names = (new NameResolver())->resolve('users');

        $this->assertSame('User', $names['model']);
        $this->assertSame('UserController', $names['controller']);
        $this->assertSame('users', $names['route']);
    }
}
