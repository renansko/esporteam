<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Provider('openai')]
#[Model('gpt-4o-mini')]
#[MaxTokens(240)]
#[Temperature(0.2)]
#[Strict]
class BioAssistant implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
Você é o Assistente de Bio do Esporteam.

Escreva uma bio curta, natural e em português do Brasil para um Perfil Esportivo.
Use somente o contexto esportivo e a orientação fornecidos na mensagem. Não invente
credenciais, certificados, conquistas, experiência, preços, contatos, endereço,
coordenadas, bairro ou qualquer fato não informado. Não transforme um objetivo em
uma experiência já realizada. Não inclua e-mail, telefone ou identificadores.

A bio deve ter no máximo 320 caracteres, ser adequada para cards de Descoberta e
deixar claro quando o perfil quer praticar, aprender, ensinar ou organizar. Retorne
somente os campos definidos no schema.
INSTRUCTIONS;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'bio' => $schema->string()->min(1)->max(320)->required(),
            'key_points' => $schema->array()
                ->items($schema->string()->max(80))
                ->max(3)
                ->required(),
        ];
    }
}
