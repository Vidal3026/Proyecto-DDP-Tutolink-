<?php
include 'Includes/Nav.php';


// 1. VERIFICACIÓN DE SESIÓN (Estudiante)
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: ../Login.php");
    exit();
}

// Incluimos la conexión a la base de datos
include "../Includes/db.php";
$id_usuario = $_SESSION['id'];

// 2. CONSULTA DE TUTORÍAS CONFIRMADAS Y COMPLETADAS
// Filtramos por el nuevo estado 'CONFIRMADA' y el estado final 'COMPLETADA'
$sql = "
    SELECT
        s.id AS solicitud_id, 
        s.fecha, s.hora_inicio, s.duracion, s.precio_total, s.estado, s.fecha_pago,
        m.nombre_materia AS materia,
        t.nombre AS nombre_tutor, t.apellido AS apellido_tutor
    FROM solicitudes_tutorias s
    JOIN ofertas_tutorias o ON s.id_oferta = o.id
    JOIN usuarios t ON s.id_tutor = t.id 
    JOIN materias m ON o.id_materia = m.id 
    WHERE s.id_estudiante = :estudiante_id
    -- Muestra CONFIRMADA (Pagada y Próxima) y COMPLETADA (Finalizada)
    AND s.estado IN ('CONFIRMADA', 'COMPLETADA') 
    ORDER BY s.fecha DESC, s.hora_inicio DESC;
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':estudiante_id', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar tutorías confirmadas: " . $e->getMessage());
    $tutorias = []; 
    $error_db = "Error al cargar el historial de tutorías.";
}

// Incluimos el encabezado y la navegación
include 'Includes/Nav.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Historial y próximas tutorías pagadas" />
    <meta name="author" content="" />
    <title>Tutorías Inscritas</title>
    <link href="css/styles.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Tutorías Inscritas</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Ver todas</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-calendar-check me-1"></i>
                            Clases Pagadas y Agendadas
                        </div>
                        <div class="card-body">

                            <?php if (isset($error_db)): ?>
                                <div class="alert alert-danger"><?= $error_db ?></div>
                            <?php endif; ?>

                            <?php if (count($tutorias) > 0): ?>

                                <div class="row row-cols-1 row-cols-md-2 g-4">

                                    <?php foreach ($tutorias as $tutoria):
                                        $estado = htmlspecialchars($tutoria['estado']);

                                        // --- LÓGICA VISUAL BASADA EN EL ESTADO ---
                                        if ($estado === 'COMPLETADA') {
                                            // Clase finalizada (Historial) -> Color Verde
                                            $clase_card = 'border-start-success'; 
                                            $clase_estado = 'badge bg-success';
                                            $label_estado = 'FINALIZADA';
                                        } elseif ($estado === 'CONFIRMADA') {
                                            // Clase pagada y pendiente (Próximas Clases) -> Color Azul
                                            $clase_card = 'border-start-primary'; 
                                            $clase_estado = 'badge bg-primary';
                                            $label_estado = 'CONFIRMADA';
                                        } else {
                                            // Fallback
                                            $clase_card = 'border-start-secondary';
                                            $clase_estado = 'badge bg-secondary';
                                            $label_estado = $estado;
                                        }
                                    ?>

                                    <div class="col">
                                        <div class="card shadow h-100 <?= $clase_card ?> border-start-5">
                                            <div class="card-body">
                                                <div class="row align-items-start">

                                                    <div class="col-8">
                                                        <div class="small text-muted mb-1">Tutoría con:</div>
                                                        <h5 class="text-uppercase mb-1 text-primary">
                                                            <?= htmlspecialchars($tutoria['materia']) ?>
                                                        </h5>
                                                        <p class="text-dark mb-3">
                                                            <i class="fas fa-user-circle me-1"></i>
                                                            **<?= htmlspecialchars($tutoria['nombre_tutor'] . ' ' . $tutoria['apellido_tutor']) ?>**
                                                        </p>

                                                        <div class="small text-muted">Precio Pagado:</div>
                                                        <h5 class="text-success mb-0">
                                                            $<?= number_format($tutoria['precio_total'], 2) ?>
                                                        </h5>
                                                    </div>

                                                    <div class="col-4 text-end">

                                                        <div class="small text-muted">Clase:</div>
                                                        <h5 class="text-dark mb-1">
                                                            <?= date('d/M/Y', strtotime($tutoria['fecha'])) ?>
                                                        </h5>
                                                        <p class="text-dark mb-3">
                                                            <?= date('H:i', strtotime($tutoria['hora_inicio'])) ?>
                                                            (<?= htmlspecialchars($tutoria['duracion']) ?> hrs.)
                                                        </p>

                                                        <span class="<?= $clase_estado ?>"><?= $label_estado ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-footer bg-light p-2 d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="fas fa-credit-card me-1"></i> ID Solicitud: #<?= htmlspecialchars($tutoria['solicitud_id']) ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-check-circle me-1"></i> Pagado el:
                                                    <?= date('d/M/Y', strtotime($tutoria['fecha_pago'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <?php endforeach; ?>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-info">
                                    Aún no tienes tutorías confirmadas o pagadas en tu historial.
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
</body>

</html>