<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TinyTrack - Communication Backend</title>
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Google Fonts (playful) -->
  <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
  <style>
    :root {
      --tt-green: #4CAF50;
      --tt-green-dark: #388E3C;
      --tt-blue: #5B9BD5;
      --tt-yellow: #FFD93D;
      --tt-pink: #FF8FAB;
      --tt-orange: #FFA726;
      --tt-purple: #9C7CDB;
      --tt-bg: #F0F7FF;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--tt-bg);
    }

    .content-wrapper {
      background: var(--tt-bg) !important;
      position: relative;
      overflow: visible;
    }
    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-6px); }
    }
    @keyframes wiggle {
      0%, 100% { transform: rotate(0deg); }
      25% { transform: rotate(3deg); }
      75% { transform: rotate(-3deg); }
    }

    /* === SIDEBAR === */
    .main-sidebar {
      background: linear-gradient(180deg, #2E7D32 0%, #1B5E20 100%) !important;
    }
    .brand-link {
      background: rgba(255,255,255,0.1) !important;
      border-bottom: 1px solid rgba(255,255,255,0.15) !important;
      padding: 12px 15px !important;
      display: flex !important;
      align-items: center !important;
      gap: 10px;
    }
    .brand-link .brand-text {
      font-family: 'Fredoka One', cursive !important;
      color: #fff !important;
      font-size: 1.3rem;
    }
    .brand-link .brand-image {
      width: 38px; height: 38px;
      border-radius: 10px;
      object-fit: contain;
      margin-left: 0; margin-right: 5px;
      max-height: 38px;
      animation: wiggle 4s ease-in-out infinite;
    }

    .sidebar .nav-link {
      font-weight: 600;
      border-radius: 12px !important;
      margin: 3px 8px;
      transition: all 0.25s;
    }
    .sidebar .nav-link:hover {
      background: rgba(255,255,255,0.15) !important;
      transform: translateX(5px);
    }
    .sidebar .nav-header {
      color: rgba(255,255,255,0.5) !important;
      font-family: 'Fredoka One', cursive;
      font-size: 0.7rem;
      letter-spacing: 0.1em;
      padding-left: 20px;
    }
    .sidebar .nav-icon { font-size: 1.1rem !important; }
    .user-panel .info a { font-family: 'Fredoka One', cursive; }

    /* === NAVBAR === */
    .main-header {
      border-bottom: 4px solid transparent;
      border-image: linear-gradient(90deg, var(--tt-green), var(--tt-blue), var(--tt-yellow), var(--tt-pink)) 1;
      background: #fff;
    }

    /* === CONTENT === */
    .content-header h1 {
      font-family: 'Fredoka One', cursive;
      color: #2D3436;
    }

    /* === CARDS === */
    .card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(0,0,0,0.08);
    }
    .card-header { border-radius: 20px 20px 0 0 !important; }
    .card-success .card-header { background: linear-gradient(135deg, #4CAF50, #81C784) !important; }
    .card-warning .card-header { background: linear-gradient(135deg, #FFA726, #FFCC80) !important; }
    .card-info .card-header { background: linear-gradient(135deg, #5B9BD5, #90CAF9) !important; }
    .card-title {
      font-family: 'Fredoka One', cursive;
      font-size: 1rem;
    }

    /* === SMALL BOXES === */
    .small-box {
      border-radius: 20px;
      overflow: hidden;
      transition: transform 0.2s;
      position: relative;
    }
    .small-box:hover { transform: scale(1.03); }
    .small-box .inner h3 {
      font-family: 'Fredoka One', cursive;
      font-size: 2.5rem;
    }
    .small-box .icon i { animation: bounce 3s ease-in-out infinite; }
    .small-box.bg-success {
      background: linear-gradient(135deg, #4CAF50 0%, #81C784 50%, #A5D6A7 100%) !important;
    }
    .small-box.bg-info {
      background: linear-gradient(135deg, #5B9BD5 0%, #90CAF9 50%, #BBDEFB 100%) !important;
    }
    .small-box.bg-warning {
      background: linear-gradient(135deg, #FFA726 0%, #FFCC80 50%, #FFE0B2 100%) !important;
    }
    .small-box.bg-danger {
      background: linear-gradient(135deg, #FF8FAB 0%, #F48FB1 50%, #F8BBD0 100%) !important;
    }
    .small-box::before {
      content: '';
      position: absolute;
      top: -15px; right: -15px;
      width: 60px; height: 60px;
      background: rgba(255,255,255,0.15);
      border-radius: 50%;
    }
    .small-box::after {
      content: '';
      position: absolute;
      bottom: -10px; left: 20px;
      width: 40px; height: 40px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
    }

    /* === TABLE === */
    .table { border-radius: 14px; overflow: hidden; }
    .table thead th {
      font-family: 'Fredoka One', cursive;
      font-weight: 400;
      font-size: 0.85rem;
      letter-spacing: 0.02em;
      background: #2D3436;
      color: #fff;
      border: none;
      padding: 0.8rem;
    }
    .table tbody td {
      vertical-align: middle;
      padding: 0.7rem 0.8rem;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background: rgba(76, 175, 80, 0.03);
    }
    .table-hover tbody tr:hover {
      background: rgba(76, 175, 80, 0.08);
      transition: background 0.2s;
    }

    /* === BADGES (playful pill shapes) === */
    .badge-primary {
      background: linear-gradient(135deg, #5B9BD5, #64B5F6);
      border-radius: 20px; padding: 0.35rem 0.8rem; font-weight: 700;
    }
    .badge-danger {
      background: linear-gradient(135deg, #FF8FAB, #F48FB1);
      border-radius: 20px; padding: 0.35rem 0.8rem; font-weight: 700;
    }
    .badge-success {
      background: linear-gradient(135deg, #4CAF50, #81C784);
      border-radius: 20px; padding: 0.35rem 0.8rem; font-weight: 700;
    }
    .badge-secondary {
      background: linear-gradient(135deg, #B0BEC5, #CFD8DC);
      border-radius: 20px; padding: 0.35rem 0.8rem; font-weight: 700;
    }
    .badge-warning {
      background: linear-gradient(135deg, #FFD93D, #FFE082);
      color: #333; border-radius: 20px; padding: 0.35rem 0.8rem; font-weight: 700;
    }

    /* === BUTTONS (rounded candy style) === */
    .btn-success {
      background: linear-gradient(135deg, #4CAF50, #66BB6A);
      border: none; border-radius: 25px;
      font-weight: 700; padding: 0.5rem 1.3rem;
      transition: all 0.25s;
      box-shadow: 0 3px 10px rgba(76,175,80,0.2);
    }
    .btn-success:hover {
      background: linear-gradient(135deg, #388E3C, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(76,175,80,0.3);
    }
    .btn-warning {
      border-radius: 25px; font-weight: 700; border: none;
      background: linear-gradient(135deg, #FFA726, #FFB74D);
      box-shadow: 0 3px 10px rgba(255,167,38,0.2);
    }
    .btn-info {
      background: linear-gradient(135deg, #5B9BD5, #64B5F6);
      border: none; border-radius: 25px; font-weight: 700;
      box-shadow: 0 3px 10px rgba(91,155,213,0.2);
    }
    .btn-danger {
      border-radius: 25px; font-weight: 700; border: none;
      background: linear-gradient(135deg, #EF5350, #E57373);
      box-shadow: 0 3px 10px rgba(239,83,80,0.2);
    }
    .btn-default { border-radius: 25px; }
    .btn-sm { border-radius: 20px !important; padding: 0.3rem 0.6rem; }

    /* === FORM (soft rounded) === */
    .form-control {
      border-radius: 12px;
      border: 2px solid #E8E8E8;
      transition: all 0.25s;
      padding: 0.55rem 0.9rem;
    }
    .form-control:focus {
      border-color: var(--tt-green);
      box-shadow: 0 0 0 4px rgba(76,175,80,0.12);
    }
    .form-control.is-invalid { border-color: #FF6B6B; }
    .form-control.is-valid { border-color: var(--tt-green); }
    label {
      font-weight: 700;
      font-size: 0.9rem;
      color: #555;
    }

    /* === ALERTS (playful) === */
    .alert {
      border-radius: 16px;
      border: none;
      font-weight: 600;
      position: relative;
      overflow: hidden;
    }
    .alert::before {
      content: '';
      position: absolute;
      top: -10px; right: -10px;
      width: 40px; height: 40px;
      background: rgba(255,255,255,0.3);
      border-radius: 50%;
    }
    .alert-success {
      background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
      color: #2E7D32;
    }
    .alert-danger {
      background: linear-gradient(135deg, #FFEBEE, #FFCDD2);
      color: #C62828;
    }
    .alert-warning {
      background: linear-gradient(135deg, #FFF8E1, #FFECB3);
      color: #E65100;
    }

    /* === INFO BOX === */
    .info-box {
      border-radius: 18px;
      overflow: hidden;
      transition: transform 0.2s;
    }
    .info-box:hover { transform: scale(1.02); }
    .info-box-icon { border-radius: 18px 0 0 18px; }

    /* === FOOTER === */
    .main-footer {
      background: #fff;
      border-top: 4px solid transparent;
      border-image: linear-gradient(90deg, var(--tt-green), var(--tt-blue), var(--tt-yellow), var(--tt-pink)) 1;
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
      color: #888;
    }

    /* === DATATABLES fix === */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--tt-green) !important;
      border-color: var(--tt-green) !important;
      color: #fff !important;
      border-radius: 8px;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <span class="nav-link" style="font-family:'Fredoka One',cursive; color:var(--tt-green);">
        <i class="fas fa-comments"></i> Communication Backend
      </span>
    </li>
  </ul>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item">
      <span class="nav-link" style="font-weight:700;"><i class="fas fa-user-shield text-success"></i> Amen Allah Rajhi</span>
    </li>
  </ul>
</nav>

