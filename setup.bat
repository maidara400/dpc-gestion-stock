@echo off
echo ============================================
echo  DPC Gestion Stock Padel - Installation
echo ============================================
echo.

echo [1/5] Installation des dependances PHP (Composer)...
call composer install --no-interaction
if errorlevel 1 (echo ERREUR Composer & pause & exit /b 1)

echo.
echo [2/5] Configuration de l'environnement...
if not exist .env (copy .env.example .env)
call php artisan key:generate --ansi

echo.
echo [3/5] Creation de la base de donnees SQLite...
if not exist database\database.sqlite (type nul > database\database.sqlite)
call php artisan migrate --seed --force

echo.
echo [4/5] Installation des dependances JS (npm)...
call npm install
if errorlevel 1 (echo ERREUR npm & pause & exit /b 1)

echo.
echo [5/5] Compilation des assets frontend...
call npm run build
if errorlevel 1 (echo ERREUR build & pause & exit /b 1)

echo.
echo ============================================
echo  Installation terminee avec succes !
echo ============================================
echo.
echo  Comptes de connexion :
echo   Admin   : admin@padel.club     / Admin@2024!
echo   Caissier: caissier@padel.club  / Caissier@2024!
echo.
echo  Pour demarrer l'application :
echo    php artisan serve
echo  Puis ouvrir : http://localhost:8000
echo.
pause
