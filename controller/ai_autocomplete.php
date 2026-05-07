<?php
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the JSON payload
$data = json_decode(file_get_contents('php://input'), true);
$text = $data['text'] ?? '';
$parentName = $data['parentName'] ?? 'un parent';
$childName = $data['childName'] ?? 'l\'enfant';
$chatHistory = $data['chatHistory'] ?? '';

if (empty(trim($text))) {
    echo json_encode(['suggestion' => '']);
    exit;
}

// Groq API Key
$apiKey = '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';

// The URL for the Groq API
$url = 'https://api.groq.com/openai/v1/chat/completions';

// Construct the prompt
$prompt = "You are an autocomplete engine for 'TinyTrack', a kindergarten/childcare application. 
You are helping the childcare staff/admin write a message to a parent. 

CONTEXT:
Parent Name: " . $parentName . "
Child Name: " . $childName . "
Recent Chat History:
" . $chatHistory . "

INSTRUCTIONS:
The user is currently typing a message. Based on the context and the incomplete sentence, suggest ONLY the next few words (max 5-8 words) to logically complete it.
Do NOT include the words the user has already typed.
Do NOT include quotes, full stops at the end, or any explanations.
Make the suggestion highly relevant to childcare, the child's name, or the parent's recent messages.
If the sentence seems already complete, just return an empty string.

Incomplete sentence: \"" . $text . "\"";

// Prepare the payload for Groq
$payload = [
    'model' => 'llama-3.1-8b-instant',
    'messages' => [
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.2, // Low temperature for more predictable completions
    'max_tokens' => 50
];

// Setup cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP SSL issues

// Execute
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Parse response
$responseData = json_decode($response, true);
$suggestion = '';

if (isset($responseData['choices'][0]['message']['content'])) {
    $suggestion = trim($responseData['choices'][0]['message']['content']);
    $suggestion = str_replace('"', '', $suggestion);
}

echo json_encode([
    'suggestion' => $suggestion, 
    'debug' => $responseData,
    'prompt' => $prompt
]);
?>
