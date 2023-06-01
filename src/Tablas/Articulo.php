<?php

namespace App\Tablas;

use PDO;

class Articulo extends Modelo
{
    protected static string $tabla = 'articulos';

    public $id;
    private $codigo;
    private $descripcion;
    private $precio;
    private $stock;
    private $descuento;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->codigo = $campos['codigo'];
        $this->descripcion = $campos['descripcion'];
        $this->precio = $campos['precio'];
        $this->stock = $campos['stock'];
        $this->descuento = null;
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCodigo()
    {
        return $this->codigo;
    }

    public function getDescripcion()
    {
        return $this->descripcion;
    }

    public function getPrecio()
    {
        return $this->precio;
    }

    public function getStock()
    {
        return $this->stock;
    }

    public function getDescuento()
    {
        $pdo = $pdo ?? conectar();
        $sent = $pdo->prepare('SELECT descuentos.descuento FROM articulos 
                                      LEFT JOIN descuentos 
                                      ON articulos.descuento = descuentos.id
                                      WHERE articulos.id = :id');
        $sent->execute([':id' => $this->id]);
        $this->descuento = $sent->fetchColumn();
        
        return $this->descuento;
    }

}
