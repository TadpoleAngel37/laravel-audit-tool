<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;

class AuditProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:audit-projects {--no-mail : Print report but do not email it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run composer audits across multiple Laravel projects and report results';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projects = (array) config('audit.projects', []);
        $composerBin = trim((string) config('audit.composer_bin', 'composer'));
        $timeout = (int) config('audit.timeout', 120);
        $sendMail = ! $this->option('no-mail');

        if (empty($projects)) {
            $this->warn('No projects configured. Add absolute paths in config/audit.php under "projects".');
            return self::INVALID;
        }

        $this->info('Starting audits...');
        $results = [];

        foreach ($projects as $path) {
            $this->line(" • Auditing project at: {$path}");

            /*
            // Build command as an array
            // If composerBin contains spaces (e.g., "php /path/composer"), split it safely:
            $binParts = preg_split('/\s+/', $composerBin);
            
            
            $cmd = array_merge(
                $binParts,
                ['audit', '--format=json']);
            */
            
            $composerPhar = getenv('HOME') . '/composer.phar';
            $process = new Process(['php', $composerPhar, 'audit', '--format=json'], $path, null, null, $timeout);

            // Run the process
            $process->run();

            $raw = $process->getOutput() ?: $process->getErrorOutput();
            
            // Attempt to decode JSON output (Composer 2.4+)
            $json = json_decode($raw, true);

            if (! is_array($json)) {
                $results[$path] = [
                    'ok' => false,
                    'error' => 'Could not parse JSON output from composer audit.',
                    'raw' => Str::limit($raw, 5000),
                    'advisories' => [],
                ];
                $this->error(' ✖ Unexpected output (not JSON).');
                continue;
            }
            
            // Composer returns advisories keyed by package
            $advisories = $json['advisories'] ?? $json['advisory-report']['advisories'] ?? [];
            

            // Normalize to a flat list
            $flat = [];
            foreach ($advisories as $package => $items) {
                // $items can be a list of advisory arrays
                foreach ((array) $items as $adv) {
                    $flat[] = [
                        'package' => $package,
                        'title' => $adv['title'] ?? ($adv['advisoryTitle'] ?? 'Unknown'),
                        'cve' => $adv['cve'] ?? ($adv['cveID'] ?? null),
                        'link' => $adv['link'] ?? ($adv['advisoryLink'] ?? null),
                        'severity' => $adv['severity'] ?? null,
                        'affected' => $adv['affectedVersions'] ?? ($adv['affetedVersionsConstraint'] ?? null),
                    ];
                }
            }

            $results[$path] = [
                'ok' => true,
                'advisories' => $flat,
            ];

            $count = count($flat);
            $count ? $this->warn(" • Found {$count} advisories") : $this->info(' • No issues found');
        }

        // Build the report
        $report = $this->buildTextReport($results);

        //Always print to console
        $this->newLine();
        $this->line($report);

        // Send email if configured
        if ($sendMail) {
            $to = trim((string) config('audit.mail.to', ''));
            $subject = (string) config('audit.mail.subject', 'Laravel Security Audit Report');

            if ($to === '') {
                $this->warn('AUDIT: No recipient configured (AUDIT_REPORT_TO in .env). Skipping email.');
            } else {
                Mail::raw($report, function ($m) use ($to, $subject) {
                    $m->to($to)->subject($subject);
                });
                $this->info("Report emailed to {$to}");
            }
        } else {
            $this->comment('Email suppressed by --no-mail option.');
        }

        $hasFailures = collect($results)->contains(fn ($r) => ! $r['ok'] === false);
        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    /**       
     * Turn the results array into a readable text report.
     */
    protected function buildTextReport(array $results): string
    {
        $lines = [];
        $lines[] = 'Laravel Security Audit Report';
        $lines[] = 'Generated: ' .now()->toDateTimeString();
        $lines[] = str_repeat('=', 40);

        foreach ($results as $path => $data) {
            $lines[] = "\n Project: {$path}";

            if ($data['ok'] === false) {
                $lines[] = '  ERROR: ' . ($data['error'] ?? 'Unknown error');
                continue;
            }

            $issues = $data['advisories'] ?? [];
            if (empty($issues)) {
                $lines[] = '  No issues found.';
                continue;
            }

            foreach ($issues as $i) {
                $pkg = $i['package'] ?? 'unknown package';
                $title = $i['title'] ?? 'Advisory';
                $cve = $i['cve'] ? " [{$i['cve']}]" : '';
                $sev = $i['severity'] ? " ({$i['severity']})" : '';
                $aff = $i['affected'] ? " affected: {$i['affected']}" : '';
                $link = $i['link'] ? " {$i['link']}" : '';

                $lines[] = "  - {$pkg}: {$title}{$cve}{$sev}{$aff}{$link}";
            }
        }

        $lines[] = "\nEnd of report.";
        return implode("\n", $lines);
    }
}
