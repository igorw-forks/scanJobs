<?PHP
namespace ScanJobs\Command;
/*
 * @todo break job specific functiosn out into a job mobel
 */
use CalEvans\Google;
use ScanJobs\Models\Job;

use Knp\Command\Command;

class ScanJobsCommand extends Command
{	
	protected $geocoder;

    protected function configure()
    {
        $this->setName('scan')
             ->setDescription('Run the job scan');
    }

	public function addGeocodeer($geocoder)
	{
		$this->geocoder = $geocoder;
	}


    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
		$app = $this->getSilexApplication();
		$db = $app['db'];
		
		$rssFeedURL = "http://careers.stackoverflow.com/jobs/feed?a=12";
		$feed       = simplexml_load_file($rssFeedURL);
		
		foreach($feed->channel->item as $singleItem) {
			if ($jobId = Job::fetchId($singleItem->guid,$db)) {
				// we already know this job
				continue;
			}
			try 
			{
				$job = new Job($app,$this->geocoder);
				$job->parse($singleItem);
				$job->save();
				// Notify the console that we did something
				$output->writeln($singleItem->title);
			} Catch (\Exception $e) {
				// Do Nothing here?
			}
		}
		return;
    }

}
