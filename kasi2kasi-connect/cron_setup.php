<?php
/**
 * Cron Job Setup Helper
 * 
 * This file provides instructions for setting up automated stock checks.
 * For manual testing, simply visit: stock_checker.php
 */

require_once "includes/db.php";

// Run the stock checker now
include "stock_checker.php";

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stock Checker Setup</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #eee; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: #2a2a2a; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        code { background: #333; padding: 2px 6px; border-radius: 6px; color: #f7c948; }
        pre { background: #333; padding: 15px; border-radius: 12px; overflow-x: auto; }
        h1 { color: #f7c948; }
        .success { color: #22c55e; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Stock Checker Setup</h1>
    
    <div class="card">
        <h2>📊 Last Run Results</h2>
        <pre><?php
        $result = file_get_contents("stock_checker.php");
        echo "Stock checker executed at: " . date("Y-m-d H:i:s") . "\n";
        ?></pre>
    </div>
    
    <div class="card">
        <h2>⏰ Setting up Automatic Stock Checks (Cron Job)</h2>
        <p>To run stock checks automatically every hour, add this line to your server's crontab:</p>
        <pre>0 * * * * php <?= __DIR__ ?>/stock_checker.php</pre>
        
        <h3>If using cPanel:</h3>
        <ol>
            <li>Log into cPanel</li>
            <li>Find "Cron Jobs"</li>
            <li>Select "Once per hour"</li>
            <li>Enter command: <code>php <?= __DIR__ ?>/stock_checker.php</code></li>
            <li>Click "Add"</li>
        </ol>
        
        <h3>If using InfinityFree / Free Hosting:</h3>
        <p>Most free hosting doesn't support cron jobs. Instead, you can:</p>
        <ul>
            <li>Manually visit <code><?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/stock_checker.php</code> daily</li>
            <li>Or use a free cron job service like <strong>cron-job.org</strong> to ping the URL hourly</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>✅ What This Does Automatically</h2>
        <ul>
            <li>✓ Checks for products with ≤ 10 units in stock</li>
            <li>✓ Creates low stock notifications for sellers</li>
            <li>✓ Detects products with 0 units (out of stock)</li>
            <li>✓ Automatically marks out of stock products as 'sold'</li>
            <li>✓ Creates out of stock notifications</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>🔗 Test Manually</h2>
        <p>Click below to run the stock checker right now:</p>
        <a href="stock_checker.php" style="background:#f7c948; color:#1a1a1a; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold;">Run Stock Checker Now →</a>
    </div>
</div>
</body>
</html>