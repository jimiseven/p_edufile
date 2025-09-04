<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Curso Seleccionado</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-container {
            display: flex;
            align-items: center;
        }

        .search-container input {
            margin-right: 10px;
        }

        .estado-select {
            min-width: 150px;
        }

        .table {
            background-color: #2C3E50;
            color: #ffffff;
            text-transform: uppercase;
        }

        .table th {
            background-color: #34495E;
            color: #ECF0F1;
        }

        .table td {
            background-color: #3E4A59;
            color: #ECF0F1;
        }

        .table td form .estado-select {
            background-color: #3E4A59;
            color: #ffffff;
        }

        .table td form .btn {
            background-color: #1ABC9C;
            color: #ffffff;
        }

        .table td form .btn:hover {
            background-color: #16A085;
        }

        .error-message {
            color: #e74c3c;
            font-weight: bold;
            text-align: center;
        }

        .curso-info-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .curso-info-card {
            background-color: #34495E;
            border-radius: 10px;
            padding: 15px;
            margin-left: 10px;
            text-align: center;
            width: 180px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .curso-info-card i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1ABC9C;
        }

        .curso-info-card h5 {
            font-size: 16px;
            margin: 0;
            color: #ECF0F1;
        }

        .curso-info-card p {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            color: #ffffff;
        }

        /* Estilo para el título del modal en color negro */
        .modal-title {
            color: #000000; /* Establece el color del texto a negro */
        }
    </style>
</head>

