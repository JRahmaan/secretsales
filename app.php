<?php
require_once('vendor/autoload.php');
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;



$console = new Application('FileParser','1.0.0');

$console
    ->register('file-parser:count-words')
    ->setDefinition(array(
        new InputArgument("filename", InputArgument::REQUIRED, 'File to be read', null),
        new InputArgument("filetype", InputArgument::OPTIONAL, 'Type of file', 'txt')
    ))
    ->setDescription('Simple application to parse a text file and count occurrences of words')
    ->setHelp('
        The file-parser:count-words command takes a file input, parses it and counts the number of times each word occurs.
        The return values is an array of words and counts in the following format:
            ["word"=> X, "example" => Y, ...]
        X, Y are integer counts

        The default file type is a space delimited text file (.txt)

        Line length must be < 10000
    ')
    ->setCode(function (InputInterface $input, OutputInterface $output)
        {
            $fileName = $input->getArgument('filename');
            $fileType = $input->getArgument('filetype');


            if (stripos($fileName, "http") !== false )
            {
                // We have a URL, so fetch the file and parse it
                $crl = curl_init();
                curl_setopt($crl, CURLOPT_URL, $fileName);
                curl_setopt($crl, CURLOPT_HEADER, 0);
                curl_setopt($crl, CURLOPT_NOBODY, true);
                $exists = curl_exec($crl);
                curl_close($crl);
            }
            else
            {
                $fs = new Filesystem();
                $exists = $fs->exists($fileName);
            }

            if (!$exists) {
                $output->writeln("File not found!");
                return 0;
            }

            $fp = fopen($fileName, 'r');
            $wordCount = [];

            // Scan the file line by line and get each word - just looking for spaces as word boundaries
            while ( !feof($fp) ) {
                $line = fgets($fp, 10000);
                $words = explode(" ",trim($line));
                for( $i=0, $j = count($words); $i < $j ; $i++ )
                {
                    $check = trim($words[$i]) ;
                    if (strlen($check) > 0 )
                    {
                        if ( array_key_exists(  $check, $wordCount) )
                        {
                            $wordCount["$words[$i]"]++;
                        }
                        else
                        {
                            $wordCount[$check] = 1;
                        }
                    }
                }
            }

            fclose($fp);
            // Now sort the array, descending order but preserve the keys / words
            arsort($wordCount);

            // Finally output the top 100 words
            $i = 0;
            foreach ($wordCount as $word => $count)
            {
                $i++;
                $output->writeln("(" . $i .")  " . $word .",". $count);
                if ($i == 100)
                {
                    break;
                }
            }
        }
    )
;

$console->run();