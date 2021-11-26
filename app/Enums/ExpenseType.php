<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ExpenseType extends Enum
{
    const Food = 1;
    const Transportation = 2;
    const Games = 3;
    const Park = 4;
    const OnlinePayment = 5;
    const Clothes = 6;
    const Others = 7;
}
