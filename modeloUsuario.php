<?php
require_once 'api.php';

class ModeloUsuario {
    private $db;

    public function __construct() {
        $this->db = new mysqli("localhost", "root","","prueba");
    }

    public function insertarUsuario($nombre, $email) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $email);

        if ($stmt->execute()) {
            return ['success' => true, 'id' => $stmt->insert_id];
        } else {
            return ['error' => 'Error al insertar usuario: ' . $stmt->error];
        }
    }
}