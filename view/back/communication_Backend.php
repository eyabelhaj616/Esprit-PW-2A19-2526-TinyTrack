<?php
require_once __DIR__ . "/../../controller/ConversationController.php";
require_once __DIR__ . "/../../controller/MessageController.php";

$devUser = require __DIR__ . "/../../config/dev_user.php";

if (($devUser['page'] ?? 'front') !== 'back') {
    header('Location: /ProjetCommunication/view/front/communication.php');
    exit;
}

$conversationController = new ConversationController();
$messageController = new MessageController();

$conversations = $conversationController->all();
$stats = $conversationController->stats();
$alertCount = $messageController->adminAlertCount();
$adminSender = $conversationController->adminSender();
$adminSenderId = $adminSender->id ?? 1;

$selectedConversationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selectedConversation = $selectedConversationId ? $conversationController->show($selectedConversationId) : null;
$messages = $selectedConversation ? $messageController->index($selectedConversationId) : [];

$totalMessages = count($messages);
$parentName = $selectedConversation ? trim(($selectedConversation->parent_prenom ?? '') . ' ' . ($selectedConversation->parent_nom ?? '')) : '';
$staffName = $selectedConversation ? trim(($selectedConversation->staff_prenom ?? '') . ' ' . ($selectedConversation->staff_nom ?? '')) : '';
$isArchived = $selectedConversation && (($selectedConversation->status ?? '') === 'archived');

include 'template/header.php';
include 'template/sidebar.php';
?>

