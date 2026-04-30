<?php

require_once __DIR__ . "/../../controller/ConversationController.php";
require_once __DIR__ . "/../../controller/MessageController.php";

$devUser = require __DIR__ . "/../../config/dev_user.php";

if (($devUser['page'] ?? 'front') !== 'front') {
    header('Location: /ProjetCommunication/view/back/communication_Backend.php');
    exit;
}

$currentUserId = (int) ($devUser['id'] ?? 0);
$currentUserRole = $devUser['role'] ?? 'parent';
$currentUserName = $devUser['name'] ?? 'User test';

$conversationController = new ConversationController();
$messageController = new MessageController();

$contacts = $conversationController->contactsForUser($currentUserId, $currentUserRole);
$conversations = $conversationController->forUser($currentUserId, $currentUserRole);
$conversationIds = array_map(static function ($conversation) {
    return (int) $conversation->id;
}, $conversations);
$contactIds = array_map(static function ($contact) {
    return (int) $contact->contact_id;
}, $contacts);

$selectedContactId = isset($_GET['contact_id']) ? (int) $_GET['contact_id'] : 0;
if ($selectedContactId > 0 && in_array($selectedContactId, $contactIds, true)) {
    $createdConversationId = $conversationController->openConversationForUser($currentUserId, $currentUserRole, $selectedContactId);
    if ($createdConversationId) {
        header('Location: /ProjetCommunication/view/front/communication.php?id=' . (int) $createdConversationId);
        exit;
    }
}

$selectedConversationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($selectedConversationId && !in_array($selectedConversationId, $conversationIds, true)) {
    $selectedConversationId = 0;
}

$selectedConversation = $selectedConversationId ? $conversationController->show($selectedConversationId) : null;
$isArchivedConversation = $selectedConversation && (($selectedConversation->status ?? '') === 'archived');

if ($isArchivedConversation && $currentUserRole !== 'admin') {
    header('Location: /ProjetCommunication/view/front/communication.php');
    exit;
}

$unreadMessageIds = [];
if ($selectedConversation) {
    $messages = $messageController->index($selectedConversationId);
    foreach ($messages as $message) {
        if (($message->sender_role ?? '') !== $currentUserRole && empty($message->read_at)) {
            $unreadMessageIds[] = (int) $message->id;
        }
    }

    $messageController->markAsRead($selectedConversationId, $currentUserRole);
} else {
    $messages = [];
}

