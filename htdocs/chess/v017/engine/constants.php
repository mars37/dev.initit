<?php

define('COLOR_NONE', 0);
define('COLOR_WHITE', 0b01); // 1
define('COLOR_BLACK', 0b10); // 2
define('COLOR_OVER', 0b11); // 3 - флаг выхода за доску
define('BOARD_SIZE', 8);

define('FG_NONE', 0);
define('FG_KING',   1 << 2); // 4
define('FG_QUEEN',  2 << 2); // 8
define('FG_ROOK',   3 << 2); // 12
define('FG_BISHOP', 4 << 2); // 16
define('FG_KNIGHT', 5 << 2); // 20
define('FG_PAWN',   6 << 2); // 24
