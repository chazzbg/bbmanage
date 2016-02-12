<?php

namespace App\Commands;

use Bitbucket\API\Api;
use Bitbucket\API\Http\Listener\OAuthListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Bitbucket\API\Repositories;

class SetServiceAllCommand extends Command
{

    private $oaut_listener;

    private $bitbucket_user;
    private $slack_url;

    protected function configure()
    {
        $this->setName('service:set-all')
             ->setDescription('Set service to all user repositories')
             ->addArgument('username', InputArgument::OPTIONAL, 'Override username')
             ->addOption('append', 'a', InputOption::VALUE_NONE, 'This will append the service instead of replacing all existing');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
 $user = $input->getArgument('username');
        if ($user) {
            $this->bitbucket_user = $user;
        }
        $this->loadConfig();

        $repos = $this->getAllRepoSlugs();
        $api = new Api();
        $client = $api->getClient();
        $client->addListener($this->oaut_listener);

        foreach ($repos as $slug) {

        	$slug = str_replace($this->bitbucket_user.'/','',$slug);
            $request = $client->get('repositories/' . $this->bitbucket_user . '/' . $slug . '/services/');

            $available_services = json_decode($request->getContent());

            if (is_null($available_services)) {
                $output->writeln('<error>Empty response from Bitbucket</error>');
            } else if (isset($available_services->error)) {
                $output->writeln('<error>' . $available_services->error->message . '</error>');
            } else {
                if (!$input->getOption('append')) {
                    if (count($available_services)) {
                        foreach ($available_services as $s) {
                            $client->delete('repositories/' . $this->bitbucket_user . '/' . $slug . '/services/' . $s->id);
                        }
                    }
                }

                $resp = $client->post('repositories/' . $this->bitbucket_user . '/' . $slug . '/services/', array(
                    'type' => 'POST',
                    'URL' => $this->slack_url,
                ));

                $resp = json_decode($resp->getContent());

                if (is_null($resp)) {
                    $output->writeln('<error>Empty response when updateing service</error>');
                } else if (isset($resp->error)) {
                    $output->writeln('<error>' . $resp->error->message . '</error>');
                }if ($resp->id) {
                    $output->writeln($slug . ' updated');
                }
            }
        }

//
    }

    private function loadConfig()
    {

        $config = Yaml::parse(getProjectRoot() . '/config.yaml');

        if (!is_array($config)) {
            throw new \Exception('Config file is not loaded , run "php run config" to create a new one');
        }

        $this->bitbucket_user = $config['bitbucket']['user'];
        $this->slack_url = $config['slack']['url'];

        $oauth_params = array(
            'oauth_consumer_key' => $config['bitbucket']['key'],
            'oauth_consumer_secret' => $config['bitbucket']['secret'],
        );

        $this->oaut_listener = new OAuthListener($oauth_params);

       
    }

    public function getAllRepoSlugs()
    {
        $api = new Repositories();
        $api->getClient()->addListener($this->oaut_listener);
        $page = 1;
        $bb_repos = array();
        do {
            $response = $api->all($this->bitbucket_user . '?page=' . $page);
            $bb_response = json_decode($response->getContent(), true);

            foreach ($bb_response['values'] as $repo) {
                $bb_repos[] = $repo['full_name'];
            }
            $page++;
        } while (isset($bb_response['next']));

        return $bb_repos;
    }
}
