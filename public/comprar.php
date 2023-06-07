<?php session_start() ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Comprar</title>
</head>

<body>
    <?php require '../vendor/autoload.php';


    if (!\App\Tablas\Usuario::esta_logueado()) {
        return redirigir_login();
    }

    $carrito = unserialize(carrito());
    $ids = implode(', ', $carrito->getIds());
    $where = "WHERE id IN (" . $ids . ")";


    if (obtener_post('_testigo') !== null) {
        $pdo = conectar();
        $sent = $pdo->prepare("SELECT *
                                 FROM articulos
                                $where");
        $sent->execute();

        foreach ($sent->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($fila['stock'] < $carrito->getLinea($fila['id'])->getCantidad()) {
                $_SESSION['error'] = 'No hay existencias suficientes para crear la factura.';
                return volver();
            }
        }
        // Crear factura
        if (obtener_post('_puntos') !== null) {

            $usuario = \App\Tablas\Usuario::logueado();
            $usuario_id = $usuario->id;
            $sent2 = $pdo->query("SELECT puntos FROM usuarios WHERE id = $usuario_id");
            $res = $sent2->fetch();
            $rebaja = $_SESSION['a'] - ($res[0] * 0.20);
            $restantes  = 0;
            if ($rebaja < 0) {
                $rebaja = 0;
                $restantes = $res[0] - ($_SESSION['a'] / 0.2);
            }

            $pdo->beginTransaction();
            $sent = $pdo->prepare("INSERT INTO facturas (usuario_id, total)
                                   VALUES (:usuario_id, :total)
                                   RETURNING id");
            $sent->execute([':usuario_id' => $usuario_id, ':total' => $rebaja]);
            $factura_id = $sent->fetchColumn();
            $lineas = $carrito->getLineas();
            $values = [];
            $execute = [':f' => $factura_id];
            $i = 1;

            $sent3 = $pdo->query("UPDATE usuarios 
                                  SET puntos = $restantes 
                                  WHERE id = $usuario_id");
            
        } else {
            $usuario = \App\Tablas\Usuario::logueado();
            $usuario_id = $usuario->id;
            $pdo->beginTransaction();
            $sent = $pdo->prepare("INSERT INTO facturas (usuario_id, total)
                                   VALUES (:usuario_id, :total)
                                   RETURNING id");
            $sent->execute([':usuario_id' => $usuario_id, ':total' => $_SESSION['a']]);
            $factura_id = $sent->fetchColumn();
            $lineas = $carrito->getLineas();
            $values = [];
            $execute = [':f' => $factura_id];
            $i = 1;

            $sent2 = $pdo->query("SELECT puntos FROM usuarios WHERE id = $usuario_id");
            $res = $sent2->fetch();

            $sumaPuntos = round($_SESSION['a'] + $res[0]);

            $sent3 = $pdo->prepare("UPDATE usuarios 
                                  SET puntos = :puntos
                                  WHERE id = :id");
            $sent3->execute([':puntos' => $sumaPuntos,
                             ':id' => $usuario_id]);
        }

        foreach ($lineas as $id => $linea) {
            $values[] = "(:a$i, :f, :c$i)";
            $execute[":a$i"] = $id;
            $execute[":c$i"] = $linea->getCantidad();
            $i++;
        }

        $values = implode(', ', $values);
        $sent = $pdo->prepare("INSERT INTO articulos_facturas (articulo_id, factura_id, cantidad)
                               VALUES $values");
        $sent->execute($execute);
        foreach ($lineas as $id => $linea) {
            $cantidad = $linea->getCantidad();
            $sent = $pdo->prepare('UPDATE articulos
                                      SET stock = stock - :cantidad
                                    WHERE id = :id');
            $sent->execute([':id' => $id, ':cantidad' => $cantidad]);
        }
        $pdo->commit();
        $_SESSION['exito'] = 'La factura se ha creado correctamente.';
        unset($_SESSION['carrito']);
        unset($_SESSION['a']);
        return volver();
    }

    ?>

    <div class="container mx-auto">
        <?php require '../src/_menu.php' ?>
        <div class="overflow-y-auto py-4 px-3 bg-gray-50 rounded dark:bg-gray-800">
            <table class="mx-auto text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <th scope="col" class="py-3 px-6">Código</th>
                    <th scope="col" class="py-3 px-6">Descripción</th>
                    <th scope="col" class="py-3 px-6">Cantidad</th>
                    <th scope="col" class="py-3 px-6">Precio</th>
                    <th scope="col" class="py-3 px-6">Descuento</th>
                    <th scope="col" class="py-3 px-6">Importe</th>
                </thead>
                <tbody>
                    <?php $total = 0; ?>
                    <?php foreach ($carrito->getLineas() as $id => $linea) : ?>
                        <?php
                        $pdo = conectar();
                        $articulo = $linea->getArticulo();
                        $codigo = $articulo->getCodigo();
                        $cantidad = $linea->getCantidad();
                        $precio = $articulo->getPrecio();
                        $importe = $cantidad * $precio;
                        $descuento;

                        switch ($articulo->getDescuento()) {
                            case '2x1':
                                if ($cantidad % 2 == 0) {
                                    $importe = $importe / 2;
                                    $total += $importe;
                                    $_SESSION['a'] = $total;
                                } else {
                                    $f = ($cantidad - 1);
                                    $importe = (($f * $precio) / 2) + $precio;
                                    $total += $importe;
                                    $_SESSION['a'] = $total;
                                }
                                break;
                            case '20%':
                                $importe = $importe - ($importe * 0.20);
                                $total += $importe;
                                $_SESSION['a'] = $total;
                                break;

                            case 'Segunda Unidad 50%':
                                $importe = ($importe * $cantidad / 2) + ($cantidad / 2 * $precio / 2);
                                $total += $importe;
                                break;
                            case '':
                                $total += $importe;
                                $_SESSION['a'] = $total;
                                break;
                        }
                        ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="py-4 px-6"><?= $articulo->getCodigo() ?></td>
                            <td class="py-4 px-6"><?= $articulo->getDescripcion() ?></td>
                            <td class="py-4 px-6 text-center"><?= $cantidad ?></td>
                            <td class="py-4 px-6 text-center">
                                <?= dinero($precio) ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?= $articulo->getDescuento() ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?= dinero($importe) ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
                <tfoot>
                    <td colspan="3"></td>
                    <td class="text-center font-semibold">TOTAL:</td>
                    <td class="text-center font-semibold"><?= dinero($total) ?></td>
                </tfoot>
            </table>
            <form action="" method="POST" class="mx-auto flex mt-4">
                <input type="hidden" name="_testigo" value="1">
                <label>
                    <input type="radio" name="_puntos" value="puntos"> Utilizar puntos
                </label>
                <button type="submit" href="" class="mx-auto focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-900">Realizar pedido</button>
            </form>
        </div>
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>