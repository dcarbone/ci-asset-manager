<?php /** @var \MY_Controller $this */?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AssetManager Demo Application</title>
    <?php
    echo $this->asset_manager->generate_queue_asset_output();
    ?>
</head>
<body>
<div id="container">
    <div id="header">
        <div id="title">
            <h1 class="title">Asset Manager</h1>
            <h3 class="subtitle">An asset management library for CodeIgniter 2 applications</h3>
        </div>
        <ul id="navigation">
            <li>
                <a href="<?php echo base_url(); ?>">Home</a>
            </li>
            <li>
                <a href=""<?php echo base_url('welcome/queue_demo'); ?>">Queue Demo</a>
            </li>
        </ul>
    </div>
    <div id="body">