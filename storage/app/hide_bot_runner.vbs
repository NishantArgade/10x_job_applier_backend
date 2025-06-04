Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "cmd.exe /c cd /d C:\Users\kitch\OneDrive\Desktop\10x_project\server && php artisan naukri:apply-jobs", 0