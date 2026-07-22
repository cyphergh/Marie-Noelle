<?php

function panel_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function panel_is_ajax_request()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function panel_json_response($success, $message, $payload = array())
{
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    echo json_encode(array_merge(
        array(
            'success' => (bool) $success,
            'message' => $message,
        ),
        $payload
    ));
    exit;
}

function panel_format_date($value, $format = 'd-m-Y')
{
    if (empty($value)) {
        return '--';
    }

    return date($format, strtotime($value));
}

function panel_table_row_number_cell()
{
    return '<th scope="row" class="js-row-number"></th>';
}
