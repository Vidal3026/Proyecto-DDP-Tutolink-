<?php
session_start();

// Código para evitar caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}

// 2. INCLUSIÓN DE LA CONEXIÓN (Punto Crítico)
// VERIFICA QUE ESTA RUTA SEA CORRECTA
include "../Includes/db.php"; 
$estudiante_id = $_SESSION['id'];

// 3. VALIDAR ID DE LA SOLICITUD
$solicitud_id = filter_input(INPUT_GET, 'solicitud_id', FILTER_VALIDATE_INT);
if (!$solicitud_id) {
    // Si la ID es inválida o no existe
    header('Location: MisSolicitudes.php?error=no_solicitud_id');
    exit();
}

// 4. VERIFICAR LA DISPONIBILIDAD DE LA CONEXIÓN
if (!isset($conn) || $conn === null) {
    error_log("FATAL ERROR: La variable \$conn (conexión a DB) no fue inicializada en db.php.");
    header('Location: MisSolicitudes.php?error=pago_fallido_db');
    exit();
}


try {
    // Verificar que la solicitud exista y sea del estudiante
    $sql = "SELECT id, id_estudiante, estado 
            FROM solicitudes_tutorias 
            WHERE id = :solicitud_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
    $stmt->execute();
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    // a) Verificar que la solicitud exista y pertenezca al estudiante
    if (!$solicitud || (int)$solicitud['id_estudiante'] !== (int)$estudiante_id) {
        header('Location: MisSolicitudes.php?error=acceso_denegado');
        exit();
    }

    // b) Verificar que solo se pague si el estado es 'ACEPTADA'
    if ($solicitud['estado'] !== 'ACEPTADA') {
        // Redirige al estado inválido si no es ACEPTADA
        header('Location: MisSolicitudes.php?error=estado_invalido');
        exit();
    }

    // =========================================================================
    // 6. LÓGICA DE PROCESAMIENTO DE PAGO (ACTUALIZACIÓN FORZADA)
    // =========================================================================
    
    // NOTA: Reemplazamos :nuevo_estado con el valor fijo 'CONFIRMADA' para evitar problemas de codificación/bind.
    $sql_update = "
        UPDATE solicitudes_tutorias 
        SET estado = 'CONFIRMADA', fecha_pago = NOW() 
        WHERE id = :solicitud_id
    ";
    
    $stmt_update = $conn->prepare($sql_update);
    // Ya no hacemos bindParam para :nuevo_estado, solo para :solicitud_id
    $stmt_update->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
    
    $stmt_update->execute();
    
    // 7. VERIFICACIÓN CRÍTICA: Contar las filas afectadas
    if ($stmt_update->rowCount() > 0) {
        // Redirección de Éxito
        header('Location: MisSolicitudes.php?success=pago_confirmado');
        exit();
    } else {
        // Si rowCount es 0, la ID ya no existía o el estado fue cambiado por otro proceso
        error_log("FALLO DE UPDATE: Solicitud ID {$solicitud_id}. Estado previo: {$solicitud['estado']}. La consulta afectó 0 filas.");
        header('Location: MisSolicitudes.php?error=no_se_actualizo');
        exit();
    }

} catch (PDOException $e) {
    // 8. MANEJO DE ERRORES DE BASE DE DATOS
    error_log("Error al procesar pago (PDO Exception): " . $e->getMessage());
    header('Location: MisSolicitudes.php?error=pago_fallido_db');
    exit();
}
?>