$roles = [
    'parent' => ['label' => 'Parent', 'icon' => 'user', 'color' => '#FFA726'],
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
    grid-template-columns: 320px 1fr;
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
    max-height: 640px;
    overflow-y: auto;
  }

  .front-conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 16px;
    background: #f8fbff;
    border: 1px solid #dbe8f3;
    text-decoration: none;
    color: inherit;
    margin-bottom: 10px;
  }

  .front-conversation-item.active {
    border-color: #4CAF50;
    background: #f3fbf4;
  }

  .front-conversation-item.is-new {
    border-style: dashed;
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

  .front-message.unread .front-bubble {
    border-color: #FFB74D;
    box-shadow: 0 0 0 1px rgba(255,183,77,0.12);
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

  .front-unread-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: #fff3e0;
    color: #e65100;
    font-size: 0.68rem;
    font-weight: 800;
  }

  .front-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: #FFF8E1;
    color: #E65100;
    font-size: 0.68rem;
    font-weight: 800;
  }

  .message-menu-wrap {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
  }

  .message-menu-trigger {
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.92);
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    cursor: pointer;
  }

  .message-menu {
    position: absolute;
    top: 36px;
    right: 0;
    min-width: 170px;
    background: #fff;
    border: 1px solid #e5ebf0;
    border-radius: 14px;
    box-shadow: 0 16px 30px rgba(31,41,55,0.14);
    padding: 6px;
    display: none;
  }

  .message-menu.is-open {
    display: block;
  }

  .message-menu a,
  .message-menu span {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 0.65rem 0.75rem;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    font-size: 0.86rem;
    font-weight: 800;
    white-space: nowrap;
  }

  .message-menu a:hover {
    background: #f3f4f6;
  }

  .front-bubble {
    position: relative;
    padding-right: 48px;
  }

  .front-message.mine .front-bubble {
    padding-right: 14px;
    padding-left: 48px;
  }

  .front-message-flag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #fff4e5;
    color: #c2410c;
    font-size: 0.68rem;
    font-weight: 800;
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
      <p class="text-muted small mb-0">Mode test: <?= htmlspecialchars($currentUserName) ?>, id <?= (int) $currentUserId ?>, role <?= htmlspecialchars($currentUserRole) ?></p>
    </div>

    <div class="front-comm-shell">
      <div class="front-panel">
        <div class="front-panel-header">
          <h3 class="front-title">Personnes a contacter</h3>
          <p class="front-help">Parents voient les educateurs de leurs enfants. Educateurs voient les parents des enfants de leurs groupes.</p>
        </div>

        <div class="front-conversation-list">
          <?php if (empty($contacts)): ?>
            <div class="text-center text-muted py-5">
              <i class="fas fa-inbox fa-2x mb-3"></i>
              <p class="mb-0">Aucun contact disponible pour cet utilisateur.</p>
            </div>
          <?php else: ?>
            <?php foreach ($contacts as $contact): ?>
              <?php
                $contactConversationId = (int) ($contact->conversation_id ?? 0);
                $isActive = $contactConversationId > 0 && (int) $contactConversationId === (int) $selectedConversationId;
                $contactName = trim(($contact->contact_prenom ?? '') . ' ' . ($contact->contact_nom ?? ''));
                $detailText = trim((string) (
                  $currentUserRole === 'parent'
                    ? ($contact->child_group_names ?? '')
                    : ($contact->child_names ?? '')
                ));
                $unreadCount = $contactConversationId ? $messageController->unreadCount($contactConversationId, $currentUserRole) : 0;
                $contactLink = $contactConversationId > 0
                  ? '/ProjetCommunication/view/front/communication.php?id=' . (int) $contactConversationId
                  : '/ProjetCommunication/view/front/communication.php?contact_id=' . (int) $contact->contact_id;
              ?>
              <a href="<?= $contactLink ?>" class="front-conversation-item<?= $isActive ? ' active' : '' ?><?= $contactConversationId === 0 ? ' is-new' : '' ?>">
                <div class="front-avatar" style="background:#4CAF50;">
                  <i class="fas fa-comments"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="fw-bold"><?= htmlspecialchars($contactName !== '' ? $contactName : 'Conversation') ?></div>
                  <small class="text-muted">
                    <?= htmlspecialchars($detailText) ?>
                    <?php if ($unreadCount > 0): ?>
                      <span class="ms-2 badge rounded-pill bg-danger"><?= (int) $unreadCount ?> non lu<?= $unreadCount > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                  </small>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="front-panel">
        <div class="front-chat-header">
          <?php if ($selectedConversation): ?>
                <?php
                  $chatPartner = $currentUserRole === 'parent'
                    ? trim(($selectedConversation->staff_prenom ?? '') . ' ' . ($selectedConversation->staff_nom ?? ''))
                    : trim(($selectedConversation->parent_prenom ?? '') . ' ' . ($selectedConversation->parent_nom ?? ''));
                  $chatRole = $currentUserRole === 'parent' ? 'Educateur' : 'Parent';
                ?>
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-bold">Conversation active</div>
                    <small class="text-muted"><?= htmlspecialchars($chatRole) ?>: <?= htmlspecialchars($chatPartner !== '' ? $chatPartner : 'Conversation') ?></small>
                    <?php if ($isArchivedConversation): ?>
                      <div class="front-status-pill" style="background:#F3F4F6;color:#4B5563;"><i class="fas fa-box-archive"></i> Cette conversation est archivee</div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <div class="fw-bold">Conversation active</div>
            <small class="text-muted">Selectionnez une personne a contacter dans la colonne de gauche.</small>
          <?php endif; ?>
        </div>

        <div class="front-chat-thread" id="frontChatThread">
          <?php if (!$selectedConversation): ?>
            <div class="text-center text-muted py-5">
              <i class="fas fa-comments fa-2x mb-3"></i>
              <p class="mb-0">Aucun message a afficher.</p>
            </div>
          <?php elseif ($isArchivedConversation): ?>
            <div class="text-center text-muted py-5">
              <i class="fas fa-box-archive fa-2x mb-3"></i>
              <p class="mb-0">Cette conversation est archivee et ne peut pas etre ouverte.</p>
            </div>
          <?php elseif (empty($messages)): ?>
            <div class="text-center text-muted py-5">
              <i class="fas fa-comments fa-2x mb-3"></i>
              <p class="mb-0">Aucun message pour le moment.</p>
            </div>
          <?php else: ?>
                <?php foreach ($messages as $message): ?>
                  <?php
                    $isMine = $message->sender_role === $currentUserRole;
                    $isUnread = !$isMine && in_array((int) $message->id, $unreadMessageIds, true);
                    $person = $roles[$message->sender_role] ?? ['label' => $message->sender_role, 'icon' => 'user', 'color' => '#999'];
                    $isFlagged = !empty($message->needs_admin_attention);
                    $canAlertMessage = !$isMine && $currentUserRole !== 'admin' && !$isArchivedConversation;
                    $messageAlertRedirect = '../view/front/communication.php?id=' . (int) $selectedConversationId;
                  ?>
                  <div class="front-message<?= $isMine ? ' mine' : '' ?><?= $isUnread ? ' unread' : '' ?>">
                    <div class="front-avatar" style="background:<?= $person['color'] ?>;">
                      <i class="fas fa-<?= $person['icon'] ?>"></i>
                    </div>
                    <div class="front-bubble">
                      <?php if ($canAlertMessage): ?>
                        <div class="message-menu-wrap">
                          <button type="button" class="message-menu-trigger" data-message-menu="front-message-menu-<?= (int) $message->id ?>" aria-label="Options">
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <div class="message-menu" id="front-message-menu-<?= (int) $message->id ?>">
                            <?php if (!$isFlagged): ?>
                              <a href="/ProjetCommunication/controller/updateMessageAlert.php?id=<?= (int) $message->id ?>&action=claim&redirect=<?= urlencode($messageAlertRedirect) ?>">
                                <i class="fas fa-bell"></i>
                                Alerter admin
                              </a>
                            <?php else: ?>
                              <span><i class="fas fa-check"></i> Alerte deja envoyee</span>
                            <?php endif; ?>
                          </div>
                        </div>
                      <?php endif; ?>
                      <div class="front-meta"><?= $isMine ? 'Vous' : htmlspecialchars($person['label']) ?></div>
                      <div><?= nl2br(htmlspecialchars($message->body)) ?></div>
                      <?php if ($isUnread): ?>
                        <div class="front-unread-pill"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Non lu</div>
                      <?php endif; ?>
                      <?php if ($isFlagged): ?>
                        <div class="front-message-flag"><i class="fas fa-bell"></i> Message signale</div>
                      <?php endif; ?>
                      <div class="front-time"><?= $message->created_at ?? '-' ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($selectedConversation && !$isArchivedConversation): ?>
          <form method="POST" action="../../controller/storeMessage.php" id="msgForm" class="front-composer" novalidate>
            <input type="hidden" name="conversation_id" value="<?= (int) $selectedConversationId ?>">
            <input type="hidden" name="sender_id" value="<?= (int) $currentUserId ?>">
            <input type="hidden" name="sender_role" value="<?= htmlspecialchars($currentUserRole) ?>">
            <input type="hidden" name="redirect_to" value="../view/front/communication.php?id=<?= (int) $selectedConversationId ?>">

            <div class="front-compose-wrap">
              <textarea name="body" id="msgBody" class="form-control" rows="2" placeholder="Tapez votre message..."></textarea>
              <button type="submit" class="btn btn-kider front-send-btn" aria-label="Envoyer le message">
                <i class="fas fa-paper-plane"></i>
              </button>
            </div>
            <div id="msgErr" class="mt-2" style="font-size:12px;font-weight:700;color:#EF5350;display:none;"></div>
          </form>
        <?php elseif ($selectedConversation && $isArchivedConversation): ?>
          <div class="front-composer">
            <div class="text-center text-muted py-4">
              <i class="fas fa-lock fa-2x mb-3"></i>
              <div>Cette conversation est archivee. Aucune action n'est disponible tant que l'admin ne la desarchive pas.</div>
            </div>
          </div>
        <?php endif; ?>
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

