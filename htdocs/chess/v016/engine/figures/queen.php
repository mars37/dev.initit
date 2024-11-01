<?php

class Queen extends Figure {
    const SHIFTS = array(
        array(-1, -1), array(-1, 0), array(-1, 1),
        array(0, -1), array(0, 1),
        array(1, -1), array(1, 0), array(1, 1)
    );

    public function getCandidateMoves() {
        return $this->getLongRangeCandidateMoves(self::SHIFTS);
    }
}
