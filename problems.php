<?php
require_once "init_without_validate.php";
require_once 'assess2/AssessStandalone.php';
global $allowedmacros;
$allowedmacros[] = "showplot_with_functions";

$GLOBALS['hide-sronly'] = true;

$_SESSION['graphdisp'] = 1;
$_SESSION['userprefs']['drawentry'] = 1;

/**
 * Parses graph settings and function definitions from a set of inputs.
 *
 * NOTE: This function assumes the existence of helper functions:
 * - listtoarray($string): Converts a comma-separated string to an array.
 * - makepretty($string): Cleans up an equation string.
 * - evalbasic($string): Evaluates a basic mathematical string into a number.
 *
 * @param array|string $funcs The function(s) to parse.
 * @return array The structured $graph_settings array.
 */
function showplot_with_functions($funcs) { //optional arguments:  $xmin,$xmax,$ymin,$ymax,labels,grid,width,height
    // --- 1. CONFIGURATION SETUP ---

    if (!is_array($funcs)) {
        settype($funcs,"array");
    }
    $settings = array(-5,5,-5,5,1,1,200,200);
    for ($i = 1; $i < func_num_args(); $i++) {
        $v = func_get_arg($i);
        if ($v === null) { $v = 0; }
        if (!is_scalar($v)) {
            // In a real application, this should throw an exception or return an error
            echo 'Invalid input '.($i+1).' to showplot';
        } else {
            $settings[$i-1] = $v;
        }
    }

    // Parse special syntax for axes
    $fqonlyx = false; $fqonlyy = false;
    if (strpos($settings[0],'0:')!==false) {
        $fqonlyx = true;
        $settings[0] = substr($settings[0],2);
    }
    if (strpos($settings[2],'0:')!==false) {
        $fqonlyy = true;
        $settings[2] = substr($settings[2],2);
    }

    // Check for auto-scaling flags but defer calculation
    $yminauto = false;
    if (substr($settings[2],0,4)=='auto') {
        $yminauto = true;
        $settings[2] = (strpos($settings[2],':')!==false) ? explode(':',$settings[2])[1] : -5;
    }
    $ymaxauto = false;
    if (substr($settings[3],0,4)=='auto') {
        $ymaxauto = true;
        $settings[3] = (strpos($settings[3],':')!==false) ? explode(':',$settings[3])[1] : 5;
    }

    // Assign final config values
    $winxmin = is_numeric($settings[0])?$settings[0]:-5;
    $winxmax = is_numeric($settings[1])?$settings[1]:5;
    $ymin = is_numeric($settings[2])?$settings[2]:-5;
    $ymax = is_numeric($settings[3])?$settings[3]:5;
    $plotwidth = is_numeric($settings[6])?$settings[6]:200;
    $plotheight = is_numeric($settings[7])?$settings[7]:200;

    $noyaxis = false; $noxaxis = false;
    if (is_numeric($ymin) && is_numeric($ymax) && $ymin==0 && $ymax==0) {
        $noyaxis = true;
    }

    // Parse labels and grid settings
    if (strpos($settings[4],':')) {
        $lbl = explode(':',$settings[4]);
        $lbl[0] = evalbasic($lbl[0], true, true);
        $lbl[1] = evalbasic($lbl[1], true, true);
        if ($lbl[0] == 0) $noxaxis = true;
        if ($lbl[1] == 0) $noyaxis = true;
    } else {
        $settings[4] = evalbasic($settings[4], true, true);
        $lbl = [];
    }
    
    if (strpos($settings[5],':')) {
        $grid = explode(':', str_replace(array('(',')'),'',$settings[5]));
        foreach ($grid as $i=>$v) { $grid[$i] = evalbasic($v, true, true); }
    } else {
        $settings[5] = evalbasic($settings[5], true, true);
        $grid = [];
    }

    // --- 2. INITIALIZE GRAPH SETTINGS ARRAY ---
    
    $graph_settings = [
        'view_window' => [
            'xmin' => $winxmin,
            'xmax' => $winxmax,
            'ymin' => $ymin,
            'ymax' => $ymax
        ],
        'is_ymin_auto' => $yminauto,
        'is_ymax_auto' => $ymaxauto,
        'canvas_size' => [
            'width' => $plotwidth,
            'height' => $plotheight
        ],
        'axes_config' => [
            'x_tick_interval' => isset($lbl[0]) ? $lbl[0] : (is_numeric($settings[4]) ? $settings[4] : 1),
            'y_tick_interval' => isset($lbl[1]) ? $lbl[1] : (is_numeric($settings[4]) ? $settings[4] : 1),
            'show_x_axis' => !$noxaxis,
            'show_y_axis' => !$noyaxis,
            'first_quadrant_x_only' => $fqonlyx,
            'first_quadrant_y_only' => $fqonlyy,
            'show_tick_labels' => !(isset($lbl[2]) && $lbl[2] == 'off')
        ],
        'grid_config' => [
            'x_interval' => isset($grid[0]) && is_numeric($grid[0]) ? $grid[0] : (is_numeric($settings[5]) ? $settings[5] : 0),
            'y_interval' => isset($grid[1]) && is_numeric($grid[1]) ? $grid[1] : (is_numeric($settings[5]) ? $settings[5] : 0)
        ],
        'functions' => [] // This will be populated below
    ];


    // --- 3. PARSE ALL INPUT FUNCTIONS ---

    foreach ($funcs as $function) {
        if ($function=='') { continue;}
        $function = str_replace('\\,','&x44;', $function);
        $function = listtoarray($function);
        if (!isset($function[0]) || $function[0]==='') { continue; }
        if ($function[0][0] == 'y') {
            $function[0] = preg_replace('/^\s*y\s*=?/', '', $function[0]);
            if ($function[0]==='') { continue; }
        }

        $current_function = [];

        if ($function[0]=='dot') {
            $current_function = [
                'type' => 'dot',
                'x' => $function[1],
                'y' => $function[2],
                'style' => isset($function[3]) && $function[3] == 'open' ? 'open' : 'closed',
                'color' => isset($function[4]) && $function[4] != '' ? $function[4] : 'black'
            ];
            if (isset($function[5]) && $function[5] != '') {
                $current_function['label'] = [
                    'text' => str_replace('&x44;', ',', $function[5]),
                    'location' => isset($function[6]) ? $function[6] : 'above'
                ];
            }

        } else if ($function[0]=='text') {
            $current_function = [
                'type' => 'text',
                'x' => $function[1],
                'y' => $function[2],
                'content' => str_replace('&x44;', ',', $function[3]),
                'color' => isset($function[4]) && $function[4] != '' ? $function[4] : 'black',
                'location' => isset($function[5]) ? $function[5] : 'centered',
                'angle' => isset($function[6]) ? intval($function[6]) : 0
            ];

        } else {
            $func_details = [];

            if ($function[0][0]=='[') {
                $func_details = [
                    'type' => 'parametric',
                    'x_equation' => makepretty(str_replace("[","",$function[0])),
                    'y_equation' => makepretty(str_replace("]","",$function[1]))
                ];
                array_shift($function);
            } else if ($function[0][0]=='<' || $function[0][0]=='>') {
                $ineqtype = ($function[0][1]=='=') ? substr($function[0],0,2) : $function[0][0];
                $func_details = [
                    'type' => 'inequality',
                    'inequality_type' => $ineqtype,
                    'equation' => makepretty(substr($function[0],strlen($ineqtype)))
                ];
            } else if (strlen($function[0])>1 && $function[0][0]=='x' && in_array($function[0][1], ['<','>','='])) {
                if ($function[0][1]=='=') {
                     $func_details = ['type' => 'vertical_line', 'x_value' => substr($function[0],2)];
                } else {
                    $ineqtype = ($function[0][2]=='=') ? substr($function[0],1,2) : $function[0][1];
                    $func_details = [
                        'type' => 'vertical_inequality',
                        'inequality_type' => $ineqtype,
                        'x_value' => substr($function[0],strlen($ineqtype)+1)
                    ];
                }
            } else {
                $func_details = ['type' => 'standard', 'equation' => makepretty($function[0])];
            }

            $attributes = [
                'color' => isset($function[1]) && $function[1] != '' ? $function[1] : 'black',
                'width' => isset($function[6]) && $function[6] != '' ? $function[6] : '1',
                'style' => (isset($function[7]) && $function[7] == 'dash') || ($func_details['type'] == 'inequality' && strlen($func_details['inequality_type'])==1) ? 'dashed' : 'solid',
                'endpoints' => [
                    'start' => isset($function[4]) && in_array($function[4], ['open','closed','arrow']) ? $function[4] : 'none',
                    'end' => isset($function[5]) && in_array($function[5], ['open','closed','arrow']) ? $function[5] : 'none'
                ]
            ];

            $domain = ['min' => $winxmin, 'max' => $winxmax];
            if (isset($function[2]) && $function[2] != '') { $domain['min'] = evalbasic($function[2], true, true); }
            $avoid = [];
            if (isset($function[3]) && $function[3] != '') {
                $xmaxarr = explode('!',$function[3]);
                $domain['max'] = ($xmaxarr[0] != '') ? evalbasic($xmaxarr[0], true, true) : $winxmax;
                if (count($xmaxarr)>1) { $avoid = array_slice($xmaxarr,1); }
            }
            $attributes['domain'] = $domain;
            $attributes['avoid_points'] = $avoid;
            
            $current_function = array_merge($func_details, $attributes);
        }
        
        if (!empty($current_function)) {
            $graph_settings['functions'][] = $current_function;
        }
    }

    // --- 4. RETURN FINAL SETTINGS OBJECT ---
    // Encode the entire settings and functions object into a JSON string.
    // This passes all configuration and function definitions to the renderer.
    $json_settings = json_encode($graph_settings);
    // Create the script command for the client-side SVG renderer.
    $commands = "drawPicture({$json_settings})";
    return "<embed type='image/svg+xml' align='middle' width='$plotwidth' height='$plotheight' script='$commands' />\n";
}


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
    $stype = isset($data["stype"]) ? $data["stype"] : "template";
    $showplot = isset($data["showplot"]) ? $data["showplot"] : "";

    if ($showplot == "fn") {
        $control = str_replace("showplot", "showplot_with_functions", $control);
    }

    $input = '{"email":"u@abc.co","id":"36","uniqueid":"1699501100492899","adddate":"1699501100","lastmoddate":"1699501137","ownerid":"1","author":"Nguyen,Vu","userights":"0","license":"1","description":"Algebra problem","qtype":"multipart","control":"","qcontrol":"","qtext":"","answer":"","solution":"","extref":"","hasimg":"0","deleted":"0","avgtime":"0","ancestors":"","ancestorauthors":"","otherattribution":"","importuid":"","replaceby":"0","broken":"0","solutionopts":"6","sourceinstall":"","meantimen":"1","meantime":"19","vartime":"0","meanscoren":"1","meanscore":"50","varscore":"0","isrand":"1"}';
    $qn = 27;

    $line = json_decode($input, true);

    $line["qtype"] = $qtype;
    $line["control"] = $control;
    $line["qtext"] = $qtext;
    if ($stype == "template") {
        $line["solution"] = $solution;
    } else {
        $line["solution"] = "";
    }

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

    if ($stype == "template") {
        $originalSolution = $question->getSolutionContent();
    } else {
        $vars = $question->getVarsOutput();
        $sanitizedVars = [];
        foreach ($vars as $key => $value) {
            $sanitizedKey = ltrim($key, '$');
            $sanitizedVars[$sanitizedKey] = $value;
        }
        extract($sanitizedVars);
        ob_start();
        eval($solution);
        $originalSolution = ob_get_clean();
    }

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
