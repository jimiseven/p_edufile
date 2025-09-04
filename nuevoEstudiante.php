<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Estudiante</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        input[type="text"],
        select {
            text-transform: uppercase;
        }
    </style>
</head>

<body style="background-color: #1E2A38; color: #ffffff;">
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>


        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-3" style="max-width: 1200px; margin: auto;">
            <h2 class="mb-3">Formulario de Registro de Estudiantes</h2>
            <form action="guardarEstudiante.php" method="POST">
                <div class="mb-3 p-3" style="background-color: #2C3E50; border-radius: 10px;">
                    <h4 class="mb-2">Información del Estudiante</h4>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="last_name_father" class="form-label">A Paterno</label>
                            <input type="text" class="form-control" id="last_name_father" name="last_name_father">
                        </div>
                        <div class="col-md-4">
                            <label for="last_name_mother" class="form-label">A Materno</label>
                            <input type="text" class="form-control" id="last_name_mother" name="last_name_mother">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label for="identity_card" class="form-label">CI</label>
                            <input type="text" class="form-control" id="identity_card" name="identity_card">
                        </div>
                        <div class="col-md-3">
                            <label for="gender" class="form-label">Sexo</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Seleccione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="birth_date" class="form-label">Fecha Nacimiento</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date">
                        </div>
                        <div class="col-md-3">
                            <label for="rude_number" class="form-label">RUDE</label>
                            <input type="text" class="form-control" id="rude_number" name="rude_number" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3 p-3" style="background-color: #2C3E50; border-radius: 10px;">
                    <h4 class="mb-2">Información del Responsable</h4>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="guardian_first_name" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="guardian_first_name" name="guardian_first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="guardian_last_name" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="guardian_last_name" name="guardian_last_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="guardian_identity_card" class="form-label">CI</label>
                            <input type="text" class="form-control" id="guardian_identity_card" name="guardian_identity_card" required>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label for="guardian_phone_number" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="guardian_phone_number" name="guardian_phone_number" required>
                        </div>
                        <div class="col-md-6">
                            <label for="guardian_relationship" class="form-label">Relación</label>
                            <select class="form-select" id="guardian_relationship" name="guardian_relationship" required>
                                <option value="">Seleccione</option>
                                <option value="padre">Padre</option>
                                <option value="madre">Madre</option>
                                <option value="tutor">Tutor</option>
                            </select>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="grade" value="<?php echo htmlspecialchars($_GET['grade']); ?>">
                <input type="hidden" name="parallel" value="<?php echo htmlspecialchars($_GET['parallel']); ?>">
                <input type="hidden" name="status" value="No Inscrito">
                <div class="text-end">
                    <button type="submit" class="btn btn-success">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>