<?php

namespace App\Enums\TicTacToe;

enum PlayerTypeEnum: string
{
    case NONE = 'none';
    case HUMAN = 'human';
    case AI = 'ai';
}
