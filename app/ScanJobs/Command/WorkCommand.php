<?PHP
namespace ScanJobs\Command;

use Knp\Command\Command;

class WorkCommand extends Command
{	

    protected function configure()
    {
        $this->setName('work')
		             ->setDescription('Generic all-purpose work script');
	}

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, 
							   \Symfony\Component\Console\Output\OutputInterface $output)
	{

		$db     = $this->getSilexApplication()['db'];
		
		$sql = "select * from tag";
		$results = $db->executeQuery($sql)->fetchAll();
		$output = array();
		foreach($results as $singleTagArray) {
			$output[] = "insert into term (term,language) VALUES ('{$singleTagArray['tag']}'," .
			                                                     "{$singleTagArray['language']});";
		}
		$payload = implode("\n",$output);
		file_put_contents('/tmp/tags.sql',$payload);
		echo "Done";
		return;
		$sql = 'select * from company';
		$results = $db->executeQuery($sql)->fetchAll();
		$apicall = 'http://ec2-107-21-104-179.compute-1.amazonaws.com/v/1/company/%s.js';
		foreach ($results as $singleCompany) {
			$company = $singleCompany['company_name'];
			$company = urlencode($company);
			$url = sprintf($apicall,$company);
			$payload = file_get_contents($url);
			$json = json_decode($payload);
			print_r($json);
		}
		$output->writeln('Done');
		return;
    }


}
