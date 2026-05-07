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
$adminSender = $conversationController->adminSender();
$adminSenderId = $adminSender->id ?? 1;

$selectedConversationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selectedConversation = $selectedConversationId ? $conversationController->show($selectedConversationId) : null;
$messages = $selectedConversation ? $messageController->index($selectedConversationId) : [];

$totalMessages = count($messages);
$parentName = $selectedConversation ? trim(($selectedConversation->parent_prenom ?? '') . ' ' . ($selectedConversation->parent_nom ?? '')) : '';
$staffName = $selectedConversation ? trim(($selectedConversation->staff_prenom ?? '') . ' ' . ($selectedConversation->staff_nom ?? '')) : '';
$childNames = $selectedConversation ? trim((string) ($selectedConversation->child_names ?? '')) : '';
$childrenCount = $selectedConversation ? (int) ($selectedConversation->children_count ?? 0) : 0;
$isArchived = $selectedConversation && (($selectedConversation->status ?? '') === 'archived');

// --- DASHBOARD STATS LOGIC ---
$dbStats = new Database();
$pdoStats = $dbStats->connect();

// Current totals
$statTotalConvos   = (int) $pdoStats->query("SELECT COUNT(*) FROM conversation")->fetchColumn();
$statActiveConvos  = (int) $pdoStats->query("SELECT COUNT(*) FROM conversation WHERE status = 'open'")->fetchColumn();
$statTotalMessages = (int) $pdoStats->query("SELECT COUNT(*) FROM message")->fetchColumn();
$statBotReplies    = (int) $pdoStats->query("SELECT COUNT(*) FROM message WHERE body LIKE '%🤖 Bot:%'")->fetchColumn();

// --- REAL TREND CALCULATION: current 28 days vs previous 28 days ---
function calcTrend($pdo, $table, $extraWhere = '') {
    $where = $extraWhere ? "AND $extraWhere" : '';
    $cur  = (int) $pdo->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 28 DAY) $where")->fetchColumn();
    $prev = (int) $pdo->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 56 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 28 DAY) $where")->fetchColumn();
    if ($prev === 0) {
        return ['pct' => ($cur > 0 ? 100 : 0), 'dir' => 'up'];
    }
    $pct = round((($cur - $prev) / $prev) * 100, 1);
    return ['pct' => abs($pct), 'dir' => $pct >= 0 ? 'up' : 'down'];
}

$trendTotalConvos   = calcTrend($pdoStats, 'conversation');
$trendActiveConvos  = calcTrend($pdoStats, 'conversation', "status = 'open'");
$trendTotalMessages = calcTrend($pdoStats, 'message');
$trendBotReplies    = calcTrend($pdoStats, 'message', "body LIKE '%🤖 Bot:%'");

