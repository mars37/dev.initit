<?php

class Bishop extends Figure {
    public function getCandidateMoves() {
        $shifts = array(array(-1, -1), array(-1, 1), array(1, -1), array(1, 1));
        return $this->getLongRangeCandidateMoves($shifts);
    }
}
