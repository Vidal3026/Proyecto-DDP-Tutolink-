<?php
include 'Includes/Nav.php';

// 2. CONSULTA DE SOLICITUDES DE TUTORÍA
$sql = "
    SELECT
        s.id AS solicitud_id,  -- ¡AÑADIDO! Necesario para el botón de pago
        s.fecha, s.hora_inicio, s.duracion, s.precio_total, s.estado,
        m.nombre_materia AS materia, -- ¡CORREGIDO! Usando nombre_materia
        t.nombre AS nombre_tutor, t.apellido AS apellido_tutor
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios t ON s.id_tutor = t.id 
    JOIN materias m ON o.id_materia = m.id 
    WHERE s.id_estudiante = :estudiante_id
    ORDER BY s.fecha DESC, s.hora_inicio DESC;
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':estudiante_id', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>s
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Solicitudess</title>
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- Panel Izquierdo -->
            <?php include 'Includes/NavIzquierdo.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Solicitudes</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mis solicitudes</li>
                    </ol>
                    <!--Contendo-->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Tutorías Solicitadas
                        </div>
                        <div class="card-body">

                            <?php
                            // 4. MOSTRAR MENSAJE DE ÉXITO (Si viene de procesar_agendamiento.php)
                            if (isset($_GET['success']) && $_GET['success'] == 'reserva_enviada'): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    ✅ **¡Solicitud Enviada!** Tu petición de tutoría ha sido registrada. Está en estado
                                    **PENDIENTE** de confirmación por el tutor.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (count($solicitudes) > 0): ?>
                                <div class="table-responsive">
                                    <table id="datatablesSimple" class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Materia</th>
                                                <th>Tutor</th>
                                                <th>Fecha</th>
                                                <th>Hora Inicio</th>
                                                <th>Duración</th>
                                                <th>Precio Total</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($solicitudes as $solicitud): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($solicitud['materia']) ?></td>
                                                    <td><?= htmlspecialchars($solicitud['nombre_tutor'] . ' ' . $solicitud['apellido_tutor']) ?>
                                                    </td>
                                                    <td><?= date('d/M/Y', strtotime($solicitud['fecha'])) ?></td>
                                                    <td><?= date('H:i', strtotime($solicitud['hora_inicio'])) ?></td>
                                                    <td><?= htmlspecialchars($solicitud['duracion']) ?> hrs.</td>
                                                    <td>$<?= number_format($solicitud['precio_total'], 2) ?></td>

                                                    <td>
                                                        <?php
                                                        $estado = htmlspecialchars($solicitud['estado']);
                                                        $clase_estado = 'badge bg-secondary'; // Valor por defecto
                                                
                                                        switch ($estado) {
                                                            case 'PENDIENTE':
                                                                $clase_estado = 'badge bg-warning text-dark';
                                                                break;
                                                            case 'ACEPTADA':
                                                                $clase_estado = 'badge bg-success';
                                                                break;
                                                            case 'CONFIRMADA':
                                                                $clase_estado = 'badge bg-primary';
                                                                break;
                                                            case 'COMPLETADA':
                                                                $clase_estado = 'badge bg-info';
                                                                break;
                                                            case 'CANCELADA':
                                                                $clase_estado = 'badge bg-danger';
                                                                break;
                                                            default:
                                                                $clase_estado = 'badge bg-secondary';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="<?= $clase_estado ?>"><?= $estado ?></span>

                                                        <?php if ($estado === 'ACEPTADA'): ?>
                                                            <div class="mt-2">
                                                                <a href="procesar_pago.php?solicitud_id=<?= $solicitud['solicitud_id'] ?>"
                                                                    class="btn btn-sm btn-info text-white">
                                                                    <i class="fas fa-credit-card"></i> Pagar Ahora
                                                                </a>
                                                                <div class="small text-muted mt-1">El tutor aceptó. Paga para
                                                                    confirmar.</div>
                                                            </div>
                                                        <?php elseif ($estado === 'CANCELADA'): ?>
                                                            <div class="small text-danger mt-1">Rechazada por el tutor.</div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Aún no has enviado ninguna solicitud de tutoría.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
</body>

</html>