<?php

use App\Ai\Agents\BioAssistant;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

it('defines a constrained structured output for short bios', function () {
    $schema = (new BioAssistant)->schema(new JsonSchemaTypeFactory);
    $serialized = json_encode($schema);

    expect($serialized)->toContain('bio')
        ->and($serialized)->toContain('key_points')
        ->and((new BioAssistant)->instructions())
        ->toContain('Não invente')
        ->toContain('coordenadas');
});
