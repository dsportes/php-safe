<?php

class AppExc extends Exception
{
  public $code;
  public $label;
  public $opName;
  public $stack;
  public $args;

  function __construct (int $code, string $label, string $opName, array $args, string $trace) {
    parent::__construct();
    $this->label = $label;
    $this->code = $code;
    $this->opName = isset($opName) ? $opname : '';
    $this->args = isset($args) ? $args : [];
    $this->stack = isset($stack) ? join('\n', $trace) : '';
    $this->message = 'AppExc: ' . $this->code . ':' + $this->label . '@' . $opName ;
  }

  function serial () { 
    $x = array(
      'code' => $this->code, 
      'label' => $this->label,
      'opName' => $this->opName,
      'stack' => $this->stack, 
      '$args' => $this->args
    );
    return msgpack_pack($x);
  }
}

?>
