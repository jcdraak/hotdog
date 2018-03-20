: Моментальная перезагрузка
: shutdown -a > nul
: timeout /t 5 /nobreak > nul
: shutdown /g /f > nul
mshta.exe vbscript:Execute("msgbox ""Fore real reboot please comment this line in restart_system.bat and uncomment others above."",0,""DEMO MODE"":close")