@echo off
REM =====================================================
REM Script de copie des assets pour MoodTracker
REM =====================================================

echo.
echo ===== Integration des Templates - MoodTracker =====
echo.

REM Chemin de base
set BASE_DIR=%~dp0

REM Variables de chemins
set SOURCE_FRONT=%BASE_DIR%templates\templates\front
set SOURCE_ADMIN=%BASE_DIR%templates\templates\back
set DEST_FRONT=%BASE_DIR%public\front_assets
set DEST_ADMIN=%BASE_DIR%public\admin_assets

echo Chemins:
echo Source Front: %SOURCE_FRONT%
echo Source Admin: %SOURCE_ADMIN%
echo Destination Front: %DEST_FRONT%
echo Destination Admin: %DEST_ADMIN%
echo.

REM Créer les répertoires de destination s'ils n'existent pas
echo Création des répertoires de destination...
if not exist "%DEST_FRONT%" mkdir "%DEST_FRONT%"
if not exist "%DEST_ADMIN%" mkdir "%DEST_ADMIN%"

REM Copie des assets front-end
echo.
echo [1/4] Copie des CSS front-end...
if exist "%SOURCE_FRONT%\css" (
    xcopy "%SOURCE_FRONT%\css" "%DEST_FRONT%\css" /S /Y /I
    echo CSS front-end copié avec succès!
) else (
    echo ERREUR: Dossier CSS non trouvé dans le template front
)

echo.
echo [2/4] Copie des JS front-end...
if exist "%SOURCE_FRONT%\js" (
    xcopy "%SOURCE_FRONT%\js" "%DEST_FRONT%\js" /S /Y /I
    echo JS front-end copié avec succès!
) else (
    echo ERREUR: Dossier JS non trouvé dans le template front
)

echo.
echo [3/4] Copie des images front-end...
if exist "%SOURCE_FRONT%\img" (
    xcopy "%SOURCE_FRONT%\img" "%DEST_FRONT%\img" /S /Y /I
    echo Images front-end copiées avec succès!
) else (
    echo ERREUR: Dossier IMG non trouvé dans le template front
)

echo.
echo [4/4] Copie des assets admin...
if exist "%SOURCE_ADMIN%\assets" (
    xcopy "%SOURCE_ADMIN%\assets" "%DEST_ADMIN%" /S /Y /I
    echo Assets admin copiés avec succès!
) else (
    echo ERREUR: Dossier assets non trouvé dans le template admin
)

echo.
echo ===== Copie terminée! =====
echo.
echo Les assets ont été copiés vers:
echo - Front: %DEST_FRONT%
echo - Admin: %DEST_ADMIN%
echo.
pause
