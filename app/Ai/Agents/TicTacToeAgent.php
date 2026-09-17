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
        //return [
        //    'row' => $schema->integer()->description('The row index of the cell.')->required(),
        //    'col' => $schema->integer()->description('The column index of the cell.')->required(),
        //];
        return [
            'cell' => $schema->string()
                ->enum($this->getCellPositionsForSchemaEnum())
                ->description('Select the position of the cell on the Tic Tac Toe board, [row, col].')
                ->required(),
        ];
    }

    public function getCellPositionsForSchemaEnum(): array
    {
        return array_map(
            fn($c) => "[" . ($c[0] + 1) . ", " . ($c[1] + 1) . "]",
            $this->availableCells
        );
    }

    public function setAvailableCells(array $availableCells): self
    {
        $this->availableCells = $availableCells;
        return $this;
    }
}
