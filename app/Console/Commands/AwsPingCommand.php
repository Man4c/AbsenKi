<?php

namespace App\Console\Commands;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Console\Command;

class AwsPingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AWS Rekognition connection and list collections';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing AWS Rekognition connection...');
        $this->newLine();

        try {
            // Check if AWS credentials are configured
            if (empty(config('services.aws.key')) || empty(config('services.aws.secret'))) {
                $this->error('AWS credentials are not configured!');
                $this->warn('Please set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY in your .env file.');
                return Command::FAILURE;
            }

            // Create Rekognition client
            $rekognition = new RekognitionClient([
                'version' => 'latest',
                'region' => config('services.aws.region'),
                'credentials' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
            ]);

            $this->info('✓ AWS SDK initialized successfully');
            $region = config('services.aws.region');
            $this->info('Region: ' . (is_string($region) ? $region : 'unknown'));
            $this->newLine();

            // Try to list collections
            $this->info('Listing Rekognition collections...');
            $result = $rekognition->listCollections();

            $collectionIds = $result['CollectionIds'] ?? [];
            if (empty($collectionIds) || !is_array($collectionIds)) {
                $this->warn('No collections found.');
                $this->info('You can create a collection later for face recognition.');
            } else {
                $this->info('Found ' . count($collectionIds) . ' collection(s):');
                foreach ($collectionIds as $collection) {
                    $this->line('  • ' . (is_string($collection) ? $collection : ''));
                }
            }

            $this->newLine();
            $collectionName = config('services.rekognition.collection');
            $threshold = config('services.rekognition.threshold');
            $this->info('Configured collection name: ' . (is_string($collectionName) ? $collectionName : 'unknown'));
            $this->info('Face threshold: ' . (is_numeric($threshold) ? $threshold : '0') . '%');

            $this->newLine();
            $this->info('✓ AWS Rekognition connection successful!');

            return Command::SUCCESS;
        } catch (\Aws\Exception\CredentialsException $e) {
            $this->error('✗ AWS Credentials Error: ' . $e->getMessage());
            $this->warn('Please check your AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY.');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
