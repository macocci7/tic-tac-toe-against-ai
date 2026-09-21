<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Enums\TicTacToe\BoardResultEnum;
use App\Enums\TicTacToe\PlayerTypeEnum;

/**
 * ボードクラス
 */
class Board
{
    public Player $none;
    public string $cellSeparatorX = '｜';
    public string $cellSeparatorY = 'ー';
    public string $cellSeparatorCross = '＋';
    public string $cellSeparatorRow = '';
    protected array $board = [];
    protected array $histories = [];

    public function __construct(
        public int $n = 3,      // {{ $n }}目並べ
        public int $xMax = 3,   // 横方向のマス目の数
        public int $yMax = 3,   // 縦方向のマス目の数
    ) {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->none = new Player(type: PlayerTypeEnum::NONE, name: '', symbol: '　');
        $this->cellSeparatorRow = implode($this->cellSeparatorCross, array_fill(0, $this->xMax, $this->cellSeparatorY));
        $this->histories = [];
        for ($row = 1; $row <= $this->yMax; $row++) {
            $this->board[$row - 1] = [];
            for ($col = 1; $col <= $this->xMax; $col++) {
                $this->board[$row - 1][$col - 1] = new Cell($row, $col, $this->none);
            }
        }
    }

    /**
     * ボードの状況取得
     */
    public function getBoard(): string
    {
        $boardString = '';
        foreach ($this->board as $rowIndex => $row) {
            $boardString .= implode($this->cellSeparatorX, array_map(fn($c) => $c->getPlayer()->getSymbol(), $row)) . PHP_EOL
                . ($rowIndex !== ($this->yMax - 1) ? $this->cellSeparatorRow . PHP_EOL : '');
        }
        return $boardString;
    }

    /**
     * 選択可能なセル抽出
     * @return array<int, array<int, int>>  選択可能なセルの座標配列
     */
    public function getAvailableCells(): array
    {
        $availableCells = [];
        foreach ($this->board as $rowIndex => $row) {
            foreach ($row as $colIndex => $cell) {
                if ($cell->isEmpty()) {
                    $availableCells[] = $cell;
                }
            }
        }
        return $availableCells;
    }

    /**
     * セル選択が有効な範囲か判定
     */
    public function isValidCellRange(int $row, int $col): bool
    {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return isset($this->board[$rowIndex][$colIndex]);
    }

    /**
     * 指定セルを選択したプレイヤー取得
     */
    public function whoChoseCell(int $row, int $col): ?Player
    {
        $rowIndex = $row - 1;
        $colIndex = $col - 1;
        return $this->board[$rowIndex][$colIndex]?->getPlayer() ?? null;
    }

    public function setCell(Cell $cell, string $comment): void
    {
        $this->board[$cell->getRow() - 1][$cell->getCol() - 1] = $cell;
        $this->setHistory($cell, $comment);
    }

    /**
     * セル選択後の結果判定
     */
    public function checkResult(Player $currentPlayer): BoardResult
    {
        // 横方向の勝利条件をチェック
        foreach ($this->board as $rowIndex => $row) {
            if ($this->isFilledWithPlayer($row, $currentPlayer)) {
                return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
            }
        }
        // 縦方向の勝利条件をチェック
        for ($colIndex = 0; $colIndex < $this->xMax; $colIndex++) {
            $column = array_column($this->board, $colIndex);
            if ($this->isFilledWithPlayer($column, $currentPlayer)) {
                return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
            }
        }
        // 斜め方向の勝利条件をチェック（左上から右下）
        $diagonal1 = array_map(fn($i) => $this->board[$i][$i], range(0, $this->xMax - 1));
        if ($this->isFilledWithPlayer($diagonal1, $currentPlayer)) {
            return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
        }
        // 斜め方向の勝利条件をチェック（右上から左下）
        $diagonal2 = array_map(fn($i) => $this->board[$i][$this->xMax - 1 - $i], range(0, $this->xMax - 1));
        if ($this->isFilledWithPlayer($diagonal2, $currentPlayer)) {
            return new BoardResult(BoardResultEnum::WIN, $currentPlayer);
        }
        // ボードが埋まっているかをチェック
        $availableCells = $this->getAvailableCells();
        if (empty($availableCells)) {
            return new BoardResult(BoardResultEnum::DRAW);
        }
        return new BoardResult(BoardResultEnum::IN_GAME);
    }

    /**
     * セルが指定プレイヤーで埋まっているか判定
     * @param array<int, Cell> $cells
     */
    protected function isFilledWithPlayer(array $cells, Player $player): bool {
        return count(array_unique(array_map(fn($c) => $c->getPlayer()->getName(), $cells))) === 1
            && $cells[0]->getPlayer() === $player;
    }

    public function setHistory(Cell $cell, string $comment): void
    {
        $this->histories[] = [
            'cell' => $cell,
            'comment' => $comment,
            'board' => $this->getBoard(),
        ];
    }

    public function getHistories(): array
    {
        return $this->histories;
    }
}
