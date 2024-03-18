<?php

class Knight extends Figure {
    const SHIFTS = array(array(-2, -1), array(-2, 1), array(-1, -2), array(-1, 2), array(1, -2), array(1, 2), array(2, -1), array(2, 1));

    public function getCandidateMoves() {
        return $this->getShortRangeCandidateMoves(self::SHIFTS);
    }
}
