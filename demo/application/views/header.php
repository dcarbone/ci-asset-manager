<?php /** @var \MY_Controller $this */?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AssetManager Demo Application</title>
    <?php
    echo $this->asset_manager->generate_asset_tag('js/jquery-1.11.1.min.js', true);
    echo $this->asset_manager->generate_asset_tag('css/basic.css', true);
    ?>
</head>
<body>
<div id="container">
    <?php $this->load->view('top_navigation'); ?>
    <div id="body">