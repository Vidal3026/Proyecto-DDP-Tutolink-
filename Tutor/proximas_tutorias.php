<?php
// NOTA IMPORTANTE: Si 'Includes/Nav.php' no inicia la sesión, debes agregar session_start() aquí.
// Pero asumiremos que ya lo hace.
include 'Includes/Nav.php'; 

// 1. Verificación de Seguridad
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'tutor') {
    header("Location: ../Login.php");
    exit();
}
$id_tutor = $_SESSION['id'];

// Asegúrate de que esta ruta sea correcta
include "../Includes/db.php";

// 1. CONSULTA DE TUTORÍAS CONFIRMADAS Y COMPLETADAS RECIENTES
// Incluye CONFIRMADAS (futuras o pasadas pendientes de cierre) 
// e incluye COMPLETADAS (solo las de los últimos 7 días)
$sql_proximas = "
    SELECT 
        s.id AS solicitud_id,
        s.fecha, 
        s.hora_inicio, 
        s.hora_fin,
        s.duracion,
        s.precio_total,
        s.estado, 
        m.nombre_materia AS materia,
        CONCAT(e.nombre, ' ', e.apellido) AS estudiante 
    FROM solicitudes_tutorias s
    JOIN usuarios e ON s.id_estudiante = e.id
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN materias m ON o.id_materia = m.id
    WHERE s.id_tutor = :id_tutor 
    AND (
        s.estado = 'CONFIRMADA'
        OR (s.estado = 'COMPLETADA' AND s.fecha_cierre >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) 
        /* NOTA: Usar fecha_cierre en lugar de fecha para COMPLETADAS, 
           asumiendo que creaste la columna como acordamos. */
    )
    ORDER BY s.fecha ASC, s.hora_inicio ASC
