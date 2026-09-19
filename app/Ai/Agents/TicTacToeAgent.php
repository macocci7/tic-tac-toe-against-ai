<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

#[UseCheapestModel]
class TicTacToeAgent implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable, RemembersConversations;

    protected string $instructions;

    protected array $availableCells = [];

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return $this->instructions;
    }

    /**
     * システム指示を設定
     */
    public function setInstructions(string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the structured output schema for the agent.
     *
     * @return array
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            // 選択可能なセル座標を列挙
            'cell' => $schema->string()
                ->enum($this->getCellPositionsForSchemaEnum())
                ->description('Select the position of the cell on the Tic Tac Toe board, [row, col].')
                ->required(),
        ];
    }

    /**
     * 選択可能なセル座標を'[row, col]'形式の文字列値として配列で返す
     */
    public function getCellPositionsForSchemaEnum(): array
    {
        return array_map(
            fn($c) => "[" . ($c[0] + 1) . ", " . ($c[1] + 1) . "]",
            $this->availableCells
        );
    }

    /**
     * 選択可能なセル座標を設定
     */
    public function setAvailableCells(array $availableCells): self
    {
        $this->availableCells = $availableCells;
        return $this;
    }
}
