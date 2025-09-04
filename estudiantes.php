<?php
include 'conexion.php'; // Incluir conexión a la base de datos
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Estudiantes</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Estilos generales */
        body {
            background-color: #1E2A38;
            color: #ffffff;
        }

        /* Estilos del sidebar (restaurados) */
        .sidebar {
            background-color: #000;
            min-width: 250px;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
        }

        .sidebar .nav-link {
            color: #ffffff;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: #2C3E50;
        }

        .sidebar .nav-link.active {
            background-color: #3498db;
            font-weight: bold;
        }

        /* Estilos de la tabla (mejorados) */
        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            max-height: 100vh;
            margin-left: 250px;
        }

        .table-container {
            background: #2C3E50;
            border-radius: 8px;
            overflow: auto; /* Added overflow auto for the table container */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: #34495E;
            color: #ECF0F1;
            padding: 16px;
            font-weight: 600;
            border-bottom: 2px solid #1E2A38;
            text-align: left;
        }

        .table tbody td {
            background-color: #3E4A59;
            color: #ECF0F1;
            padding: 14px;
            border-bottom: 1px solid #2C3E50;
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover td {
            background-color: #47535f;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

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

        .btn-clear {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-clear:hover {
            background-color: #c82333;
        }

        .btn-new-student {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .btn-new-student:hover {
            background-color: #218838;
        }

        .alert-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            padding: 15px 20px;
            background-color: #dc3545;
            color: #fff;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .alert-popup.show {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Incluir el sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Contenido principal -->
        <div class="main-content">
            <h2 class="mb-4">Lista de Estudiantes</h2>

            <div class="action-buttons">
                <div class="search-container">
                    <input type="text" id="searchStudent" class="form-control" placeholder="Buscar estudiante por nombre, apellido o RUDE...">
                    <button class="btn btn-clear" id="clearSearch">&times;</button>
                </div>
                <div class="d-flex flex-column">
                    <a href="cambioCurso.php" class="btn btn-danger mb-2">Cambio Curso</a>
                    <a href="nuevoRegistroEstudiante.php" class="btn btn-new-student">Nuevo Estudiante</a>
                </div>
            </div>

            <div class="table-container">
                <table class="table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Apellido Paterno</th>
                            <th>Apellido Materno</th>
                            <th>Nombres</th>
                            <th>Nivel</th>
                            <th>Curso</th>
                            <th>Paralelo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                            SELECT s.last_name_father, s.last_name_mother, s.first_name, l.name AS level_name, c.grade, c.parallel, s.rude_number
                            FROM students s
                            INNER JOIN student_courses sc ON s.id = sc.student_id
                            INNER JOIN courses c ON sc.course_id = c.id
                            INNER JOIN levels l ON c.level_id = l.id
                            ORDER BY s.last_name_father ASC, s.last_name_mother ASC, s.first_name ASC
                        ";

                        $result = $conn->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['last_name_father']) . "</td>
                                    <td>" . htmlspecialchars($row['last_name_mother']) . "</td>
                                    <td>" . htmlspecialchars($row['first_name']) . "</td>
                                    <td>" . htmlspecialchars($row['level_name']) . "</td>
                                    <td>" . htmlspecialchars($row['grade']) . "</td>
                                    <td>" . htmlspecialchars($row['parallel']) . "</td>
                                    <td>
                                        <a href='editarEstudiante.php?student_id=" . urlencode($row['rude_number']) . "&source=estudiantes' class='btn btn-primary btn-sm btn-action'>Editar</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No hay estudiantes disponibles</td></tr>";
                        }

                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchStudent');
            const clearButton = document.getElementById('clearSearch');
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tr');

            searchInput.addEventListener('input', function() {
                const searchValue = this.value.toLowerCase();

                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let found = false;

                    // Buscar en nombre, apellidos y RUDE
                    const fullName = cells[0].textContent.toLowerCase() + ' ' +
                        cells[1].textContent.toLowerCase() + ' ' +
                        cells[2].textContent.toLowerCase();
                    const rudeNumber = rows[i].querySelector('a[href*="editarEstudiante.php"]')
                        .getAttribute('href')
                        .split('student_id=')[1]
                        .toLowerCase();

                    if (fullName.includes(searchValue) || rudeNumber.includes(searchValue)) {
                        found = true;
                    }

                    rows[i].style.display = found ? '' : 'none';
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

    <!-- Pop-up de confirmación -->
    <div id="alertPopup" class="alert-popup">Estudiante eliminado correctamente.</div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') && urlParams.get('status') === 'deleted') {
                const alertPopup = document.getElementById('alertPopup');
                alertPopup.classList.add('show');

                setTimeout(() => {
                    alertPopup.classList.remove('show');
                }, 1500);
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') && urlParams.get('status') === 'updated') {
                const alertPopup = document.createElement('div');
                alertPopup.className = 'alert-popup show';
                alertPopup.textContent = 'Datos del estudiante actualizados correctamente';
                document.body.appendChild(alertPopup);

                setTimeout(() => {
                    alertPopup.remove();
                }, 1500);
            }
        });
    </script>
</body>

</html>
