<?php

class Logger
{
    private string $logFile;
    private bool $logToConsole;

    /**
     * Constructor
     * 
     * @param string $file Path to log file
     * @param bool $console Whether to also log to console
     */
    public function __construct(string $file = __DIR__ . '/app.log', bool $console = false)
    {
        $this->logFile = $file;
        $this->logToConsole = $console;

        // Ensure the log directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Log an info message
     */
    public function info(string $message): void
    {
        $this->writeLog('INFO', $message);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message): void
    {
        $this->writeLog('WARNING', $message);
    }

    /**
     * Log an error message
     */
    public function error(string $message): void
    {
        $this->writeLog('ERROR', $message);
    }

    /**
     * Write the log to file (and optionally to console)
     */
    private function writeLog(string $level, string $message): void
    {
        $time = date('Y-m-d H:i:s');
        $logEntry = "[$time][$level] $message" . PHP_EOL;

        // Write to file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);

        // Optional console output
        if ($this->logToConsole) {
            echo $logEntry;
        }
    }
}
