<?php
session_start();

// Validar si el usuario tiene permiso de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== "admin") {
    header("Location: ../Login.php");
    exit();
}

// Incluir el archivo de conexión a la base de datos
include "../Includes/db.php";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Preparar la consulta SQL para eliminar el usuario
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                // Redirigir de nuevo con un mensaje de éxito
                header("Location: GestionarUsuarios.php?status=eliminado_exitoso");
                exit();
            } else {
                // Si no se encontró el usuario
                header("Location: GestionarUsuarios.php?status=no_encontrado");
                exit();
            }
        } else {
            // Si la eliminación falla
            header("Location: GestionarUsuarios.php?status=error");
            exit();
        }
    } catch (PDOException $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    // Si no se recibió un ID válido
    header("Location: GestionarUsuarios.php?status=no_id");
    exit();
}
?>