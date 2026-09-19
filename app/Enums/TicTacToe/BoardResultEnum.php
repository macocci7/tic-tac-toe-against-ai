<?php

namespace App\Enums\TicTacToe;

enum BoardResultEnum
{
    case IN_GAME;
    case WIN;
    case LOSE;
    case DRAW;
}
