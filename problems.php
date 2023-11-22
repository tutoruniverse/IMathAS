<?php
require_once "init_without_validate.php";
require_once 'assess2/AssessStandalone.php';

$_SESSION['graphdisp'] = 1;

function processContent($matches) {
    $content = $matches[1];
    $processedContent = makepretty($content);
    return "`" . $processedContent . "`";
}

$a2 = new AssessStandalone($DBH);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');

    $data = json_decode($rawData, true);

    if (!is_array($data)) {
        throw new Exception('Received content contained invalid JSON!');
    }

    $qtype = $data['qtype'];
    $control = $data['control'];
    $qtext = $data['qtext'];
    $solution = $data['solution'];
    $seed = isset($data['seed']) ? $data["seed"] : rand(0,10000);

    $input = '{"email":"u@abc.co","id":"36","uniqueid":"1699501100492899","adddate":"1699501100","lastmoddate":"1699501137","ownerid":"1","author":"Nguyen,Vu","userights":"0","license":"1","description":"Algebra problem","qtype":"multipart","control":"","qcontrol":"","qtext":"","answer":"","solution":"","extref":"","hasimg":"0","deleted":"0","avgtime":"0","ancestors":"","ancestorauthors":"","otherattribution":"","importuid":"","replaceby":"0","broken":"0","solutionopts":"6","sourceinstall":"","meantimen":"1","meantime":"19","vartime":"0","meanscoren":"1","meanscore":"50","varscore":"0","isrand":"1"}';
    $qn = 27;

    $line = json_decode($input, true);

    $line["qtype"] = $qtype;
    $line["control"] = $control;
    $line["qtext"] = $qtext;
    $line["solution"] = $solution;

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

    $disp = $a2->displayQuestion($qn, [
        'showans' => false,
        'showallparts' => false,
        'printformat' => true
    ]);

    $question = $a2->getQuestion();
    
    $originalSolution = $question->getSolutionContent();
    $prettySolution = preg_replace_callback('/`([^`]*)`/', 'processContent', $originalSolution);

    $response = array(
        "question" => $question->getQuestionContent(),
        "originalSolution" => $originalSolution,
        "solution" => $prettySolution,
        "seed" => $seed,
        "jsparams" => $disp["jsparams"],
        "vars" => $question->getVarsOutput(),
        "answers" => $question->getCorrectAnswersForParts()
    );

    // Send response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('HTTP/1.0 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
}