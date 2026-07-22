<?php

function send_sms($phone)
{
    $ini = parse_ini_file(__DIR__ . '/../../info.ini', true);
    $apiKey = $ini['sms']['api_key'] ?? '';
    $senderId = $ini['sms']['sender_id'] ?? 'MarieNoelle';

    if (empty($apiKey)) {
        return false;
    }

    $phone = trim($phone);
    $phone = ltrim($phone, '0');
    if (strlen($phone) === 9) {
        $phone = '233' . $phone;
    }

    $message = "Thank you for choosing our spa today. We're delighted to have served you. Your well-being is our priority, and we look forward to welcoming you again soon.";

    $payload = json_encode([
        'key' => $apiKey,
        'msisdn' => $phone,
        'message' => $message,
        'sender_id' => $senderId,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents('https://sms.nalosolutions.com/smsbackend/Resl_Nalo/send-message/', false, $context);

    if ($response === false) {
        return false;
    }

    if (isset($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $httpCode = (int) ($matches[1] ?? 0);
        return $httpCode >= 200 && $httpCode < 300;
    }

    return true;
}
