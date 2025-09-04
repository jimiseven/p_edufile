@echo off
REM Cambia el directorio al repositorio local
cd C:\xampp\htdocs\edufile1

REM Realiza un git pull para actualizar el repositorio local
git pull origin main

REM Agrega todos los cambios
git add -A

REM Haz commit de los cambios
git commit -m "cambios desde sandra"

REM Realiza un git push para subir los cambios al repositorio remoto
git push origin main

REM Pausa para ver el resultado antes de cerrar la ventana
pause
