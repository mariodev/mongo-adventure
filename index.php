<?php
/**
 * Author: mario <mario16@poczta.fm>
 *
 * My copy of PHP & MongoDB example application
 */
session_cache_limiter(false);
session_start();

function valid_tag_filter($tag) {
	$tags = explode(",", $tags);
	$tags_clean = array();

	foreach($tags as $tag) {
		# code...
	}
}

require 'lib/Slim/Slim/Slim.php';
$app = new Slim();

//GET /articles/new
$app->get('/articles/new', function () use ($app) {
    $app->render('blogpost.html', array());
});

//POST /articles/new
$app->post('/articles/new', function () use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');

		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'created_at' => new MongoDate(),
			'tags' => $tags_clean
		);
		$coll->insert($article);
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database." . $e->getMessage());
	} catch(MongoException $e) {
		die('Failed to insert data ' . $e->getMessage());
	}

	$app->flash('success', 'Article saved successfully. ID: ' . $article['_id']);
	// die('<pre>' . print_r($b, 1) . '</pre>');

	$app->redirect('/blog/articles/new');
});

//GET /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database " . $e->getMessage());
	}

	$article = $coll->findOne(array('_id' => new MongoId($id)));

    $app->render('blog.html', array('article' => $article));
});

//GET /articles
$app->get('/articles', function () use ($app) {
	try {
		$conn = new Mongo();
		$db = $conn->selectDB('myblogsite');
		$coll = $db->selectCollection('articles');
	} catch(MongoConnectionException $e) {
		die("Failed to connect to database " . $e->getMessage());
	}
	$cursor = $coll->find();

    $app->render('blogs.html', array('cursor' => $cursor));
});

$app->run();
