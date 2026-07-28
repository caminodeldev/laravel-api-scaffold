<?php

declare(strict_types=1);

namespace CaminoDelDev\LaravelApiScaffold\Tests\Unit;

use CaminoDelDev\LaravelApiScaffold\Generators\ControllerGenerator;
use CaminoDelDev\LaravelApiScaffold\Naming\NameResolver;
use CaminoDelDev\LaravelApiScaffold\Support\StubRenderer;
use CaminoDelDev\LaravelApiScaffold\Tests\TestCase;

class ControllerGeneratorTest extends TestCase
{
    public function test_it_uses_configurable_envelope_for_all_generated_actions(): void
    {
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new ControllerGenerator(new StubRenderer()))->generate($names, crud: true, withDelete: true);

        $this->assertStringContainsString('use CaminoDelDev\\LaravelApiScaffold\\Support\\ResponseEnvelope;', $contents);
        $this->assertSame(10, substr_count($contents, 'ResponseEnvelope::make('));

        $this->assertStringContainsString("config('api-scaffold.envelope.messages.store_success'", $contents);
        $this->assertStringContainsString("config('api-scaffold.envelope.messages.update_success'", $contents);
        $this->assertStringContainsString("config('api-scaffold.envelope.messages.destroy_success'", $contents);

        $this->assertStringNotContainsString("config('api-scaffold.envelope.keys.code'", $contents);
        $this->assertStringNotContainsString("'code' => 201", $contents);
        $this->assertStringNotContainsString("'message' => 'Record created successfully.'", $contents);
        $this->assertStringNotContainsString("'data' => new SolicitudResource", $contents);
    }

    public function test_it_uses_generic_records_variable_for_index_collections(): void
    {
        $names = (new NameResolver())->resolve('solicitudes', 'Solicitud');

        $contents = (new ControllerGenerator(new StubRenderer()))->generate($names);

        $this->assertStringContainsString('$records = $this->solicitudService->paginate($request->validated());', $contents);
        $this->assertStringContainsString('SolicitudResource::collection($records)', $contents);
        $this->assertStringNotContainsString('$solicituds', $contents);
    }
}
