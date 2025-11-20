<?php
require_once 'controladorUsuario.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$metodo = $_SERVER['REQUEST_METHOD'];
$controlador = new ControladorUsuario();

switch ($metodo) {
    case 'POST':
        echo json_encode($controlador->crearUsuario($input));
        break
}