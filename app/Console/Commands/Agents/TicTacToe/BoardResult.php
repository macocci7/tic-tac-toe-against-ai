<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Enums\TicTacToe\BoardResultEnum;
use App\Enums\TicTacToe\PlayerTypeEnum;
use App\Console\Commands\Agents\TicTacToe\Player;

/**
 * ボード結果クラス
 */
class BoardResult
{
    public function __construct(
        public BoardResultEnum $result, // ボードの結果
        public ?Player $winner = null,  // 勝者
    ) {
    }

    public function get(): BoardResultEnum {
        return $this->result;
    }

    public function isInGame(): bool {
        return $this->result === BoardResultEnum::IN_GAME;
    }

    public function isWin(): bool {
        return $this->result === BoardResultEnum::WIN;
    }

    public function isDraw(): bool {
        return $this->result === BoardResultEnum::DRAW;
    }

    public function getWinner(): ?Player {
        return $this->winner;
    }
}
