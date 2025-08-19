<?php
require_once "init_without_validate.php";
require_once 'assess2/AssessStandalone.php';
require_once "new_return_class.php";

function getPost($key, $default = "") {
    return $_POST[$key] ?? $default;
}

$a2 = new AssessStandalone($DBH);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seed = getPost('seed');
    $qtype = getPost('qtype');
    $control = getPost('control');
    $answer = getPost("answer");

    $input = '{"email":"u@abc.co","id":"36","uniqueid":"1699501100492899","adddate":"1699501100","lastmoddate":"1699501137","ownerid":"1","author":"Nguyen,Vu","userights":"0","license":"1","description":"Algebra problem","qtype":"multipart","control":"","qcontrol":"","qtext":"","answer":"","solution":"","extref":"","hasimg":"0","deleted":"0","avgtime":"0","ancestors":"","ancestorauthors":"","otherattribution":"","importuid":"","replaceby":"0","broken":"0","solutionopts":"6","sourceinstall":"","meantimen":"1","meantime":"19","vartime":"0","meanscoren":"1","meanscore":"50","varscore":"0","isrand":"1"}';
    $qn = 27;

    $line = json_decode($input, true);

    $line["qtype"] = $qtype;
    $line["control"] = $control;

    $a2->setQuestionData($qn, $line);

    $state = array(
        'seeds' => array($qn => $seed),
        'qsid' => array($qn => $qn),
        'stuanswers' => array(),
        'stuanswersval' => array(),
        'scorenonzero' => array(($qn+1) => -1),
        'scoreiscorrect' => array(($qn+1) => -1),
        'partattemptn' => array($qn => array()),
        'rawscores' => array($qn => array())
    );

    $a2->setState($state);

    $toscoreqn = getPost("toscoreqn");
    if ($toscoreqn != "") {
        $toscoreqn = json_decode($toscoreqn, true);
        $parts_to_score = array();
        if (isset($toscoreqn[$qn])) {
            foreach ($toscoreqn[$qn] as $pn) {
                $parts_to_score[$pn] = true;
            };
        }
    }

    $result = $a2->scoreQuestion($qn, $parts_to_score);
    $student_func = $a2->get_student_func();
    $fin_function = array();
    
    foreach ($student_func as $i => $value) {
        error_log("AAAAAbbbAAA");
        $temp = new DrawResult($value[0], $value[1]);
        $fin_function[] = $temp;
    }

    header('Content-Type: application/json');
    echo json_encode($fin_function);

} else {
    header('HTTP/1.0 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
}