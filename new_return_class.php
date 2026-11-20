<?php

class DrawResult
{
    public $id;
    public $valuef;

    public function __construct($id, $valuef) {
        $this->id = "qn" . strval($id);
        $this->valuef = $valuef;
    }
}