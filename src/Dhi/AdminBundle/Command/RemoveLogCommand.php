<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class RemoveLogCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('dhi:remove-log')->setDescription('Remove log files');
	}

	public function execute(InputInterface $input, OutputInterface $output) {

		$output->writeln("\n####### Start Remove log Cron at " . date('M j H:i') . " #######\n");

		if($this->recursive_remove_directory('app/logs/', TRUE)){

            $output->writeln("\n####### Log removed successfully #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
	}

    // to use this function to totally remove a directory, write:
    // recursive_remove_directory('path/to/directory/to/delete');

    // to use this function to empty a directory, write:
    // recursive_remove_directory('path/to/full_directory',TRUE);

    function recursive_remove_directory($directory, $empty=FALSE)
    {
        // if the path has a slash at the end we remove it here
        if(substr($directory,-1) == '/')
        {
            $directory = substr($directory,0,-1);
        }

        // if the path is not valid or is not a directory ...
        if(!file_exists($directory) || !is_dir($directory))
        {
            // ... we return false and exit the function
            return FALSE;

            // ... if the path is not readable
        }elseif(!is_readable($directory))
        {
            // ... we return false and exit the function
            return FALSE;

            // ... else if the path is readable
        }else{

            // we open the directory
            $handle = opendir($directory);

            // and scan through the items inside
            while (FALSE !== ($item = readdir($handle)))
            {
                // if the filepointer is not the current directory
                // or the parent directory
                if($item != '.' && $item != '..')
                {
                    // we build the new path to delete
                    $path = $directory.'/'.$item;

                    // if the new path is a directory
                    if(is_dir($path))
                    {
                        // we call this function with the new path
                        $this->recursive_remove_directory($path);

                        // if the new path is a file
                    }else{
                        // we remove the file
                        unlink($path);
                    }
                }
            }
            // close the directory
            closedir($handle);

            // if the option to empty is not set to true
            if($empty == FALSE)
            {
                // try to delete the now empty directory
                if(!rmdir($directory))
                {
                    // return false if not possible
                    return FALSE;
                }
            }
            // return success
            return TRUE;
        }
    }
}
