<?php
/**
 * Author: mario <mario16@poczta.fm>
 *
 * My copy of PHP & MongoDB example application
 */
session_cache_limiter(false);
session_start();

require 'lib/Slim/Slim/Slim.php';
$app = new Slim();

//GET /article/new
$app->get('/article/new', function () use ($app) {
    $app->render('blogpost.html', array());
});

//POST /article/new
$app->post('/article/new', function () use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'created_at' => new MongoDate()
		);
		$coll->insert($article);
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database." . $e->getMessage());
	} catch(MongoException $e) {
		die('Failed to insert data ' . $e->getMessage());
	}

	$app->flash('success', 'Article saved successfully. ID: ' . $article['_id']);
	$app->redirect('/blog/article/new');
});



$app->run();
