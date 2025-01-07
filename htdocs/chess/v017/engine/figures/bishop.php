<?php

class Bishop extends Figure {
    const SHIFTS = array(-17, -15, 15, 17);

    public function getCandidateMoves() {
        return $this->getLongRangeCandidateMoves(self::SHIFTS);
    }
}