<style>
  .comm-page {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 18px;
  }

  .comm-panel {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    overflow: hidden;
  }

  .comm-panel-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #edf1f5;
  }

  .comm-title {
    margin: 0 0 6px;
    font-family: 'Fredoka One', cursive;
    color: #2D3436;
    font-size: 1.15rem;
  }

  .comm-help {
    margin: 0;
    color: #7a8794;
    font-size: 0.88rem;
    font-weight: 700;
  }

  .comm-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
  }

  .stat-card {
    background: #fff;
    border-radius: 18px;
    padding: 16px 18px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.05);
  }

  .stat-label {
    color: #7a8794;
    font-size: 0.84rem;
    font-weight: 800;
    margin-bottom: 6px;
  }

  .stat-value {
    color: #2D3436;
    font-family: 'Fredoka One', cursive;
    font-size: 2rem;
    line-height: 1;
  }

  .conversation-search-wrap {
    padding: 14px 20px 0;
  }

  .conversation-search {
    border: 1px solid #e4eaf0;
    border-radius: 14px;
    background: #f8fafc;
    padding: 12px 14px;
  }

  .conversation-list {
    padding: 12px;
    max-height: 650px;
    overflow-y: auto;
  }

  .conversation-item {
    display: block;
    text-decoration: none;
    color: #2D3436;
    border: 1px solid transparent;
    border-radius: 16px;
    padding: 14px;
    margin-bottom: 10px;
    background: #fff;
  }

  .conversation-item:hover,
  .conversation-item.active {
    background: #f8fbff;
    border-color: #dbe8f3;
  }

  .conversation-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
  }

  .conversation-name {
    font-size: 0.98rem;
    font-weight: 800;
  }

  .conversation-meta,
  .conversation-preview {
    color: #7a8794;
    font-size: 0.84rem;
  }

  .conversation-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 0.32rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 800;
  }

  .conversation-status.active {
    background: #E8F5E9;
    color: #2E7D32;
  }

  .conversation-status.archived {
    background: #FFF3E0;
    color: #E65100;
  }

  .conversation-status.alert {
    background: #FFF8E1;
    color: #E65100;
  }

  .chat-header {
    padding: 18px 20px;
    border-bottom: 1px solid #edf1f5;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
  }

  .chat-user {
    margin: 0;
    font-family: 'Fredoka One', cursive;
    color: #2D3436;
    font-size: 1.12rem;
  }

  .chat-subtext {
    color: #7a8794;
    font-size: 0.88rem;
    font-weight: 700;
    margin-top: 4px;
  }

  .chat-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .chat-actions .btn {
    border-radius: 12px !important;
    font-weight: 800 !important;
    padding: 0.6rem 0.9rem !important;
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
    background: rgba(255,255,255,0.95);
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
    min-width: 180px;
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

  .chat-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #edf1f5;
    background: #fbfcfe;
  }

  .chat-info-box {
    background: #fff;
    border: 1px solid #edf1f5;
    border-radius: 14px;
    padding: 12px 14px;
  }

  .chat-info-label {
    color: #7a8794;
    font-size: 0.8rem;
    font-weight: 800;
    margin-bottom: 4px;
  }

  .chat-info-value {
    color: #2D3436;
    font-size: 0.94rem;
    font-weight: 800;
  }

  .chat-thread {
    height: 460px;
    overflow-y: auto;
    padding: 18px 20px;
    background: #f8fbff;
  }

  .chat-message {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
  }

  .chat-message.mine {
    flex-direction: row-reverse;
  }

  .chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
  }

  .chat-bubble {
    max-width: 72%;
    background: #fff;
    border: 1px solid #e5ebf0;
    border-radius: 18px 18px 18px 6px;
    padding: 12px 14px;
    position: relative;
  }

  .chat-message.mine .chat-bubble {
    background: #E8F5E9;
    border-color: #C8E6C9;
    border-radius: 18px 18px 6px 18px;
  }

  .chat-message.flagged .chat-bubble {
    border-color: #f59e0b;
    box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.08);
  }

  .chat-meta {
    color: #7a8794;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .chat-time {
    color: #a5b0bb;
    font-size: 0.72rem;
    text-align: right;
    margin-top: 6px;
  }

  .chat-flag {
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

  .composer {
    padding: 16px 20px 20px;
    border-top: 1px solid #edf1f5;
    background: #fff;
  }

  .composer-label {
    color: #7a8794;
    font-size: 0.84rem;
    font-weight: 800;
    margin-bottom: 8px;
  }

  .composer-wrap {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    border: 1px solid #e5ebf0;
    border-radius: 18px;
    background: #f8fafc;
    padding: 12px;
  }

  .composer-wrap textarea {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    resize: none;
    min-height: 54px;
    padding: 6px;
  }

  .composer-wrap .btn {
    border-radius: 14px !important;
    padding: 0.8rem 1rem !important;
    font-weight: 800 !important;
    white-space: nowrap;
  }

  .empty-box {
    padding: 80px 24px;
    text-align: center;
    color: #7a8794;
  }

  .empty-box i {
    font-size: 2rem;
    margin-bottom: 12px;
  }

  @media (max-width: 991px) {
    .comm-stats,
    .comm-page,
    .chat-info {
      grid-template-columns: 1fr;
    }

    .chat-header {
      flex-direction: column;
      align-items: stretch;
    }

    .chat-bubble {
      max-width: 86%;
    }
  }
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-sm-12">
          <h1 class="m-0"><i class="fas fa-comments text-primary"></i> Communication Backend</h1>
          <p class="mb-0" style="color:#7a8794;font-weight:700;">Choisissez une conversation, lisez l'historique et repondez sans changer de page.</p>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="comm-stats">
        <div class="stat-card">
          <div class="stat-label">Total conversations</div>
          <div class="stat-value"><?= (int) $stats->total ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Messages de la conversation active</div>
          <div class="stat-value"><?= $selectedConversation ? $totalMessages : 0 ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Messages signales</div>
          <div class="stat-value"><?= (int) $alertCount ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Conversations archivees</div>
          <div class="stat-value"><?= (int) $stats->archived_count ?></div>
        </div>
      </div>

      <div class="comm-page">
        <div class="comm-panel">
          <div class="comm-panel-header">
            <h2 class="comm-title">Liste des conversations</h2>
            <p class="comm-help">A gauche la liste, a droite la conversation choisie.</p>
          </div>

          <div class="conversation-search-wrap">
            <input type="text" id="conversationSearch" class="form-control conversation-search" placeholder="Rechercher par parent, staff ou statut...">
          </div>

          <div class="conversation-list" id="conversationList">
            <?php if (empty($conversations)): ?>
              <div class="empty-box">
                <i class="fas fa-comments"></i>
                <div>Aucune conversation disponible.</div>
              </div>
            <?php else: ?>
              <?php foreach ($conversations as $conversation): ?>
                <?php
                  $rowParentName = trim(($conversation->parent_prenom ?? '') . ' ' . ($conversation->parent_nom ?? ''));
                  $rowStaffName = trim(($conversation->staff_prenom ?? '') . ' ' . ($conversation->staff_nom ?? ''));
                  $rowIsArchived = ($conversation->status ?? '') === 'archived';
                  $rowActive = $selectedConversation && (int) $selectedConversation->id === (int) $conversation->id;
                  $rowAlertMessages = (int) ($conversation->alert_messages_count ?? 0);
                ?>
                <a
                  href="/ProjetCommunication/view/back/communication_Backend.php?id=<?= $conversation->id ?>"
                  class="conversation-item<?= $rowActive ? ' active' : '' ?>"
                  data-search="<?= htmlspecialchars(strtolower($rowParentName . ' ' . $rowStaffName . ' ' . ($conversation->status ?? ''))) ?>"
                >
                  <div class="conversation-item-top">
                    <div class="conversation-name"><?= htmlspecialchars($rowParentName) ?></div>
                    <div class="d-flex flex-column align-items-end gap-1">
                      <span class="conversation-status <?= $rowIsArchived ? 'archived' : 'active' ?>">
                        <i class="fas <?= $rowIsArchived ? 'fa-box-archive' : 'fa-comments' ?>"></i>
                        <?= $rowIsArchived ? 'Archivee' : 'Active' ?>
                      </span>
                      <?php if ($rowAlertMessages > 0): ?>
                        <span class="conversation-status alert">
                          <i class="fas fa-bell"></i>
                          <?= $rowAlertMessages > 1 ? $rowAlertMessages . ' messages' : 'Message signale' ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="conversation-meta">Staff: <?= htmlspecialchars($rowStaffName) ?></div>
                  <div class="conversation-preview">
                    <?= (int) $conversation->messages_count ?> message<?= (int) $conversation->messages_count > 1 ? 's' : '' ?> - dernier message: <?= $conversation->last_message_at ?? '-' ?>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="comm-panel">
          <?php if (!$selectedConversation): ?>
            <div class="empty-box">
              <i class="fas fa-inbox"></i>
              <div>Selectionnez une conversation dans la colonne de gauche.</div>
            </div>
          <?php else: ?>
            <div class="chat-header">
              <div>
                <h2 class="chat-user"><?= htmlspecialchars($parentName) ?></h2>
                <div class="chat-subtext">Parent: <?= htmlspecialchars($parentName) ?> | Staff: <?= htmlspecialchars($staffName) ?></div>
              </div>
              <div class="chat-actions">
                <a href="/ProjetCommunication/controller/updateConversationStatus.php?id=<?= $selectedConversation->id ?>&action=<?= $isArchived ? 'restore' : 'archive' ?>&redirect=../view/back/communication_Backend.php?id=<?= $selectedConversation->id ?>" class="btn <?= $isArchived ? 'btn-success' : 'btn-warning' ?>">
                  <i class="fas <?= $isArchived ? 'fa-box-open' : 'fa-box-archive' ?>"></i>
                  <?= $isArchived ? 'Desarchiver' : 'Archiver' ?>
                </a>
                <a href="/ProjetCommunication/controller/deleteConversation.php?id=<?= $selectedConversation->id ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette conversation ?')">
                  <i class="fas fa-trash"></i>
                  Supprimer
                </a>
              </div>
            </div>

            <div class="chat-info">
              <div class="chat-info-box">
                <div class="chat-info-label">Statut</div>
                <div class="chat-info-value"><?= $isArchived ? 'Archivee' : 'Active' ?></div>
              </div>
              <div class="chat-info-box">
                <div class="chat-info-label">Date de creation</div>
                <div class="chat-info-value"><?= $selectedConversation->created_at ?? '-' ?></div>
              </div>
              <div class="chat-info-box">
                <div class="chat-info-label">Total messages</div>
                <div class="chat-info-value"><?= $totalMessages ?></div>
              </div>
            </div>

            <div class="chat-thread" id="chatThread">
              <?php if (empty($messages)): ?>
                <div class="empty-box">
                  <i class="fas fa-comments"></i>
                  <div>Aucun message dans cette conversation.</div>
                </div>
              <?php else: ?>
                <?php foreach ($messages as $message): ?>
                  <?php
                    $isMine = $message->sender_role === 'admin';
                    $avatarColor = $isMine ? '#4CAF50' : ($message->sender_role === 'educateur' ? '#5B9BD5' : '#FFA726');
                    $avatarIcon = $isMine ? 'shield-alt' : ($message->sender_role === 'educateur' ? 'user-tie' : 'user');
                    $isFlagged = !empty($message->needs_admin_attention);
                  ?>
                  <div class="chat-message<?= $isMine ? ' mine' : '' ?><?= $isFlagged ? ' flagged' : '' ?>">
                    <div class="chat-avatar" style="background:<?= $avatarColor ?>;">
                      <i class="fas fa-<?= $avatarIcon ?>"></i>
                    </div>
                    <div class="chat-bubble">
                      <?php if ($isFlagged): ?>
                        <div class="message-menu-wrap">
                          <button type="button" class="message-menu-trigger" data-message-menu="back-message-menu-<?= (int) $message->id ?>" aria-label="Options">
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <div class="message-menu" id="back-message-menu-<?= (int) $message->id ?>">
                            <a href="/ProjetCommunication/controller/updateMessageAlert.php?id=<?= (int) $message->id ?>&action=clear&redirect=../view/back/communication_Backend.php?id=<?= (int) $selectedConversation->id ?>">
                              <i class="fas fa-check"></i>
                              Marquer traitee
                            </a>
                          </div>
                        </div>
                      <?php endif; ?>
                      <div class="chat-meta"><?= $isMine ? 'Admin' : htmlspecialchars($message->sender_role) ?></div>
                      <div><?= nl2br(htmlspecialchars($message->body)) ?></div>
                      <?php if ($isFlagged): ?>
                        <div class="chat-flag"><i class="fas fa-bell"></i> Message signale</div>
                      <?php endif; ?>
                      <div class="chat-time"><?= $message->created_at ?? '-' ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <form method="POST" action="/ProjetCommunication/controller/storeMessage.php" class="composer">
              <input type="hidden" name="conversation_id" value="<?= $selectedConversation->id ?>">
              <input type="hidden" name="sender_id" value="<?= $adminSenderId ?>">
              <input type="hidden" name="sender_role" value="admin">
              <input type="hidden" name="redirect_to" value="../view/back/communication_Backend.php?id=<?= $selectedConversation->id ?>">

              <div class="composer-label">Envoyer une reponse dans cette conversation</div>
              <div class="composer-wrap">
                <textarea name="body" class="form-control" rows="2" placeholder="Tapez votre message..." required></textarea>
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-paper-plane"></i>
                  Envoyer
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'template/footer.php'; ?>
<script>
var chatThread = document.getElementById('chatThread');
if (chatThread) {
  chatThread.scrollTop = chatThread.scrollHeight;
}

var conversationSearch = document.getElementById('conversationSearch');
var conversationItems = document.querySelectorAll('.conversation-item');

  if (conversationSearch) {
    conversationSearch.addEventListener('input', function() {
      var value = this.value.toLowerCase().trim();
      conversationItems.forEach(function(item) {
        item.style.display = item.dataset.search.indexOf(value) !== -1 ? 'block' : 'none';
      });
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
