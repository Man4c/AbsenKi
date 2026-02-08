@echo off
REM Backup script for AbsenKi database
REM Run this daily via Windows Task Scheduler

set MYSQL_USER=root
set MYSQL_HOST=127.0.0.1
set DB_NAME=absenki
set BACKUP_DIR=C:\laragon\www\AbsenKi\storage\backups
set DATE_STR=%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set DATE_STR=%DATE_STR: =0%

REM Create backup directory if not exists
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM Create backup
mysqldump -u %MYSQL_USER% -h %MYSQL_HOST% %DB_NAME% > "%BACKUP_DIR%\%DB_NAME%_%DATE_STR%.sql"

REM Keep only last 7 days of backups
forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -7 /c "cmd /c del @path" 2>nul

echo Backup completed: %DB_NAME%_%DATE_STR%.sql
