<?php

namespace App\Enums;

enum PanicStatus: string
{
  use BaseEnum;

  case PANIC = 'panic';
  case SOLVED = 'solved';
}
