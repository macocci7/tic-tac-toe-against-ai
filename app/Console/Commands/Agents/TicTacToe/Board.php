<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Enums\TicTacToe\BoardResultEnum;
use App\Enums\TicTacToe\PlayerTypeEnum;

/**
 * ボードクラス
 */
class Board
{
    public Player $initialValue;
    public string $cellSeparatorX = '｜';
    public string $cellSeparatorY = 'ー';
    public string $cellSeparatorCross = '＋';
    public string $cellSeparatorRow = '';
    protected array $board = [];

    public function __construct(
        public int $n = 3,      // {{ $n }}目並べ
        public int $xMax = 3,   // 横方向のマス目の数
        public int $yMax = 3,   // 縦方向のマス目の数
    ) {
        $this->initialize();
    }

    public function initialize(): void {
        $this->initialValue = new Player(type: PlayerTypeEnum::NONE, name: '', symbol: '　');
        $this->board = array_fill(0, $this->yMax, array_fill(0, $this->xMax, $this->initialValue));
        $this->cellSeparatorRow = implode($this->cellSeparatorCross, array_fill(0, $this->xMax, $this->cellSeparatorY));
    }

    /**
     * ボードの状況取得
     */
    public function getBoard(): string {
        $boardString = '';
        foreach ($this->board as $rowIndex => $row) {
            $boardString .= implode($this->cellSeparatorX, array_map(fn($c) => $c->getSymbol(), $row)) . PHP_EOL
                . ($rowIndex !== ($this->yMax - 1) ? $this->cellSeparatorRow . PHP_EOL : '');
        }
        return $boardString;
    }

    /**
     * 選択可能なセル抽出
     */
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

    /**
     * セル選択が有効な範囲か判定
     */
    public function isValidCellRange(int $row, int $col): bool {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return isset($this->board[$rowIndex][$colIndex]);
    }

    /**
     * 指定セルを選択したプレイヤー取得
     */
    public function whoChoseCell(int $row, int $col): ?Player {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return $this->board[$rowIndex][$colIndex] ?? null;
    }

    public function setCell(int $rowIndex, int $colIndex, Player $player): void {
        $this->board[$rowIndex][$colIndex] = $player;
    }

    /**
     * セル選択後の結果判定
     */
    public function checkResult(Player $currentPlayer): BoardResult {
        // 横方向の勝利条件をチェック
        foreach ($this->board as $rowIndex => $row) {
            if (count(array_unique(array_map(fn($c) => $c->getName(), $row))) === 1 && $row[0] === $currentPlayer) {
                return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
            }
        }
        // 縦方向の勝利条件をチェック
        for ($colIndex = 0; $colIndex < $this->xMax; $colIndex++) {
            $column = array_column($this->board, $colIndex);
            if (count(array_unique(array_map(fn($c) => $c->getName(), $column))) === 1 && $column[0] === $currentPlayer) {
                return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
            }
        }
        // 斜め方向の勝利条件をチェック（左上から右下）
        $diagonal1 = array_map(fn($i) => $this->board[$i][$i], range(0, $this->xMax - 1));
        if (count(array_unique(array_map(fn($c) => $c->getName(), $diagonal1))) === 1 && $diagonal1[0] === $currentPlayer) {
            return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
        }
        // 斜め方向の勝利条件をチェック（右上から左下）
        $diagonal2 = array_map(fn($i) => $this->board[$i][$this->xMax - 1 - $i], range(0, $this->xMax - 1));
        if (count(array_unique(array_map(fn($c) => $c->getName(), $diagonal2))) === 1 && $diagonal2[0] === $currentPlayer) {
            return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
        }
        // ボードが埋まっているかをチェック
        $availableCells = $this->getAvailableCells();
        if (empty($availableCells)) {
            return new BoardResult(BoardResultEnum::DRAW);
        }
        return new BoardResult(BoardResultEnum::IN_GAME);
    }
}
