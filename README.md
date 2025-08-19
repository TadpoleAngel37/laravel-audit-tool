# Laravel Audit Tool

A Laravel Artisan command that automates security checks for multiple Laravel projects.  
It runs `composer audit` on each project, parses the results, and generates a summary report.  
The report can be emailed automatically, making it easier to stay ahead of security advisories.

---

## Features

- Audits multiple Laravel projects at once
- Uses `composer audit` for official security advisories
- Parses JSON output for clean, human-readable reports
- Sends results by email (or prints to console with `--no-mail`)
- Configurable via `.env` and `config/audit.php`
- Easy to extend (add new project paths, adjust email, tweak timeouts)

---

## Installation 

1. Clone the repo onto your server.
2. Install composer in the project root folder:
    - cd into 'laravel-audit-tool' and run 'composer install'.
3. Set up environment file:
    - run 'cp .env.example .env'
4. Configure .env:
    - Add the following variables:
          - AUDIT_REPORT_TO= . . . . . (Recipient email of summary report (e.g. "test@example.org"))
          - AUDIT_REPORT_SUBJECT=. . . (Subject line of summary report email (e.g. "Example Security Audit Report"))
          - AUDIT_COMPOSER_BIN=. . . . (How you would like to call composer (e.g. "composer"; this defaults to "composer" if left blank))
    - Set up email using the MAIL_ variable series. If left unchanged, the email will be printed in storage/logs/laravel.log.

---

## Configuration

- Edit config/audit.php to customize which projects will be audited, composer binary, command timeout, and mail options.

---

## Usage

- Run an audit and email the results              -> 'php artisan app:audit-projects'
- Run without sneding email (console output only) -> 'php artisan app:audit-projects --no-mail'

---

## Example Report

Starting audits...
 • Auditing project at: /home/uXXXX/domains/project1.hostingersite.com
 ✔ No vulnerabilities found.
 • Auditing project at: /home/uXXXX/domains/project2.hostingersite.com
 ✖ Found 2 advisories.

Laravel Security Audit Report
Generated: 2025-08-18 18:04:02
========================================

 Project: /home/uXXXX/domains/project2.hostingersite.com
  - [Critical] Livewire RCE vulnerability (CVE-2025-54068)
  - [High] SomePackage XXE injection

End of report.
