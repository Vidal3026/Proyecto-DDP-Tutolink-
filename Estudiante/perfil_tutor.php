<?php
include 'Includes/Nav.php';

// 1. Validar que se ha recibido el ID del Tutor (tutor_id)
if (!isset($_GET['tutor_id']) || !is_numeric($_GET['tutor_id'])) {
    // Redirigir si no hay ID válido
    header('Location: BuscarTutor.php?error=tutor_invalido');
    exit;
}

$tutor_id = $_GET['tutor_id'];

// 2. Consulta para obtener todos los detalles del tutor
try {
    $sql_tutor = "
        SELECT 
            u.id AS tutor_id,
            u.nombre AS nombre_tutor, 
            u.apellido AS apellido_tutor, 
            u.perfil_imagen, 
            u.universidad_tutor, 
            u.modalidad_tutor,
            u.descripcion,
            
            /* Obtener la materia con el precio más bajo (la mejor oferta) */
            (
                SELECT MIN(ot.precio_hora) 
                FROM ofertas_tutorias ot 
                WHERE ot.id_tutor = u.id AND ot.activo = 1 
            ) AS precio_minimo,
            
            /* Obtener todas las materias que imparte el tutor */
            (
                SELECT GROUP_CONCAT(DISTINCT m_all.nombre_materia SEPARATOR ', ')
                FROM materias m_all
                WHERE EXISTS (
                    SELECT 1 
                    FROM ofertas_tutorias ot_all 
                    /* Incluye el filtro activo = 1 para que solo muestre materias con ofertas activas */
                    WHERE ot_all.id_materia = m_all.id AND ot_all.id_tutor = u.id AND ot_all.activo = 1 
                )
                OR EXISTS (
                    SELECT 1 
                    FROM tutor_materias tm 
                    WHERE tm.id_materia = m_all.id AND tm.id_tutor = u.id
                )
            ) AS todas_las_materias
            
        FROM 
            usuarios u
        WHERE 
            u.id = :tutor_id AND u.rol = 'Tutor'";

    $stmt_tutor = $conn->prepare($sql_tutor);
    $stmt_tutor->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_tutor->execute();
    $tutor_detalle = $stmt_tutor->fetch(PDO::FETCH_ASSOC);

    // Si el tutor no existe, redirigir
    if (!$tutor_detalle) {
        header('Location: BuscarTutor.php?error=tutor_noexiste');
        exit;
    }

    // 3. Obtener la disponibilidad horaria semanal del tutor
    $sql_disponibilidad = "
        SELECT 
            dia_semana, 
            hora_inicio, 
            hora_fin 
        FROM 
            disponibilidad 
        WHERE 
            id_tutor = :tutor_id
        ORDER BY 
            FIELD(dia_semana, 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'), hora_inicio";

    $stmt_disp = $conn->prepare($sql_disponibilidad);
    $stmt_disp->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_disp->execute();
    $disponibilidad = $stmt_disp->fetchAll(PDO::FETCH_ASSOC);

    // Formatear la disponibilidad por día de la semana
    $horario_semanal = [
        'LUNES' => [],
        'MARTES' => [],
        'MIÉRCOLES' => [],
        'JUEVES' => [],
        'VIERNES' => [],
        'SÁBADO' => [],
        'DOMINGO' => []
    ];

    foreach ($disponibilidad as $bloque) {
        $horario_semanal[$bloque['dia_semana']][] = [
            'inicio' => date('H:i', strtotime($bloque['hora_inicio'])),
            'fin' => date('H:i', strtotime($bloque['hora_fin'])),
        ];
    }

} catch (PDOException $e) {
    die("Error al cargar los detalles del tutor: " . $e->getMessage());
}

// 4. Obtener las ofertas de tutoría activas de forma individual
try {
    $sql_ofertas = "
        SELECT 
            ot.id AS oferta_id,
            ot.precio_hora,
            m.nombre_materia
        FROM 
            ofertas_tutorias ot
        JOIN 
            materias m ON ot.id_materia = m.id
        WHERE 
            ot.id_tutor = :tutor_id AND ot.activo = 1
        ORDER BY 
            m.nombre_materia";

    $stmt_ofertas = $conn->prepare($sql_ofertas);
    $stmt_ofertas->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
    $stmt_ofertas->execute();
    $ofertas_activas = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar las ofertas de tutoría: " . $e->getMessage());
}

// Determinar la ruta de la imagen del tutor
$ruta_imagen = !empty($tutor_detalle['perfil_imagen']) ?
    '../' . htmlspecialchars($tutor_detalle['perfil_imagen']) :
    '../Assets/perfil_default.png';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Perfil de Tutor | Agendar Tutoria</title>
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
                    <h1 class="mt-4">Perfil de Tutor</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="BuscarTutor.php">Buscar Tutores</a></li>
                        <li class="breadcrumb-item active">
                            <?= htmlspecialchars($tutor_detalle['nombre_tutor'] . ' ' . $tutor_detalle['apellido_tutor']) ?>
                        </li>
                    </ol>

                    <div class="row">

                        <div class="col-lg-4">

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-primary text-white text-center">
                                    <h5 class="mb-0">Datos del Tutor</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img src="<?= $ruta_imagen ?>" alt="Foto de perfil"
                                        class="rounded-circle mb-3 border border-3"
                                        style="width: 120px; height: 120px; object-fit: cover;">
                                    <h4 class="mb-1">
                                        <?= htmlspecialchars($tutor_detalle['nombre_tutor'] . ' ' . $tutor_detalle['apellido_tutor']) ?>
                                    </h4>
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-graduation-cap me-1"></i>
                                        <?= htmlspecialchars($tutor_detalle['universidad_tutor'] ?? 'Universidad no especificada') ?>
                                    </p>

                                    <div class="border-top pt-3">
                                        <p class="mb-1 text-start">
                                            <i class="fas fa-tags text-info me-2"></i>
                                            <strong>Precio Mínimo:</strong>
                                            <span
                                                class="fw-bold text-success">$<?= number_format($tutor_detalle['precio_minimo'] ?? 0, 2) ?>/h</span>
                                        </p>
                                        <p class="mb-1 text-start">
                                            <i class="fas fa-map-marker-alt text-warning me-2"></i>
                                            <strong>Modalidad:</strong>
                                            <?= htmlspecialchars($tutor_detalle['modalidad_tutor']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="card-footer bg-light">
                                    <h6 class="mb-2 text-primary"><i class="fas fa-user-circle me-1"></i> Sobre Mí</h6>
                                    <p class="card-text small text-start mb-0">
                                        <?= nl2br(htmlspecialchars($tutor_detalle['descripcion'] ?? 'El tutor no ha proporcionado una descripción detallada.')) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <i class="fas fa-book me-1"></i> Materias Impartidas
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($tutor_detalle['todas_las_materias'])): ?>
                                        <p class="card-text">
                                            <?= nl2br(htmlspecialchars($tutor_detalle['todas_las_materias'])) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">No hay materias activas listadas por este tutor.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-calendar-alt me-1"></i> Disponibilidad Horaria Semanal
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Día</th>
                                                <th>Horario Disponible</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Esta lógica PHP debe estar definida antes en el archivo para que funcione
                                            $dias_espanol = ['LUNES' => 'Lunes', 'MARTES' => 'Martes', 'MIÉRCOLES' => 'Miércoles', 'JUEVES' => 'Jueves', 'VIERNES' => 'Viernes', 'SÁBADO' => 'Sábado', 'DOMINGO' => 'Domingo'];
                                            foreach ($horario_semanal as $dia => $bloques):
                                                ?>
                                                <tr>
                                                    <td class="fw-bold"><?= $dias_espanol[$dia] ?></td>
                                                    <td>
                                                        <?php if (!empty($bloques)): ?>
                                                            <?php foreach ($bloques as $bloque): ?>
                                                                <span class="badge bg-success me-1">
                                                                    <?= $bloque['inicio'] ?> - <?= $bloque['fin'] ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Disponible</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card shadow-lg mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <i class="fas fa-clipboard-check me-1"></i> Agendar Tutoría
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Utiliza este formulario para solicitar una tutoría con
                                        <?= htmlspecialchars($tutor_detalle['nombre_tutor']) ?>.
                                    </p>
                                    <form action="procesar_agendamiento.php" method="POST">
                                        <input type="hidden" name="tutor_id" value="<?= $tutor_id ?>">

                                        <div class="mb-3">
                                            <label for="materia_agendar" class="form-label">Materia a Agendar</label>
                                            <select class="form-select" id="materia_agendar" name="oferta_id" required>
                                                <option value="" disabled selected>Seleccione una materia</option>

                                                <?php if (!empty($ofertas_activas)): ?>
                                                    <?php foreach ($ofertas_activas as $oferta): ?>
                                                        <option value="<?= $oferta['oferta_id'] ?>">
                                                            <?= htmlspecialchars($oferta['nombre_materia']) ?> (Precio:
                                                            $<?= number_format($oferta['precio_hora'], 2) ?>/h)
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="" disabled>El tutor no tiene ofertas activas.</option>
                                                <?php endif; ?>

                                            </select>
                                            <small class="form-text text-muted">Selecciona la materia y verifica el
                                                precio.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="fecha_agendar" class="form-label">Fecha Deseada</label>
                                            <input type="date" class="form-control" id="fecha_agendar" name="fecha"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="hora_agendar" class="form-label">Hora Deseada (Basado en
                                                Disponibilidad)</label>
                                            <input type="time" class="form-control" id="hora_agendar" name="hora"
                                                required>
                                        </div>

                                        <button type="submit" class="btn btn-warning btn-lg w-100 mt-2">
                                            <i class="fas fa-check-circle me-1"></i> Solicitar Tutoría
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>