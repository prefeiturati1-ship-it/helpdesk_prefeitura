Set WshShell = CreateObject("WScript.Shell")
strDesktop = WshShell.SpecialFolders("Desktop")
Set oShellLink = WshShell.CreateShortcut(strDesktop & "\Help TI Borborema.lnk")

' Configura o caminho do Chrome e a URL do seu sistema
oShellLink.TargetPath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
oShellLink.Arguments = "http://localhost/helpdesk_prefeitura/account/login.php"
oShellLink.WorkingDirectory = "C:\Program Files\Google\Chrome\Application"

' Define o ícone (O número 0 usa o ícone padrão, troque a DLL ou o caminho do .ico se quiser)
oShellLink.IconLocation = "%SystemRoot%\System32\SHELL32.dll, 0" 

oShellLink.Save