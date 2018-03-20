: Моментальное выключение
: shutdown -a > nul
: timeout /t 5 /nobreak > nul
: shutdown /p
mshta.exe vbscript:Execute("msgbox ""Fore real shutdown please comment this line in shutdown_system.bat and uncomment others above."",0,""DEMO MODE"":close")