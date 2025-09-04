<?php
include 'conexion.php';

// Consulta para datos globales
$queryGlobal = "SELECT 
                SUM(CASE WHEN gender = 'M' THEN 1 ELSE 0 END) AS hombres,
                SUM(CASE WHEN gender = 'F' THEN 1 ELSE 0 END) AS mujeres,
                SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS efectivos,
                SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END) AS no_inscritos
            FROM students s
            LEFT JOIN student_courses sc ON s.id = sc.student_id";
$resultGlobal = $conn->query($queryGlobal);
$globalData = $resultGlobal->fetch_assoc();

// Consulta para niveles disponibles
$queryLevels = "SELECT DISTINCT name FROM levels";
$resultLevels = $conn->query($queryLevels);
$levels = [];
while($row = $resultLevels->fetch_assoc()) {
    $levels[] = $row['name'];
}

// Consulta para datos por nivel
$queryByLevel = "SELECT 
                l.name AS level_name,
                SUM(CASE WHEN s.gender = 'M' THEN 1 ELSE 0 END) AS hombres,
                SUM(CASE WHEN s.gender = 'F' THEN 1 ELSE 0 END) AS mujeres,
                SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS efectivos,
                SUM(CASE WHEN sc.status = 'Efectivo - I' AND s.gender = 'M' THEN 1 ELSE 0 END) AS efectivos_hombres,
                SUM(CASE WHEN sc.status = 'Efectivo - I' AND s.gender = 'F' THEN 1 ELSE 0 END) AS efectivos_mujeres,
                SUM(CASE WHEN sc.status = 'No Inscrito' THEN 1 ELSE 0 END) AS no_inscritos
            FROM students s
            INNER JOIN student_courses sc ON s.id = sc.student_id
            INNER JOIN courses c ON sc.course_id = c.id
            INNER JOIN levels l ON c.level_id = l.id
            GROUP BY l.name";
$resultByLevel = $conn->query($queryByLevel);

