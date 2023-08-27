<?php

class BoardPosition {
    private $position;

    public function setPosition(array $position) {
        $this->position = $position;
    }

    // Метод возвращает булево значение - атаковано-ли поле с индексом $cell_index фигурами цвета $color
    public function isFieldUnderAttack($cell_index, $color) {
        return false;
    }
}
