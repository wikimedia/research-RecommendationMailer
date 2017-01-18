<?php
require_once '/srv/mediawiki/multiversion/MWMultiVersion.php';
require_once MWMultiVersion::getMediaWiki( 'maintenance/commandLine.inc', 'enwiki' );

$sender = new MailAddress( 'recommender-feedback@wikimedia.org', 'Wikimedia Research' );

$subjects = array(
	'en' => "Help improve Wikipedia's coverage in your language",
	'es' => "Ayúdanos a mejorar la extensión de la Wikipedia en español",
	'fr' => "Aidez à améliorer l'exhaustivité de Wikipédia en français",
);

$code = 'fr';

$treatments = array(
	'personal' => 'personal',
	'random'   => 'random',
);

foreach ( $treatments as $treatment => $lang ) {
	$targetsFile = __DIR__ . "/{$code}-{$treatment}-recs.json";
	if ( !file_exists( $targetsFile ) ) {
		continue;
	}
	$textTemplate = file_get_contents( __DIR__ . "/templates/{$code}.txt" );
	$htmlTemplate = file_get_contents( __DIR__ . "/templates/{$code}.html" );
	$records = json_decode( file_get_contents( $targetsFile ), true );
	foreach ( $records as $record ) {
		$templateVars = array();
		$recs = '';
		foreach ( $record['recommendations'] as $i => $rec ) {
			$num = $i + 1;
			$title = strtr( $rec['title'], '_', ' ' );
			$url = $rec['ct_url'];
			$recs .= "* $title:\n   $url\n\n";
			$templateVars["{{rec{$num}}}"] = "<a href=\"$url\" target=\"_blank\">$title</a>";
		}
		$name = $record['user'];
		$email = $record['email'];
		$templateVars = array_merge( $templateVars, array(
			'{{user}}'  => $name,
			'{{email}}' => $email,
			'{{recs}}'  => $recs,
		) );
		$content = array(
			'text' => strtr( $textTemplate, $templateVars ),
			'html' => strtr( $htmlTemplate, $templateVars ),
		);
		// $email = 'recommender-feedback@wikimedia.org';
		// $email = 'ori@wikimedia.org';
		$address = new MailAddress( $email, $name );
		$subject = $subjects[$code];

		UserMailer::send( $address, $sender, $subject, $content );
		print "Sent to $name <$address> in $lang\n";
		sleep( 0.01 );
	}
}
