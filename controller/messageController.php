<?php

require_once __DIR__ . "/../model/conversation.php";
require_once __DIR__ . "/../model/message.php";

class MessageController {

    private $model;
    private $conversationModel;

    public function __construct() {
        $this->model = new Message();
        $this->conversationModel = new Conversation();
    }

    public function store() {

        $conversation_id = isset($_POST['conversation_id']) ? $_POST['conversation_id'] : null;
        $sender_id = isset($_POST['sender_id']) ? $_POST['sender_id'] : null;
        $sender_role = isset($_POST['sender_role']) ? $_POST['sender_role'] : null;
        $body = isset($_POST['body']) ? trim($_POST['body']) : '';
        $redirect = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : '../view/front/communication.php';

        // validation
        if (!$conversation_id || !$sender_id || !$sender_role || $body === '') {
            die("Invalid input");
        }

        $conversation = $this->conversationModel->find($conversation_id);
        if (!$conversation) {
            die("Conversation not found");
        }

        if (($conversation->status ?? '') === 'archived' && $sender_role !== 'admin') {
            die("This conversation is archived");
        }

        $this->model->create($conversation_id, $sender_id, $sender_role, $body);

        // --- AI AUTO-REPLY LOGIC ---
        if ($sender_role === 'parent' && strpos(strtolower($body), '/bot') !== false) {
            $apiKey = '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';
            $url = 'https://api.groq.com/openai/v1/chat/completions';

            // Extract context from DB
            $db = new Database();
            $pdo = $db->connect();
            
            // Get parent info
            $stmt = $pdo->prepare("SELECT nom, prenom FROM user WHERE id = ?");
            $stmt->execute([$sender_id]);
            $parent = $stmt->fetch(PDO::FETCH_OBJ);
            $parentName = $parent ? ($parent->prenom . ' ' . $parent->nom) : 'le parent';

            // Get children info
            $stmt2 = $pdo->prepare("SELECT e.nom, e.prenom, TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) as age, g.nom as groupe_nom, dm.allergies, dm.notes 
                                    FROM enfant e 
                                    LEFT JOIN groupe g ON e.groupe_id = g.id 
                                    LEFT JOIN dossier_medical dm ON e.id = dm.enfant_id 
                                    WHERE e.parent_id = ?");
            $stmt2->execute([$sender_id]);
            $enfants = $stmt2->fetchAll(PDO::FETCH_OBJ);

            $childrenContext = "";
            foreach($enfants as $enf) {
                $childrenContext .= "- " . $enf->prenom . " " . $enf->nom . " (" . $enf->age . " ans), Groupe: " . ($enf->groupe_nom ?? 'Non assigné') . ".\n";
                if (!empty($enf->allergies)) $childrenContext .= "  Allergies: " . $enf->allergies . "\n";
                if (!empty($enf->notes)) $childrenContext .= "  Notes médicales: " . $enf->notes . "\n";
            }
            if (empty($childrenContext)) $childrenContext = "Aucun enfant trouvé.";

            $prompt = "You are a highly professional, warm, and reassuring automated virtual assistant for 'TinyTrack' kindergarten.
You are replying directly to the parent, " . $parentName . ".

DATABASE CONTEXT FOR THIS PARENT:
Enfants:
" . $childrenContext . "

The parent sent the following message specifically invoking you (the bot):
\"" . $body . "\"

INSTRUCTIONS:
- Write a highly professional, clear, and detailed auto-reply in French.
- DO NOT use storytelling or long paragraphs. 
- Format your response using clear bullet points and precise info.
- Directly address the parent's question using the context above (e.g. child's exact group, allergies, etc.).
- Keep the tone reassuring but strictly informational and structured.
- Do not use quotes.
- Do not mention that you have access to a database; just act naturally knowledgeable.";

            $payload = [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 300
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP SSL issues

            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['choices'][0]['message']['content'])) {
                    $botReply = trim($responseData['choices'][0]['message']['content']);
                    $botReply = str_replace('"', '', $botReply);
                    if (!empty($botReply)) {
                        // Insert bot reply into database (using admin role and an assumed admin ID 1)
                        $this->model->create($conversation_id, 1, 'admin', '🤖 Bot: ' . $botReply);
                    }
                }
            }
        }
        // --- END AI AUTO-REPLY LOGIC ---

        header("Location: " . $redirect);
        exit;
    }

    public function index($conversation_id) {
        return $this->model->read($conversation_id);
    }

    public function unreadCount($conversation_id, $reader_role) {
        return $this->model->unreadCount($conversation_id, $reader_role);
    }

    public function markAsRead($conversation_id, $reader_role) {
        return $this->model->markAsRead($conversation_id, $reader_role);
    }

    public function adminAlertCount() {
        return $this->model->adminAlertCount();
    }

    public function claimForAdmin($id, $byRole) {
        return $this->model->setAdminAlert($id, $byRole);
    }

    public function clearAdminAlert($id) {
        return $this->model->clearAdminAlert($id);
    }
}
