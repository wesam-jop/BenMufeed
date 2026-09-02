<?php

namespace App\Enums;

enum WarehouseType: string
{
    case Main = 'main';
    case Kitchen = 'kitchen';
    case ColdStorage = 'cold_storage';
    case Retail = 'retail';
    case Production = 'production';
}
