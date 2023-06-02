<?php

use App\Tablas\Factura;
use App\Tablas\Usuario;

session_start() ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/output.css" rel="stylesheet">
    <title>Perfil</title>
</head>

<body>
    <?php require '../vendor/autoload.php';

    if (!\App\Tablas\Usuario::esta_logueado()) {
        return redirigir_login();
    }

    $usuario = \App\Tablas\Usuario::logueado();
    $id = $usuario->id;

    ?>

    <div class="container mx-auto">
        <?php require_once '../src/_menu.php';

        $pdo = conectar();

        $sent = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');

        $sent->execute([':id' => $id]);

        $res = $sent->fetch();
        
        ?>

<div class="relative overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Usuario
                </th>
                <th scope="col" class="px-6 py-3">
                    Nombre
                </th>
                <th scope="col" class="px-6 py-3">
                    Apellido
                </th>
                <th scope="col" class="px-6 py-3">
                    Puntos
                </th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    <?= $res['usuario'] ?>
                </th>
                <td class="px-6 py-4">
                    <?= $res['nombre'] ?>
                </td>
                <td class="px-6 py-4">
                <?= $res['apellido'] ?>
                </td>
                <td class="px-6 py-4">
                <?= $res['puntos'] ?>
                </td>
            </tr>
            </tr>
        </tbody>
    </table>
</div>



       
    </div>
    <script src="/js/flowbite/flowbite.js"></script>
</body>

</html>