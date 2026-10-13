using System;
using System.Diagnostics;
using System.IO;
using System.Reflection;
using System.Text;
using System.Windows.Forms;

[assembly: AssemblyTitle("Inventory System Setup")]
[assembly: AssemblyDescription("Installer for the Inventory System local Windows application")]
[assembly: AssemblyCompany("San Roque Elementary School")]
[assembly: AssemblyProduct("Inventory System")]
[assembly: AssemblyVersion("1.1.0.0")]
[assembly: AssemblyFileVersion("1.1.0.0")]

internal static class InstallerBootstrap
{
    private const string RunFromTemporaryCopyArgument = "--run-from-temporary-copy";

    private static int Main(string[] arguments)
    {
        bool quiet = Array.IndexOf(arguments, "--quiet") >= 0;
        bool runningFromTemporaryCopy = Array.IndexOf(arguments, RunFromTemporaryCopyArgument) >= 0;
        if (!quiet && !runningFromTemporaryCopy)
        {
            return LaunchTemporaryCopy();
        }

        string temporaryDirectory = Path.Combine(Path.GetTempPath(), "InventorySystemSetup-" + Guid.NewGuid().ToString("N"));
        Directory.CreateDirectory(temporaryDirectory);

        try
        {
            string payloadPath = Path.Combine(temporaryDirectory, "InventorySystemPayload.zip");
            string installScriptPath = Path.Combine(temporaryDirectory, "install-local.ps1");
            ExtractResource("InventorySystemPayload.zip", payloadPath);
            ExtractResource("install-local.ps1", installScriptPath);

            ProcessStartInfo startInfo = new ProcessStartInfo();
            startInfo.FileName = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.Windows), "System32", "WindowsPowerShell", "v1.0", "powershell.exe");
            startInfo.Arguments = "-NoProfile -ExecutionPolicy Bypass -File \"" + installScriptPath + "\"" + (quiet ? " -NoBrowser" : "");
            startInfo.WorkingDirectory = temporaryDirectory;
            startInfo.CreateNoWindow = true;
            startInfo.UseShellExecute = false;
            startInfo.RedirectStandardOutput = true;
            startInfo.RedirectStandardError = true;
            StringBuilder installerOutput = new StringBuilder();
            object outputLock = new object();
            using (Process installer = new Process())
            {
                installer.StartInfo = startInfo;
                installer.OutputDataReceived += (sender, eventArgs) =>
                {
                    if (eventArgs.Data != null) lock (outputLock) installerOutput.AppendLine(eventArgs.Data);
                };
                installer.ErrorDataReceived += (sender, eventArgs) =>
                {
                    if (eventArgs.Data != null) lock (outputLock) installerOutput.AppendLine(eventArgs.Data);
                };
                installer.Start();
                installer.BeginOutputReadLine();
                installer.BeginErrorReadLine();
                installer.WaitForExit();
                try { installer.CancelOutputRead(); } catch { }
                try { installer.CancelErrorRead(); } catch { }

                string logDirectory = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "InventorySystem", "logs");
                Directory.CreateDirectory(logDirectory);
                string logPath = Path.Combine(logDirectory, "installer.log");
                string logEntry;
                lock (outputLock)
                {
                    logEntry = "\r\n--- Setup " + DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + " (exit " + installer.ExitCode + ") ---\r\n" + installerOutput.ToString();
                }
                File.AppendAllText(logPath, logEntry, new UTF8Encoding(false));

                if (installer.ExitCode != 0)
                {
                    if (!quiet)
                    {
                        MessageBox.Show(
                            "Setup could not complete (exit code " + installer.ExitCode + "). Details were saved to:\r\n" + logPath,
                            "Inventory System Setup",
                            MessageBoxButtons.OK,
                            MessageBoxIcon.Error);
                    }
                    return installer.ExitCode;
                }
            }

            if (!quiet)
            {
                MessageBox.Show(
                    "Inventory System was installed for the current Windows user. Your database will be stored separately under LocalAppData.",
                    "Inventory System Setup",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);
            }
            return 0;
        }
        catch (Exception exception)
        {
            if (!quiet)
            {
                MessageBox.Show(exception.Message, "Inventory System Setup", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
            return 1;
        }
        finally
        {
            try
            {
                Directory.Delete(temporaryDirectory, true);
            }
            catch
            {
            }

            if (runningFromTemporaryCopy)
            {
                ScheduleTemporaryCopyCleanup(Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location));
            }
        }
    }

    private static int LaunchTemporaryCopy()
    {
        string temporaryDirectory = Path.Combine(Path.GetTempPath(), "InventorySystemSetupRun-" + Guid.NewGuid().ToString("N"));
        try
        {
            Directory.CreateDirectory(temporaryDirectory);
            string sourcePath = Assembly.GetExecutingAssembly().Location;
            string temporarySetupPath = Path.Combine(temporaryDirectory, Path.GetFileName(sourcePath));
            File.Copy(sourcePath, temporarySetupPath, true);

            ProcessStartInfo startInfo = new ProcessStartInfo();
            startInfo.FileName = temporarySetupPath;
            startInfo.Arguments = RunFromTemporaryCopyArgument;
            startInfo.WorkingDirectory = temporaryDirectory;
            startInfo.CreateNoWindow = true;
            startInfo.UseShellExecute = false;
            startInfo.WindowStyle = ProcessWindowStyle.Hidden;
            Process.Start(startInfo);
            return 0;
        }
        catch (Exception exception)
        {
            try
            {
                Directory.Delete(temporaryDirectory, true);
            }
            catch
            {
            }

            MessageBox.Show(
                "The setup could not start from a temporary copy. " + exception.Message,
                "Inventory System Setup",
                MessageBoxButtons.OK,
                MessageBoxIcon.Error);
            return 1;
        }
    }

    private static void ScheduleTemporaryCopyCleanup(string temporaryDirectory)
    {
        try
        {
            string escapedDirectory = temporaryDirectory.Replace("'", "''");
            string cleanupScript = "Wait-Process -Id " + Process.GetCurrentProcess().Id +
                " -ErrorAction SilentlyContinue; Remove-Item -LiteralPath '" + escapedDirectory +
                "' -Recurse -Force -ErrorAction SilentlyContinue";
            string encodedScript = Convert.ToBase64String(Encoding.Unicode.GetBytes(cleanupScript));
            ProcessStartInfo cleanupInfo = new ProcessStartInfo();
            cleanupInfo.FileName = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.Windows), "System32", "WindowsPowerShell", "v1.0", "powershell.exe");
            cleanupInfo.Arguments = "-NoProfile -ExecutionPolicy Bypass -EncodedCommand " + encodedScript;
            cleanupInfo.CreateNoWindow = true;
            cleanupInfo.UseShellExecute = false;
            cleanupInfo.WindowStyle = ProcessWindowStyle.Hidden;
            Process.Start(cleanupInfo);
        }
        catch
        {
        }
    }

    private static void ExtractResource(string name, string destinationPath)
    {
        using (Stream source = Assembly.GetExecutingAssembly().GetManifestResourceStream(name))
        {
            if (source == null)
            {
                throw new InvalidOperationException("Installer resource is missing: " + name);
            }

            using (FileStream destination = File.Create(destinationPath))
            {
                source.CopyTo(destination);
            }
        }
    }
}
