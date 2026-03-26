<?php
/**
 * preview_question.php — No-auth question preview tool (local dev only)
 *
 * Initial load:  POST with JSON body {qtype, control, qtext, solution, seed}
 * Score submit:  POST with form fields (handled automatically by the form)
 */

// Minimal bootstrap — no DB connection required for preview.
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/Rand.php';
require_once __DIR__ . '/../i18n/i18n.php';

$CFG = [];
$imasroot = '';
$httpmode = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ? 'https://' : 'http://';
$GLOBALS['basesiteurl'] = $httpmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot;
$staticroot = $imasroot;

// Stub DB — satisfies PDO type hint; preview does not query the DB.
$DBH = new PDO('sqlite::memory:');
$GLOBALS['DBH'] = $DBH;

// Random wrapper used by QuestionGenerator / ScoreEngine.
$GLOBALS['RND'] = new Rand();

if (!defined('MYSQL_LEFT_WRDBND')) {
    define('MYSQL_LEFT_WRDBND', '[[:<:]]');
    define('MYSQL_RIGHT_WRDBND', '[[:>:]]');
}

session_start();

require_once '../assess2/AssessStandalone.php';

$_SESSION['graphdisp'] = 1;
$_SESSION['userprefs']['drawentry'] = 1;

$is_score_submit = isset($_POST['toscoreqn']) && isset($_POST['qtype']);

// --- Read params ---
if ($is_score_submit) {
    // Form re-submission for scoring — params come from hidden fields
    $data = $_POST;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initial JSON load
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $data = $_GET;
}

$qtype    = $data['qtype']    ?? 'multipart';
$control  = $data['control']  ?? '';
$qtext    = $data['qtext']    ?? '';
$solution = $data['solution'] ?? '';
$seed     = isset($data['seed']) ? (int)$data['seed'] : rand(0, 10000);

$qn = 27;

$line = [
    'email'           => 'preview@preview.local',
    'id'              => '1',
    'uniqueid'        => '1',
    'adddate'         => '0',
    'lastmoddate'     => '0',
    'ownerid'         => '1',
    'author'          => 'Preview',
    'userights'       => '0',
    'license'         => '1',
    'description'     => 'Preview question',
    'qtype'           => $qtype,
    'control'         => $control,
    'qcontrol'        => '',
    'qtext'           => $qtext,
    'answer'          => '',
    'solution'        => $solution,
    'extref'          => '',
    'hasimg'          => '0',
    'deleted'         => '0',
    'avgtime'         => '0',
    'ancestors'       => '',
    'ancestorauthors' => '',
    'otherattribution'=> '',
    'importuid'       => '',
    'replaceby'       => '0',
    'broken'          => '0',
    'solutionopts'    => '6',
    'sourceinstall'   => '',
    'meantimen'       => '0',
    'meantime'        => '0',
    'vartime'         => '0',
    'meanscoren'      => '0',
    'meanscore'       => '0',
    'varscore'        => '0',
    'isrand'          => '1',
];

$a2 = new AssessStandalone($DBH);
$a2->setQuestionData($qn, $line);

