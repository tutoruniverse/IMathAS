<?php
/**
 * Template self-test: renders a question template on each seed and, when asked, scores its correct answer.
 *
 * POST JSON {"qtype", "control", "qtext", "solution"?, "seeds": [int, ...] (1 to 50), "check_scoring": bool}
 * 200 -> {"ok": bool, "results": [{"seed", "ok", "render_errors", "warnings", "score_errors", "raw",
 *         "skipped_parts", "ms"}]}
 * 400 on a malformed body, 405 on any other method. See assess2/selftest/TemplateSelfTest.php for the rules.
 */
require_once "init_without_validate.php";
require_once 'assess2/selftest/TemplateSelfTest.php';

use IMathAS\assess2\selftest\TemplateSelfTest;

const SELFTEST_MAX_SEEDS = 50;

$GLOBALS['hide-sronly'] = true;
$_SESSION['graphdisp'] = 1;
$_SESSION['userprefs']['drawentry'] = 1;

function selftest_respond(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body, JSON_PARTIAL_OUTPUT_ON_ERROR);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    selftest_respond(405, ['status' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$seeds = $data['seeds'] ?? null;
$validSeeds = is_array($seeds) && count($seeds) >= 1 && count($seeds) <= SELFTEST_MAX_SEEDS
    && count(array_filter($seeds, 'is_int')) === count($seeds);
if (!is_array($data) || !is_string($data['qtype'] ?? null) || !is_string($data['control'] ?? null)
    || !is_string($data['qtext'] ?? null) || !$validSeeds) {
    selftest_respond(400, ['status' => false,
        'message' => 'Expected qtype, control, qtext (strings) and seeds (1-' . SELFTEST_MAX_SEEDS . ' integers).']);
    exit;
}

$template = [
    'qtype' => $data['qtype'],
    'control' => $data['control'],
    'qtext' => $data['qtext'],
    'solution' => is_string($data['solution'] ?? null) ? $data['solution'] : '',
];
$report = (new TemplateSelfTest($DBH))->run($template, $seeds, !empty($data['check_scoring']));
selftest_respond(200, $report);
