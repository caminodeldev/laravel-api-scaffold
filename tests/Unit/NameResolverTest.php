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
        $this->assertSame('users', $names['routeName']);
        $this->assertSame('users', $names['routeParameterResource']);
        $this->assertSame('user', $names['routeParameter']);
    }

    public function test_it_uses_table_name_as_default_route_resource_for_non_english_names(): void
    {
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $this->assertSame('Solicitud', $names['model']);
        $this->assertSame('solicitudes', $names['route']);
        $this->assertSame('solicitudes', $names['routeName']);
        $this->assertSame('solicitudes', $names['routeParameterResource']);
        $this->assertSame('solicitud', $names['routeParameter']);
    }

    public function test_it_uses_explicit_route_resource_when_provided(): void
    {
        $names = (new NameResolver())->resolve('cha_solicitudes', 'Solicitud', 'tramites/solicitudes');

        $this->assertSame('tramites/solicitudes', $names['route']);
        $this->assertSame('tramites.solicitudes', $names['routeName']);
        $this->assertSame('solicitudes', $names['routeParameterResource']);
        $this->assertSame('solicitud', $names['routeParameter']);
    }
}
