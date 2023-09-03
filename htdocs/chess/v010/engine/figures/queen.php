<?php

class Queen extends Figure {
    public function getCandidateMoves() {
        $shifts = array(
            array(-1, -1), array(-1, 0), array(-1, 1),
            array(0, -1), array(0, 1),
            array(1, -1), array(1, 0), array(1, 1)
        );
        return $this->getLongRangeCandidateMoves($shifts);
    }
}
