#define AppName "Inventory System"
#define AppVersion "1.1.0"
#define AppPublisher "San Roque Elementary School"

[Setup]
AppId={{2B7D9DF4-84BB-4D7A-BDD9-47A52AF54E2F}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher={#AppPublisher}
DefaultDirName={localappdata}\Programs\InventorySystem
DefaultGroupName={#AppName}
DisableProgramGroupPage=yes
PrivilegesRequired=lowest
ArchitecturesInstallIn64BitMode=x64
OutputDir=output
OutputBaseFilename=InventorySystemSetup
SetupIconFile=..\..\assets\img\inventory-system.ico
UninstallDisplayIcon={app}\assets\img\inventory-system.ico
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern

[Tasks]
Name: "desktopicon"; Description: "Create a desktop shortcut"; GroupDescription: "Additional shortcuts:"

[Files]
Source: "stage\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{autoprograms}\{#AppName}"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\tools\installer\start-local.ps1"""; WorkingDir: "{app}"; IconFilename: "{app}\assets\img\inventory-system.ico"
Name: "{autoprograms}\Stop {#AppName}"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\tools\installer\stop-local.ps1"""; WorkingDir: "{app}"; IconFilename: "{app}\assets\img\inventory-system.ico"
Name: "{autoprograms}\Back Up {#AppName}"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\tools\installer\backup-local.ps1"""; WorkingDir: "{app}"; IconFilename: "{app}\assets\img\inventory-system.ico"
Name: "{autoprograms}\Restore {#AppName}"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\tools\installer\restore-local.ps1"""; WorkingDir: "{app}"; IconFilename: "{app}\assets\img\inventory-system.ico"
Name: "{autoprograms}\Remove {#AppName}"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\tools\installer\remove-local.ps1"""; WorkingDir: "{app}"; IconFilename: "{app}\assets\img\inventory-system.ico"
Name: "{autodesktop}\{#AppName}"; Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\tools\installer\start-local.ps1"""; WorkingDir: "{app}"; Tasks: desktopicon; IconFilename: "{app}\assets\img\inventory-system.ico"

[Run]
Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\tools\installer\start-local.ps1"""; WorkingDir: "{app}"; Description: "Launch {#AppName}"; Flags: postinstall nowait skipifsilent

[UninstallRun]
Filename: "{sys}\WindowsPowerShell\v1.0\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\tools\installer\stop-local.ps1"""; Flags: runhidden waituntilterminated

[Code]
var
  RemoveUserData: Boolean;

function InitializeUninstall(): Boolean;
var
  Choice: Integer;
begin
  Choice := MsgBox('Remove Inventory System? Click Yes to also delete its database, backups, logs, and settings. Click No to keep the data for a later reinstall.', mbConfirmation, MB_YESNOCANCEL);
  if Choice = IDYES then
  begin
    RemoveUserData := True;
    Result := True;
  end
  else if Choice = IDNO then
  begin
    RemoveUserData := False;
    Result := True;
  end
  else
    Result := False;
end;

procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
begin
  if (CurUninstallStep = usPostUninstall) and RemoveUserData then
    DelTree(ExpandConstant('{localappdata}\InventorySystem'), True, True, True);
end;