var msgForm = document.getElementById('msgForm');
if (msgForm) {
  msgForm.addEventListener('submit', function(e) {
    var body = document.getElementById('msgBody').value.trim();
    var err = document.getElementById('msgErr');

    if (body === '') {
      e.preventDefault();
      err.textContent = 'Le message ne peut pas etre vide.';
      err.style.display = 'block';
      document.getElementById('msgBody').style.borderColor = '#EF5350';
    }
  });
}

var msgBody = document.getElementById('msgBody');
  if (msgBody) {
    msgBody.addEventListener('input', function() {
      document.getElementById('msgErr').style.display = 'none';
      this.style.borderColor = 'transparent';
    });
  }

  document.querySelectorAll('.message-menu-trigger').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.stopPropagation();
      var targetId = this.getAttribute('data-message-menu');
      var menu = document.getElementById(targetId);

      document.querySelectorAll('.message-menu.is-open').forEach(function(openMenu) {
        if (openMenu !== menu) {
          openMenu.classList.remove('is-open');
        }
      });

      if (menu) {
        menu.classList.toggle('is-open');
      }
    });
  });

  document.addEventListener('click', function() {
    document.querySelectorAll('.message-menu.is-open').forEach(function(menu) {
      menu.classList.remove('is-open');
    });
  });
</script>
