<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Configurar el cliente de Google
$client = new Google_Client();
$client->setClientId('TU_CLIENT_ID');
$client->setClientSecret('TU_CLIENT_SECRET');
$client->setRedirectUri('http://localhost:8080/callback.php');
$client->addScope('email');
$client->addScope('profile');

// Si no tenemos el código de autorización, redirigir a Google
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
}

// Si tenemos el código, obtener el token
try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    echo "Token de acceso: " . $token['id_token'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 