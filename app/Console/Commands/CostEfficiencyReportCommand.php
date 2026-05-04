<?php

namespace App\Console\Commands;

use App\Services\Monitoring\MonitoringService;
use App\Models\VirtualMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CostEfficiencyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:cost-efficiency-report {--output= : Output file path for the report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly cost efficiency report for all VMs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Generating cost efficiency report...');
            
            $monitoringService = app(MonitoringService::class);
            $monitoredVms = VirtualMachine::where('is_monitored', true)
                ->where('status', 'running')
                ->with(['project', 'cloudProvider'])
                ->get();
            
            if ($monitoredVms->isEmpty()) {
                $this->info('No monitored VMs found for report generation.');
                return 0;
            }
            
            $reportData = [
                'generated_at' => now()->toISOString(),
                'period' => 'Last 7 days',
                'summary' => [
                    'total_vms' => $monitoredVms->count(),
                    'total_monthly_cost' => $monitoredVms->sum('monthly_cost'),
                ],
                'vm_analyses' => [],
                'recommendations' => [],
            ];
            
            $totalPotentialSavings = 0;
            $downsizeCount = 0;
            $upscaleCount = 0;
            
            foreach ($monitoredVms as $vm) {
                $analysis = $monitoringService->getCostEfficiencyAnalysis($vm);
                $reportData['vm_analyses'][] = $analysis;
                
                // Collect recommendations
                foreach ($analysis['recommendations'] as $recommendation) {
                    $reportData['recommendations'][] = [
                        'vm_name' => $vm->name,
                        'vm_id' => $vm->id,
                        'project' => $vm->project->name,
                        'cloud_provider' => $vm->cloudProvider->name,
                        'type' => $recommendation['type'],
                        'component' => $recommendation['component'],
                        'current_utilization' => $recommendation['current_utilization'],
                        'reason' => $recommendation['reason'],
                        'potential_savings' => $recommendation['potential_savings'] ?? 0,
                        'potential_cost_increase' => $recommendation['potential_cost_increase'] ?? 0,
                    ];
                    
                    if ($recommendation['type'] === 'downsize') {
                        $totalPotentialSavings += $recommendation['potential_savings'] ?? 0;
                        $downsizeCount++;
                    } elseif ($recommendation['type'] === 'upscale') {
                        $upscaleCount++;
                    }
                }
            }
            
            // Update summary with calculated values
            $reportData['summary']['avg_efficiency_score'] = round(
                collect($reportData['vm_analyses'])->avg('efficiency_score'), 2
            );
            $reportData['summary']['downsize_recommendations'] = $downsizeCount;
            $reportData['summary']['upscale_recommendations'] = $upscaleCount;
            $reportData['summary']['total_potential_savings'] = $totalPotentialSavings;
            
            // Sort recommendations by potential savings (highest first)
            usort($reportData['recommendations'], function ($a, $b) {
                return ($b['potential_savings'] ?? 0) - ($a['potential_savings'] ?? 0);
            });
            
            // Output report
            if ($outputPath = $this->option('output')) {
                $jsonReport = json_encode($reportData, JSON_PRETTY_PRINT);
                Storage::put($outputPath, $jsonReport);
                $this->info("Report saved to: {$outputPath}");
            } else {
                $this->displayReport($reportData);
            }
            
            Log::info('Cost efficiency report generated', [
                'total_vms' => $reportData['summary']['total_vms'],
                'total_cost' => $reportData['summary']['total_monthly_cost'],
                'potential_savings' => $totalPotentialSavings,
                'recommendations_count' => count($reportData['recommendations']),
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate cost efficiency report: {$e->getMessage()}");
            
            Log::error('Cost efficiency report generation failed', [
                'error' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
    
    /**
     * Display the report in the console.
     */
    private function displayReport(array $reportData): void
    {
        $this->newLine();
        $this->info('=== Cost Efficiency Report ===');
        $this->info("Generated: {$reportData['generated_at']}");
        $this->info("Period: {$reportData['period']}");
        $this->newLine();
        
        // Summary
        $this->info('Summary:');
        $this->info("- Total VMs: {$reportData['summary']['total_vms']}");
        $this->info("- Total Monthly Cost: \${$reportData['summary']['total_monthly_cost']}");
        $this->info("- Average Efficiency Score: {$reportData['summary']['avg_efficiency_score']}%");
        $this->info("- Downsize Recommendations: {$reportData['summary']['downsize_recommendations']}");
        $this->info("- Upscale Recommendations: {$reportData['summary']['upscale_recommendations']}");
        $this->info("- Potential Monthly Savings: \${$reportData['summary']['total_potential_savings']}");
        $this->newLine();
        
        // Top recommendations
        if (!empty($reportData['recommendations'])) {
            $this->info('Top Recommendations:');
            $topRecommendations = array_slice($reportData['recommendations'], 0, 10);
            
            foreach ($topRecommendations as $rec) {
                $savings = $rec['potential_savings'] > 0 ? " (Save \${$rec['potential_savings']}/mo)" : '';
                $this->line("- {$rec['vm_name']} ({$rec['project']}): {$rec['type']} {$rec['component']} - {$rec['reason']}{$savings}");
            }
        }
        
        $this->newLine();
        $this->info('Report completed successfully!');
    }
}
