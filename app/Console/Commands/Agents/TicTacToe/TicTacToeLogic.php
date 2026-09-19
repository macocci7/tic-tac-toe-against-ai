<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Ai\Agents\TicTacToeAgent;
use App\Enums\TicTacToe\PlayerTypeEnum;
use Laravel\Ai\Enums\Lab;

use function Laravel\Prompts\{spin, text, select, error};

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
    public int $playCount = 0;
    public int $yourWins = 0;
    public int $draws = 0;

    public function __construct(
        protected ?Lab $provider,
        protected ?string $model,
    ) {
    }

    public function initialize(): void {
        $this->playCount++;
        $this->isGameOver = false;
        $this->initializeBoard();
    }

    public function initializeBoard(): void {
        $this->initialValue = new Player(type: PlayerTypeEnum::NONE, name: '', symbol: '　');
        $this->board = array_fill(0, $this->yMax, array_fill(0, $this->xMax, $this->initialValue));
        $this->cellSeparatorRow = implode($this->cellSeparatorCross, array_fill(0, $this->xMax, $this->cellSeparatorY));
    }

    public function setPlayers(): void {
        $name = text('あなたのお名前は何ですか？');
        if (empty($name)) {
            $name = '名無し＠通りすがり';
        }
        echo view('tic-tac-toe.messages.welcome', ['name' => $name])->render() . PHP_EOL . PHP_EOL;
        $playerSymbol = select(
            label: "あなたの記号を選んでください",
            options: config('tic-tac-toe.symbols.human'),
        );
        $aiSymbols = config('tic-tac-toe.symbols.ai');
        $aiSymbol = $aiSymbols[array_rand($aiSymbols)];
        $this->players = [
            new Player(type: PlayerTypeEnum::HUMAN, name: $name, symbol: $playerSymbol),
            new Player(type: PlayerTypeEnum::AI, name: 'AI', symbol: $aiSymbol),
        ];
    }

    public function decideWhoGoesFirst(): void {
        echo "先行・後攻を適当に決めます。" . PHP_EOL;
        shuffle($this->players);
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
        $availableCells = $this->getAvailableCells();
        $error = "";
        $maxAttempts = 3;
        $i = 0;
        while (true) {
            if ($i >= $maxAttempts) {
                error("これ以上の再試行は無駄なので終了します。残念です😭");
                exit;
            }
            $i++;
            $choice = $this->getAisChoice($error);
            if (empty($choice)) {
                $error = "AIがセルを選択できませんでした。選び直してください。";
                error($error);
                continue;
            }
            $cell = json_decode(json_decode($choice, true)["cell"] ?? "[]", true);
            if(empty($cell)) {
                $error = "AIがセルを選択できませんでした。選び直してください。";
                error($error);
                continue;
            }
            if (! $this->isValidCellRange(...$cell)) {
                $error = "AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]は範囲外です。選びなおしてください。";
                error($error);
                continue;
            }
            $who = $this->whoChoseCell(...$cell);
            if (is_null($who)) {
                error("AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]の情報を取得できませんでした。処理を中止します🚫");
                exit;
            }
            if ($who !== $this->initialValue) {
                $error = "AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]は既に" . $who->getName() . "が選択済なので選べません。選びなおしてください。";
                error($error);
                continue;
            }
            break;
        }

        echo "AIが選んだセル: " . $cell[0] . "行 " . $cell[1] . "列" . PHP_EOL;
        [$rowIndex, $colIndex] = [$cell[0] - 1, $cell[1] - 1];
        $this->board[$rowIndex][$colIndex] = $currentPlayer;
    }

    protected function getAisChoice(string $error = ""): string {
        $availableCells = $this->getAvailableCells();
        return spin(
            callback: fn () => (new TicTacToeAgent)
                ->setAvailableCells($availableCells)
                ->setInstructions(view('tic-tac-toe.instructions.choose', [
                    'n' => $this->n,
                    'ai' => array_values(array_filter($this->players, fn($p) => $p->getType() === PlayerTypeEnum::AI))[0],
                    'human' => array_values(array_filter($this->players, fn($p) => $p->getType() === PlayerTypeEnum::HUMAN))[0],
                ]))
                ->prompt(view('tic-tac-toe.prompts.choose', [
                        'board' => $this->getBoard(),
                        'players' => $this->players,
                        'availableCells' => $availableCells,
                        'error' => $error,
                    ]),
                    provider: $this->provider,
                    model: $this->model
                ),
            message: "考え中・・・",
        );
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

    protected function isValidCellRange(int $row, int $col): bool {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return isset($this->board[$rowIndex][$colIndex]);
    }

    protected function whoChoseCell(int $row, int $col): ?Player {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return $this->board[$rowIndex][$colIndex] ?? null;
    }

    public function checkResult(Player $currentPlayer): string {
        // 横方向の勝利条件をチェック
        foreach ($this->board as $rowIndex => $row) {
            if (count(array_unique(array_map(fn($c) => $c->getName(), $row))) === 1 && $row[0] === $currentPlayer) {
                $this->isGameOver = true;
                if ($currentPlayer->getType() === PlayerTypeEnum::HUMAN) {
                    $this->yourWins++;
                }
                return $currentPlayer->getName() . "が勝ちました✨🎉🎊";
            }
        }
        // 縦方向の勝利条件をチェック
        for ($colIndex = 0; $colIndex < $this->xMax; $colIndex++) {
            $column = array_column($this->board, $colIndex);
            if (count(array_unique(array_map(fn($c) => $c->getName(), $column))) === 1 && $column[0] === $currentPlayer) {
                if ($currentPlayer->getType() === PlayerTypeEnum::HUMAN) {
                    $this->yourWins++;
                }
                $this->isGameOver = true;
                return $currentPlayer->getName() . "が勝ちました✨🎉🎊";
            }
        }
        // 斜め方向の勝利条件をチェック（左上から右下）
        $diagonal1 = array_map(fn($i) => $this->board[$i][$i], range(0, $this->xMax - 1));
        if (count(array_unique(array_map(fn($c) => $c->getName(), $diagonal1))) === 1 && $diagonal1[0] === $currentPlayer) {
            if ($currentPlayer->getType() === PlayerTypeEnum::HUMAN) {
                $this->yourWins++;
            }
            $this->isGameOver = true;
            return $currentPlayer->getName() . "が勝ちました✨🎉🎊";
        }
        // 斜め方向の勝利条件をチェック（右上から左下）
        $diagonal2 = array_map(fn($i) => $this->board[$i][$this->xMax - 1 - $i], range(0, $this->xMax - 1));
        if (count(array_unique(array_map(fn($c) => $c->getName(), $diagonal2))) === 1 && $diagonal2[0] === $currentPlayer) {
            if ($currentPlayer->getType() === PlayerTypeEnum::HUMAN) {
                $this->yourWins++;
            }
            $this->isGameOver = true;
            return $currentPlayer->getName() . "が勝ちました✨🎉🎊";
        }
        // ボードが埋まっているかをチェック
        $availableCells = $this->getAvailableCells();
        if (empty($availableCells)) {
            $this->isGameOver = true;
            $this->draws++;
            return "引き分けです。";
        }
        return "";
    }

    public function willYouContinue(): bool {
        $answer = readline("続けますか？ (y/n): ");
        return strtolower($answer) === 'y';
    }

    public function displayResults(): void {
        echo "- プレイ回数: " . $this->playCount . PHP_EOL;
        echo "- あなたの勝利回数: " . $this->yourWins . PHP_EOL;
        echo "- 引き分け回数: " . $this->draws . PHP_EOL;
        echo "- AIの勝利回数: " . ($this->playCount - $this->yourWins - $this->draws) . PHP_EOL;
        echo "- あなたの勝率: " . ($this->playCount > 0 ? ($this->yourWins / $this->playCount) * 100 : 0) . "%" . PHP_EOL;
        echo "- AIの勝率: " . ($this->playCount > 0 ? (($this->playCount - $this->yourWins - $this->draws) / $this->playCount) * 100 : 0) . "%" . PHP_EOL;
    }

    public function play(): void
    {
        $this->initialize();
        $this->decideWhoGoesFirst();
        $playersCount = count($this->players);
        $turn = 0;
        while (! $this->isGameOver) {
            $i = $turn % $playersCount;
            $turn++;
            $currentPlayer = $this->players[$i];
            echo $this->getBoard() . PHP_EOL;
            $this->decideCell($currentPlayer, $turn);
            $result = $this->checkResult($currentPlayer);
            if ($result !== "") {
                echo $this->getBoard() . PHP_EOL;
                echo $result . PHP_EOL;
            }
        }
    }
}
