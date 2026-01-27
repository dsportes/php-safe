<?php

class AppExc extends Exception
{
  public $label;
  public $opName;
  public $stack;
  public $args;
  public $message;
  public $code;

  public function __construct (int $code, string $label, string $opName, array $args, array $trace) {
    parent::__construct($label, $code);
    $this->label = $label;
    $this->code = $code;
    $this->opName = isset($opName) ? $opName : '';
    $this->args = isset($args) ? $args : [];
    $this->stack = isset($trace) ? join('\n', $trace) : '';
    $this->message = 'AppExc: ' . $this->code . ':' . $this->label . '@' . $opName ;
  }

  public function serial () { 
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