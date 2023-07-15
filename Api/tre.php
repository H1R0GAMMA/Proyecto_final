<?php

include 'conexion.php';

$pdo = new Conexion();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['Id_producto'];
    $fechaVenta = $_POST['Fecha_venta'];
    $cantidad = $_POST['Cantidad_producto'];
    $precio = $_POST['Precio'];
    $nombreProducto = $_POST['Nombre_Producto'];

    // Verificar si el producto existe en la tabla "Registro_productos"
    $sqlVerificar = "SELECT COUNT(*) FROM Registro_productos WHERE Id = :Id";
    $stmtVerificar = $pdo->prepare($sqlVerificar);
    $stmtVerificar->bindValue(':Id', $id);
    $stmtVerificar->execute();

    if ($stmtVerificar->fetchColumn() > 0) {
        // El producto existe en la tabla "Registro_productos"
        // Verificar si hay suficiente cantidad en el inventario
        $sqlVerificarInventario = "SELECT Cantidad FROM Productos_inventario WHERE Id = :Id";
        $stmtVerificarInventario = $pdo->prepare($sqlVerificarInventario);
        $stmtVerificarInventario->bindValue(':Id', $id);
        $stmtVerificarInventario->execute();

        $cantidadDisponible = $stmtVerificarInventario->fetchColumn();

        if ($cantidad <= $cantidadDisponible) {
            // Restar la cantidad en la tabla "Productos_inventario"
            $sqlRestarCantidadInventario = "UPDATE Productos_inventario SET Cantidad = Cantidad - :Cantidad WHERE Id = :Id";
            $stmtRestarCantidadInventario = $pdo->prepare($sqlRestarCantidadInventario);
            $stmtRestarCantidadInventario->bindValue(':Cantidad', $cantidad);
            $stmtRestarCantidadInventario->bindValue(':Id', $id);
            $stmtRestarCantidadInventario->execute();

            // Insertar en la tabla "Actualizacion_Inventario"
            $sqlActualizacion = "INSERT INTO Actualizacion_Inventario (Id_producto, Cantidad, Fecha_de_compra, Numero_de_factura) 
                                VALUES (:Id, :Cantidad, :FechaVenta, :NumeroFactura)";
            $stmtActualizacion = $pdo->prepare($sqlActualizacion);
            $stmtActualizacion->bindValue(':Id', $id);
            $stmtActualizacion->bindValue(':Cantidad', -$cantidad); // Restamos la cantidad en la actualización
            $stmtActualizacion->bindValue(':FechaVenta', $fechaVenta);

            // Verificar si la clave "Numero_factura" está definida en $_POST
            if (isset($_POST['Numero_factura'])) {
                $numeroFactura = $_POST['Numero_factura'];
                $stmtActualizacion->bindValue(':NumeroFactura', $numeroFactura);
            } else {
                $stmtActualizacion->bindValue(':NumeroFactura', null); // Asignar null si no está definida
            }

            $stmtActualizacion->execute();

            // Insertar en la tabla "Ventas_Productos"
            $sqlInsertarVenta = "INSERT INTO Ventas_Productos (Id_producto, Fecha_venta, Cantidad_producto, Precio, Nombre_Producto) 
                                VALUES (:Id, :FechaVenta, :Cantidad, :Precio, :NombreProducto)";
            $stmtInsertarVenta = $pdo->prepare($sqlInsertarVenta);
            $stmtInsertarVenta->bindValue(':Id', $id);
            $stmtInsertarVenta->bindValue(':FechaVenta', $fechaVenta);
            $stmtInsertarVenta->bindValue(':Cantidad', $cantidad);
            $stmtInsertarVenta->bindValue(':Precio', $precio);
            $stmtInsertarVenta->bindValue(':NombreProducto', $nombreProducto);
            $stmtInsertarVenta->execute();

            // Calcular el total a pagar
			
            $totalPagar = $cantidad * $precio;

            header("HTTP/1.1 200 OK");
			echo json_encode("venta realizada con exito profesor ");
            echo json_encode(" Producto adquirido:" . $nombreProducto );
			echo json_encode(" Total a pagar: $" . $totalPagar);
            exit;
        } else {
            // No hay suficiente cantidad disponible en el inventario
            header("HTTP/1.1 400 Bad Request");
            echo json_encode("No hay suficiente cantidad disponible en el inventario");
            exit;
        }
    } else {
        // El producto no existe en la tabla "Registro_productos"
        header("HTTP/1.1 404 Not Found");
        echo json_encode("El producto no existe en el registro. Verifica el ID del producto.");
        exit;
    }
}

header("HTTP/1.1 400 Bad Request");

?>