// --- Scoring ---
$score_msg = '';
if ($is_score_submit) {
    $state = json_decode($_POST['state'], true);
    $a2->setState($state);

    $toscoreqn = json_decode($_POST['toscoreqn'], true);
    $parts_to_score = [];
    if (isset($toscoreqn[$qn])) {
        foreach ($toscoreqn[$qn] as $pn) {
            $parts_to_score[$pn] = true;
        }
    }
    $res = $a2->scoreQuestion($qn, $parts_to_score);
    $score = implode('~', $res['scores']);
    $score_msg = '<p id="score-result" style="font-weight:bold;font-size:16px;">Score: '
        . htmlspecialchars($score) . ' / 1</p>';
    if (!empty($res['errors'])) {
        $score_msg .= '<ul style="color:red;">';
        foreach ($res['errors'] as $err) {
            $score_msg .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $score_msg .= '</ul>';
    }
} else {
    $state = [
        'seeds'          => [$qn => $seed],
        'qsid'           => [$qn => $qn],
        'stuanswers'     => [],
        'stuanswersval'  => [],
        'scorenonzero'   => [($qn + 1) => -1],
        'scoreiscorrect' => [($qn + 1) => -1],
        'partattemptn'   => [$qn => []],
        'rawscores'      => [$qn => []],
    ];
    $a2->setState($state);
}

$disp = $a2->displayQuestion($qn, [
    'showans'      => true,
    'showallparts' => false,
]);

$lastupdate = '20221027';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Question Preview — seed=<?php echo (int)$seed; ?></title>
<base href="<?php echo $GLOBALS['basesiteurl']; ?>">
<link rel="stylesheet" href="<?php echo $staticroot; ?>/imascore.css?ver=020123">
<link rel="stylesheet" href="<?php echo $staticroot; ?>/assess2/vue/css/index.css?v=<?php echo $lastupdate; ?>">
<link rel="stylesheet" href="<?php echo $staticroot; ?>/assess2/vue/css/chunk-common.css?v=<?php echo $lastupdate; ?>">
<link rel="stylesheet" href="<?php echo $staticroot; ?>/mathquill/mathquill-basic.css?v=021823">
<link rel="stylesheet" href="<?php echo $staticroot; ?>/mathquill/mqeditor.css?v=081122">
<script src="<?php echo $staticroot; ?>/javascript/jquery.min.js"></script>
<script>window.staticroot = '<?php echo $staticroot; ?>';</script>
<script type="text/x-mathjax-config">
  MathJax.Hub.Config({"messageStyle":"none", asciimath2jax:{ignoreClass:"skipmathrender"}});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=AM_CHTML-full" async></script>
<script>
  var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "MathJax";
  function rendermathnode(node, callback) {
    if (window.MathJax) {
      MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]);
      if (typeof callback == "function") { MathJax.Hub.Queue(callback); }
    } else {
      setTimeout(function() { rendermathnode(node, callback); }, 100);
    }
  }
</script>
<script src="<?php echo $staticroot; ?>/javascript/ASCIIsvg_min.js?ver=110123"></script>
<script src="<?php echo $staticroot; ?>/javascript/general.js?v=092823"></script>
<script src="<?php echo $staticroot; ?>/mathquill/mathquill.min.js?v=112822"></script>
<script src="<?php echo $staticroot; ?>/javascript/assess2_min.js?v=20231106"></script>
<script src="<?php echo $staticroot; ?>/javascript/assess2supp.js?v=041522"></script>
<style>
  body { font-family: sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; }
  .meta { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 16px; border-radius: 4px;
          margin-bottom: 20px; font-size: 13px; color: #555; }
  .meta strong { color: #333; }
  .questionpane { border: 1px solid #ccc; padding: 20px; border-radius: 4px; }
  #score-result { margin-top: 12px; padding: 8px 14px; background: #e8f5e9; border: 1px solid #a5d6a7;
                  border-radius: 4px; display: inline-block; }
</style>
</head>
<body>

<div class="meta">
  <strong>Preview</strong> &nbsp;|&nbsp;
  qtype: <strong><?php echo htmlspecialchars($qtype); ?></strong> &nbsp;|&nbsp;
  seed: <strong><?php echo (int)$seed; ?></strong>
</div>

<?php echo $score_msg; ?>

<?php if (!empty($disp['errors'])): ?>
<ul style="color:red;">
  <?php foreach ($disp['errors'] as $err): ?>
    <li><?php echo htmlspecialchars($err); ?></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<form method="post" action="/course/preview_question.php" class="questionwrap" enctype="multipart/form-data"
      onsubmit="return dopresubmit(<?php echo $qn; ?>, false)">
  <!-- Question identity fields passed through on re-submit -->
  <input type="hidden" name="qtype"    value="<?php echo htmlspecialchars($qtype); ?>">
  <input type="hidden" name="control"  value="<?php echo htmlspecialchars($control); ?>">
  <input type="hidden" name="qtext"    value="<?php echo htmlspecialchars($qtext); ?>">
  <input type="hidden" name="solution" value="<?php echo htmlspecialchars($solution); ?>">
  <input type="hidden" name="seed"     value="<?php echo (int)$seed; ?>">

  <div class="questionpane">
    <div class="question" id="questionwrap<?php echo $qn; ?>">
      <?php echo $disp['html']; ?>
    </div>
  </div>
  <div style="margin-top:12px;">
    <input type="submit" class="primary" value="Submit">
  </div>
  <input type="hidden" name="toscoreqn" value="">
  <input type="hidden" name="state" value="<?php echo htmlspecialchars(json_encode($a2->getState())); ?>">
</form>

<script>
$(function() {
  initq(<?php echo $qn; ?>, <?php echo json_encode($disp['jsparams']); ?>);
});
</script>
</body>
</html>