";
try {
    $stmt = $conn->prepare($sql_proximas);
    $stmt->bindParam(':id_tutor', $id_tutor, PDO::PARAM_INT);
    $stmt->execute();
    $proximas_tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Es bueno registrar el error, pero no exponerlo al usuario final.
    error_log("Error al cargar próximas tutorías: " . $e->getMessage()); 
    $proximas_tutorias = [];
    $error_db = "Error al cargar la lista de tutorías. Intente más tarde.";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Tutorías - Tutor</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Tutorías Confirmadas y Historial Reciente</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Próximas y Pendientes</li>
                    </ol>
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['tipo_mensaje']) ?> alert-dismissible fade show position-relative"
                            style="z-index: 1100;" role="alert">
                            <?= htmlspecialchars($_SESSION['mensaje']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php
                        // Limpiar las variables de sesión
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['tipo_mensaje']);
                        ?>
                    <?php endif; ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-calendar-check me-1"></i>
                            Gestión de Sesiones (Próximas, Pendientes de Cierre y Recientes)
                        </div>
                        <div class="card-body">
                            <?php if (isset($error_db)): ?>
                                <div class="alert alert-danger"><?= $error_db ?></div>
                            <?php endif; ?>

                            <?php if (count($proximas_tutorias) > 0): ?>
                                <div class="table-responsive">
                                    <table id="datatablesSimple" class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Materia</th>
                                                <th>Estudiante</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Costo</th> <th>Estado</th>
                                                <th data-sortable="false">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($proximas_tutorias as $t):
                                                // Lógica para determinar si la sesión ya pasó
                                                $timestamp_inicio = strtotime($t['fecha'] . ' ' . $t['hora_inicio']);
                                                $timestamp_fin = strtotime($t['fecha'] . ' ' . $t['hora_fin']);
                                                $sesion_ya_paso = ($timestamp_fin < time());

                                                // Lógica para el formato de duración (sin cambios)
                                                $duracion_horas = floatval($t['duracion']);
                                                if ($duracion_horas < 1 && $duracion_horas > 0) {
                                                    $duracion_str = ($duracion_horas * 60) . ' min.';
                                                } elseif ($duracion_horas >= 1) {
                                                    $duracion_str = rtrim(rtrim(number_format($duracion_horas, 2), '0'), '.') . ' hrs.';
                                                } else {
                                                    $duracion_str = 'N/A';
                                                }
                                                ?>
                                                <tr>
                                                    <td>#<?= htmlspecialchars($t['solicitud_id']) ?></td>
                                                    <td><?= htmlspecialchars($t['materia']) ?></td>
                                                    <td><?= htmlspecialchars($t['estudiante']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                                                    <td><?= date('h:i A', $timestamp_inicio) ?></td>
                                                    <td class="text-success fw-bold">$<?= number_format($t['precio_total'], 2) ?></td> <td>
                                                        <?php
                                                        if ($t['estado'] === 'COMPLETADA'):
                                                            $badge_class = 'bg-success';
                                                            $estado_texto = 'COMPLETADA';
                                                        elseif ($sesion_ya_paso && $t['estado'] === 'CONFIRMADA'):
                                                            $badge_class = 'bg-warning text-dark';
                                                            $estado_texto = 'PENDIENTE DE CIERRE';
                                                        else:
                                                            $badge_class = 'bg-primary';
                                                            $estado_texto = 'PRÓXIMA';
                                                        endif;
                                                        ?>
                                                        <span class="badge <?= $badge_class ?>"><?= $estado_texto ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($t['estado'] === 'CONFIRMADA' && !$sesion_ya_paso): ?>
                                                            <a href="sala_virtual.php?id=<?= $t['solicitud_id'] ?>"
                                                                class="btn btn-sm btn-primary me-2"
                                                                title="Ir a la Sala de Videoconferencia">
                                                                <i class="fas fa-video"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($t['estado'] === 'CONFIRMADA' && $sesion_ya_paso): ?>
                                                            <button type="button" class="btn btn-sm btn-success me-2"
                                                                title="Marcar como Completada" data-bs-toggle="modal"
                                                                data-bs-target="#modalFinalizar-<?= $t['solicitud_id'] ?>">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="button" class="btn btn-sm btn-info me-2"
                                                            title="Ver Detalles de la Reserva" data-bs-toggle="modal"
                                                            data-bs-target="#modalDetalle-<?= $t['solicitud_id'] ?>">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>

                                                        <?php if ($t['estado'] === 'CONFIRMADA'): ?>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                title="Cancelar Tutoría" data-bs-toggle="modal"
                                                                data-bs-target="#modalCancelar-<?= $t['solicitud_id'] ?>">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($t['estado'] === 'COMPLETADA'): ?>
                                                            <span class="text-success me-2" title="Tutoría Finalizada"><i
                                                                    class="fas fa-check-double fa-lg"></i></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="modalFinalizar-<?= $t['solicitud_id'] ?>"
                                                    tabindex="-1"
                                                    aria-labelledby="modalFinalizarLabel-<?= $t['solicitud_id'] ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST" action="procesar_cierre_tutoria.php">
                                                                <input type="hidden" name="solicitud_id"
                                                                    value="<?= $t['solicitud_id'] ?>">
                                                                <div class="modal-header bg-success text-white">
                                                                    <h5 class="modal-title"
                                                                        id="modalFinalizarLabel-<?= $t['solicitud_id'] ?>">
                                                                        Finalizar Tutoría #<?= $t['solicitud_id'] ?></h5>
                                                                    <button type="button" class="btn-close btn-close-white"
                                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Confirma que la tutoría de
                                                                        **<?= htmlspecialchars($t['materia']) ?>** con
                                                                        **<?= htmlspecialchars($t['estudiante']) ?>** ha sido
                                                                        completada
                                                                        (<?= date('d/m/Y', strtotime($t['fecha'])) ?>).</p>
                                                                    <p class="small text-danger">Al confirmar, el estado
                                                                        cambiará a **COMPLETADA** y se habilitará la
                                                                        calificación para el estudiante.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-success">Confirmar
                                                                        Finalización</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($t['estado'] === 'CONFIRMADA'): ?>
                                                    <div class="modal fade" id="modalCancelar-<?= $t['solicitud_id'] ?>"
                                                        tabindex="-1" aria-labelledby="modalCancelarLabel-<?= $t['solicitud_id'] ?>"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST" action="cancelar_tutor_accion.php">
                                                                    <input type="hidden" name="solicitud_id"
                                                                        value="<?= $t['solicitud_id'] ?>">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title"
                                                                            id="modalCancelarLabel-<?= $t['solicitud_id'] ?>">
                                                                            Confirmar Cancelación #<?= $t['solicitud_id'] ?></h5>
                                                                        <button type="button" class="btn-close btn-close-white"
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>¿Estás seguro de que deseas **CANCELAR** la tutoría de
                                                                            **<?= htmlspecialchars($t['materia']) ?>** con
                                                                            **<?= htmlspecialchars($t['estudiante']) ?>**
                                                                            (<?= date('d/m/Y', strtotime($t['fecha'])) ?>)?</p>
                                                                        <p class="small text-danger">Esta acción no se puede
                                                                            deshacer y el estudiante recibirá una notificación de
                                                                            cancelación.</p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary"
                                                                            data-bs-dismiss="modal">No, Volver</button>
                                                                        <button type="submit" class="btn btn-danger">Sí, Cancelar
                                                                            Tutoría</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="modal fade" id="modalDetalle-<?= $t['solicitud_id'] ?>"
                                                    tabindex="-1" aria-labelledby="modalDetalleLabel-<?= $t['solicitud_id'] ?>"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-info text-white">
                                                                <h5 class="modal-title"
                                                                    id="modalDetalleLabel-<?= $t['solicitud_id'] ?>">Detalle de
                                                                    la Tutoría #<?= $t['solicitud_id'] ?></h5>
                                                                <button type="button" class="btn-close btn-close-white"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <h4 class="text-primary mb-3">
                                                                    <?= htmlspecialchars($t['materia']) ?>
                                                                </h4>
                                                                <ul class="list-group list-group-flush">
                                                                    <li class="list-group-item">
                                                                        <i class="fas fa-user-graduate me-2 text-muted"></i>
                                                                        <strong>Estudiante:</strong>
                                                                        <span
                                                                            class="float-end"><?= htmlspecialchars($t['estudiante']) ?></span>
                                                                    </li>
                                                                    <li class="list-group-item">
                                                                        <i class="fas fa-calendar-day me-2 text-muted"></i>
                                                                        <strong>Fecha:</strong>
                                                                        <span
                                                                            class="float-end"><?= date('d/m/Y', strtotime($t['fecha'])) ?></span>
                                                                    </li>
                                                                    <li class="list-group-item">
                                                                        <i class="fas fa-clock me-2 text-muted"></i>
                                                                        <strong>Hora:</strong>
                                                                        <span
                                                                            class="float-end"><?= date('h:i A', $timestamp_inicio) ?>
                                                                            - <?= date('h:i A', $timestamp_fin) ?></span>
                                                                    </li>
                                                                    <li class="list-group-item">
                                                                        <i class="fas fa-hourglass-half me-2 text-muted"></i>
                                                                        <strong>Duración:</strong>
                                                                        <span class="float-end"><?= $duracion_str ?></span>
                                                                    </li>
                                                                    <li class="list-group-item">
                                                                        <i class="fas fa-money-bill-wave me-2 text-muted"></i>
                                                                        <strong>Costo:</strong>
                                                                        <span
                                                                            class="float-end text-success fw-bold">$<?= number_format($t['precio_total'], 2) ?></span>
                                                                    </li>
                                                                    <li class="list-group-item">
                                                                        <i class="fas fa-info-circle me-2 text-muted"></i>
                                                                        <strong>Estado:</strong>
                                                                        <span class="float-end">
                                                                            <span
                                                                                class="badge <?= $badge_class ?>"><?= $estado_texto ?></span>
                                                                        </span>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cerrar</button>
                                                                <?php if (!$sesion_ya_paso && $t['estado'] === 'CONFIRMADA'): ?>
                                                                    <a href="sala_virtual.php?id=<?= $t['solicitud_id'] ?>"
                                                                        class="btn btn-primary" title="Unirse a la sala virtual">
                                                                        <i class="fas fa-video"></i> Ir a Sesión
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    🗓️ No tienes tutorías confirmadas programadas ni historial reciente.
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