// --- Today's messages vs yesterday for chart header trend ---
$msgsToday     = (int) $pdoStats->query("SELECT COUNT(*) FROM message WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$msgsYesterday = (int) $pdoStats->query("SELECT COUNT(*) FROM message WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
$chartDayTrend = $msgsYesterday === 0 ? ($msgsToday > 0 ? 100 : 0) : round((($msgsToday - $msgsYesterday) / $msgsYesterday) * 100, 1);
$chartDayDir   = $chartDayTrend >= 0 ? 'up' : 'down';

// --- REAL CHART DATA: messages per hour today (0–23) ---
$hourlyStmt = $pdoStats->query("SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt FROM message WHERE DATE(created_at) = CURDATE() GROUP BY hr");
$hourlyRaw = $hourlyStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$chartData = [];
for ($h = 0; $h < 24; $h += 2) {
    $chartData[] = (int)($hourlyRaw[$h] ?? 0) + (int)($hourlyRaw[$h + 1] ?? 0);
}

include 'template/header.php';
include 'template/sidebar.php';
?>

<style>
  /* Dashboard CSS */
  .dashboard-wrap { margin-bottom: 24px; display: flex; flex-direction: column; gap: 16px; padding: 20px; background: #fafafa; border-bottom: 1px solid #edf1f5; }
  .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
  .stat-card { background: #fff; border-radius: 12px; border: 1px solid #edf1f5; padding: 18px 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; flex-direction: column; position: relative; }
  .stat-card-title { font-size: 0.9rem; color: #4b5563; font-weight: 600; margin-bottom: 8px; }
  .stat-card-value { font-size: 2rem; color: #111827; font-weight: 700; margin-bottom: 8px; line-height: 1.2; font-family: 'Inter', sans-serif; }
  .stat-card-trend { font-size: 0.75rem; color: #9ca3af; font-weight: 600; display: flex; align-items: center; gap: 4px; }
  .stat-card-trend span { font-weight: 700; }
  .stat-card-trend.up span { color: #10b981; }
  .stat-card-trend.down span { color: #ef4444; }
  .stat-icon { position: absolute; top: 18px; right: 24px; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
  .icon-red { background: #fee2e2; color: #ef4444; }
  .icon-green { background: #d1fae5; color: #10b981; }
  .icon-blue { background: #dbeafe; color: #3b82f6; }
  .icon-orange { background: #ffedd5; color: #f97316; }

  .chart-card { background: #fff; border-radius: 12px; border: 1px solid #edf1f5; padding: 18px 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
  .chart-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
  .chart-title { font-size: 1.1rem; color: #111827; font-weight: 600; margin-bottom: 4px; }
  .chart-value { font-size: 2rem; color: #111827; font-weight: 700; display: flex; align-items: center; gap: 12px; line-height: 1; font-family: 'Inter', sans-serif; }
  .chart-trend { font-size: 0.85rem; color: #9ca3af; font-weight: 600; display: flex; flex-direction: column; }
  .chart-trend span { color: #10b981; }
  .chart-filters { display: flex; background: #f3f4f6; border-radius: 8px; padding: 2px; }
  .chart-filter { padding: 4px 12px; font-size: 0.8rem; font-weight: 600; color: #4b5563; cursor: pointer; border-radius: 6px; transition: background 0.2s; }
  .chart-filter.active { background: #fff; color: #111827; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .chart-container { height: 200px; width: 100%; position: relative; }
  .comm-page {
    display: block;
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
    overflow: visible;
  }

  .conversation-list .table-responsive {
    overflow: visible;
  }

  .conversation-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }

  .conversation-table thead th {
    position: sticky;
    top: 0;
    background: #f7f9fc;
    color: #6b7280;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    padding: 14px 12px;
    border-bottom: 1px solid #e8eef5;
    z-index: 1;
  }

  .conversation-table tbody tr {
    cursor: pointer;
  }

  .conversation-table tbody tr:hover {
    background: #f8fbff;
  }

  .conversation-table tbody tr.active {
    background: #f3fbf4;
  }

  .conversation-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #edf1f5;
    vertical-align: middle;
  }

  .conversation-main-title {
    font-size: 1rem;
    font-weight: 800;
    color: #2D3436;
    margin-bottom: 4px;
  }

  .conversation-main-sub {
    color: #7a8794;
    font-size: 0.82rem;
    font-weight: 700;
  }

  .conversation-main-sub.child {
    color: #4CAF50;
    font-weight: 800;
    font-size: 0.9rem;
  }

  .conversation-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 0.32rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 800;
    white-space: nowrap;
  }

  .conversation-pill.active {
    background: #E8F5E9;
    color: #2E7D32;
  }

  .conversation-pill.archived {
    background: #FFF3E0;
    color: #E65100;
  }

  .conversation-pill.alert {
    background: #FFF8E1;
    color: #E65100;
  }

  .row-actions {
    position: relative;
    text-align: right;
    width: 1%;
    white-space: nowrap;
  }

  .row-menu-trigger {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .row-menu {
    position: fixed;
    top: -9999px;
    left: -9999px;
    min-width: 170px;
    background: #fff;
    border: 1px solid #e5ebf0;
    border-radius: 14px;
    box-shadow: 0 16px 30px rgba(31,41,55,0.14);
    padding: 6px;
    display: block;
    visibility: hidden;
    opacity: 0;
    pointer-events: none;
    z-index: 9999;
    max-height: 220px;
    overflow-y: auto;
    transform: translateY(6px) scale(0.98);
    transform-origin: top right;
    transition: opacity 0.14s ease, transform 0.14s ease, visibility 0.14s ease;
  }

  .row-menu.is-open {
    visibility: visible;
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0) scale(1);
  }

  .row-menu.open-up {
    transform-origin: bottom right;
  }

  .row-menu a {
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

  .row-menu a:hover {
    background: #f3f4f6;
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

  .chat-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .back-to-list {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #e5ebf0;
    background: #fff;
    color: #374151;
    border-radius: 12px;
    padding: 0.6rem 0.9rem;
    font-weight: 800;
    text-decoration: none;
  }

  .back-to-list:hover {
    background: #f8fafc;
    color: #374151;
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
    padding: 16px 20px;
    border-top: 1px solid #f1f3f5;
    background: #fff;
  }

  .composer-wrap {
    position: relative;
    max-width: 100%;
    display: flex;
    flex-direction: column;
  }

  .composer-attach {
    height: 38px;
    width: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 1.2rem;
    cursor: pointer;
    transition: color 0.2s, background 0.2s;
    border-radius: 50%;
    flex-shrink: 0;
    margin-bottom: 1px;
    margin-right: 8px;
  }

  .composer-attach:hover {
    color: #4CAF50;
    background: #f1f3f5;
  }

  .composer-shell {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 24px;
    display: flex;
    align-items: flex-end;
    padding: 6px 16px 6px 8px;
    min-height: 40px;
    transition: all 0.2s ease;
  }

  .composer-shell:focus-within {
    border-color: #4CAF50;
    box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
    background: #fff;
  }

  .composer-wrap textarea {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    resize: none;
    width: 100%;
    min-height: 40px;
    max-height: 120px;
    padding: 8px 0;
    margin: 0;
    margin-right: 12px;
    font-size: 0.95rem;
    color: #343a40;
    line-height: 1.5;
    outline: none;
    align-self: center;
    font-family: inherit;
  }

  .composer-wrap textarea::placeholder {
    color: #adb5bd;
  }

  .composer-send {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 50%;
    background: #4CAF50;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.2s ease, background 0.2s;
    margin-bottom: 1px;
  }

  .composer-send:hover {
    background: #43a047;
    transform: scale(1.05);
  }

  .composer-files {
    position: absolute;
    bottom: 2px;
    left: 48px;
    right: 6px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    pointer-events: none;
  }

  .composer-file-chip {
    padding: 1px 6px;
    border-radius: 4px;
    background: rgba(255,255,255,0.6);
    color: #4b5563;
    font-size: 0.65rem;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
    pointer-events: auto;
  }

  .composer-wrap input[type="file"] {
    display: none;
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

  .ai-suggestion {
    position: absolute;
    top: -30px;
    left: 48px;
    background: rgba(255, 255, 255, 0.95);
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.8rem;
    color: #4CAF50;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: none;
    pointer-events: none;
    z-index: 10;
  }

  .ai-suggestion.visible {
    display: block;
    animation: fadeIn 0.2s ease-out;
  }

  .ai-suggestion span.tab-key {
    background: #edf1f5;
    color: #2D3436;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    margin-right: 6px;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 991px) {
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
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="comm-page">
        <div class="comm-panel">
          <?php if (!$selectedConversation): ?>
            <div class="dashboard-wrap">
              <div class="stat-grid">
                <div class="stat-card">
                  <div class="stat-icon icon-red"><i class="fas fa-user-plus"></i></div>
                  <div class="stat-card-title">Total Conversations</div>
                  <div class="stat-card-value"><?= $statTotalConvos ?></div>
                  <div class="stat-card-trend <?= $trendTotalConvos['dir'] ?>"><i class="fas fa-arrow-trend-<?= $trendTotalConvos['dir'] ?>" style="color: <?= $trendTotalConvos['dir'] === 'up' ? '#10b981' : '#ef4444' ?>;"></i> <span><?= $trendTotalConvos['pct'] ?>%</span> VS PREV. 28 DAYS</div>
                </div>
                <div class="stat-card">
                  <div class="stat-icon icon-green"><i class="fas fa-star"></i></div>
                  <div class="stat-card-title">Active Conversations</div>
                  <div class="stat-card-value"><?= $statActiveConvos ?></div>
                  <div class="stat-card-trend <?= $trendActiveConvos['dir'] ?>"><i class="fas fa-arrow-trend-<?= $trendActiveConvos['dir'] ?>" style="color: <?= $trendActiveConvos['dir'] === 'up' ? '#10b981' : '#ef4444' ?>;"></i> <span><?= $trendActiveConvos['pct'] ?>%</span> VS PREV. 28 DAYS</div>
                </div>
                <div class="stat-card">
                  <div class="stat-icon icon-blue"><i class="fas fa-fire"></i></div>
                  <div class="stat-card-title">Total Messages</div>
                  <div class="stat-card-value"><?= number_format($statTotalMessages) ?></div>
                  <div class="stat-card-trend <?= $trendTotalMessages['dir'] ?>"><i class="fas fa-arrow-trend-<?= $trendTotalMessages['dir'] ?>" style="color: <?= $trendTotalMessages['dir'] === 'up' ? '#10b981' : '#ef4444' ?>;"></i> <span><?= $trendTotalMessages['pct'] ?>%</span> VS PREV. 28 DAYS</div>
                </div>
                <div class="stat-card">
                  <div class="stat-icon icon-orange"><i class="fas fa-clock"></i></div>
                  <div class="stat-card-title">AI Bot Replies</div>
                  <div class="stat-card-value"><?= $statBotReplies ?></div>
                  <div class="stat-card-trend <?= $trendBotReplies['dir'] ?>"><i class="fas fa-arrow-trend-<?= $trendBotReplies['dir'] ?>" style="color: <?= $trendBotReplies['dir'] === 'up' ? '#10b981' : '#ef4444' ?>;"></i> <span><?= $trendBotReplies['pct'] ?>%</span> VS PREV. 28 DAYS</div>
                </div>
              </div>

              <div class="chart-card">
                <div class="chart-header">
                  <div>
                    <div class="chart-title">Messages Traffic</div>
                    <div class="chart-value">
                      <?= number_format($statTotalMessages) ?> 
                      <div class="chart-trend">
                        <span><i class="fas fa-arrow-trend-<?= $chartDayDir ?>" style="color:<?= $chartDayDir === 'up' ? '#10b981' : '#ef4444' ?>;"></i> <?= abs($chartDayTrend) ?>%</span>
                        <span style="color:#9ca3af;font-size:0.75rem;font-weight:600;">VS YESTERDAY</span>
                      </div>
                    </div>
                  </div>
                  <div class="chart-filters">
                    <div class="chart-filter">Day</div>
                    <div class="chart-filter active">Week</div>
                    <div class="chart-filter">Month</div>
                  </div>
                </div>
                <div class="chart-container">
                  <canvas id="trafficChart"></canvas>
                </div>
              </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
              document.addEventListener("DOMContentLoaded", function() {
                var ctx = document.getElementById('trafficChart').getContext('2d');
                var gradient = ctx.createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

                new Chart(ctx, {
                  type: 'line',
                  data: {
                    labels: ['12–2 AM', '2–4 AM', '4–6 AM', '6–8 AM', '8–10 AM', '10–12 AM', '12–2 PM', '2–4 PM', '4–6 PM', '6–8 PM', '8–10 PM', '10–12 PM'],
                    datasets: [{
                      label: 'Messages',
                      data: <?= json_encode($chartData) ?>,
                      borderColor: '#3b82f6',
                      borderWidth: 2,
                      backgroundColor: gradient,
                      fill: true,
                      tension: 0.4,
                      pointRadius: 0,
                      pointHoverRadius: 4
                    }]
                  },
                  options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                    scales: {
                      x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                      y: { grid: { borderDash: [4, 4], color: '#f3f4f6' }, ticks: { color: '#9ca3af', font: { size: 10 }, stepSize: 10 } }
                    }
                  }
                });
              });
            </script>

            <div class="comm-panel-header">
              <h2 class="comm-title">Conversations</h2>
            </div>

            <div class="conversation-search-wrap">
              <input type="text" id="conversationSearch" class="form-control conversation-search" placeholder="Rechercher">
            </div>

            <div class="conversation-list" id="conversationList">
              <?php if (empty($conversations)): ?>
                <div class="empty-box">
                  <i class="fas fa-comments"></i>
                  <div>Aucune conversation disponible.</div>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="conversation-table">
                  <thead>
                    <tr>
                      <th>Conversation</th>
                      <th>Enfant</th>
                      <th>Status</th>
                      <th class="text-end"></th>
                    </tr>
                  </thead>
                    <tbody>
                      <?php foreach ($conversations as $conversation): ?>
                        <?php
                          $rowParentName = trim(($conversation->parent_prenom ?? '') . ' ' . ($conversation->parent_nom ?? ''));
                          $rowStaffName = trim(($conversation->staff_prenom ?? '') . ' ' . ($conversation->staff_nom ?? ''));
                          $rowChildNames = trim((string) ($conversation->child_names ?? ''));
                          $rowIsArchived = ($conversation->status ?? '') === 'archived';
                          $rowAlertMessages = (int) ($conversation->alert_messages_count ?? 0);
                          $rowLink = '/ProjetCommunication/view/back/communication_Backend.php?id=' . (int) $conversation->id;
                          $rowMenuId = 'row-menu-' . (int) $conversation->id;
                          $rowStatusLink = '/ProjetCommunication/controller/updateConversationStatus.php?id=' . (int) $conversation->id
                            . '&action=' . ($rowIsArchived ? 'restore' : 'archive')
                            . '&redirect=../view/back/communication_Backend.php';
                        ?>
                      <tr
                        class="conversation-row"
                        data-href="<?= htmlspecialchars($rowLink) ?>"
                        data-search="<?= htmlspecialchars(strtolower($rowParentName . ' ' . $rowStaffName . ' ' . $rowChildNames . ' ' . ($conversation->status ?? ''))) ?>"
                      >
                        <td>
                          <div class="conversation-main-title"><?= htmlspecialchars($rowStaffName !== '' ? $rowStaffName : 'Conversation') ?></div>
                          <?php if ($rowParentName !== ''): ?>
                            <div class="conversation-main-sub" style="color:#94a3b8;font-weight:700;"><?= htmlspecialchars($rowParentName) ?></div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="conversation-main-sub child"><?= htmlspecialchars($rowChildNames !== '' ? $rowChildNames : 'Aucun') ?></div>
                        </td>
                        <td>
                          <span class="conversation-pill <?= $rowIsArchived ? 'archived' : 'active' ?>">
                            <i class="fas <?= $rowIsArchived ? 'fa-box-archive' : 'fa-comments' ?>"></i>
                            <?= $rowIsArchived ? 'Archivee' : 'Active' ?>
                            </span>
                            <?php if ($rowAlertMessages > 0): ?>
                              <div class="mt-2">
                                <span class="conversation-pill alert">
                                  <i class="fas fa-bell"></i>
                                <?= $rowAlertMessages > 1 ? $rowAlertMessages . ' msgs' : 'Signal' ?>
                                </span>
                              </div>
                            <?php endif; ?>
                        </td>
                        <td class="row-actions">
                          <button type="button" class="row-menu-trigger" data-row-menu="<?= htmlspecialchars($rowMenuId) ?>" aria-label="Actions">
                            <i class="fas fa-ellipsis-h"></i>
                          </button>
                          <div class="row-menu" id="<?= htmlspecialchars($rowMenuId) ?>">
                              <a href="<?= $rowStatusLink ?>">
                                <i class="fas <?= $rowIsArchived ? 'fa-box-open' : 'fa-box-archive' ?>"></i>
                                <?= $rowIsArchived ? 'Desarchiver' : 'Archiver' ?>
                              </a>
                              <a href="/ProjetCommunication/controller/deleteConversation.php?id=<?= (int) $conversation->id ?>" onclick="return confirm('Supprimer cette conversation ?')">
                                <i class="fas fa-trash"></i>
                                Supprimer
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="chat-header">
              <div>
                <a href="/ProjetCommunication/view/back/communication_Backend.php" class="back-to-list">
                  <i class="fas fa-arrow-left"></i>
                  Retour a la liste
                </a>
                <h2 class="chat-user mt-3"><?= htmlspecialchars($staffName !== '' ? $staffName : 'Conversation') ?></h2>
                <div class="chat-subtext" style="color:#4CAF50;font-weight:800;"><?= htmlspecialchars($childNames !== '' ? $childNames : 'Aucun') ?></div>
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
                  <div>Aucun.</div>
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
                        <div class="chat-flag"><i class="fas fa-bell"></i> Signale</div>
                      <?php endif; ?>
                      <div class="chat-time"><?= $message->created_at ?? '-' ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <form method="POST" action="/ProjetCommunication/controller/storeMessage.php" class="composer" enctype="multipart/form-data">
              <input type="hidden" name="conversation_id" value="<?= $selectedConversation->id ?>">
              <input type="hidden" name="sender_id" value="<?= $adminSenderId ?>">
              <input type="hidden" name="sender_role" value="admin">
              <input type="hidden" name="redirect_to" value="../view/back/communication_Backend.php?id=<?= $selectedConversation->id ?>">
              
              <div class="composer-wrap">
                <div id="aiSuggestionBox" class="ai-suggestion"></div>
                <input type="file" id="composerFiles" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.png,.jpg,.jpeg,.webp" style="display: none;">
                
                <div class="composer-shell">
                  <label for="composerFiles" class="composer-attach" aria-label="Ajouter des fichiers">
                    <i class="fas fa-plus"></i>
                  </label>
                  <textarea name="body" class="form-control" rows="1" placeholder="Message..." required></textarea>
                  <button type="submit" class="composer-send" aria-label="Envoyer le message">
                    <i class="fas fa-arrow-up"></i>
                  </button>
                </div>
                
                <div class="composer-files" id="composerFilesList"></div>
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
var conversationRows = document.querySelectorAll('.conversation-row');
var composerFiles = document.getElementById('composerFiles');
var composerFilesList = document.getElementById('composerFilesList');

if (conversationSearch) {
  conversationSearch.addEventListener('input', function() {
    var value = this.value.toLowerCase().trim();
    conversationRows.forEach(function(row) {
      row.style.display = row.dataset.search.indexOf(value) !== -1 ? 'table-row' : 'none';
    });
  });
}

if (composerFiles && composerFilesList) {
  composerFiles.addEventListener('change', function() {
    composerFilesList.innerHTML = '';

    Array.prototype.slice.call(this.files || []).forEach(function(file) {
      var chip = document.createElement('span');
      chip.className = 'composer-file-chip';
      chip.textContent = file.name;
      composerFilesList.appendChild(chip);
    });
  });
}

conversationRows.forEach(function(row) {
  row.addEventListener('click', function(e) {
    if (e.target.closest('.row-actions')) {
      return;
    }

    window.location.href = this.dataset.href;
  });
});

document.querySelectorAll('.row-menu-trigger').forEach(function(button) {
  button.addEventListener('click', function(e) {
    e.stopPropagation();
    var targetId = this.getAttribute('data-row-menu');
    var menu = document.getElementById(targetId);

    document.querySelectorAll('.row-menu.is-open').forEach(function(openMenu) {
      if (openMenu !== menu) {
        openMenu.classList.remove('is-open');
      }
    });

    if (menu) {
      var isOpening = !menu.classList.contains('is-open');

      document.querySelectorAll('.row-menu.is-open').forEach(function(openMenu) {
        if (openMenu !== menu) {
          openMenu.classList.remove('is-open', 'open-up');
          openMenu.style.top = '';
          openMenu.style.left = '';
          openMenu.style.maxHeight = '';
        }
      });

      if (isOpening) {
        menu.style.visibility = 'hidden';
        menu.style.opacity = '0';
        menu.style.top = '-9999px';
        menu.style.left = '-9999px';

        var triggerRect = this.getBoundingClientRect();
        var menuRect = menu.getBoundingClientRect();
        var menuWidth = menuRect.width || 170;
        var menuHeight = menuRect.height || 220;
        var gap = 8;
        var viewportPadding = 8;
        var spaceBelow = window.innerHeight - triggerRect.bottom;
        var spaceAbove = triggerRect.top;
        var openDown = spaceBelow >= menuHeight + gap || spaceBelow >= spaceAbove;

        var top = openDown
          ? triggerRect.bottom + gap
          : Math.max(viewportPadding, triggerRect.top - menuHeight - gap);

        var left = triggerRect.right - menuWidth + 8;
        var maxLeft = window.innerWidth - menuWidth - viewportPadding;

        if (left > maxLeft) {
          left = maxLeft;
        }

        if (left < viewportPadding) {
          left = viewportPadding;
        }

        if (top < viewportPadding) {
          top = viewportPadding;
        }

        var availableHeight = openDown
          ? window.innerHeight - top - viewportPadding
          : triggerRect.top - viewportPadding;

        menu.classList.toggle('open-up', !openDown);
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
        menu.style.maxHeight = Math.max(140, availableHeight) + 'px';

        menu.classList.add('is-open');
        requestAnimationFrame(function() {
          menu.style.visibility = 'visible';
          menu.style.opacity = '1';
        });
      } else {
        menu.classList.remove('is-open', 'open-up');
        menu.style.top = '';
        menu.style.left = '';
        menu.style.maxHeight = '';
        menu.style.visibility = '';
        menu.style.opacity = '';
      }
    }
  });
});

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
    document.querySelectorAll('.row-menu.is-open').forEach(function(menu) {
      menu.classList.remove('is-open', 'open-up');
      menu.style.top = '';
      menu.style.left = '';
      menu.style.maxHeight = '';
      menu.style.visibility = '';
      menu.style.opacity = '';
    });
    document.querySelectorAll('.message-menu.is-open').forEach(function(menu) {
      menu.classList.remove('is-open');
    });
  });

  // AI Autocomplete Logic
  var bodyInput = document.querySelector('textarea[name="body"]');
  var aiBox = document.getElementById('aiSuggestionBox');
  var typingTimer;
  var currentSuggestion = '';

  if (bodyInput && aiBox) {
    bodyInput.addEventListener('keyup', function(e) {
      clearTimeout(typingTimer);
      
      if (e.key === 'Tab' && currentSuggestion !== '') {
        e.preventDefault();
        var currentText = bodyInput.value;
        if (!currentText.endsWith(' ') && !currentSuggestion.startsWith(' ') && currentText.length > 0) {
          bodyInput.value += ' ' + currentSuggestion;
        } else {
          bodyInput.value += currentSuggestion;
        }
        
        currentSuggestion = '';
        aiBox.classList.remove('visible');
        return;
      }
      
      aiBox.classList.remove('visible');
      currentSuggestion = '';

      if (bodyInput.value.trim().length > 5) {
        typingTimer = setTimeout(fetchAiSuggestion, 600); // 600ms debounce
      }
    });

    bodyInput.addEventListener('keydown', function(e) {
      if (e.key === 'Tab' && currentSuggestion !== '') {
        e.preventDefault(); // Prevent default tabbing behavior immediately
      }
    });

    function fetchAiSuggestion() {
      var text = bodyInput.value;
      
      var parentName = <?= json_encode($parentName ?? '') ?>;
      var childName = <?= json_encode($childNames ?? '') ?>;
      
      var chatElements = document.querySelectorAll('.chat-thread .chat-bubble');
      var recentChats = Array.from(chatElements).slice(-3).map(function(el) {
          var sender = el.querySelector('.chat-meta') ? el.querySelector('.chat-meta').innerText : 'User';
          // Find the text content div (it's the one after chat-meta)
          var msgDiv = el.querySelectorAll('div')[1];
          var msg = msgDiv ? msgDiv.innerText : '';
          return sender + ': ' + msg;
      }).join('\n');

      fetch('/ProjetCommunication/controller/ai_autocomplete.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            text: text,
            parentName: parentName,
            childName: childName,
            chatHistory: recentChats
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.suggestion && data.suggestion.trim() !== '') {
          currentSuggestion = data.suggestion;
          aiBox.innerHTML = '<span class="tab-key">Tab</span>' + currentSuggestion;
          aiBox.classList.add('visible');
        }
      })
      .catch(error => console.error('Error fetching AI suggestion:', error));
    }
  }
</script>
