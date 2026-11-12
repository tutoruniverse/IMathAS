<?php

namespace IMathAS\assess2\questions;

use Throwable;

class ErrorHandler
{
    const ERROR_CODES = array(
        E_ERROR => 'error',
        E_WARNING => 'warning',
        E_NOTICE => 'notice',
    );

    /**
     * Warning handler for question evals and scoring.
     *
     * Sometimes errors are generating while eval'ing question writer code
     * due to missing variables or incorrectly coded questions.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return bool
     */
    /**
     * PHP 8 raised these PHP 7.4 notices to warnings (and reworded some); PHP 8.3 added array_sum() and
     * array_product() warnings for values 7.4 skipped silently. The question error handlers
     * show warnings and ignore notices, so on PHP 8 such a warning must be ignored to keep 7.4 output.
     * "A non-numeric value encountered" is listed because on PHP 8 it only comes from a leading-numeric
     * string ("3abc"), which was the 7.4 notice "A non well formed numeric value encountered".
     *
     * Example: wasNoticeBeforePhp8('Undefined variable $x') === true (on PHP 8 only).
     */
    public static function wasNoticeBeforePhp8(int $errno, string $errstr): bool
    {
        if (PHP_MAJOR_VERSION < 8 || $errno !== E_WARNING) {
            return false;
        }
        return (bool) preg_match('/^(Undefined variable \$|Undefined global variable \$|Undefined array key |'
            . 'Undefined property: |Trying to access array offset on |Attempt to read property "|'
            . 'Array to string conversion$|Uninitialized string offset |A non-numeric value encountered$|'
            . 'array_sum\(\): Addition is not supported|array_product\(\): Multiplication is not supported)/', $errstr);
    }

    public static function evalErrorHandler(int $errno, string $errstr, string $errfile,
                                            int $errline, array $errcontext = []): bool
    {
      if (E_WARNING == $errno || E_ERROR == $errno) {
        error_log(sprintf('Caught error by QuestionGenerator in %s in QSID %d:%s -- %s',
            $errfile, $GLOBALS['curqsetid'], $errline, $errstr));

        if (!empty($GLOBALS['CFG']['newrelic_log_question_errors']) && extension_loaded('newrelic')) {
            newrelic_add_custom_parameter('cur_qsid', $GLOBALS['curqsetid']);
            newrelic_notice_error($errno, $errstr, $errfile, $errline, $errcontext);
        }
      }
        // True = Don't execute the PHP internal error handler.
        // False = Populate $php_errormsg.
        // Reference: https://secure.php.net/manual/en/function.set-error-handler.php
        return true;
    }

    /**
     * Exception handler for question evals and scoring.
     *
     * Sometimes errors are generating while eval'ing question writer code
     * due to missing variables or incorrectly coded questions.
     *
     * @param Throwable $t
     */
    public static function evalExceptionHandler(Throwable $t): void
    {

        error_log(sprintf('Caught exception by QuestionGenerator from %s in QSID %d:%s -- %s',
            $t->getFile(), $GLOBALS['curqsetid'], $t->getLine(), $t->getMessage()));

        if (!empty($GLOBALS['CFG']['newrelic_log_question_errors']) && extension_loaded('newrelic')) {
            newrelic_add_custom_parameter('cur_qsid', $GLOBALS['curqsetid']);
            newrelic_notice_error($t);
        }
    }
}
