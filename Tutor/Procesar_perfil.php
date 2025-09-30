<?php
include 'Includes/Nav.php';

// 🛑 Incluir la conexión a la base de datos
if (!isset($conn)) {
    // Asegúrate de que esta ruta sea correcta para la carpeta Tutor/
    include '../Includes/db.php';
}

// 1. **AUTENTICACIÓN Y VARIABLES**
if ($usuario['rol'] !== 'tutor') {
    header('Location: ../index.php');
    exit;
}
$tutor_id = $usuario['id'];
$seccion = $_POST['seccion'] ?? '';

// 2. **DETERMINAR ANCLA DE REDIRECCIÓN**
// Esta variable se usará tanto para el éxito como para el error en la URL
$anchor_key = '';
if ($seccion === 'perfil') {
    $anchor_key = 'perfil';
} elseif ($seccion === 'materias') {
    $anchor_key = 'materias';
} elseif ($seccion === 'disponibilidad') {
    $anchor_key = 'horario';
} else {
    // Si la sección es inválida, usamos un error y salimos
    header("Location: Configurar_perfil.php?error=seccion_invalida");
    exit;
}

// 3. **FUNCIÓN DE REDIRECCIÓN CENTRALIZADA**
function redirigir($type, $key)
{
    // 'type' puede ser 'exito' o 'error'
    // 'key' es 'perfil', 'materias', 'horario' o un código de error
    $base_url = "Configurar_perfil.php?";

    if ($type === 'exito') {
        header("Location: {$base_url}exito={$key}");
    } else {
        // Para errores, redirigimos a la pestaña (anchor) y pasamos el código de error
        $anchor = $key === 'db_perfil' || $key === 'campos_requeridos' ? 'perfil' :
            ($key === 'db_modalidad' || $key === 'db_materias' ? 'materias' :
                ($key === 'db_disponibilidad' || $key === 'horario_invalido' ? 'horario' : 'perfil'));

        header("Location: {$base_url}anchor={$anchor}&error={$key}");
    }
    exit;
}


// ======================================================================
// 4. **PROCESAR SECCIÓN DE PERFIL (DATOS PERSONALES)**
// ======================================================================
if ($seccion === 'perfil') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $universidad = trim($_POST['universidad_tutor'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones básicas
    if (empty($nombre) || empty($correo)) {
        redirigir('error', 'campos_requeridos');
    }

    try {
        // Actualizar los datos personales en la tabla 'usuarios'
        $sql = "UPDATE usuarios SET 
                    nombre = :nombre, 
                    apellido = :apellido, 
                    correo = :correo, 
                    telefono = :telefono, 
                    universidad_tutor = :universidad, 
                    descripcion = :descripcion
                WHERE id = :tutor_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':universidad', $universidad);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt->execute();

        // Éxito
        redirigir('exito', 'perfil');

    } catch (PDOException $e) {
        error_log("Error al actualizar perfil del tutor: " . $e->getMessage());
        redirigir('error', 'db_perfil');
    }
}
// ======================================================================
// 5. **PROCESAR SECCIÓN DE MATERIAS Y MODALIDAD (CORREGIDO CON oferta_tutorias)**
// ======================================================================
elseif ($seccion === 'materias') {
    $modalidad_tutor = $_POST['modalidad_tutor'] ?? null;
    $materias_impartidas = $_POST['materias_impartidas'] ?? []; // Array de Materias con IDs y Precios

    if (empty($modalidad_tutor)) {
        header('Location: configurar_perfil.php?error=modalidad_vacia&anchor=materias');
        exit;
    }

    try {
        $conn->beginTransaction();

        // 2.3. Actualizar el campo 'modalidad_tutor' en la tabla 'usuarios'
        $sql_update_modalidad = "UPDATE usuarios SET modalidad_tutor = :modalidad WHERE id = :tutor_id";
        $stmt_modalidad = $conn->prepare($sql_update_modalidad);
        $stmt_modalidad->bindParam(':modalidad', $modalidad_tutor, PDO::PARAM_STR);
        $stmt_modalidad->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_modalidad->execute();

        // 2.4. Sincronizar las materias y sus precios (Tabla 'oferta_tutorias')

        // 1. Eliminar todas las ofertas/precios antiguas del tutor en la tabla CORREGIDA
        // Asumiendo que oferta_tutorias tiene id_tutor
        $sql_delete_ofertas = "DELETE FROM ofertas_tutorias WHERE id_tutor = :tutor_id";
        $stmt_delete_m = $conn->prepare($sql_delete_ofertas);
        $stmt_delete_m->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_delete_m->execute();

        // 2. Insertar las materias seleccionadas junto con su precio en la tabla CORREGIDA
        if (!empty($materias_impartidas)) {
            // Ajustar el INSERT para la tabla oferta_tutorias. 
            // ASUMO que esta tabla tiene al menos: id_tutor, id_materia, y precio_hora
            $sql_insert_oferta = "INSERT INTO ofertas_tutorias (id_tutor, id_materia, precio_hora, activo) 
                       VALUES (:tutor_id, :id_materia, :precio_hora, :activo)";
            $stmt_insert_m = $conn->prepare($sql_insert_oferta);

            foreach ($materias_impartidas as $materia_data) {
                $id_materia = (int) ($materia_data['id'] ?? 0);
                $precio = (float) ($materia_data['precio'] ?? 0.00);

                // Definir el valor para la columna 'activo' (asumimos 1 para activo)
                $activo = 1;

                if ($id_materia > 0) {
                    $stmt_insert_m->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
                    $stmt_insert_m->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                    $stmt_insert_m->bindParam(':precio_hora', $precio);
                    $stmt_insert_m->bindParam(':activo', $activo, PDO::PARAM_INT); // <-- NUEVA LÍNEA CLAVE
                    $stmt_insert_m->execute();
                }
            }
        }

        $conn->commit();

        // Redireccionar con éxito, manteniendo la pestaña de materias activa
        header('Location: configurar_perfil.php?exito=materias&anchor=materias');
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        // Nota: Si quieres ver el error de la BD, cambia el header por un die()
        // die("Error en oferta_tutorias: " . $e->getMessage()); 
        $error_detail = urlencode("Error BD: " . $e->getMessage());
        header("Location: configurar_perfil.php?error=db_materias&anchor=materias");
        exit;
    }
}

