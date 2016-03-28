<?php

include $_SERVER['DOCUMENT_ROOT'].'/classes/Template.php';
include $_SERVER['DOCUMENT_ROOT'].'/classes/PseudoCrypt.php';

$test = new Template('/views/test.html');
$data = array(
    "var" => "I am a variable!",
    "bool" => true,
    "number" => 4,
    "one" => 1,
    "two" => 2,
    "three" => 3,
    "four" => 4,
    "days" => array(
        array(
            "name" => "Sunday"
        ),
        array(
            "name" => "Monday"
        ),
        array(
            "name" => "Tuesday"
        ),
        array(
            "name" => "Wednesday"
        ),
        array(
            "name" => "Thursday"
        ),
        array(
            "name" => "Friday"
        ),
        array(
            "name" => "Saturday"
        )
    ),
    "page" => "/views/test.1.html",
    "badvar" => "<span>Test</span>"
);
echo "<h1>Template test</h1>";
echo $test->process($data);
echo "<h1>Pseudocrypt test</h1>";

$x = 4;
//// BASE GENERATION
/*$pow = gmp_pow(36, $x);
$phi = (string)round((float)(gmp_strval($pow) / 1.618033988749894848));
$prime = gmp_nextprime($phi);
$mod = invmod($prime, $pow);
echo $pow . " === '" . $prime . "' => '" . $mod . "',<br/>";

function invmod($a, $n){
    if ($n < 0) $n = -$n;
    if ($a < 0) $a = $n - (-$a % $n);
    $t = 0; $nt = 1; $r = $n; $nr = $a % $n;
    while ($nr != 0) {
        $quot= intval($r/$nr);
        $tmp = $nt;  $nt = $t - $quot*$nt;  $t = $tmp;
        $tmp = $nr;  $nr = $r - $quot*$nr;  $r = $tmp;
    }
    if ($r > 1) return -1;
    if ($t < 0) $t += $n;
    return $t;
}*/

//// TEST
echo "<pre>";
$hashes = array();
foreach(range(1, 1000000) as $n) {
    echo $n . ' - ';
    $hash = PseudoCrypt::hash($n, $x);
    if (!in_array($hash, $hashes)) {
        $hashes[] = $hash;
    } else {
        die("COLLISION ON $hash AFTER $n HASHES!");
    }
    echo $hash."<br/>";
}
die;