// Consulta para cursos con filtro
$queryByCourse = "SELECT 
                l.name AS level_name,
                CONCAT(c.grade, ' ', c.parallel) AS course_name,
                SUM(CASE WHEN s.gender = 'M' AND sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS hombres,
                SUM(CASE WHEN s.gender = 'F' AND sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS mujeres,
                SUM(CASE WHEN sc.status = 'Efectivo - I' THEN 1 ELSE 0 END) AS total
            FROM courses c
            INNER JOIN levels l ON c.level_id = l.id
            LEFT JOIN student_courses sc ON c.id = sc.course_id
            LEFT JOIN students s ON sc.student_id = s.id";

if(!empty($selectedLevel) && in_array($selectedLevel, $levels)) {
    $queryByCourse .= " WHERE l.name = '".$conn->real_escape_string($selectedLevel)."'";
}

$queryByCourse .= " GROUP BY l.name, c.grade, c.parallel
                ORDER BY l.name, c.grade, c.parallel";
$resultByCourse = $conn->query($queryByCourse);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFile - Dashboard Estadístico</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #3498db;    --color-secondary: #2ecc71;
            --color-accent: #e74c3c;     --color-warning: #f1c40f;
            --color-dark: #2c3e50;       --color-pink: #e91e63;
        }

        body {
            background-color: #1E2A38;
            color: #ffffff;
        }

        /* Sidebar actualizado para coincidir con estudiantes.php */
        .sidebar {
            background-color: #000;
            min-width: 250px;
            min-height: 100vh;
            padding: 20px;
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

        /* Contenedor principal */
        .main-content {
            flex-grow: 1;
            padding: 20px;
        }

        /* Estilos específicos del dashboard */
        .totales-generales {
            background: linear-gradient(135deg, var(--color-dark) 0%, #1a2533 100%);
            border-radius: 15px;
            border: 2px solid var(--color-primary);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--color-dark);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid var(--color-primary);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .total-badge {
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            margin: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .total-hombres { background: var(--color-primary); }
        .total-mujeres { background: var(--color-pink); }
        .total-efectivos { background: var(--color-secondary); }
        .total-noinscritos { background: var(--color-warning); }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-top: 0.5rem;
        }

        .filter-container {
            background: #2c3e50;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .form-select {
            background-color: #34495e;
            color: white;
            border: 1px solid #3498db;
            width: 250px;
        }

        .loading {
            display: none;
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Contenido principal -->
        <div class="main-content">
            <!-- Conteo total -->
            <div class="totales-generales">
                <h3 class="mb-4"><i class="bi bi-building me-2"></i>Totales Generales</h3>
                <div class="row">
                    <div class="col-md-3">
                        <div class="total-badge total-hombres">
                            <i class="bi bi-gender-male"></i>
                            <div class="h5">Hombres</div>
                            <div class="stat-value"><?= htmlspecialchars($globalData['hombres']) ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="total-badge total-mujeres">
                            <i class="bi bi-gender-female"></i>
                            <div class="h5">Mujeres</div>
                            <div class="stat-value"><?= htmlspecialchars($globalData['mujeres']) ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="total-badge total-efectivos">
                            <i class="bi bi-check2-circle"></i>
                            <div class="h5">Efectivos</div>
                            <div class="stat-value"><?= htmlspecialchars($globalData['efectivos']) ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="total-badge total-noinscritos">
                            <i class="bi bi-x-circle"></i>
                            <div class="h5">No Inscritos</div>
                            <div class="stat-value"><?= htmlspecialchars($globalData['no_inscritos']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas por Nivel -->
            <h3 class="mb-4"><i class="bi bi-layers me-2"></i>Estadísticas por Nivel</h3>
            <div class="row g-4">
                <?php while($level = $resultByLevel->fetch_assoc()) { ?>
                <div class="col-lg-4 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-mortarboard fs-4 me-2"></i>
                            <h4 class="mb-0"><?= htmlspecialchars($level['level_name']) ?></h4>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="bg-dark p-2 rounded text-center">
                                    <div class="text-primary small">Hombres</div>
                                    <div class="stat-value"><?= $level['hombres'] ?></div>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="bg-dark p-2 rounded text-center">
                                    <div class="text-pink small">Mujeres</div>
                                    <div class="stat-value"><?= $level['mujeres'] ?></div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <div class="bg-dark p-3 rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-success small">Efectivos</span>
                                        <span class="badge bg-success">Total: <?= $level['efectivos'] ?></span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="text-primary small">Hombres</div>
                                            <div class="stat-value"><?= $level['efectivos_hombres'] ?></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-pink small">Mujeres</div>
                                            <div class="stat-value"><?= $level['efectivos_mujeres'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <div class="bg-dark p-2 rounded text-center">
                                    <div class="text-warning small">No Inscritos</div>
                                    <div class="stat-value"><?= $level['no_inscritos'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <!-- Tabla de Cursos con Filtro -->
            <div class="chart-container mt-5">
                <div class="filter-container d-flex align-items-center justify-content-between">
                    <h3 class="mb-0"><i class="bi bi-table me-2"></i>Detalle por Curso</h3>
                    <div class="d-flex align-items-center">
                        <span class="me-2"><i class="bi bi-funnel"></i> Filtro:</span>
                        <select id="filtroNivel" class="form-select">
                            <option value="">Todos los niveles</option>
                            <?php foreach($levels as $level): ?>
                            <option value="<?= htmlspecialchars($level) ?>">
                                <?= htmlspecialchars($level) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="loading ms-2" id="loading">Cargando...</div>
                    </div>
                </div>

                <div class="table-responsive mt-3" id="tablaCursosContainer">
                    <?php include 'tabla_cursos.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Filtro asíncrono para la tabla de cursos
        $('#filtroNivel').change(function() {
            var nivel = $(this).val();
            $('#loading').show();
            
            $.ajax({
                url: 'tabla_cursos.php',
                type: 'GET',
                data: { nivel: nivel },
                success: function(response) {
                    $('#tablaCursosContainer').html(response);
                    $('#loading').hide();
                },
                error: function() {
                    alert('Error al cargar los datos');
                    $('#loading').hide();
                }
            });
        });
    });
    </script>
</body>
</html>