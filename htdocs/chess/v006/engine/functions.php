<?php

class Functions {
    static public function color(int $figure) {
        return $figure & 0b11;
    }

    static public function figure_type(int $figure) {
        return $figure & 0b11100;
    }
}