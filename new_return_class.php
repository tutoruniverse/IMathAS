<?php

class DrawResult
{
    public $id;
    public $valuef;

    public function DrawResult($id, $valuef) {
        $this->id = "qn" . strval($id);
        $this->valuef = $valuef;
    }
}