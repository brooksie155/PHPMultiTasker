<?php

class CoroutineReturnValue 
{
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }
}

function retval($value) {
    return new CoroutineReturnValue($value);
}


