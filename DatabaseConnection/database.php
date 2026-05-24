<?php
    class Database{

        private $host ="localhost";
        private $user = "root";
        private $password = "";
        private $dbname = "InformationManagement";

        public $conn;

        public function __construct(){
            
            try{
                $this->conn = new PDO("mysql:host=".$this->host . ";dbname=".$this->dbname,
                $this->user,$this->password);

                $this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
                #echo "Connection success";
            }catch(PDOException $e){
    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES);
    echo "<!doctype html>
<html lang=\"en\">
<head>
  <meta charset=\"utf-8\">
  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
  <title>No Database Connection</title>
  <style>
    body{margin:0;font-family:Segoe UI,Roboto,Arial,sans-serif;background:#111;color:#fff}
    .db-overlay{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);z-index:9999}
    .db-modal{background:#0f1720;color:#e6eef6;padding:28px 32px;border-radius:14px;max-width:540px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.8);text-align:center}
    .db-icon{width:120px;height:80px;margin:0 auto 16px;display:block;overflow:visible}
    .db-title{font-size:20px;margin:8px 0 4px;font-weight:600;color:#f1f5f9}
    .db-msg{font-size:14px;color:#94a3b8;margin:8px 0 0;line-height:1.6}
    .db-btn{margin-top:18px;display:inline-block;padding:10px 22px;border-radius:8px;background:#e74c3c;color:#fff;text-decoration:none;font-size:14px;font-weight:500;transition:background .2s}
    .db-btn:hover{background:#c0392b}
    .db-details{margin-top:14px;font-size:11px;color:#64748b;word-break:break-word;font-family:monospace;background:#080e14;padding:8px 12px;border-radius:6px;text-align:left}
    @keyframes flicker{0%,100%{opacity:1}25%{opacity:.5}50%{opacity:.9}75%{opacity:.4}}
    @keyframes spark{0%{opacity:1;transform:translate(0,0)}100%{opacity:0;transform:translate(var(--tx),var(--ty))}}
    @media(prefers-reduced-motion:no-preference){
      .arc{animation:flicker .2s steps(1) infinite}
      .s1{animation:spark .7s ease-out infinite;--tx:10px;--ty:-18px}
      .s2{animation:spark .9s ease-out .15s infinite;--tx:-12px;--ty:-22px}
      .s3{animation:spark .6s ease-out .3s infinite;--tx:16px;--ty:12px}
      .s4{animation:spark .8s ease-out .05s infinite;--tx:-14px;--ty:16px}
    }
  </style>
</head>
<body>
<div class=\"db-overlay\">
  <div class=\"db-modal\">

    <!-- Broken wire SVG icon -->
    <svg class=\"db-icon\" viewBox=\"0 0 200 100\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\">
      <defs>
        <linearGradient id=\"cl\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"0\">
          <stop offset=\"0%\" stop-color=\"#2a2a3a\"/><stop offset=\"100%\" stop-color=\"#444\"/>
        </linearGradient>
        <linearGradient id=\"cr\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"0\">
          <stop offset=\"0%\" stop-color=\"#444\"/><stop offset=\"100%\" stop-color=\"#2a2a3a\"/>
        </linearGradient>
        <filter id=\"g\"><feGaussianBlur stdDeviation=\"1.5\" result=\"b\"/><feMerge><feMergeNode in=\"b\"/><feMergeNode in=\"SourceGraphic\"/></feMerge></filter>
      </defs>

      <!-- Left cable -->
      <rect x=\"4\" y=\"40\" width=\"58\" height=\"20\" rx=\"10\" fill=\"url(#cl)\"/>
      <!-- Left plug body -->
      <rect x=\"58\" y=\"33\" width=\"22\" height=\"34\" rx=\"4\" fill=\"#1e2535\" stroke=\"#3a4a5a\" stroke-width=\".8\"/>
      <circle cx=\"65\" cy=\"41\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>
      <circle cx=\"65\" cy=\"59\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>

      <!-- Left frayed wires -->
      <path d=\"M80 42 C88 40, 90 35, 96 30\" fill=\"none\" stroke=\"#b5651d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M96 30 C98 28, 101 26, 103 23\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M80 45 C89 43, 92 39, 99 36\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M99 36 C102 34, 105 32, 107 29\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M80 50 C90 49, 94 47, 101 45\" fill=\"none\" stroke=\"#a0522d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M101 45 C104 43, 107 42, 110 40\" fill=\"none\" stroke=\"#f4a460\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>
      <path d=\"M80 55 C89 55, 92 58, 99 62\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M99 62 C102 64, 105 67, 107 70\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M80 58 C88 60, 90 65, 95 70\" fill=\"none\" stroke=\"#6b3a2a\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M95 70 C97 73, 100 76, 102 78\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>

      <!-- Right cable -->
      <rect x=\"138\" y=\"40\" width=\"58\" height=\"20\" rx=\"10\" fill=\"url(#cr)\"/>
      <!-- Right plug body -->
      <rect x=\"120\" y=\"33\" width=\"22\" height=\"34\" rx=\"4\" fill=\"#1e2535\" stroke=\"#3a4a5a\" stroke-width=\".8\"/>
      <circle cx=\"135\" cy=\"41\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>
      <circle cx=\"135\" cy=\"59\" r=\"3\" fill=\"#111\" stroke=\"#3a4a5a\" stroke-width=\".6\"/>

      <!-- Right frayed wires -->
      <path d=\"M120 42 C112 40, 110 35, 104 30\" fill=\"none\" stroke=\"#b5651d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M104 30 C102 28, 99 26, 97 23\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M120 45 C111 43, 108 39, 101 36\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M101 36 C98 34, 95 32, 93 29\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M120 50 C110 49, 106 47, 99 45\" fill=\"none\" stroke=\"#a0522d\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M99 45 C96 43, 93 42, 90 40\" fill=\"none\" stroke=\"#f4a460\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>
      <path d=\"M120 55 C111 55, 108 58, 101 62\" fill=\"none\" stroke=\"#8b4513\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M101 62 C98 64, 95 67, 93 70\" fill=\"none\" stroke=\"#cd853f\" stroke-width=\"1.4\" stroke-linecap=\"round\"/>
      <path d=\"M120 58 C112 60, 110 65, 105 70\" fill=\"none\" stroke=\"#6b3a2a\" stroke-width=\"2\" stroke-linecap=\"round\"/>
      <path d=\"M105 70 C103 73, 100 76, 98 78\" fill=\"none\" stroke=\"#daa520\" stroke-width=\"1.2\" stroke-linecap=\"round\"/>

      <!-- Lightning bolt arc in gap -->
      <g class=\"arc\" filter=\"url(#g)\">
        <path d=\"M97 28 L104 45 L99 50 L106 72\" fill=\"none\" stroke=\"#FFD700\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>
        <path d=\"M97 28 L104 45 L99 50 L106 72\" fill=\"none\" stroke=\"#FFFDE7\" stroke-width=\".8\" stroke-linecap=\"round\" stroke-linejoin=\"round\" opacity=\".9\"/>
      </g>

      <!-- Spark particles -->
      <circle class=\"s1\" cx=\"101\" cy=\"42\" r=\"1.8\" fill=\"#FFD700\"/>
      <circle class=\"s2\" cx=\"99\" cy=\"52\" r=\"1.4\" fill=\"#FFA500\"/>
      <circle class=\"s3\" cx=\"103\" cy=\"60\" r=\"1.6\" fill=\"#FFD700\"/>
      <circle class=\"s4\" cx=\"100\" cy=\"35\" r=\"1.2\" fill=\"#FFFDE7\"/>
    </svg>

    <div class=\"db-title\">No Database Connection</div>
    <div class=\"db-msg\">The application could not connect to the database.<br>Please check your database server and configuration.</div>
    <a class=\"db-btn\" href=\"#\" onclick=\"location.reload();return false;\">&#8635; Retry</a>
    <div class=\"db-details\">Error: " . $message . "</div>
  </div>
</div>
</body>
</html>";
    exit;
}

        }

    }

?>