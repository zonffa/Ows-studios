controladorUsuario.php
<?php
require_once 'modeloUsuario.php';

class ControladorUsuario {
    private $modelo;

    public function __construct() {
        $this->modelo = new ModeloUsuario();
    }

    public function crearUsuario($datos) {
          return $this->modelo->insertarUsuario( $datos['nombre'],  $datos['email'] );
    }
}