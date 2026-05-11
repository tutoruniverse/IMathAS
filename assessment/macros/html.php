<?php

// html markup macros

array_push(
    $GLOBALS['allowedmacros'],
    'formhoverover',
    'formpopup',
    'forminlinebutton'
);

// Returns a data-nonce attribute string signing $payload, or '' if no secret is set.
// $payload should be the fully built href value of the <a> tag.
// Python verifies by recomputing: hmac(secret, timestamp + "." + href)
function _make_link_nonce($payload) {
    if (!isset($GLOBALS['csv_nonce_secret']) || $GLOBALS['csv_nonce_secret'] === '') {
        return '';
    }
    $timestamp = time();
    $sig = hash_hmac('sha256', $timestamp . '.' . $payload, $GLOBALS['csv_nonce_secret']);
    return ' data-nonce="' . $timestamp . '.' . $sig . '"';
}

function formhoverover($label, $tip) {
    if (function_exists('filter')) {
        $tip = filter($tip);
    }
    $tip = htmlentities($tip);
    $tip = str_replace('`', '&#96;', $tip);
    return '<span role="button" tabindex="0" class="link" data-tip="' . $tip . '" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">' . $label . '</span>';
}

function formpopup($label, $content, $width = 600, $height = 400, $type = 'link', $scroll = 'null', $id = 'popup', $ref = '', $presanitized = false) {
    if (!is_scalar($content)) {
        echo "invalid content in formpopup";
        return '';
    }
    if (!is_scalar($label)) {
        echo "invalid label in formpopup";
        return '';
    }
    $labelSanitized = $presanitized ? $label : Sanitize::encodeStringForDisplay($label);
    $href = Sanitize::encodeStringForDisplay($content);
    return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $labelSanitized . '</a>';
}

function forminlinebutton($label, $content, $style = 'button', $outstyle = 'block') {
    if (!is_scalar($content)) {
        echo "invalid content in forminlinebutton";
        return '';
    }
    if (!is_scalar($style)) {
        echo "invalid style in forminlinebutton";
        return '';
    }
    if (!is_scalar($label)) {
        echo "invalid label in forminlinebutton";
        return '';
    }

    $r = uniqid();
    $label = str_replace('"', '', $label);
    $common = 'id="inlinebtn' . $r . '" aria-controls="inlinebtnc' . $r . '" aria-expanded="false" onClick="toggleinlinebtn(\'inlinebtnc' . $r . '\', \'inlinebtn' . $r . '\');return false;"';
    if ($style == 'link') {
        $out = '<a href="#" ' . $common . '>' . $label . '</a>';
    } else {
        $out = '<button type="button" ' . $common . '>' . $label . '</button>';
    }
    if ($outstyle == 'inline') {
        $out .= ' <span id="inlinebtnc' . $r . '" style="display:none;" aria-hidden="true">' . $content . '</span>';
    } else {
        $out .= '<div id="inlinebtnc' . $r . '" style="display:none;" aria-hidden="true">' . $content . '</div>';
    }
    return $out;
}
