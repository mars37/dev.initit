<?php

class Knight extends Figure {
    public function getCandidateMoves() {
        $shifts = array(
            array(-2, -1), array(-2, 1), array(-1, -2), array(-1, 2), array(1, -2), array(1, 2), array(2, -1),array(2, 1)
        );
        return $this->getShortRangeCandidateMoves($shifts);
    }
}
