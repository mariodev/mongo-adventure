<?php
/**
 * Author: mario <mario16@poczta.fm>
 *
 * My copy of PHP & MongoDB example application
 */

require 'lib/Slim/Slim/Slim.php';
require 'lib/TwigView.php';
require_once('lib/dbconnection.php');
require('lib/paginator.php');
require('lib/session.php');

$app = new Slim(array(
	'view' => 'TwigView'
));


//GET /
$app->get('/test', function () use ($app) {
	// echo $app->view()->helper();
 //    try {
	// 	$conn = new Mongo();
	// 	$coll = $conn->myblogsite->articles;
	// } catch(MongoConnectionException $e) {
	// 	die("Failed to connect to database " . $e->getMessage());
	// }

	// $cursor = $coll->find(array('comments.name' => 'Bob'));
	// die('<pre>' . print_r(iterator_to_array($cursor, true), 1) . '</pre>');
    $app->render('test.html', array('name' => 'mario'));

});



//GET /profile
$app->get('/profile', function() use ($app) {
	require('lib/user.php');
	$user = new User();

    $app->render('profile.html', array('user' => $user));
});


//GET /login
$app->get('/login', function() use ($app) {

    $app->render('login.html', array());
});


//POST /login
$app->post('/login', function() use ($app) {
	
	require('lib/user.php');

	$user = new User();
	$username = $app->request()->post('id_username');
	$password = $app->request()->post('id_password');

	if($user->authenticate($username, $password)) {
	    $app->flash('success', 'You have successfully logged in.');
		$app->redirect('/blog/profile');
	} else {
		$app->flash('error', 'Incorrect login details.');
		$app->redirect('/blog/login');
	}
});


//GET /logout
$app->get('/logout', function() use ($app) {
	require('lib/user.php');

	$user = new User();
	$user->logout();

    $app->flash('success', 'You have successfully logged out.');
	$app->redirect('/blog/login');
});


//PUT /articles/:id/comments - insert new comment for article
$app->put('/articles/:article_id/comments', function ($article_id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');

	$comment = array(
		'name' => $app->request()->post('id_name'),
		'email' => $app->request()->post('id_email'),
		'comment' => $app->request()->post('id_comment'),
		'posted_at' => new MongoDate()
	);

	$coll->update(array('_id' => new MongoId($article_id)), array(
		'$push' => array('comments' => $comment)
	));

	$app->flash('success', 'Comment saved successfully for article ID: ' . $article_id);
	$app->redirect('/blog/articles/' . $article_id);
})->name('comments');


//GET /articles/new
$app->get('/articles/new', function () use ($app) {
    $app->render('article_form.html', array());
});


//GET /articles
$app->get('/articles', function () use ($app) {
	$coll = DBConnection::init()->getCollection('articles');

	$cursor = $coll->find();
	$paginator = new Paginator($cursor, 10);
	$cursor->sort(array('created_at' => -1))
		->skip($paginator->get_skip())
		->limit(10);
	$articles = iterator_to_array($cursor);

    $app->render('article_list.html', array(
    	'articles' => $articles, 'paginator' => $paginator)
    );
});


//PUT /articles - create new article post
$app->put('/articles', function () use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	try {
		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$time = new MongoDate();
		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'created_at' => $time,
			'updated_at' => $time,
			'tags' => $tags_clean
		);
		$coll->insert($article);
	} catch(MongoException $e) {
		die('Failed to insert data ' . $e->getMessage());
	}

	$app->flash('success', 'Article saved successfully. ID: ' . $article['_id']);
	$app->redirect('/blog/dashboard');
});


//GET /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	$article = $coll->findOne(array('_id' => new MongoId($id)));

    $app->render('article_details.html', array('article' => $article));
})->name('view');


//POST /articles/:id - update article
$app->post('/articles/:id', function ($id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	try {
		// some dummy validating
		$tags_clean = array_filter(array_map(function($tag) {
			return trim($tag);
		}, explode(',', $app->request()->post('id_tags'))));

		$article = array(
			'title' => $app->request()->post('id_title'),
			'content' => $app->request()->post('id_content'),
			'updated_at' => new MongoDate(),
			'tags' => $tags_clean
		);;

		$coll->update(array('_id' => new MongoId($id)), array(
			'$set' => $article
		));
	} catch(MongoException $e) {
		die('Failed to update data ' . $e->getMessage());
	}

	$app->flash('success', 'Article updated successfully. ID: ' . $id);
	$app->redirect('/blog/dashboard');
});


//DELETE /articles/:id - delete article
$app->delete('/articles/:id', function ($id) use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	$coll->remove(array('_id' => new MongoId($id)));

	$app->flash('success', 'Article deleted successfully. ID: ' . $id);
	$app->redirect('/blog/dashboard');
})->name('delete');


//GET /articles/edit/:id
$app->get('/articles/:id/edit', function ($id) use ($app) {
	$article = DBConnection::init()
		->getCollection('articles')
		->findOne(array('_id' => new MongoId($id)));

    $app->render('article_form.html', array('article' => $article));
})->name('edit');


//GET /dashboard
$app->get('/dashboard', function () use ($app) {
	$coll = DBConnection::init()->getCollection('articles');
	$cursor = $coll->find(array(), array('title', 'created_at', 'updated_at'));
	$paginator = new Paginator($cursor, 5);
	// $coll->update(array(), array('$set' => array('updated_at' => new MongoDate())), array('multiple' => True));
	// die('<pre>' . print_r(iterator_to_array($cursor, true), 1) . '</pre>');

	$cursor->sort(array('created_at'=>-1))->skip($paginator->get_skip())->limit(5);
	
	$articles = iterator_to_array($cursor);
    $app->render('dashboard.html', array('articles' => $articles, 'paginator' => $paginator));
});

$app->run();
