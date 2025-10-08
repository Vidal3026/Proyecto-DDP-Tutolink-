<?php
// Incluye la navegación
include 'Includes/Nav.php';

// 1. VERIFICACIÓN DE SESIÓN: Asegura que el usuario sea un tutor
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];

include "../Includes/db.php"; // Incluye tu conexión a la BD

// 2. CONSULTA DE TUTORÍAS DEL HISTORIAL
$sql_historial = "
    SELECT 
        s.id AS solicitud_id,
        s.fecha, 
        s.hora_inicio, 
        s.hora_fin, 
        s.estado,
        m.nombre_materia AS materia,
        CONCAT(e.nombre, ' ', e.apellido) AS estudiante 
    FROM solicitudes_tutorias s
    JOIN usuarios e ON s.id_estudiante = e.id
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN materias m ON o.id_materia = m.id
    WHERE s.id_tutor = :id_tutor 
    AND (
        -- Cierre definitivo: Éxito (COMPLETADA) o Fracaso (RECHAZADA/CANCELADA)
        s.estado IN ('COMPLETADA', 'RECHAZADA', 'CANCELADA')
        
        -- Cierre por Expiración: Solicitudes que se quedaron a medio camino y la fecha ya pasó
        OR (s.estado IN ('ACEPTADA', 'CONFIRMADA') AND s.fecha < CURDATE())
    )
    ORDER BY s.fecha DESC, s.hora_inicio DESC
";
try {
    $stmt = $conn->prepare($sql_historial);
    $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al consultar historial de tutorías: " . $e->getMessage());
    $historial = [];
    $error_db = "Error al cargar datos de la base de datos.";
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Historial de Tutorías - Tutor</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; // Navegación lateral ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Historial de Tutorías</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>

                    <?php if (isset($error_db)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_db) ?></div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-history me-1"></i>
                            Tutorías Pasadas y Solicitudes Cerradas
                        </div>
                        <div class="card-body">
                            <?php if (count($historial) > 0): ?>
                                <div class="table-responsive">
                                    <table id="datatablesSimple" class="table table-striped table-hover">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Materia</th>
                                                <th>Estudiante</th>
                                                <th>Fecha</th>
                                                <th>Duración</th>
                                                <th>Estado Final</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historial as $h): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($h['materia']) ?></td>
                                                    <td><?= htmlspecialchars($h['estudiante']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                                                    <td><?= date('h:i A', strtotime($h['hora_inicio'])) ?> -
                                                        <?= date('h:i A', strtotime($h['hora_fin'])) ?></td>
                                                    <td>
                                                        <?php
                                                        $estado = $h['estado'];
                                                        $badge_class = 'bg-secondary';
                                                        if ($estado == 'COMPLETADA')
                                                            $badge_class = 'bg-success';
                                                        if ($estado == 'RECHAZADA' || $estado == 'CANCELADA')
                                                            $badge_class = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?= $badge_class; ?>">
                                                            <?= htmlspecialchars($estado) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="ver_detalle_historial.php?id=<?= $h['solicitud_id'] ?>"
                                                            class="btn btn-sm btn-info" title="Ver Detalles">
                                                            <i class="fas fa-info-circle"></i> Ver
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    📂 Tu historial de tutorías está vacío.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
            <?php include 'Includes/Footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
</body>

</html>