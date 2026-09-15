<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Ai\Agents\TicTacToeAgent;
use App\Enums\TicTacToe\PlayerTypeEnum;
use Laravel\Ai\Enums\Lab;

use function Laravel\Prompts\{spin, text, select};

class TicTacToeLogic
{
    public array $players = [];
    public array $board = [];
    public int $n = 3;      // {{ $n }}目並べ
    public int $xMax = 3;   // 横方向のマス目の数
    public int $yMax = 3;   // 縦方向のマス目の数
    public Player $initialValue;
    public string $cellSeparatorX = '｜';
    public string $cellSeparatorY = 'ー';
    public string $cellSeparatorCross = '＋';
    public string $cellSeparatorRow = '';
    public bool $isGameOver = false;

    public function __construct(
        protected ?Lab $provider,
        protected ?string $model,
    ) {
    }

    public function initializeBoard(): void {
        $this->initialValue = new Player(type: PlayerTypeEnum::NONE, name: '', symbol: '　');
        $this->board = array_fill(0, $this->yMax, array_fill(0, $this->xMax, $this->initialValue));
        $this->cellSeparatorRow = implode($this->cellSeparatorCross, array_fill(0, $this->xMax, $this->cellSeparatorY));
    }

    public function decideWhoGoesFirst(): void {
        $name = text('あなたのお名前は何ですか？');
        echo view('tic-tac-toe.messages.welcome', ['name' => $name])->render() . PHP_EOL . PHP_EOL;
        echo "先行・後攻を適当に決めます。" . PHP_EOL;
        $player = new Player(type: PlayerTypeEnum::HUMAN, name: $name, symbol: '😊');
        $ai = new Player(type: PlayerTypeEnum::AI, name: 'AI', symbol: '🤖');
        $this->players = rand(0, 1) === 0 ? [$player, $ai] : [$ai, $player];
    }

    public function getBoard(): string {
        $boardString = '';
        foreach ($this->board as $rowIndex => $row) {
            $boardString .= implode($this->cellSeparatorX, array_map(fn($c) => $c->getSymbol(), $row)) . PHP_EOL
                . ($rowIndex !== ($this->yMax - 1) ? $this->cellSeparatorRow . PHP_EOL : '');
        }
        return $boardString;
    }

    public function decideCell(Player $currentPlayer, int $turn): void {
        match ($currentPlayer->getType()) {
            PlayerTypeEnum::HUMAN => $this->playerDecidesCell($currentPlayer, $turn),
            PlayerTypeEnum::AI => $this->aiDecidesCell($currentPlayer, $turn),
        };
    }

    public function playerDecidesCell(Player $currentPlayer, int $turn): void {
        $availableCells = $this->getAvailableCells();
        $options = array_map(fn($c) => ($c[0] + 1) . '行 ' . ($c[1] + 1) . '列', $availableCells);
        $choice = select(
            label: "ターン {$turn}、" . $currentPlayer->getName() . "の番です。どのセルを選びますか？",
            options: $options,
            scroll: 3,
        );
        $chosenIndex = array_search($choice, $options);
        [$rowIndex, $colIndex] = $availableCells[$chosenIndex];
        $this->board[$rowIndex][$colIndex] = $currentPlayer;
    }

    public function aiDecidesCell(Player $currentPlayer, int $turn): void {
        echo "ターン {$turn}、" . $currentPlayer->getName() . "の番です。" . PHP_EOL;
        $choice = spin(
            callback: fn () => (new TicTacToeAgent)
                ->setInstructions(view('tic-tac-toe.instructions.choose', [
                    'n' => $this->n,
                    'ai' => array_values(array_filter($this->players, fn($p) => $p->getType() === PlayerTypeEnum::AI))[0],
                ]))
                ->prompt(view('tic-tac-toe.prompts.choose', [
                        'board' => $this->getBoard(),
                        'players' => $this->players,
                        'availableCells' => $this->getAvailableCells(),
                    ]),
                    provider: $this->provider,
                    model: $this->model
                ),
            message: "考え中・・・",
        );
        echo "AIが選んだセル: 行 " . $choice['row'] . " 列 " . $choice['col'] . PHP_EOL;
        [$rowIndex, $colIndex] = [$choice['row'] - 1, $choice['col'] - 1];
        $this->board[$rowIndex][$colIndex] = $currentPlayer;
    }

    public function getAvailableCells(): array {
        $availableCells = [];
        foreach ($this->board as $rowIndex => $row) {
            foreach ($row as $colIndex => $cell) {
                if ($cell === $this->initialValue) {
                    $availableCells[] = [$rowIndex, $colIndex];
                }
            }
        }
        return $availableCells;
    }

    public function checkResult(Player $currentPlayer): string {
        // 横方向の勝利条件をチェック
        foreach ($this->board as $rowIndex => $row) {
            if (count(array_unique(array_map(fn($c) => $c->getName(), $row))) === 1 && $row[0] === $currentPlayer) {
                $this->isGameOver = true;
                return $currentPlayer->getName() . "が勝ちました。";
            }
        }
        // 縦方向の勝利条件をチェック
        for ($colIndex = 0; $colIndex < $this->xMax; $colIndex++) {
            $column = array_column($this->board, $colIndex);
            if (count(array_unique(array_map(fn($c) => $c->getName(), $column))) === 1 && $column[0] === $currentPlayer) {
                $this->isGameOver = true;
                return $currentPlayer->getName() . "が勝ちました。";
            }
        }
        // 斜め方向の勝利条件をチェック（左上から右下）
        $diagonal1 = array_map(fn($i) => $this->board[$i][$i], range(0, $this->xMax - 1));
        if (count(array_unique(array_map(fn($c) => $c->getName(), $diagonal1))) === 1 && $diagonal1[0] === $currentPlayer) {
            $this->isGameOver = true;
            return $currentPlayer->getName() . "が勝ちました。";
        }
        // 斜め方向の勝利条件をチェック（右上から左下）
        $diagonal2 = array_map(fn($i) => $this->board[$i][$this->xMax - 1 - $i], range(0, $this->xMax - 1));
        if (count(array_unique(array_map(fn($c) => $c->getName(), $diagonal2))) === 1 && $diagonal2[0] === $currentPlayer) {
            $this->isGameOver = true;
            return $currentPlayer->getName() . "が勝ちました。";
        }
        // ボードが埋まっているかをチェック
        $availableCells = $this->getAvailableCells();
        if (empty($availableCells)) {
            $this->isGameOver = true;
            return "引き分けです。";
        }
        return "";
    }
}
