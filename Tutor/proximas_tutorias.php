<?php
include 'Includes/Nav.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];

include "../Includes/db.php";

// 1. CONSULTA DE PRÓXIMAS TUTORÍAS CONFIRMADAS
// Se incluyen las tutorías confirmadas que son HOY o en el FUTURO.
$sql_proximas = "
    SELECT 
        s.id AS solicitud_id,
        s.fecha, 
        s.hora_inicio, 
        s.hora_fin, 
        m.nombre_materia AS materia,
        CONCAT(e.nombre, ' ', e.apellido) AS estudiante 
    FROM solicitudes_tutorias s
    JOIN usuarios e ON s.id_estudiante = e.id
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN materias m ON o.id_materia = m.id
    WHERE s.id_tutor = :id_tutor 
    AND s.estado = 'CONFIRMADA'
    AND s.fecha >= CURDATE()
    ORDER BY s.fecha ASC, s.hora_inicio ASC
";
$stmt = $conn->prepare($sql_proximas);
$stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
$stmt->execute();
$proximas_tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Próximas Tutorías - Tutor</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Próximas Tutorías Confirmadas</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tutorías Confirmadas</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-calendar-check me-1"></i>
                            Tutorías Agendadas y Confirmadas
                        </div>
                        <div class="card-body">
                            <?php if (count($proximas_tutorias) > 0): ?>
                                <div class="table-responsive">
                                    <table id="datatablesSimple" class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Materia</th>
                                                <th>Estudiante</th>
                                                <th>Fecha</th>
                                                <th>Inicio</th>
                                                <th>Fin</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($proximas_tutorias as $t): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($t['materia']) ?></td>
                                                    <td><?= htmlspecialchars($t['estudiante']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                                                    <td><?= date('h:i A', strtotime($t['hora_inicio'])) ?></td>
                                                    <td><?= date('h:i A', strtotime($t['hora_fin'])) ?></td>
                                                    <td><span class="badge bg-success">CONFIRMADA</span></td>
                                                    <td>
                                                        <a href="sala_virtual.php?id=<?= $t['solicitud_id'] ?>"
                                                            class="btn btn-sm btn-primary me-2"
                                                            title="Ir a la Sala de Videoconferencia">
                                                            <i class="fas fa-video"></i> Iniciar Sesión
                                                        </a>

                                                        <a href="marcar_completada.php?id=<?= $t['solicitud_id'] ?>"
                                                            class="btn btn-sm btn-success me-2" title="Marcar como Completada">
                                                            <i class="fas fa-check-circle"></i> Completar
                                                        </a>

                                                        <a href="ver_detalle_solicitud.php?id=<?= $t['solicitud_id'] ?>"
                                                            class="btn btn-sm btn-info me-2" title="Ver Detalles de la Reserva">
                                                            <i class="fas fa-info-circle"></i> Detalles
                                                        </a>

                                                        <a href="cancelar_tutor_accion.php?id=<?= $t['solicitud_id'] ?>"
                                                            class="btn btn-sm btn-danger" title="Cancelar Tutoría">
                                                            <i class="fas fa-ban"></i> Cancelar
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    🗓️ No tienes tutorías confirmadas programadas a partir de hoy.
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
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"
        crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>