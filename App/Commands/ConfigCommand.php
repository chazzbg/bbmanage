<?php
/**
 * Created by PhpStorm.
 * User: dilkov
 * Date: 6/19/15
 * Time: 2:25 PM
 * 
 */

namespace App\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends Command {
	protected function configure() {
		$this->setName('config')
		     ->setDescription('Create new configuration file');
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');

		$key_question = new Question('Please provide Bitbucket OAuth consumer key:');
		$key_question->setValidator(function($value){
			if(trim($value) ==''){
				throw new \Exception('Key should not be empty');
			}
			return $value;
		});
		$key = $helper->ask( $input, $output, $key_question );


		$secret_question = new Question('Please provide Bitbucket OAuth consumer secret:');
		$secret_question->setValidator(function($value){
			if(trim($value) ==''){
				throw new \Exception('Secret should not be empty');
			}
			return $value;
		});
		$secret = $helper->ask( $input, $output, $secret_question );


		$user_question = new Question('Please provide Bitbucket user:');
		$user_question->setValidator(function($value){
			if(trim($value) ==''){
				throw new \Exception('User should not be empty');
			}
			return $value;
		});
		$username = $helper->ask( $input, $output, $user_question );


		$url_question = new Question('Please provide slack webhook url:');
		$url_question->setValidator(function($value){
			if(trim($value) ==''){
				throw new \Exception('Url should not be empty');
			}
			return $value;
		});
		$slack_url = $helper->ask( $input, $output, $url_question );



		$data = [
			'bitbucket' => [
				'key'=> $key,
				'secret'=> $secret,
				'user'=>$username,
			],
			'slack'=>  [
				'url'=> $slack_url
			]
		];

		$yaml = Yaml::dump($data);
		file_put_contents(getProjectRoot().'config.yaml',$yaml);

		$output->writeln('Thank you!');
	}


}