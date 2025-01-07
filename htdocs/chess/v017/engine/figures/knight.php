<?php

class Knight extends Figure {
    const SHIFTS = array(-33, -31, -18, -14, 14, 18, 31, 33);

    public function getCandidateMoves() {
        return $this->getShortRangeCandidateMoves(self::SHIFTS);
    }
}
