<?php
require_once __DIR__ . "/../../controller/MessageController.php";

$controller = new MessageController();
$messages = $controller->index(1);
$currentUserRole = 'parent';

$roles = [
    'parent' => ['label' => 'Vous', 'icon' => 'user', 'color' => '#FFA726'],
    'educateur' => ['label' => 'Educateur', 'icon' => 'user-tie', 'color' => '#5B9BD5'],
    'admin' => ['label' => 'Admin', 'icon' => 'shield-alt', 'color' => '#4CAF50']
];

include 'template/header.php';
?>

<style>
  .front-comm {
    max-width: 1100px;
    margin: 0 auto;
  }

  .front-comm-shell {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 18px;
  }

  .front-panel {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    overflow: hidden;
  }

  .front-panel-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #eef2f5;
  }

  .front-title {
    margin: 0 0 6px;
    font-family: 'Fredoka One', cursive;
    color: #4CAF50;
    font-size: 1.12rem;
  }

  .front-help {
    margin: 0;
    color: #7b8794;
    font-size: 0.88rem;
    font-weight: 700;
  }

  .front-conversation-list {
    padding: 12px;
  }

  .front-conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 16px;
    background: #f8fbff;
    border: 1px solid #dbe8f3;
  }

  .front-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
  }

  .front-chat-header {
    padding: 18px 20px;
    border-bottom: 1px solid #eef2f5;
  }

  .front-chat-thread {
    height: 460px;
    overflow-y: auto;
    padding: 18px 20px;
    background: #fcfcfd;
  }

  .front-message {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
  }

  .front-message.mine {
    flex-direction: row-reverse;
  }

  .front-bubble {
    max-width: 74%;
    background: #f1f3f5;
    border: 1px solid #e6eaee;
    border-radius: 18px 18px 18px 6px;
    padding: 12px 14px;
  }

  .front-message.mine .front-bubble {
    background: #FFF3E0;
    border-color: #FFD9A8;
    border-radius: 18px 18px 6px 18px;
  }

  .front-meta {
    font-size: 0.72rem;
    font-weight: 800;
    color: #8b96a3;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .front-time {
    font-size: 0.72rem;
    color: #a8b1bb;
    text-align: right;
    margin-top: 6px;
  }

  .front-composer {
    padding: 18px 20px 20px;
    border-top: 1px solid #eef2f5;
    background: #fff;
  }

  .front-compose-wrap {
    position: relative;
    border: 1px solid #e5ebf0;
    border-radius: 22px;
    background: #fcfcfd;
    box-shadow: 0 8px 22px rgba(31,41,55,0.05);
    padding: 12px 14px 76px;
  }

  .front-compose-wrap textarea {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    resize: none;
    min-height: 110px;
    padding: 8px 4px;
    font-size: 1rem;
  }

  .front-send-btn {
    position: absolute;
    right: 14px;
    bottom: 14px;
    width: 54px;
    height: 54px;
    align-items: center;
    justify-content: center;
    padding: 0;
    border-radius: 50%;
    box-shadow: 0 12px 24px rgba(76,175,80,0.24);
  }

  .front-send-btn i {
    font-size: 1.05rem;
    margin-left: 2px;
  }

  @media (max-width: 991px) {
    .front-comm-shell {
      grid-template-columns: 1fr;
    }

    .front-chat-thread {
      height: 360px;
    }

    .front-bubble {
      max-width: 86%;
    }
  }
</style>

<div class="container py-4">
  <div class="front-comm">
    <div class="text-center mb-4">
      <h2 class="section-title"><i class="fas fa-comments"></i> Communication</h2>
      <p class="text-muted mt-3">Votre conversation avec l'equipe TinyTrack.</p>
    </div>

    <div class="front-comm-shell">
      <div class="front-panel">
        <div class="front-panel-header">
          <h3 class="front-title">Votre conversation</h3>
          <p class="front-help">Une seule zone simple pour suivre les echanges.</p>
        </div>

        <div class="front-conversation-list">
          <div class="front-conversation-item">
            <div class="front-avatar" style="background:#4CAF50;">
              <i class="fas fa-comments"></i>
            </div>
            <div>
              <div class="fw-bold">TinyTrack</div>
              <small class="text-muted"><?= count($messages) ?> message<?= count($messages) > 1 ? 's' : '' ?></small>
            </div>
          </div>
        </div>
      </div>

      <div class="front-panel">
        <div class="front-chat-header">
          <div class="fw-bold">Conversation active</div>
          <small class="text-muted">Vos messages sont a droite, les messages recus sont a gauche.</small>
        </div>

        <div class="front-chat-thread" id="frontChatThread">
          <?php if (empty($messages)): ?>
            <div class="text-center text-muted py-5">
              <i class="fas fa-comments fa-2x mb-3"></i>
              <p class="mb-0">Aucun message pour le moment.</p>
            </div>
          <?php else: ?>
            <?php foreach ($messages as $message): ?>
              <?php
                $isMine = $message->sender_role === $currentUserRole;
                $person = $roles[$message->sender_role] ?? ['label' => $message->sender_role, 'icon' => 'user', 'color' => '#999'];
              ?>
              <div class="front-message<?= $isMine ? ' mine' : '' ?>">
                <div class="front-avatar" style="background:<?= $person['color'] ?>;">
                  <i class="fas fa-<?= $person['icon'] ?>"></i>
                </div>
                <div class="front-bubble">
                  <div class="front-meta"><?= $isMine ? 'Vous' : htmlspecialchars($person['label']) ?></div>
                  <div><?= nl2br(htmlspecialchars($message->body)) ?></div>
                  <div class="front-time"><?= $message->created_at ?? '-' ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <form method="POST" action="../../controller/storeMessage.php" id="msgForm" class="front-composer" novalidate>
          <input type="hidden" name="conversation_id" value="1">
          <input type="hidden" name="sender_id" value="1">
          <input type="hidden" name="sender_role" value="parent">
          <input type="hidden" name="redirect_to" value="../view/front/communication.php">

          <div class="front-compose-wrap">
            <textarea name="body" id="msgBody" class="form-control" rows="2" placeholder="Tapez votre message..."></textarea>
            <button type="submit" class="btn btn-kider front-send-btn" aria-label="Envoyer le message">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
          <div id="msgErr" class="mt-2" style="font-size:12px;font-weight:700;color:#EF5350;display:none;"></div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'template/footer.php'; ?>
<script>
var frontChatThread = document.getElementById('frontChatThread');
if (frontChatThread) {
  frontChatThread.scrollTop = frontChatThread.scrollHeight;
}

document.getElementById('msgForm').addEventListener('submit', function(e) {
  var body = document.getElementById('msgBody').value.trim();
  var err = document.getElementById('msgErr');

  if (body === '') {
    e.preventDefault();
    err.textContent = 'Le message ne peut pas etre vide.';
    err.style.display = 'block';
    document.getElementById('msgBody').style.borderColor = '#EF5350';
  }
});

document.getElementById('msgBody').addEventListener('input', function() {
  document.getElementById('msgErr').style.display = 'none';
  this.style.borderColor = 'transparent';
});
</script>
