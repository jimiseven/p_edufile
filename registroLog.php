<?php
include 'conexion.php'; // Incluir conexión a la base de datos
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Log de Estudiantes</title>
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
            <h2 class="mb-4">Registro Log de Estudiantes - Últimas 10 Acciones</h2>

            <div class="table-container">
                <table class="table" id="logTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo del Estudiante</th>
                            <th>Ver Información</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                            SELECT
                                s.rude_number,
                                UPPER(CONCAT(s.last_name_father, ' ', s.last_name_mother, ', ', s.first_name)) AS nombre_completo
                            FROM students s
                            ORDER BY s.id DESC
                            LIMIT 10  -- Limit to last 10 records
                        ";
                        $result = $conn->query($query);

                        if ($result->num_rows > 0) {
                            $counter = 1;
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $counter++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['nombre_completo']) . "</td>";
                                echo "<td><a href='editarEstudiante.php?student_id=" . urlencode($row['rude_number']) . "&source=registroLog' class='btn btn-primary btn-sm btn-action'>Ver Información</a></td>";
                                echo "<td>Nuevo Registro</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>No hay registros de estudiantes disponibles</td></tr>";
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
</body>

</html>
