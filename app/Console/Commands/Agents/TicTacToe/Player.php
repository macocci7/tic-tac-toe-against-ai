<?php

namespace App\Console\Commands\Agents\TicTacToe;

use App\Enums\TicTacToe\PlayerTypeEnum;

/**
 * プレイヤー定義クラス
 */
class Player
{
    public function __construct(
        protected PlayerTypeEnum $type = PlayerTypeEnum::HUMAN,
        protected string $name = '',
        protected string $symbol = '',
    ) {
    }

    public function getType(): PlayerTypeEnum
    {
        return $this->type;
    }

    public function setType(PlayerTypeEnum $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }
}
