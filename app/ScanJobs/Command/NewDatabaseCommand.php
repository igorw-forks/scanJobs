<?PHP
namespace ScanJobs\Command;

use Knp\Command\Command;

class NewDatabaseCommand extends Command
{	
	protected $geocoder;

    protected function configure()
    {
        $this->setName('newDatabase')
             ->setDescription('Wipe the database and start over.');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, 
							   \Symfony\Component\Console\Output\OutputInterface $output)
	{
		$app = $this->getSilexApplication();
		$dbFile = $this->getSilexApplication()['database']['path'];
		if (file_exists($dbFile)) {
      		unlink($dbFile);
  		}

		$db     = $this->getSilexApplication()['db'];
		$rawsql = file_get_contents($this->getProjectDirectory().'/scripts/sqlite.sql');
		$sql    = explode(';',$rawsql);

		foreach($sql as $statement) {
			$statement = trim($statement);

			if (!empty($statement)) {
				$db->executeQuery($statement);
			}

		}
		$output->writeln('Done');
		return;
    }


}