<body style="background-color: #1E2A38; color: #ffffff;">
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-4">
            <?php
            include 'conexion.php';

            $grade = strtoupper(isset($_GET['grade']) ? $_GET['grade'] : '');
            $parallel = strtoupper(isset($_GET['parallel']) ? $_GET['parallel'] : '');
            $level = isset($_GET['level']) ? $_GET['level'] : '';

            if (empty($grade) || empty($parallel) || empty($level)) {
                echo "<div class='error-message'>Error: Faltan datos en la URL.</div>";
                exit;
            }

            $levelQuery = "SELECT levels.name AS level_name FROM levels WHERE levels.name = ? LIMIT 1";
            $stmt = $conn->prepare($levelQuery);
            $stmt->bind_param("s", $level);
            $stmt->execute();
            $levelResult = $stmt->get_result();

            if ($levelResult && $levelResult->num_rows > 0) {
                $levelData = $levelResult->fetch_assoc();
                $levelName = strtoupper($levelData['level_name']);
            } else {
                echo "<div class='error-message'>Error: Curso no encontrado para este nivel.</div>";
                exit;
            }

            echo "<h2 class='mb-4'>NIVEL: $levelName</h2>";
            echo "<h3 class='mb-4'>CURSO: $grade \"$parallel\"</h3>";

            // Obtener estadísticas del curso
            $statsQuery = "SELECT
                            SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END) AS no_inscritos,
                            SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS efectivos,
                            SUM(CASE WHEN s.gender = 'M' THEN 1 ELSE 0 END) AS masculinos,
                            SUM(CASE WHEN s.gender = 'F' THEN 1 ELSE 0 END) AS femeninos
                          FROM students s
                          INNER JOIN student_courses sc ON s.id = sc.student_id
                          INNER JOIN courses c ON sc.course_id = c.id
                          WHERE c.grade = ? AND c.parallel = ? AND c.level_id = (SELECT id FROM levels WHERE name = ?)";

            $stmt = $conn->prepare($statsQuery);
            $stmt->bind_param("sss", $grade, $parallel, $level);
            $stmt->execute();
            $statsResult = $stmt->get_result();
            $stats = $statsResult->fetch_assoc();
            ?>
            <!-- Diseño mejorado para la información del curso -->
            <div class="curso-info-container">
                <div class="curso-info-card">
                    <i class="bi bi-person-x"></i>
                    <h5>No Inscritos</h5>
                    <p><?php echo $stats['no_inscritos']; ?></p>
                </div>
                <div class="curso-info-card">
                    <i class="bi bi-person-check"></i>
                    <h5>Efectivos</h5>
                    <p><?php echo $stats['efectivos']; ?></p>
                </div>
                <div class="curso-info-card">
                    <i class="bi bi-gender-male"></i>
                    <h5>Masculinos</h5>
                    <p><?php echo $stats['masculinos']; ?></p>
                </div>
                <div class="curso-info-card">
                    <i class="bi bi-gender-female"></i>
                    <h5>Femeninos</h5>
                    <p><?php echo $stats['femeninos']; ?></p>
                </div>
            </div>

            <?php
            // Consulta para obtener los estudiantes del curso
            $query = "SELECT s.id, UPPER(CONCAT(s.last_name_father, ' ', s.last_name_mother, ' ', s.first_name)) AS nombre, UPPER(s.rude_number) AS rude_number, sc.status
                      FROM students s
                      INNER JOIN student_courses sc ON s.id = sc.student_id
                      INNER JOIN courses c ON sc.course_id = c.id
                      WHERE c.grade = ? AND c.parallel = ? AND c.level_id = (SELECT id FROM levels WHERE name = ?)
                      ORDER BY FIELD(sc.status, 'Efectivo - I', 'No Inscrito') DESC, s.last_name_father ASC, s.last_name_mother ASC, s.first_name ASC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $grade, $parallel, $level);
            $stmt->execute();
            $result = $stmt->get_result();

            if (!$result) {
                echo "<div class='error-message'>Error al obtener los estudiantes: " . $conn->error . "</div>";
                exit;
            }
            ?>

            <div class="action-buttons">
                <div class="search-container">
                    <input type="text" id="searchStudent" class="form-control" placeholder="Buscar estudiante...">
                    <button class="btn btn-light" id="clearSearch" title="Borrar búsqueda">&times;</button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#printModal">Vista PDF</button>
                </div>
            </div>

            <table class="table table-bordered" id="studentsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Rude</th>
                        <th>Estado</th>
                        <th>Editar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $counter = 1;
                        while ($row = $result->fetch_assoc()) {
                            $estado_color = match ($row['status']) {
                                'Efectivo - I' => '#28a745',
                                'No Inscrito' => '#dc3545',
                                default => '#6c757d',
                            };
                            echo "<tr>
                                    <td>" . $counter . "</td>
                                    <td>" . htmlspecialchars($row['nombre']) . "</td>
                                    <td>" . htmlspecialchars($row['rude_number']) . "</td>
                                    <td>
                                        <form class='estado-form d-flex align-items-center' data-student-id='" . htmlspecialchars($row['id']) . "' data-grade='$grade' data-parallel='$parallel'>
                                            <select name='estado' class='form-select form-select-sm estado-select me-2' style='background-color: $estado_color; color: white;'>
                                                <option value='Efectivo - I'" . ($row['status'] == 'Efectivo - I' ? ' selected' : '') . ">Efectivo - I</option>
                                                <option value='No Inscrito'" . ($row['status'] == 'No Inscrito' ? ' selected' : '') . ">No Inscrito</option>
                                            </select>
                                            <button type='button' class='btn btn-sm estado-guardar'>Guardar</button>
                                        </form>
                                    </td>
                                    <td><a href='editarEstudiante.php?student_id=" . htmlspecialchars($row['rude_number']) . "&source=vistaGenCurso&grade=$grade&parallel=$parallel&level=$level' class='btn btn-primary btn-sm'>Editar</a></td>
                                  </tr>";
                            $counter++;
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>NO HAY ESTUDIANTES DISPONIBLES</td></tr>";
                    }

                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>

            <!-- Modal de Impresión -->
            <div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="printModalLabel" style="color: black;">Seleccionar Orientación de Impresión</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p style="color: black;">¿Cómo desea generar la vista PDF?</p>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-print-vertical">Vertical</button>
                                <button type="button" class="btn btn-outline-secondary btn-print-horizontal">Horizontal</button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('.estado-form');
            const searchInput = document.getElementById('searchStudent');
            const clearButton = document.getElementById('clearSearch');
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tr');
            const printModal = document.getElementById('printModal');
            const printVerticalButton = printModal.querySelector('.btn-print-vertical');
            const printHorizontalButton = printModal.querySelector('.btn-print-horizontal');

            printVerticalButton.addEventListener('click', function() {
                const grade = '<?php echo urlencode($grade); ?>';
                const parallel = '<?php echo urlencode($parallel); ?>';
                const level = '<?php echo urlencode($level); ?>';
                window.open(`vistaPDFv.php?grade=${grade}&parallel=${parallel}&level=${level}`, '_blank');
                bootstrap.Modal.getInstance(printModal).hide(); // Close modal after redirect
            });

            printHorizontalButton.addEventListener('click', function() {
                const grade = '<?php echo urlencode($grade); ?>';
                const parallel = '<?php echo urlencode($parallel); ?>';
                const level = '<?php echo urlencode($level); ?>';
                window.open(`vistaPDFh.php?grade=${grade}&parallel=${parallel}&level=${level}`, '_blank');
                bootstrap.Modal.getInstance(printModal).hide(); // Close modal after redirect
            });


            forms.forEach(form => {
                const select = form.querySelector('.estado-select');
                const button = form.querySelector('.estado-guardar');

                button.addEventListener('click', () => {
                    const studentId = form.dataset.studentId;
                    const grade = form.dataset.grade;
                    const parallel = form.dataset.parallel;
                    const estado = select.value;

                    fetch('cambiarEstado.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                student_id: studentId,
                                estado,
                                grade,
                                parallel
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const newColor = data.color;
                                select.style.backgroundColor = newColor;
                                button.style.backgroundColor = newColor;
                                alert('Estado actualizado correctamente.');
                            } else {
                                alert('Error al actualizar el estado: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Ocurrió un error al intentar actualizar el estado.');
                        });
                });
            });

            searchInput.addEventListener('input', function() {
                const searchValue = this.value.toLowerCase();

                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    const studentName = cells[1]?.textContent.toLowerCase() || '';
                    const studentRude = cells[2]?.textContent.toLowerCase() || '';

                    if (studentName.includes(searchValue) || studentRude.includes(searchValue)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            });

            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                for (let i = 1; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            });
        });
    </script>
</body>

</html>
