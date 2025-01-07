<?php

class Queen extends Figure {
    const SHIFTS = array(-17, -16, -15, -1, 1, 15, 16, 17);

    public function getCandidateMoves() {
        return $this->getLongRangeCandidateMoves(self::SHIFTS);
    }
}
