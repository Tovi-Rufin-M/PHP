<?php
// opereration
//$num1 = 1;
//$num2 = 2;
//$sum = $num1 + $num2;
//echo $sum;

// OOP
//include 'module.php';   //import the file
//
//$fanc = new nums();     //call the class
//$fanc->add(1,2);        //serch the fanction in the class
//$fanc->get_details();   //call the display

// array
//$cars = array("Volvo", "BMW", "Toyota");
//$myArr = array("Volvo", 15, ["apples", "bananas"]);
//$cars = array("Volvo", "BMW", "Toyota");
$cars = ["Volvo", "BMW", "Toyota"];

// loop ++$i, $i++, $i+=1
for ($i = 0; $i < 3; $i+=1 ){
    echo '<br>' . $i;
    echo $cars[$i];
    array_push($cars, $i);
}
// foreach
foreach ($cars as $i){
    echo '<br>' . $i;
}
?>