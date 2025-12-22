@echo off
echo ===================================================
echo   NEXT DRIVE IMPORT - SERVEUR LOCAL
echo ===================================================
echo.
echo Demarrage du serveur PHP...
echo Le site va s'ouvrir dans votre navigateur.
echo.
echo NE FERMEZ PAS CETTE FENETRE tant que vous utilisez le site.
echo Pour arreter le serveur, fermez cette fenetre ou faites Ctrl+C.
echo.

start http://localhost:8000
"%USERPROFILE%\scoop\shims\php.exe" -S localhost:8000