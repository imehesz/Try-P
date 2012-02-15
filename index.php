<?php

error_reporting(E_ALL); 
ini_set("display_errors", 1);

$app = require __DIR__ . '/lib/base.php';

$app->set('CACHE', false);
$app->set('DEBUG',1);
$app->set('UI','ui/');

$app->set( 'html_title', 'Try-PHP.org');

$app->route('GET /',function(){
	F3::set( 'content', 'home.html' );
	echo Template::serve( 'main.html' );
});

$app->route( 'GET /contact', function(){
	F3::set( 'content', 'contact.html' );
	echo Template::serve( 'main.html' );
});

$app->route( 'GET /learn', function(){
	F3::set( 'content', 'learn.html' );
	echo Template::serve( 'main.html' );
});

$app->route( 'GET /learn/lesson/@lesson_id', function(){
	F3::set( 'lesson_id', '{{@PARAMS.lesson_id}}' );
	$lesson_id = F3::get( 'lesson_id' );

	// first let's load all the available lessons ...
	$lessons = loadLessons();

	if( ! in_array( $lesson_id, $lessons ) )
	{
		throw new Exception( 'Ooops, something bad happened ...' );
	}

	F3::set( 'lesson', 'lessons/lesson-' . $lesson_id . '.html' );

	// let's check for next and previous lessons
	F3::set( 'lesson_next', in_array( ($lesson_id+1), $lessons ) ? '/learn/lesson/' . ($lesson_id+1) : null );
	F3::set( 'lesson_prev', in_array( ($lesson_id-1), $lessons ) ? '/learn/lesson/' . ($lesson_id-1) : null );

	F3::set( 'content', 'learn.html' );
	echo Template::serve( 'main.html' );
});


$app->route('GET /sayhi/@name', function(){
	F3::set( 'html_title', 'Saying Hi - ' . F3::get( 'html_title' ) );
	F3::set( 'name', '{{@PARAMS.name}}' );

	F3::set( 'content', 'sayhi.html' );
	echo Template::serve( 'main.html' );
});

// returning an array of available Lesson IDs
// based on the files in the system
// TODO implement some sort of caching so 
//   we don't hit the hard drive so many times
function loadLessons()
{
	$dir = 'ui/lessons/';
	$lessons = glob( $dir . "lesson-*.html" );
	$retval = array();

	if( !empty( $lessons ) )
	{
		foreach( $lessons as $lesson )
		{
			$lesson_id = str_replace( $dir .'lesson-', '', str_replace( '.html', '', $lesson ) );
			if( is_numeric( $lesson_id ) )
			{
				$retval[] = $lesson_id;
			}
		}
	}

	return $retval;
}

$app->run();
