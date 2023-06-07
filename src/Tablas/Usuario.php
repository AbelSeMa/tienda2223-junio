<?php
namespace App\Tablas;

use PDO;

class Usuario extends Modelo
{
    protected static string $tabla = 'usuarios';

    public $id;
    public $usuario;
    private $nombre;
    private $apellido;
    public $validado;
    public $puntos;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->usuario = $campos['usuario'];
        $this->nombre = $campos['nombre'];
        $this->apellido = $campos['apellido'];
        $this->puntos = $campos['puntos'];
        $this->validado = $campos['validado'];
    }

    public function es_admin(): bool
    {
        return $this->usuario == 'admin';
    }

    public static function esta_logueado(): bool
    {
        return isset($_SESSION['login']);
    }

    public static function logueado(): ?static
    {
        return isset($_SESSION['login']) ? unserialize($_SESSION['login']) : null;
    }

    public static function comprobar($login, $password, ?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();

        $sent = $pdo->prepare('SELECT *
                                 FROM usuarios
                                WHERE usuario = :login');
        $sent->execute([':login' => $login]);
        $fila = $sent->fetch(PDO::FETCH_ASSOC);

        if ($fila === false) {
            return false;
        }

        return password_verify($password, $fila['password'])
            ? new static($fila)
            : false;
    }

    public static function existe($login, ?PDO $pdo = null): bool
    {
        return $login == '' ? false :
            !empty(static::todos(
                ['usuario = :usuario'],
                [':usuario' => $login],
                $pdo
            ));
    }

    public static function registrar($login, $password, $nombre, $apellido, ?PDO $pdo = null)
    {
        $sent = $pdo->prepare('INSERT INTO usuarios (usuario, password, nombre, apellido, validado)
                               VALUES (:login, :password, :nombre, :apellido, false)');
        $sent->execute([
            ':login' => $login,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':nombre' => $nombre,
            ':apellido' => $apellido
        ]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNombre() 
    {
        return $this->nombre;
    }

    public function getApellido() 
    {
        return $this->apellido;
    }

    public function getUsuario()
    {
        return $this->usuario;
    }

    public function getPuntos()
    {
        return $this->puntos;
    }
}
