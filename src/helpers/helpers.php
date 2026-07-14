<?php
// src/helpers/helpers.php

require_once __DIR__ . '/../config/config.php';

function base_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset_url($path = '') {
    return base_url('public/' . ltrim($path, '/'));
}

function redirect($path) {
    header('Location: ' . base_url($path));
    exit();
}
?>