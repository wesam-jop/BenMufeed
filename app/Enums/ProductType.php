<?php

namespace App\Enums;

enum ProductType: string
{
    case Raw = 'raw';
    case SemiFinished = 'semi_finished';
    case Finished = 'finished';
    case Service = 'service';
    case Combo = 'combo';
}
