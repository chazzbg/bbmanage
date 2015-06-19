<?php
require dirname(__FILE__).'/vendor/autoload.php';

$oauth_params = array(
	'oauth_consumer_key'      => 'fNjv8zRsBC7qFRfepW',
	'oauth_consumer_secret'   => 'Vn8Z7DgtfEn6uD8aUJhv5FNYY9HPykQE'
);
$slack_url = 'https://hooks.slack.com/services/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';
$listener = new Bitbucket\API\Http\Listener\OAuthListener($oauth_params);
$api = new Bitbucket\API\Api();
$api->getClient()->addListener($listener);

$client = $api->getClient()->setApiVersion(1.0);

//$data = $client->get('user/repositories/');


$repos = new \Bitbucket\API\User\Repositories();
$repos->getClient()->addListener($listener);
$all_repos = json_decode($repos->get()->getContent());
foreach ( $all_repos as $r ) {
	$slug = $r->slug;


	$services = json_decode( $repos->requestGet( 'repositories/inixweb/' . $slug . '/services/' )->getContent() );

	if ( !count( $services ) ) {
//		foreach ( $services as $s ) {
//			$repos->requestDelete( 'repositories/inixweb/' . $slug . '/services/' . $s->id );
//		}

		$resp = $repos->requestPost( 'repositories/inixweb/' . $slug . '/services/', array(
			'type' => 'POST',
			'URL'  => $slack_url,
		) );

		$resp = json_decode( $resp->getContent() );

		if ( $resp->id ) {
			echo $slug . ' updated<br />';
		}

	}


}
