<?php

class Bishop extends Figure {
    const SHIFTS = array(array(-1, -1), array(-1, 1), array(1, -1), array(1, 1));

    public function getCandidateMoves() {
        return $this->getLongRangeCandidateMoves(self::SHIFTS);
    }
}