// ======================================================================
// 6. **PROCESAR SECCIÓN DE DISPONIBILIDAD (HORARIO) - MÚLTIPLES BLOQUES**
// ======================================================================
elseif ($seccion === 'disponibilidad') {
    // 🛑 AHORA ESPERAMOS UN ARRAY DE BLOQUES (horarios)
    $horarios = $_POST['horarios'] ?? []; // Debería ser un array de arrays: [['dia' => 'LUNES', 'inicio' => 'HH:MM:SS', 'fin' => 'HH:MM:SS'], ...]

    try {
        $conn->beginTransaction();

        // Paso 1: Eliminar toda la disponibilidad existente (DEBE HACERSE SIEMPRE)
        $sql_del_dispo = "DELETE FROM disponibilidad WHERE id_tutor = :tutor_id";
        $stmt_del_dispo = $conn->prepare($sql_del_dispo);
        $stmt_del_dispo->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
        $stmt_del_dispo->execute();

        // Paso 2: Insertar los nuevos bloques de disponibilidad
        $dias_validos = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO', 'DOMINGO'];

        $sql_ins_dispo = "INSERT INTO disponibilidad (id_tutor, dia_semana, hora_inicio, hora_fin) VALUES (:tutor_id, :dia, :inicio, :fin)";
        $stmt_ins_dispo = $conn->prepare($sql_ins_dispo);

        foreach ($horarios as $bloque) {
            $dia_upper = strtoupper($bloque['dia'] ?? '');
            $inicio = $bloque['inicio'] ?? '';
            $fin = $bloque['fin'] ?? '';

            // Solo insertamos si el día es válido y ambas horas están presentes
            if (in_array($dia_upper, $dias_validos) && !empty($inicio) && !empty($fin)) {
                // Validación de que la hora de inicio sea menor a la hora de fin (ya lo hace JS, pero es buena práctica de backend)
                if (strtotime($inicio) >= strtotime($fin)) {
                    // Si un bloque falla, deshacemos todo
                    $conn->rollBack();
                    redirigir('error', 'horario_invalido');
                }

                $stmt_ins_dispo->bindParam(':tutor_id', $tutor_id, PDO::PARAM_INT);
                $stmt_ins_dispo->bindParam(':dia', $dia_upper);
                $stmt_ins_dispo->bindParam(':inicio', $inicio);
                $stmt_ins_dispo->bindParam(':fin', $fin);
                $stmt_ins_dispo->execute();
            }
        }

        $conn->commit();

        // Éxito
        redirigir('exito', 'disponibilidad');

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error al actualizar disponibilidad con bloques: " . $e->getMessage());
        redirigir('error', 'db_disponibilidad');
    }
}

// 7. **REDIRECCIÓN POR DEFECTO** (Si no se reconoce la sección)
redirigir('error', 'seccion_invalida');

?>