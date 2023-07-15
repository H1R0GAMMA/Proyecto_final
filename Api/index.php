<?php

include 'conexion.php';

$pdo=new Conexion();

if($_SERVER['REQUEST_METHOD']=='GET')
{
	if(isset($_GET['id']))
	{
		$sql=$pdo->prepare("SELECT * FROM registro_productos WHERE :id");
		$sql->bindValue(':id',$_GET['Id']);
		$sql->execute();
		$sql->setFetchMode(PDO::FETCH_ASSOC);
		header ("http/1.1 200 OK");
		echo json_encode($sql->fetchAll());
		exit;
	}
	else
	{
		$sql=$pdo->prepare("SELECT * FROM registro_productos");
		$sql->execute();
		$sql->setFetchMode(PDO::FETCH_ASSOC);
		header ("http/1.1 200 OK");
		echo json_encode($sql->fetchAll());
		exit;
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['Id'];
    $nombreProducto = $_POST['Nombre'];

    // Insertar en la tabla "registro_productos"
    $sqlRegistro = "INSERT INTO registro_productos (Id, Nombre, Marca, Presentación, Precio) VALUES (:Id, :Nom, :Mar, :Pre, :Precio)";
    $stmtRegistro = $pdo->prepare($sqlRegistro);
    $stmtRegistro->bindValue(':Id', $id);
    $stmtRegistro->bindValue(':Nom', $nombreProducto);
    $stmtRegistro->bindValue(':Mar', $_POST['Marca']);
    $stmtRegistro->bindValue(':Pre', $_POST['Presentación']);
    $stmtRegistro->bindValue(':Precio', $_POST['Precio']);
    $stmtRegistro->execute();

    // Obtener el último ID insertado en la tabla "registro_productos"
    $lastInsertId = $pdo->lastInsertId();

    // Insertar en la tabla "productos_inventario"
    $sqlInventario = "INSERT INTO productos_inventario (Id, Cantidad) VALUES (:Id, 0)";
    $stmtInventario = $pdo->prepare($sqlInventario);
    $stmtInventario->bindValue(':Id', $lastInsertId);
    $stmtInventario->execute();

    header("HTTP/1.1 200 OK");
    echo json_encode("PRODUCTOS AGREGADOS CORRECTAMENTE EN LA BASE DE DATOS");
    exit;
}



if($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    
    if(isset($data['Id'])) {
        $id = $data['Id'];

        $sql = "DELETE FROM registro_productos WHERE Id = :Id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':Id', $id);
        $stmt->execute();

        $rowCount = $stmt->rowCount();
        
        if($rowCount > 0) {
            header("HTTP/1.1 200 OK");
            echo json_encode("Producto eliminado correctamente");
            exit;
        } else {
            header("HTTP/1.1 404 Not Found");
            echo json_encode("No se encontró ningún producto con el ID proporcionado");
            exit;
        }
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Obtener los datos enviados en formato JSON
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Verificar si se proporcionó el ID
    if (isset($data['Id'])) {
        $id = $data['Id'];

        // Verificar si el registro existe en la base de datos
        $checkSql = "SELECT COUNT(*) FROM registro_productos WHERE Id = :Id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindValue(':Id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            // Actualizar el registro
            $sql = "UPDATE registro_productos SET Nombre = :Nom, Marca = :Mar, Presentación = :Pre, Precio = :Precio WHERE Id = :Id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':Nom', $data['Nombre']);
            $stmt->bindValue(':Mar', $data['Marca']);
            $stmt->bindValue(':Pre', $data['Presentación']);
            $stmt->bindValue(':Precio', $data['Precio']);
            $stmt->bindValue(':Id', $id);
            $stmt->execute();

            // Verificar si se realizó la actualización correctamente
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                header("HTTP/1.1 200 OK");
                echo json_encode("Producto modificado correctamente");
                exit;
            }
        }
    }


    // Si el registro no existe o no se pudo actualizar
    header("HTTP/1.1 404 Not Found");
    echo json_encode("No se encontró ningún producto con el ID proporcionado");
    exit;
}



/*header ("HTTP/1.1 400 Bad REQUEST_METHOD")*/
?>
