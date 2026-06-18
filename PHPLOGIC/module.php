<?php
class nums {
  // Properties
  public $num1;
  public $num2;
  public $result;

  // Method to set the properties
  function add($num1, $num2) {
    $this->num1 = $num1;
    $this->num2 = $num2;
    $this->result = $num1 + $num2;
  }

  // Method to display the properties
  function get_details() {
    echo "resule " . $this->num1 . " + " . $this->num2 ."=" . $this-> result;
  }
}
?>