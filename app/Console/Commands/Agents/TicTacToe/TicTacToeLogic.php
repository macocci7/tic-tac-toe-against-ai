<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Ai\Agents\TicTacToeAgent;
use App\Enums\TicTacToe\PlayerTypeEnum;
use Laravel\Ai\Enums\Lab;

use function Laravel\Prompts\{spin, text, select, error};

class TicTacToeLogic
{
    public array $players = [];
    public Board $board;
    public int $n = 3;      // {{ $n }}目並べ
    public int $xMax = 3;   // 横方向のマス目の数
    public int $yMax = 3;   // 縦方向のマス目の数
    public Player $initialValue;
    public bool $isGameOver = false;
    public int $playCount = 0;
    public int $yourWins = 0;
    public int $draws = 0;

    public function __construct(
        protected ?Lab $provider,
        protected ?string $model,
    ) {
    }

    /**
     * プレイヤー情報の設定
     */
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

    /**
     * ゲームの初期化
     */
    public function initializeGame(): void {
        $this->playCount++;
        $this->isGameOver = false;
        $this->board = new Board(n: $this->n, xMax: $this->xMax, yMax: $this->yMax);
    }

    /**
     * 先攻後攻決定
     */
    public function decideWhoGoesFirst(): void {
        echo "先行・後攻を適当に決めます。" . PHP_EOL;
        shuffle($this->players);
    }

    /**
     * セル選択分岐
     */
    public function decideCell(Player $currentPlayer, int $turn): void {
        match ($currentPlayer->getType()) {
            PlayerTypeEnum::HUMAN => $this->playerDecidesCell($currentPlayer, $turn),
            PlayerTypeEnum::AI => $this->aiDecidesCell($currentPlayer, $turn),
        };
    }

    /**
     * 人間プレイヤーのセル選択
     */
    public function playerDecidesCell(Player $currentPlayer, int $turn): void {
        $availableCells = $this->board->getAvailableCells();
        $options = array_map(fn($c) => ($c[0] + 1) . '行 ' . ($c[1] + 1) . '列', $availableCells);
        $choice = select(
            label: "ターン {$turn}、" . $currentPlayer->getName() . "の番です。どのセルを選びますか？",
            options: $options,
            scroll: 3,
        );
        $chosenIndex = array_search($choice, $options);
        [$rowIndex, $colIndex] = $availableCells[$chosenIndex];
        $this->board->setCell($rowIndex, $colIndex, $currentPlayer);
    }

    /**
     * AIのセル選択
     */
    public function aiDecidesCell(Player $currentPlayer, int $turn): void {
        echo "ターン {$turn}、" . $currentPlayer->getName() . "の番です。" . PHP_EOL;
        $availableCells = $this->board->getAvailableCells();
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
            if (! $this->board->isValidCellRange(...$cell)) {
                $error = "AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]は範囲外です。選びなおしてください。";
                error($error);
                continue;
            }
            $who = $this->board->whoChoseCell(...$cell);
            if (is_null($who)) {
                error("AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]の情報を取得できませんでした。処理を中止します🚫");
                exit;
            }
            if ($who !== $this->board->initialValue) {
                $error = "AIが選択したセル[" . $cell[0] . ", " . $cell[1] . "]は既に" . $who->getName() . "が選択済なので選べません。選びなおしてください。";
                error($error);
                continue;
            }
            break;
        }

        echo "AIが選んだセル: " . $cell[0] . "行 " . $cell[1] . "列" . PHP_EOL;
        [$rowIndex, $colIndex] = [$cell[0] - 1, $cell[1] - 1];
        $this->board->setCell($rowIndex, $colIndex, $currentPlayer);
    }

    /**
     * AIのセル選択取得
     */
    protected function getAisChoice(string $error = ""): string {
        $availableCells = $this->board->getAvailableCells();
        return spin(
            callback: fn () => (new TicTacToeAgent)
                ->setAvailableCells($availableCells)
                ->setInstructions(view('tic-tac-toe.instructions.choose', [
                    'n' => $this->n,
                    'ai' => array_values(array_filter($this->players, fn($p) => $p->getType() === PlayerTypeEnum::AI))[0],
                    'human' => array_values(array_filter($this->players, fn($p) => $p->getType() === PlayerTypeEnum::HUMAN))[0],
                ]))
                ->prompt(view('tic-tac-toe.prompts.choose', [
                        'board' => $this->board->getBoard(),
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

    /**
     * セル選択後の結果判定
     */
    public function checkResult(Player $currentPlayer): void {
        $result = $this->board->checkResult($currentPlayer);
        if ($result->isInGame()) {
            return;
        }
        $this->isGameOver = true;
        echo $this->board->getBoard() . PHP_EOL;
        if ($result->isWin()) {
            if ($currentPlayer->getType() === PlayerTypeEnum::HUMAN) {
                $this->yourWins++;
            }
            echo $currentPlayer->getName() . "が勝ちました✨🎉🎊" . PHP_EOL;
        }
        if ($result->isDraw()) {
            echo "引き分けです🤝" . PHP_EOL;
            $this->draws++;
        }
    }

    /**
     * 1プレイ分のシーケンス
     */
    public function play(): void
    {
        $this->initializeGame();
        $this->decideWhoGoesFirst();
        $playersCount = count($this->players);
        $turn = 0;
        while (! $this->isGameOver) {
            $i = $turn % $playersCount;
            $turn++;
            $currentPlayer = $this->players[$i];
            echo $this->board->getBoard() . PHP_EOL;
            $this->decideCell($currentPlayer, $turn);
            $this->checkResult($currentPlayer);
        }
    }

    /**
     * ゲームの結果を表示
     */
    public function displayResults(): void {
        echo "- プレイ回数: " . $this->playCount . PHP_EOL;
        echo "- あなたの勝利回数: " . $this->yourWins . PHP_EOL;
        echo "- 引き分け回数: " . $this->draws . PHP_EOL;
        echo "- AIの勝利回数: " . ($this->playCount - $this->yourWins - $this->draws) . PHP_EOL;
        echo "- あなたの勝率: " . ($this->playCount > 0 ? ($this->yourWins / $this->playCount) * 100 : 0) . "%" . PHP_EOL;
        echo "- AIの勝率: " . ($this->playCount > 0 ? (($this->playCount - $this->yourWins - $this->draws) / $this->playCount) * 100 : 0) . "%" . PHP_EOL;
    }
}
