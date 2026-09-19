<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Console\Commands\Agents\TicTacToe\TicTacToeLogic;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Enums\Lab;

use function Laravel\Prompts\{intro, confirm};

#[Signature('play:tic-tac-toe {provider? : AIプロバイダー (例 openai, ollama)} {model? : AIモデル名 (例 gpt-5.6-luna, gemma3:1b)}')]
#[Description('AI対戦３並べをプレイします。')]
class TicTacToeCommand extends Command
{
    public function handle()
    {
        $logic = new TicTacToeLogic($this->getProvider(), $this->getModel());
        intro('AI対戦' . $logic->n . '目並べ');
        $logic->setPlayers();
        while (true) {
            $logic->play();
            if (! confirm("続けますか？")) {
                break;
            }
        }
        echo "ゲームを終了します。お疲れ様でした。" . PHP_EOL;
        $logic->displayResults();
    }

    /**
     * コマンドライン引数からAIプロバイダーを取得
     */
    protected function getProvider(): ?Lab
    {
        $provider = $this->argument('provider');
        if (empty($provider)) {
            return null;
        }
        $enum = Lab::tryFrom(strtolower($provider));
        if ($enum === null) {
            throw new \InvalidArgumentException("Unsupported provider: $provider");
        }
        return $enum;
    }

    /**
     * コマンドライン引数からAIモデル名を取得
     */
    protected function getModel(): ?string
    {
        return $this->argument('model');
    }
}
