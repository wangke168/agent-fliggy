<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FliggyClient;
use Illuminate\Support\Facades\Log;

class FliggyTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fliggy:test-products {--page=1} {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Fliggy API by fetching a paginated list of products.';

    /**
     * Execute the console command.
     *
     * @param \App\Services\FliggyClient $fliggyClient
     * @return int
     */
    public function handle(FliggyClient $fliggyClient)
    {
        $page = (int) $this->option('page');
        $limit = (int) $this->option('limit');

        $this->info("Fetching products from Fliggy API (Page: {$page}, Limit: {$limit})...");

        try {
            // It's good practice to use the pre-production environment for testing
            $response = $fliggyClient->usePreEnvironment()->queryProductBaseInfoByPage($page, $limit);

            if ($response->successful()) {
                $this->info('API call successful!');
                $this->line('Response Body:');
                $this->info(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("API call failed with status: {$response->status()}");
                $this->line('Response Body:');
                $this->info(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $this->info("\nCheck storage/logs/fliggy.log for detailed request and response logs.");

        } catch (\Exception $e) {
            $this->error('An exception occurred: ' . $e->getMessage());
            Log::channel('fliggy')->error('Exception during Fliggy API test command:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
