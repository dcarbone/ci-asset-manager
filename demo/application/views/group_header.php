<?php /** @var \MY_Controller $this */?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logical Group Demo</title>
    <?php
    echo $this->asset_manager->generate_asset_tag('css/basic.css');
    echo $this->asset_manager->generate_asset_tag('js/jquery-1.11.1.min.js');
    echo $this->asset_manager->generate_logical_group_asset_output('noty');
    echo $this->asset_manager->generate_asset_tag('js/noty-woot.js');
    ?>
</head>
<body>
<div id="container">
    <?php $this->load->view('top_navigation'); ?>
    <div id="body">