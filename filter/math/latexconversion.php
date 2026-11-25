<?php

function applyLatexConversion($text, $AMT, $wrap = true, $isAnswerMode = false) {
    if (is_array($text)) {
        foreach ($text as $k => $v) {
            $text[$k] = applyLatexConversion($v, $AMT, $wrap, $isAnswerMode);
        }
        return $text;
    }

    if (!is_string($text)) return $text;

    // Regex: Find anything between backticks and convert it
    return preg_replace_callback('/`(.*?)`/s', function($matches) use ($AMT, $wrap, $isAnswerMode) {
        // The math sits in HTML, so its entities are still encoded. AMtoTeX only knows &lt; &gt; &nbsp;
        // and passes any other "&" into the TeX ("&ne;0" -> "&\ne;0"), which pandoc refuses.
        // Malformed entities (e.g. "&nbsp" without ";") are not decoded: that is a content fix.
        // The no-break space is dropped, as AMtoTeX already did with "&nbsp;".
        $asciimath = str_replace("\u{00A0}", '', html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $tex = $AMT->convert($asciimath);

        if ($wrap) {
            return '<math-field read-only="">' . $tex . '</math-field>';
        } else if ($isAnswerMode) {
            return '\[' . $tex . '\]';
        } else {
            return $tex;
        }
    }, $text);
}
